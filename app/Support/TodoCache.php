<?php

namespace App\Support;

use App\Services\AiAssistantService;
use App\Services\ArchiveService;
use App\Services\ReportService;

/**
 * Pusat invalidasi cache milik user setelah data tugas berubah.
 *
 * Satu sumber kebenaran untuk daftar key cache yang harus di-flush, dipakai
 * baik oleh controller (TodoController, CategoryController) maupun service
 * yang menulis todo di luar alur controller (sync Classroom, konfirmasi AI,
 * aksi bot Telegram). Mencegah dashboard/statistik basi.
 */
class TodoCache
{
    public static function flush(int $userId): void
    {
        cache()->forget("user:{$userId}:todo_stats:basic");
        cache()->forget("user:{$userId}:todo_stats:full");
        cache()->forget("user:{$userId}:home_dashboard");

        AiAssistantService::forgetTaskContextCache($userId);
        ReportService::forgetReportCache($userId);
        ArchiveService::forgetArchiveCache($userId);
    }
}
