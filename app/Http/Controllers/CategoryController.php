<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::where('user_id', Auth::id())
            ->withCount('todos')
            ->orderBy('order', 'asc')
            ->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'sometimes|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        $category = Category::create([
            ...$validated,
            'user_id' => Auth::id(),
            'order' => Category::where('user_id', Auth::id())->max('order') + 1,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:7',
            'icon' => 'nullable|string|max:50',
            'order' => 'sometimes|integer',
        ]);

        $category->update($validated);

        return response()->json(['success' => true, 'category' => $category]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json(['success' => true]);
    }
}
