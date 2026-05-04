<?php

namespace App\Services;

use App\Models\Todo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * ReportService
 * 
 * Service layer untuk semua query agregasi & logika bisnis laporan.
 * Terpisah dari controller agar reusable & testable.
 * 
 * Alur:
 *   ReportController -> ReportService -> Todo Model -> Database (MySQL)
 *   ReportController <- ReportService <- aggregated data
 * 
 * Semua method menerima $userId dan $period (string: '7d','30d','90d','180d','365d').
 * Period dikonversi ke date range via parsePeriod().
 * Hasil di-cache sesuai config('todos.stats_cache_ttl').
 * 
 * CATATAN: Semua query menggunakan fungsi MySQL (CURDATE(), TIMESTAMPDIFF(), DATE_FORMAT())
 *          karena project ini menggunakan MySQL sebagai database.
 */
class ReportService
{
    // =========================================================================
    // PERIOD HELPER
    // =========================================================================

    /**
     * Konversi period string ke [Carbon $start, Carbon $end].
     */
    public function parsePeriod(string $period): array
    {
        $days = match ($period) {
            '7d'   => 7,
            '30d'  => 30,
            '90d'  => 90,
            '180d' => 180,
            '365d' => 365,
            default => 30,
        };

        return [
            now()->subDays($days)->startOfDay(),
            now()->endOfDay(),
        ];
    }

    // =========================================================================
    // OVERVIEW STATS (Kartu Ringkasan)
    // =========================================================================

    /**
     * Statistik ringkasan utama untuk kartu di atas halaman.
     */
    public function getOverviewStats(int $userId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:overview:{$period}", $userId, function () use ($userId, $start, $end) {
            $row = Todo::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status <> 'completed' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status <> 'completed' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue
                ")
                ->first();

            $total     = (int) ($row->total ?? 0);
            $completed = (int) ($row->completed ?? 0);

            // Tingkat ketepatan waktu: % tugas selesai sebelum/tepat deadline
            $onTimeRow = Todo::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNotNull('due_date')
                ->whereBetween('completed_at', [$start, $end])
                ->selectRaw("
                    COUNT(*) AS with_deadline,
                    SUM(CASE WHEN DATE(completed_at) <= due_date THEN 1 ELSE 0 END) AS on_time
                ")
                ->first();

            $withDeadline = (int) ($onTimeRow->with_deadline ?? 0);
            $onTime = (int) ($onTimeRow->on_time ?? 0);

            return [
                'total'           => $total,
                'completed'       => $completed,
                'pending'         => (int) ($row->pending ?? 0),
                'overdue'         => (int) ($row->overdue ?? 0),
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'on_time_rate'    => $withDeadline > 0 ? round(($onTime / $withDeadline) * 100) : null,
            ];
        });
    }

    // =========================================================================
    // TREN PRODUKTIVITAS (Line Chart)
    // =========================================================================

    /**
     * Data tren: task created vs completed per hari/minggu.
     * Untuk periode panjang (>90d), group per minggu agar chart tidak terlalu padat.
     */
    public function getCompletionTrend(int $userId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:trend:{$period}", $userId, function () use ($userId, $start, $end, $period) {
            $groupByWeek = in_array($period, ['180d', '365d']);

            // Task created per hari/minggu
            $created = Todo::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw($groupByWeek
                    ? "CONCAT(YEAR(created_at), '-W', LPAD(WEEK(created_at, 1), 2, '0')) AS period_key, COUNT(*) AS cnt"
                    : "DATE(created_at) AS period_key, COUNT(*) AS cnt"
                )
                ->groupBy('period_key')
                ->pluck('cnt', 'period_key')
                ->toArray();

            // Task completed per hari/minggu
            $completed = Todo::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->selectRaw($groupByWeek
                    ? "CONCAT(YEAR(completed_at), '-W', LPAD(WEEK(completed_at, 1), 2, '0')) AS period_key, COUNT(*) AS cnt"
                    : "DATE(completed_at) AS period_key, COUNT(*) AS cnt"
                )
                ->groupBy('period_key')
                ->pluck('cnt', 'period_key')
                ->toArray();

            // Generate semua tanggal/minggu dalam range
            $result = [];
            if ($groupByWeek) {
                $cursor = $start->copy()->startOfWeek();
                while ($cursor->lte($end)) {
                    $key = $cursor->format('Y-\\WW');
                    $result[] = [
                        'date'      => $cursor->format('d M'),
                        'created'   => $created[$key] ?? 0,
                        'completed' => $completed[$key] ?? 0,
                    ];
                    $cursor->addWeek();
                }
            } else {
                $datePeriod = CarbonPeriod::create($start, $end);
                foreach ($datePeriod as $date) {
                    $key = $date->format('Y-m-d');
                    $result[] = [
                        'date'      => $date->format('d M'),
                        'created'   => $created[$key] ?? 0,
                        'completed' => $completed[$key] ?? 0,
                    ];
                }
            }

            return $result;
        });
    }

    // =========================================================================
    // DISTRIBUSI KUADRAN EISENHOWER (Doughnut Chart)
    // =========================================================================

    /**
     * Jumlah task per kuadran Eisenhower (1-4).
     */
    public function getKuadranDistribution(int $userId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:kuadran:{$period}", $userId, function () use ($userId, $start, $end) {
            $rows = Todo::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                    SUM(CASE WHEN kuadran = 1 THEN 1 ELSE 0 END) AS q1,
                    SUM(CASE WHEN kuadran = 2 THEN 1 ELSE 0 END) AS q2,
                    SUM(CASE WHEN kuadran = 3 THEN 1 ELSE 0 END) AS q3,
                    SUM(CASE WHEN kuadran = 4 THEN 1 ELSE 0 END) AS q4
                ")
                ->first();

            return [
                'q1' => (int) ($rows->q1 ?? 0),
                'q2' => (int) ($rows->q2 ?? 0),
                'q3' => (int) ($rows->q3 ?? 0),
                'q4' => (int) ($rows->q4 ?? 0),
            ];
        });
    }

    // =========================================================================
    // DISTRIBUSI PRIORITAS (Bar Chart)
    // =========================================================================

    /**
     * Jumlah task per level prioritas (high/medium/low).
     */
    public function getPriorityDistribution(int $userId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:priority:{$period}", $userId, function () use ($userId, $start, $end) {
            $rows = Todo::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) AS high,
                    SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) AS medium,
                    SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) AS low
                ")
                ->first();

            return [
                'high'   => (int) ($rows->high ?? 0),
                'medium' => (int) ($rows->medium ?? 0),
                'low'    => (int) ($rows->low ?? 0),
            ];
        });
    }

    // =========================================================================
    // DISTRIBUSI KATEGORI (Horizontal Bar Chart)
    // =========================================================================

    /**
     * Jumlah task per kategori (kuliah/pekerjaan/daily_activity).
     */
    public function getCategoryDistribution(int $userId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:category:{$period}", $userId, function () use ($userId, $start, $end) {
            return Todo::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("category, COUNT(*) AS cnt")
                ->groupBy('category')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'category')
                ->toArray();
        });
    }

    // =========================================================================
    // DISTRIBUSI SUMBER (Pie Chart)
    // =========================================================================

    /**
     * Jumlah task berdasarkan sumber (manual vs google_classroom).
     */
    public function getSourceDistribution(int $userId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:source:{$period}", $userId, function () use ($userId, $start, $end) {
            $rows = Todo::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                    SUM(CASE WHEN sumber = 'manual' THEN 1 ELSE 0 END) AS manual,
                    SUM(CASE WHEN sumber = 'google_classroom' THEN 1 ELSE 0 END) AS google_classroom
                ")
                ->first();

            return [
                'manual'           => (int) ($rows->manual ?? 0),
                'google_classroom' => (int) ($rows->google_classroom ?? 0),
            ];
        });
    }

    // =========================================================================
    // CALENDAR HEATMAP (mirip GitHub contribution graph)
    // =========================================================================

    /**
     * Data heatmap: jumlah task completed per hari selama 1 tahun terakhir.
     */
    public function getHeatmapData(int $userId): array
    {
        $start = now()->subYear()->startOfDay();
        $end   = now()->endOfDay();

        return $this->cached("report:heatmap", $userId, function () use ($userId, $start, $end) {
            $completedByDate = Todo::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->selectRaw("DATE(completed_at) AS date_key, COUNT(*) AS cnt")
                ->groupBy('date_key')
                ->pluck('cnt', 'date_key')
                ->toArray();

            $result = [];
            $datePeriod = CarbonPeriod::create($start, $end);
            foreach ($datePeriod as $date) {
                $key = $date->format('Y-m-d');
                $result[] = [
                    'date'  => $key,
                    'count' => $completedByDate[$key] ?? 0,
                ];
            }

            return $result;
        });
    }

    // =========================================================================
    // STREAK INFO
    // =========================================================================

    /**
     * Hitung streak: hari berturut-turut user menyelesaikan minimal 1 task.
     */
    public function getStreakInfo(int $userId): array
    {
        return $this->cached("report:streak", $userId, function () use ($userId) {
            $dates = Todo::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->selectRaw("DISTINCT DATE(completed_at) AS d")
                ->orderBy('d', 'desc')
                ->pluck('d')
                ->map(fn($d) => Carbon::parse($d))
                ->values();

            if ($dates->isEmpty()) {
                return ['current' => 0, 'longest' => 0];
            }

            // Current streak (dari hari ini mundur)
            $currentStreak = 0;
            $checkDate = today();

            if (!$dates->contains(fn($d) => $d->isSameDay($checkDate))) {
                $checkDate = today()->subDay();
            }

            foreach ($dates as $date) {
                if ($date->isSameDay($checkDate)) {
                    $currentStreak++;
                    $checkDate = $checkDate->subDay();
                } elseif ($date->lt($checkDate)) {
                    break;
                }
            }

            // Longest streak
            $longestStreak = 0;
            $tempStreak = 1;
            $sortedDates = $dates->sortBy(fn($d) => $d->timestamp)->values();

            for ($i = 1; $i < $sortedDates->count(); $i++) {
                if ($sortedDates[$i]->diffInDays($sortedDates[$i - 1]) === 1) {
                    $tempStreak++;
                } else {
                    $longestStreak = max($longestStreak, $tempStreak);
                    $tempStreak = 1;
                }
            }
            $longestStreak = max($longestStreak, $tempStreak);

            return [
                'current' => $currentStreak,
                'longest' => $longestStreak,
            ];
        });
    }

    // =========================================================================
    // TASK TERLAMA DISELESAIKAN (Tabel)
    // =========================================================================

    /**
     * Top N task dengan waktu penyelesaian terlama.
     */
    public function getSlowestTasks(int $userId, string $period = '30d', int $limit = 10): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("report:slowest:{$period}", $userId, function () use ($userId, $start, $end, $limit) {
            return Todo::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->selectRaw("title, priority, category, created_at, completed_at, TIMESTAMPDIFF(SECOND, created_at, completed_at) / 3600 AS hours")
                ->orderByDesc('hours')
                ->limit($limit)
                ->get()
                ->map(fn($row) => [
                    'title'        => $row->title,
                    'priority'     => $row->priority,
                    'category'     => $row->category,
                    'created_at'   => Carbon::parse($row->created_at)->format('d M Y'),
                    'completed_at' => Carbon::parse($row->completed_at)->format('d M Y'),
                    'hours'        => round((float) $row->hours, 1),
                ])
                ->toArray();
        });
    }

    // =========================================================================
    // DATA UNTUK EXPORT PDF
    // =========================================================================

    /**
     * Ambil semua data laporan sekaligus untuk export.
     */
    public function getExportData(int $userId, string $period = '30d'): array
    {
        return [
            'overview'  => $this->getOverviewStats($userId, $period),
            'kuadran'   => $this->getKuadranDistribution($userId, $period),
            'priority'  => $this->getPriorityDistribution($userId, $period),
            'category'  => $this->getCategoryDistribution($userId, $period),
            'source'    => $this->getSourceDistribution($userId, $period),
            'streak'    => $this->getStreakInfo($userId),
            'slowest'   => $this->getSlowestTasks($userId, $period),
        ];
    }

    // =========================================================================
    // CACHE MANAGEMENT
    // =========================================================================

    /**
     * Invalidate semua cache laporan untuk user tertentu.
     * Dipanggil dari TodoController::forgetStatsCache() saat task berubah.
     */
    public static function forgetReportCache(int $userId): void
    {
        $periods = ['7d', '30d', '90d', '180d', '365d'];
        $keys = ['overview', 'trend', 'kuadran', 'priority', 'category', 'source', 'slowest'];

        foreach ($periods as $period) {
            foreach ($keys as $key) {
                cache()->forget("user:{$userId}:report:{$key}:{$period}");
            }
        }

        cache()->forget("user:{$userId}:report:heatmap");
        cache()->forget("user:{$userId}:report:streak");
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function cached(string $key, int $userId, callable $resolver): mixed
    {
        $ttl = (int) config('todos.stats_cache_ttl', 60);
        $cacheKey = "user:{$userId}:{$key}";

        if ($ttl <= 0) {
            return $resolver();
        }

        return cache()->remember($cacheKey, $ttl, $resolver);
    }
}
