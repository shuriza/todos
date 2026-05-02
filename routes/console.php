<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled tasks hanya jalan di production.
// Di local env, jalankan manual: php artisan notification:send-reminders --type=deadline
// Ini mencegah notifikasi dobel saat production dan localhost jalan bersamaan.
if (app()->environment('production')) {
    // Notifikasi: kirim reminder deadline setiap 1 menit untuk presisi tinggi
    Schedule::command('notification:send-reminders --type=deadline')
        ->everyMinute()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/reminders.log'));

    // Overdue check setiap 1 menit untuk presisi tinggi
    Schedule::command('notification:send-reminders --type=overdue')
        ->everyMinute()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/reminders.log'));

    // Daily summary: jalan tiap menit, tapi hanya kirim jika waktu cocok
    // dengan preferensi user (daily_summary_time, default 07:00, window ±30 menit)
    Schedule::command('notification:send-reminders --type=daily')
        ->everyMinute()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/reminders.log'));

    // Sync Google Classroom setiap 6 jam
    Schedule::command('classroom:sync')
        ->everySixHours()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/classroom-sync.log'));

    // Re-kalkulasi kuadran Eisenhower setiap jam.
    // Tugas yang mendekati deadline otomatis naik ke kuadran lebih urgent.
    Schedule::command('todos:recalculate-kuadran')
        ->hourly()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/kuadran-recalc.log'));
}
