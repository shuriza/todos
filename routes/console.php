<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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

// Daily summary setiap pagi jam 7
Schedule::command('notification:send-reminders --type=daily')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reminders.log'));

// Sync Google Classroom setiap 6 jam
Schedule::command('classroom:sync')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/classroom-sync.log'));
