<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    /**
     * Seed default categories for all users who don't have any categories yet.
     */
    public function run(): void
    {
        $defaultCategories = [
            ['name' => 'Kuliah', 'order' => 1],
            ['name' => 'Pekerjaan', 'order' => 2],
            ['name' => 'Daily Activity', 'order' => 3],
        ];

        $users = User::all();
        $createdCount = 0;

        foreach ($users as $user) {
            // Skip if user already has categories
            if ($user->categories()->exists()) {
                continue;
            }

            // Create default categories for this user
            foreach ($defaultCategories as $categoryData) {
                Category::create([
                    'user_id' => $user->id,
                    'name' => $categoryData['name'],
                    'color' => '#6366f1', // Default blue
                    'icon' => null,
                    'order' => $categoryData['order'],
                ]);
            }

            $createdCount++;
        }

        echo "\n✅ Default categories created for {$createdCount} user(s)!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📁 Categories: Kuliah, Pekerjaan, Daily Activity\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * Create default categories for a specific user.
     */
    public static function createForUser(User $user): void
    {
        // Skip if user already has categories
        if ($user->categories()->exists()) {
            return;
        }

        $defaultCategories = [
            ['name' => 'Kuliah', 'order' => 1],
            ['name' => 'Pekerjaan', 'order' => 2],
            ['name' => 'Daily Activity', 'order' => 3],
        ];

        foreach ($defaultCategories as $categoryData) {
            Category::create([
                'user_id' => $user->id,
                'name' => $categoryData['name'],
                'color' => '#6366f1',
                'icon' => null,
                'order' => $categoryData['order'],
            ]);
        }
    }
}
