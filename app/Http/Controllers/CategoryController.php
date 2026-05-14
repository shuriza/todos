<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AiAssistantService;
use App\Services\ArchiveService;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controller CRUD API untuk mengelola kategori tugas (JSON response, tanpa halaman UI).
 *
 * Fitur terkait: Kategori Tugas
 */
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('user_id', Auth::id())
            ->withCount('todos')
            ->orderBy('order', 'asc')
            ->get();

        return ApiResponse::ok($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->authorize('create', Category::class);

        $category = Category::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
            'order'   => (int) Category::where('user_id', Auth::id())->max('order') + 1,
        ]);

        $this->forgetCategoryCaches(Auth::id());

        return ApiResponse::created($category, 'Kategori berhasil dibuat');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        $this->forgetCategoryCaches(Auth::id());

        return ApiResponse::ok($category, 'Kategori berhasil diperbarui');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->todos()->update([
            'category_id' => null,
            'category' => null,
        ]);

        $category->delete();

        $this->forgetCategoryCaches(Auth::id());

        return ApiResponse::ok(null, 'Kategori berhasil dihapus');
    }

    protected function forgetCategoryCaches(int $userId): void
    {
        cache()->forget("user:{$userId}:todo_stats:basic");
        cache()->forget("user:{$userId}:todo_stats:full");
        cache()->forget("user:{$userId}:home_dashboard");
        AiAssistantService::forgetTaskContextCache($userId);
        ReportService::forgetReportCache($userId);
        ArchiveService::forgetArchiveCache($userId);
    }
}
