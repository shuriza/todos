<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use App\Models\Todo;
use Illuminate\Support\Facades\Auth;

/**
 * HomeController
 *
 * Menangani halaman Dashboard Beranda dengan tampilan Matriks Eisenhower.
 * Menampilkan ringkasan statistik tugas dan pengelompokan tugas berdasarkan
 * empat kuadran prioritas (Do Now, Schedule, Delegate, Eliminate).
 *
 * Endpoints:
 *   GET /home -> index() -> Halaman utama dashboard beranda
 */
class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;

        // Re-kalkulasi kuadran Eisenhower berdasarkan waktu saat ini.
        // Tugas yang mendekati deadline otomatis naik ke kuadran yang lebih urgent.
        Todo::refreshKuadranForUser($userId);

        // Stats: single aggregate query (dulu: 6 query terpisah)
        $stats = $this->computeStats($userId);

        // Kuadran: single query diambil lalu di-group di PHP (dulu: 4 query terpisah)
        $byKuadran = Todo::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->whereIn('kuadran', [
                Todo::KUADRAN_DO_NOW,
                Todo::KUADRAN_SCHEDULE,
                Todo::KUADRAN_DELEGATE,
                Todo::KUADRAN_ELIMINATE,
            ])
            ->with('course')
            ->orderBy('due_date', 'asc')
            ->get()
            ->groupBy('kuadran');

        $urgentImportant      = $byKuadran->get(Todo::KUADRAN_DO_NOW, collect());
        $notUrgentImportant   = $byKuadran->get(Todo::KUADRAN_SCHEDULE, collect());
        $urgentNotImportant   = $byKuadran->get(Todo::KUADRAN_DELEGATE, collect());
        $notUrgentNotImportant = $byKuadran->get(Todo::KUADRAN_ELIMINATE, collect());

        // Fallback: data lama tanpa kuadran → group berdasarkan priority dalam 1 query
        $allEmpty = $urgentImportant->isEmpty()
            && $notUrgentImportant->isEmpty()
            && $urgentNotImportant->isEmpty()
            && $notUrgentNotImportant->isEmpty();

        if ($allEmpty) {
            $byPriority = Todo::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->whereIn('priority', ['high', 'medium', 'low'])
                ->get()
                ->groupBy('priority');

            $urgentImportant    = $byPriority->get('high', collect());
            $notUrgentImportant = $byPriority->get('medium', collect());
            $urgentNotImportant = $byPriority->get('low', collect());
        }

        $categoryOptions = Category::where('user_id', $userId)
            ->orderBy('order', 'asc')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values();

        return view('home', compact(
            'stats',
            'urgentImportant',
            'notUrgentImportant',
            'urgentNotImportant',
            'notUrgentNotImportant',
            'categoryOptions'
        ));
    }

    protected function computeStats(int $userId): array
    {
        $ttl = (int) config('todos.stats_cache_ttl', 60);

        $resolver = function () use ($userId) {
            $row = Todo::where('user_id', $userId)
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status <> 'completed' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status <> 'completed' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue,
                    SUM(CASE WHEN sumber = 'google_classroom' THEN 1 ELSE 0 END) AS classroom
                ")
                ->first();

            return [
                'total'     => (int) ($row->total ?? 0),
                'completed' => (int) ($row->completed ?? 0),
                'pending'   => (int) ($row->pending ?? 0),
                'overdue'   => (int) ($row->overdue ?? 0),
                'classroom' => (int) ($row->classroom ?? 0),
                'courses'   => Course::where('user_id', $userId)->count(),
            ];
        };

        if ($ttl <= 0) {
            return $resolver();
        }

        return cache()->remember("user:{$userId}:home_dashboard", $ttl, $resolver);
    }
}
