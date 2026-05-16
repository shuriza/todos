<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTodoCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_add_task_modal_shows_user_categories(): void
    {
        $user = User::factory()->create();
        Category::create([
            'user_id' => $user->id,
            'name' => 'Kuliah',
            'color' => '#6366f1',
            'icon' => null,
            'order' => 1,
        ]);

        cache()->put("user:{$user->id}:home_dashboard", [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'overdue' => 0,
            'classroom' => 0,
            'courses' => 0,
        ], 60);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee('Kuliah');
        $response->assertDontSee('Atur kategori dari menu Semua Tugas');
    }

    public function test_dashboard_can_create_manual_task_with_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Kuliah',
            'color' => '#6366f1',
            'icon' => null,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/todos', [
            'title' => 'Tugas dari Dashboard',
            'category_id' => $category->id,
            'priority' => 'high',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Tugas dari Dashboard',
            'category_id' => $category->id,
            'category' => 'Kuliah',
        ]);
    }
}
