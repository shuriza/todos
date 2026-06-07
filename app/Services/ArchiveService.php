<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Todo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ArchiveService
 *
 * Service untuk halaman Arsip Tugas — daftar tugas yang sudah diselesaikan
 * mahasiswa sebagai bukti portofolio akademik.
 *
 * Berbeda dengan ReportService (agregat statistik), ArchiveService fokus pada
 * item-level: daftar tugas selesai, dapat di-search, di-filter per mata kuliah,
 * dan di-export sebagai list portofolio.
 */
class ArchiveService
{
    private const VALID_PERIODS = ['7d', '30d', '90d', '180d', '365d', 'all'];

    /**
     * Konversi period string ke [Carbon $start, Carbon $end] berdasarkan completed_at.
     * 'all' => [null, now()]: tanpa lower bound.
     */
    public function parsePeriod(string $period): array
    {
        $end = now()->endOfDay();

        if ($period === 'all') {
            return [null, $end];
        }

        $days = match ($period) {
            '7d'   => 7,
            '30d'  => 30,
            '90d'  => 90,
            '180d' => 180,
            '365d' => 365,
            default => 30,
        };

        return [now()->subDays($days)->startOfDay(), $end];
    }

    /**
     * Ambil daftar tugas selesai dengan filter, pagination, eager load course.
     */
    public function getArchivedTasks(
        int $userId,
        string $period = 'all',
        ?string $search = null,
        ?int $courseId = null,
        string $sort = 'latest',
        int $perPage = 15
    ): LengthAwarePaginator {
        [$start, $end] = $this->parsePeriod($period);

        $query = Todo::with('course')
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'unfinished'])
            ->whereNotNull('completed_at');

        if ($start !== null) {
            $query->whereBetween('completed_at', [$start, $end]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $query->orderBy('completed_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Ringkasan statistik arsip untuk header halaman.
     */
    public function getSummary(int $userId, string $period = 'all'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        return $this->cached("archive:summary:{$period}", $userId, function () use ($userId, $start, $end) {
            $q = Todo::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at');

            if ($start !== null) {
                $q->whereBetween('completed_at', [$start, $end]);
            }

            $row = (clone $q)
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN sumber = 'google_classroom' THEN 1 ELSE 0 END) AS from_classroom,
                    SUM(CASE WHEN sumber = 'manual' THEN 1 ELSE 0 END) AS from_manual,
                    SUM(CASE WHEN due_date IS NOT NULL THEN 1 ELSE 0 END) AS with_deadline,
                    SUM(CASE WHEN due_date IS NOT NULL AND DATE(completed_at) <= due_date THEN 1 ELSE 0 END) AS on_time
                ")
                ->first();

            $courseCount = (clone $q)
                ->whereNotNull('course_id')
                ->distinct('course_id')
                ->count('course_id');

            $withDeadline = (int) ($row->with_deadline ?? 0);
            $onTime = (int) ($row->on_time ?? 0);

            return [
                'total'          => (int) ($row->total ?? 0),
                'from_classroom' => (int) ($row->from_classroom ?? 0),
                'from_manual'    => (int) ($row->from_manual ?? 0),
                'course_count'   => $courseCount,
                'on_time_rate'   => $withDeadline > 0 ? round(($onTime / $withDeadline) * 100) : null,
            ];
        });
    }

    /**
     * Ambil semua tugas selesai dikelompokkan per mata kuliah (untuk PDF portofolio).
     * Tugas tanpa course_id masuk kelompok 'Tugas Pribadi'.
     */
    public function getArchivedGroupedByCourse(int $userId, string $period = 'all'): Collection
    {
        [$start, $end] = $this->parsePeriod($period);

        $query = Todo::with('course')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('completed_at');

        if ($start !== null) {
            $query->whereBetween('completed_at', [$start, $end]);
        }

        return $query
            ->orderBy('completed_at', 'desc')
            ->get()
            ->groupBy(fn(Todo $t) => $t->course?->nama_course ?? 'Tugas Pribadi');
    }

    /**
     * Daftar mata kuliah yang punya setidaknya 1 tugas selesai (untuk dropdown filter).
     */
    public function getCoursesWithArchived(int $userId): Collection
    {
        return Course::where('user_id', $userId)
            ->whereHas('todos', fn($q) => $q->whereIn('status', ['completed', 'unfinished'])->whereNotNull('completed_at'))
            ->orderBy('nama_course')
            ->get(['id', 'nama_course']);
    }

    /**
     * Validasi period, fallback ke default.
     */
    public function validatePeriod(?string $period, string $default = 'all'): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : $default;
    }

    /**
     * Invalidate cache arsip untuk user.
     * Dipanggil dari TodoController::forgetStatsCache() saat status tugas berubah.
     */
    public static function forgetArchiveCache(int $userId): void
    {
        foreach (self::VALID_PERIODS as $period) {
            cache()->forget("user:{$userId}:archive:summary:{$period}");
        }
    }

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
