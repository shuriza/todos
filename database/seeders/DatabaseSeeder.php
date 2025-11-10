<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => \Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        echo "\n✅ Test user created successfully!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📧 Email: test@example.com\n";
        echo "🔑 Password: password\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
}
