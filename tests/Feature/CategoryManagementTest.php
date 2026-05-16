<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_category_with_name_only(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/categories', [
            'name' => 'Organisasi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Organisasi');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Organisasi',
        ]);
    }

    public function test_default_category_seeder_adds_missing_default_categories_for_existing_user(): void
    {
        $user = User::factory()->create();

        Category::create([
            'user_id' => $user->id,
            'name' => 'Organisasi',
            'color' => '#6366f1',
            'order' => 1,
        ]);

        DefaultCategoriesSeeder::createForUser($user);

        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Kuliah']);
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Pekerjaan']);
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Daily Activity']);
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Organisasi']);
    }
}
