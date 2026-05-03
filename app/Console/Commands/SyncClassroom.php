<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoogleClassroomService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Artisan command untuk sinkronisasi manual data Google Classroom (courses & tasks) untuk semua user.
 *
 * Fitur terkait: Google Classroom
 */
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

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

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
                $taskResult = null;
                if (!$coursesOnly) {
                    $taskResult = $service->syncAllCoursework();
                    $this->line("  Tugas: {$taskResult['synced']} baru, {$taskResult['updated']} diperbarui, {$taskResult['skipped']} dilewati");
                }

                // Notif Telegram: hanya jika user enable classroom_sync pref + Telegram aktif + ada perubahan
                if (
                    $taskResult
                    && $user->hasTelegram()
                    && $user->isNotifEnabled('classroom_sync')
                    && ($taskResult['synced'] > 0 || $taskResult['updated'] > 0)
                ) {
                    $this->telegramService->sendClassroomSyncNotification(
                        $user,
                        $taskResult['synced'],
                        $taskResult['updated']
                    );
                    $this->line('  📱 Notifikasi Telegram terkirim');
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
