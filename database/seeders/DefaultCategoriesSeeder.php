<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    public const DEFAULT_CATEGORIES = [
        ['name' => 'Kuliah', 'order' => 1],
        ['name' => 'Pekerjaan', 'order' => 2],
        ['name' => 'Daily Activity', 'order' => 3],
    ];

    /**
     * Seed default categories for all users who don't have the defaults yet.
     */
    public function run(): void
    {
        $users = User::all();
        $createdCount = 0;

        foreach ($users as $user) {
            $createdCount += self::createForUser($user);
        }

        echo "\n✅ Default categories created: {$createdCount}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📁 Categories: Kuliah, Pekerjaan, Daily Activity\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * Create missing default categories for a specific user.
     */
    public static function createForUser(User $user): int
    {
        $createdCount = 0;

        foreach (self::DEFAULT_CATEGORIES as $categoryData) {
            $exists = $user->categories()
                ->where('name', $categoryData['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            Category::create([
                'user_id' => $user->id,
                'name' => $categoryData['name'],
                'color' => '#6366f1',
                'icon' => null,
                'order' => $categoryData['order'],
            ]);

            $createdCount++;
        }

        return $createdCount;
    }
}
