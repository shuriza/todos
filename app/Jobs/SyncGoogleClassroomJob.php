<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GoogleClassroomService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGoogleClassroomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan ulang jika gagal.
     */
    public int $tries = 2;

    /**
     * Timeout maksimum job (10 menit — cukup untuk banyak tugas).
     */
    public int $timeout = 600;

    public function __construct(
        protected User $user
    ) {}

    public function handle(): void
    {
        Log::info("SyncGoogleClassroomJob: Mulai sync untuk user {$this->user->id}");

        try {
            $service = new GoogleClassroomService($this->user);

            // Sync mata kuliah dulu
            $courseResult = $service->syncCourses();
            Log::info("SyncGoogleClassroomJob: Sync courses selesai", $courseResult);

            // Sync semua tugas
            $taskResult = $service->syncAllCoursework();
            Log::info("SyncGoogleClassroomJob: Sync tasks selesai", $taskResult);

        } catch (\Exception $e) {
            Log::error("SyncGoogleClassroomJob: Gagal untuk user {$this->user->id}", [
                'error' => $e->getMessage(),
            ]);

            // Re-throw agar job ditandai failed dan bisa di-retry
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SyncGoogleClassroomJob: Job gagal permanen untuk user {$this->user->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
