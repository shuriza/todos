<?php

namespace App\Http\Controllers;

use App\Http\Requests\Todo\ReorderTodoRequest;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Todo;
use App\Services\AiAssistantService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TodoController extends Controller
{
    /**
     * Display a listing of todos with server-side pagination and filtering.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Todo::with(['categoryModel', 'course'])
            ->where('user_id', $userId)
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category (string field)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by kuadran
        if ($request->filled('kuadran') && $request->kuadran !== 'all') {
            $query->where('kuadran', $request->kuadran);
        }

        // Filter by sumber
        if ($request->filled('sumber') && $request->sumber !== 'all') {
            $query->where('sumber', $request->sumber);
        }

        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $perPage = (int) config('todos.per_page', 25);
        $todos = $query->paginate($perPage)->withQueryString();

        $stats = $this->computeStats($userId);

        $categories = Category::where('user_id', $userId)
            ->orderBy('order', 'asc')
            ->get();
        $courses = Course::where('user_id', $userId)->get();

        $filters = $request->only(['search', 'status', 'category', 'kuadran', 'sumber']);

        return view('todos.index', compact('todos', 'categories', 'courses', 'stats', 'filters'));
    }

    /**
     * Store a newly created todo.
     * Ownership category_id & course_id divalidasi via OwnedByUser rule di FormRequest.
     */
    public function store(StoreTodoRequest $request)
    {
        $this->authorize('create', Todo::class);

        $validated = $request->validated();

        $kuadran = Todo::hitungKuadran(
            $validated['priority'] ?? 'medium',
            $validated['due_date'] ?? null
        );

        $todo = Todo::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status'  => 'todo',
            'kuadran' => $kuadran,
            'sumber'  => 'manual',
        ]);

        $this->forgetStatsCache(Auth::id());

        if ($request->expectsJson()) {
            return ApiResponse::created($todo->fresh(), 'Tugas berhasil dibuat');
        }

        return redirect()->route('todos.index')->with('success', 'Tugas berhasil dibuat');
    }

    /**
     * Update the specified todo.
     */
    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $this->authorize('update', $todo);

        $validated = $request->validated();

        if (isset($validated['status']) && $validated['status'] === 'completed' && $todo->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif (isset($validated['status']) && $validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        if (!isset($validated['kuadran']) && (isset($validated['priority']) || isset($validated['due_date']))) {
            $validated['kuadran'] = Todo::hitungKuadran(
                $validated['priority'] ?? $todo->priority,
                $validated['due_date'] ?? $todo->due_date?->format('Y-m-d')
            );
        }

        $todo->update($validated);

        $this->forgetStatsCache(Auth::id());

        if ($request->expectsJson()) {
            return ApiResponse::ok($todo->fresh(), 'Tugas berhasil diperbarui');
        }

        return redirect()->route('todos.index')->with('success', 'Tugas berhasil diperbarui');
    }

    /**
     * Remove the specified todo.
     */
    public function destroy(Todo $todo)
    {
        $this->authorize('delete', $todo);

        $todo->delete();
        $this->forgetStatsCache(Auth::id());

        if (request()->expectsJson()) {
            return ApiResponse::ok(null, 'Tugas berhasil dihapus');
        }

        return redirect()->route('todos.index')->with('success', 'Tugas berhasil dihapus');
    }

    /**
     * Bulk update todo order.
     * Ownership tiap todo_id divalidasi via OwnedByUser rule di FormRequest.
     */
    public function reorder(ReorderTodoRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $userId = Auth::id();
            foreach ($validated['todos'] as $todoData) {
                Todo::where('id', $todoData['id'])
                    ->where('user_id', $userId)
                    ->update(['order' => $todoData['order']]);
            }
        });

        return ApiResponse::ok(null, 'Urutan tugas diperbarui');
    }

    /**
     * Get statistics.
     */
    public function statistics()
    {
        return response()->json($this->computeStats(Auth::id(), true));
    }

    /**
     * Compute user stats dengan single aggregate query.
     * Opsional di-cache sesuai config('todos.stats_cache_ttl').
     */
    protected function computeStats(int $userId, bool $includePriority = false): array
    {
        $ttl = (int) config('todos.stats_cache_ttl', 60);
        $cacheKey = "user:{$userId}:todo_stats:" . ($includePriority ? 'full' : 'basic');

        $resolver = function () use ($userId, $includePriority) {
            $row = Todo::where('user_id', $userId)
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status <> 'completed' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) AS todo,
                    SUM(CASE WHEN status <> 'completed' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue,
                    SUM(CASE WHEN status <> 'completed' AND priority = 'high' THEN 1 ELSE 0 END) AS pri_high,
                    SUM(CASE WHEN status <> 'completed' AND priority = 'medium' THEN 1 ELSE 0 END) AS pri_medium,
                    SUM(CASE WHEN status <> 'completed' AND priority = 'low' THEN 1 ELSE 0 END) AS pri_low
                ")
                ->first();

            $base = [
                'total'     => (int) ($row->total ?? 0),
                'completed' => (int) ($row->completed ?? 0),
                'pending'   => (int) ($row->pending ?? 0),
                'overdue'   => (int) ($row->overdue ?? 0),
            ];

            if (!$includePriority) {
                return $base;
            }

            return array_merge($base, [
                'in_progress' => (int) ($row->in_progress ?? 0),
                'todo'        => (int) ($row->todo ?? 0),
                'by_priority' => [
                    'high'   => (int) ($row->pri_high ?? 0),
                    'medium' => (int) ($row->pri_medium ?? 0),
                    'low'    => (int) ($row->pri_low ?? 0),
                ],
            ]);
        };

        if ($ttl <= 0) {
            return $resolver();
        }

        return cache()->remember($cacheKey, $ttl, $resolver);
    }

    protected function forgetStatsCache(int $userId): void
    {
        cache()->forget("user:{$userId}:todo_stats:basic");
        cache()->forget("user:{$userId}:todo_stats:full");
        cache()->forget("user:{$userId}:home_dashboard");
        AiAssistantService::forgetTaskContextCache($userId);
    }
}
