<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoogleClassroomService;
use Illuminate\Console\Command;

class SyncClassroom extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'classroom:sync {--user= : Sync for a specific user ID} {--courses-only : Only sync courses, not tasks}';

    /**
     * The console command description.
     */
    protected $description = 'Sinkronisasi data dari Google Classroom (courses & tasks)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        $coursesOnly = $this->option('courses-only');

        // Get users to sync
        $query = User::whereNotNull('google_access_token');
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada user dengan akses Google Classroom.');
            return Command::SUCCESS;
        }

        $this->info("Memulai sinkronisasi untuk {$users->count()} user...");
        $this->newLine();

        foreach ($users as $user) {
            $this->info("▸ Syncing: {$user->name} ({$user->email})");

            try {
                $service = new GoogleClassroomService($user);

                // Sync courses
                $courseResult = $service->syncCourses();
                $this->line("  Mata Kuliah: {$courseResult['synced']} baru, {$courseResult['existing']} diperbarui (total: {$courseResult['total']})");

                // Sync tasks
                if (!$coursesOnly) {
                    $taskResult = $service->syncAllCoursework();
                    $this->line("  Tugas: {$taskResult['synced']} baru, {$taskResult['updated']} diperbarui, {$taskResult['skipped']} dilewati");
                }

                $this->info("  ✓ Berhasil");
            } catch (\Exception $e) {
                $this->error("  ✗ Gagal: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info('Sinkronisasi selesai!');
        return Command::SUCCESS;
    }
}
