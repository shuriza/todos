<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('No user found. Please create a user first.');
            return;
        }

        $todos = [
            [
                'title' => 'Meeting dengan Client Botnesia',
                'description' => 'Meeting di Starbucks Canggu, membahas customer ingin melakukan pembuatan bot Capcut baru.',
                'category' => 'pekerjaan',
                'priority' => 'high',
                'status' => 'todo',
                'due_date' => Carbon::now()->format('Y-m-d'),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Selesaikan Tugas Kuliah Database',
                'description' => 'Membuat ERD dan implementasi database untuk sistem inventory',
                'category' => 'kuliah',
                'priority' => 'high',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Review Code Pull Request',
                'description' => 'Review PR dari tim untuk fitur payment gateway',
                'category' => 'pekerjaan',
                'priority' => 'medium',
                'status' => 'todo',
                'due_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Belajar Laravel 12 New Features',
                'description' => 'Pelajari fitur-fitur baru di Laravel 12',
                'category' => 'daily_activity',
                'priority' => 'low',
                'status' => 'todo',
                'due_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Presentasi Project Akhir',
                'description' => 'Presentasi project Todo x AI Assistant di kampus',
                'category' => 'kuliah',
                'priority' => 'high',
                'status' => 'todo',
                'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Update Documentation',
                'description' => 'Update README dan API documentation',
                'category' => 'pekerjaan',
                'priority' => 'low',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'completed_at' => Carbon::now()->subDays(1),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Olahraga Pagi',
                'description' => 'Jogging 5km di taman',
                'category' => 'daily_activity',
                'priority' => 'medium',
                'status' => 'completed',
                'due_date' => Carbon::now()->format('Y-m-d'),
                'completed_at' => Carbon::now(),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Setup CI/CD Pipeline',
                'description' => 'Configure Github Actions untuk auto deployment',
                'category' => 'pekerjaan',
                'priority' => 'medium',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'user_id' => $user->id,
            ],
        ];

        foreach ($todos as $index => $todoData) {
            Todo::create(array_merge($todoData, ['order' => $index + 1]));
        }

        $this->command->info('Sample todos created successfully!');
    }
}
