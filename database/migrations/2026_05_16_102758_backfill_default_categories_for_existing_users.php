<?php

use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')->select('id')->get();

        foreach ($users as $user) {
            foreach (DefaultCategoriesSeeder::DEFAULT_CATEGORIES as $category) {
                $exists = DB::table('categories')
                    ->where('user_id', $user->id)
                    ->where('name', $category['name'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('categories')->insert([
                    'user_id' => $user->id,
                    'name' => $category['name'],
                    'color' => '#6366f1',
                    'icon' => null,
                    'order' => $category['order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill only; do not delete user categories on rollback.
    }
};
