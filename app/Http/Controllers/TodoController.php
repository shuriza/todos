<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    /**
     * Display dashboard with overview.
     */
    public function dashboard()
    {
        $userId = Auth::id();

        // Statistics
        $stats = [
            'total' => Todo::where('user_id', $userId)->count(),
            'completed' => Todo::where('user_id', $userId)->where('status', 'completed')->count(),
            'in_progress' => 0, // Calendar events - placeholder untuk fitur masa depan
            'overdue' => 0, // Saved ideas - placeholder untuk fitur masa depan
        ];

        return view('dashboard', compact('stats'));
    }

    /**
     * Display a listing of todos.
     */
    public function index(Request $request)
    {
        $query = Todo::with('category')
            ->where('user_id', Auth::id())
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $todos = $query->get();
        $categories = Category::where('user_id', Auth::id())
            ->orderBy('order', 'asc')
            ->get();

        return view('todos.index', compact('todos', 'categories'));
    }

    /**
     * Store a newly created todo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'category' => 'nullable|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);

        $todo = Todo::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status' => 'todo',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'todo' => $todo->load('category')]);
        }

        return redirect()->route('todos.index')->with('success', 'Todo created successfully!');
    }

    /**
     * Update the specified todo.
     */
    public function update(Request $request, Todo $todo)
    {
        $this->authorize('update', $todo);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'priority' => 'sometimes|in:low,medium,high',
            'status' => 'sometimes|in:todo,in_progress,completed',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'order' => 'sometimes|integer',
        ]);

        // Mark completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && $todo->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif (isset($validated['status']) && $validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $todo->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'todo' => $todo->load('category')]);
        }

        return redirect()->route('todos.index')->with('success', 'Todo updated successfully!');
    }

    /**
     * Remove the specified todo.
     */
    public function destroy(Todo $todo)
    {
        $this->authorize('delete', $todo);

        $todo->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('todos.index')->with('success', 'Todo deleted successfully!');
    }

    /**
     * Bulk update todo order.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'todos' => 'required|array',
            'todos.*.id' => 'required|exists:todos,id',
            'todos.*.order' => 'required|integer',
        ]);

        foreach ($validated['todos'] as $todoData) {
            Todo::where('id', $todoData['id'])
                ->where('user_id', Auth::id())
                ->update(['order' => $todoData['order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get statistics.
     */
    public function statistics()
    {
        $userId = Auth::id();

        $stats = [
            'total' => Todo::where('user_id', $userId)->count(),
            'completed' => Todo::where('user_id', $userId)->where('status', 'completed')->count(),
            'in_progress' => Todo::where('user_id', $userId)->where('status', 'in_progress')->count(),
            'todo' => Todo::where('user_id', $userId)->where('status', 'todo')->count(),
            'overdue' => Todo::where('user_id', $userId)->overdue()->count(),
            'by_priority' => [
                'high' => Todo::where('user_id', $userId)->where('priority', 'high')->where('status', '!=', 'completed')->count(),
                'medium' => Todo::where('user_id', $userId)->where('priority', 'medium')->where('status', '!=', 'completed')->count(),
                'low' => Todo::where('user_id', $userId)->where('priority', 'low')->where('status', '!=', 'completed')->count(),
            ],
        ];

        return response()->json($stats);
    }
}
