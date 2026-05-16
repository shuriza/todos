<?php

namespace App\Console\Commands;

use App\Models\Todo;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Artisan command untuk mengirim pengingat tugas (deadline, overdue, daily summary) via Telegram.
 *
 * Fitur terkait: Notifikasi Telegram
 */
class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notification:send-reminders 
        {--type=all : Type of reminder to send (deadline|overdue|daily|all)}
        {--user= : Send to a specific user ID}
        {--dry-run : Preview without sending}';

    /**
     * The console command description.
     */
    protected $description = 'Kirim notifikasi pengingat tugas via Telegram (deadline, overdue, daily summary)';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->telegramService->isConfigured()) {
            $this->error('❌ Telegram Bot Token belum dikonfigurasi di .env');
            return self::FAILURE;
        }

        $type = $this->option('type');
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        // Get eligible users
        $query = User::whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '');

        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('⚠️ Tidak ada user dengan Telegram yang terhubung.');
            return self::SUCCESS;
        }

        $this->info("📱 Memproses {$users->count()} user dengan Telegram terhubung...");

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $this->line("\n👤 {$user->name} (Chat ID: {$user->telegram_chat_id})");

            if (in_array($type, ['deadline', 'all'])) {
                [$s, $f] = $this->sendDeadlineReminders($user, $dryRun);
                $sent += $s;
                $failed += $f;
            }

            if (in_array($type, ['overdue', 'all'])) {
                [$s, $f] = $this->sendOverdueAlerts($user, $dryRun);
                $sent += $s;
                $failed += $f;
            }

            if (in_array($type, ['daily', 'all'])) {
                [$s, $f] = $this->sendDailySummaryIfEnabled($user, $dryRun);
                $sent += $s;
                $failed += $f;
            }
        }

        $this->newLine();
        $this->info(" Selesai! Terkirim: {$sent} | Gagal: {$failed}");

        return self::SUCCESS;
    }

    /**
     * Send deadline reminders for upcoming tasks.
     */
    protected function sendDeadlineReminders(User $user, bool $dryRun): array
    {
        if (!$user->isNotifEnabled('deadline_reminder')) {
            $this->line('  ⏭️ Deadline reminder: DISABLED');
            return [0, 0];
        }

        $globalReminderHours = $user->getNotifPref('reminder_hours', 2);

        // Max window: pick the larger of global setting or 48h (to catch per-task overrides)
        $maxWindowHours = max($globalReminderHours, 48);

        // Find all incomplete todos with deadline within the max window
        $todos = $user->todos()
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', today()->toDateString())
            ->where('due_date', '<=', now()->addHours($maxWindowHours)->toDateString())
            ->whereDoesntHave('notificationLogs', function ($q) {
                // Don't send if already reminded in last 15 minutes
                $q->telegram()
                    ->sent()
                    ->where('pesan', 'LIKE', '%Pengingat Deadline%')
                    ->where('created_at', '>', now()->subMinutes(15));
            })
            ->get()
            ->filter(function ($todo) use ($globalReminderHours) {
                $deadline = $todo->deadline;
                if (!$deadline || $deadline->isPast()) return false;

                $minutesLeft = now()->diffInMinutes($deadline, false);

                // Use per-task reminder_minutes if set, otherwise global setting
                if ($todo->reminder_minutes !== null) {
                    $windowMinutes = $todo->reminder_minutes;
                } else {
                    $windowMinutes = $globalReminderHours * 60;
                }

                return $minutesLeft >= 0 && $minutesLeft <= $windowMinutes;
            });

        if ($todos->isEmpty()) {
            $this->line("  📌 Deadline reminder: tidak ada tugas mendekat");
            return [0, 0];
        }

        $sent = 0;
        $failed = 0;

        foreach ($todos as $todo) {
            if ($dryRun) {
                $this->line("  📌 [DRY RUN] Would remind: {$todo->title} (deadline: {$todo->deadline})");
                $sent++;
                continue;
            }

            $log = $this->telegramService->sendDeadlineReminder($user, $todo);
            if ($log && $log->isSent()) {
                $this->line("  ✅ Reminder sent: {$todo->title}");
                $sent++;
            } else {
                $this->line("  ❌ Failed: {$todo->title}");
                $failed++;
            }

            // Rate limiting: 50ms between messages
            usleep(50000);
        }

        return [$sent, $failed];
    }

    /**
     * Send overdue alerts.
     *
     * Guards untuk mencegah spam saat sync banyak tugas lama:
     *  1. `overdue_max_days`: hanya kirim untuk tugas yang terlambat <= N hari (default 7).
     *     Tugas lama dari sync Classroom tahun-tahun sebelumnya di-skip.
     *  2. `overdue_cooldown_hours`: cooldown pengulangan per-tugas (default 24 jam).
     *  3. Batching: jika >3 tugas overdue sekaligus, kirim 1 pesan ringkasan.
     */
    protected function sendOverdueAlerts(User $user, bool $dryRun): array
    {
        if (!$user->isNotifEnabled('overdue_alert')) {
            $this->line('  ⏭️ Overdue alert: DISABLED');
            return [0, 0];
        }

        $maxDays = (int) $user->getNotifPref('overdue_max_days', 7);
        $cooldownHours = (int) $user->getNotifPref('overdue_cooldown_hours', 24);
        $earliestDate = now()->subDays($maxDays)->toDateString();

        // Find overdue todos: past days OR today with due_time already passed
        $overdueTodos = $user->todos()
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', $earliestDate)
            ->where(function ($q) {
                $q->where('due_date', '<', now()->toDateString())
                  ->orWhere(function ($q2) {
                      // Today's tasks where due_time has already passed
                      $q2->whereDate('due_date', today())
                         ->whereNotNull('due_time')
                         ->where('due_time', '<', now()->format('H:i:s'));
                  });
            })
            ->whereDoesntHave('notificationLogs', function ($q) use ($cooldownHours) {
                // Cooldown pengulangan per-tugas.
                // Pattern harus match dengan pesan aktual di TelegramService:
                //   - sendOverdueAlert():   "Tugas Lewat Deadline"
                //   - sendOverdueSummary(): "Ringkasan Tugas Terlambat"
                $q->telegram()
                    ->sent()
                    ->where(function ($q2) {
                        $q2->where('pesan', 'LIKE', '%Tugas Lewat Deadline%')
                           ->orWhere('pesan', 'LIKE', '%Ringkasan Tugas Terlambat%');
                    })
                    ->where('created_at', '>', now()->subHours($cooldownHours));
            })
            ->get();

        if ($overdueTodos->isEmpty()) {
            $this->line('  ⚠️ Overdue alert: tidak ada tugas overdue baru');
            return [0, 0];
        }

        // Batch jika overdue banyak → 1 ringkasan, bukan spam per-tugas
        if ($overdueTodos->count() > 3) {
            if ($dryRun) {
                $this->line("  📋 [DRY RUN] Would send overdue SUMMARY ({$overdueTodos->count()} tugas)");
                return [1, 0];
            }

            $log = $this->telegramService->sendOverdueSummary($user, $overdueTodos);
            if ($log && $log->isSent()) {
                $this->line("  ✅ Overdue summary sent ({$overdueTodos->count()} tugas)");
                return [1, 0];
            }

            $this->line('  ❌ Overdue summary failed');
            return [0, 1];
        }

        $sent = 0;
        $failed = 0;

        foreach ($overdueTodos as $todo) {
            if ($dryRun) {
                $this->line("  🚨 [DRY RUN] Would alert: {$todo->title} (overdue since: {$todo->deadline})");
                $sent++;
                continue;
            }

            $log = $this->telegramService->sendOverdueAlert($user, $todo);
            if ($log && $log->isSent()) {
                $this->line("  ✅ Overdue alert: {$todo->title}");
                $sent++;
            } else {
                $this->line("  ❌ Failed: {$todo->title}");
                $failed++;
            }

            usleep(50000);
        }

        return [$sent, $failed];
    }

    /**
     * Send daily summary if enabled and at the right time.
     */
    protected function sendDailySummaryIfEnabled(User $user, bool $dryRun): array
    {
        if (!$user->isNotifEnabled('daily_summary')) {
            $this->line('  ⏭️ Daily summary: DISABLED');
            return [0, 0];
        }

        // Kirim hanya setelah jam pilihan, dengan window 30 menit.
        // Contoh jadwal 07:00: valid 07:00 sampai 07:30, bukan 06:30.
        $preferredTime = $user->getNotifPref('daily_summary_time', '07:00');
        $preferredHour = (int) explode(':', $preferredTime)[0];
        $preferredMinute = (int) (explode(':', $preferredTime)[1] ?? 0);
        $preferredMoment = now()->copy()->setTime($preferredHour, $preferredMinute);
        $minutesAfterPreferred = $preferredMoment->diffInMinutes(now(), false);

        if ($minutesAfterPreferred < 0 || $minutesAfterPreferred > 30) {
            $this->line("   Daily summary: belum waktunya (jadwal: {$preferredTime})");
            return [0, 0];
        }

        // Check if already sent today
        $alreadySent = $user->notificationLogs()
            ->telegram()
            ->sent()
            ->where('pesan', 'LIKE', '%Rangkuman Harian%')
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            $this->line('   Daily summary: sudah dikirim hari ini');
            return [0, 0];
        }

        if ($dryRun) {
            $this->line('   [DRY RUN] Would send daily summary');
            return [1, 0];
        }

        $log = $this->telegramService->sendDailySummary($user);
        if ($log && $log->isSent()) {
            $this->line('  ✅ Daily summary sent');
            return [1, 0];
        }

        $this->line('  ❌ Daily summary failed');
        return [0, 1];
    }
}
