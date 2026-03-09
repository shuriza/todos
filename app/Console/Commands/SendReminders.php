<?php

namespace App\Console\Commands;

use App\Models\Todo;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;

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
     */
    protected function sendOverdueAlerts(User $user, bool $dryRun): array
    {
        if (!$user->isNotifEnabled('overdue_alert')) {
            $this->line('  ⏭️ Overdue alert: DISABLED');
            return [0, 0];
        }

        // Find overdue todos: past days OR today with due_time already passed
        $overdueTodos = $user->todos()
            ->where('status', '!=', 'completed')
            ->where(function ($q) {
                $q->where('due_date', '<', now()->toDateString())
                  ->orWhere(function ($q2) {
                      // Today's tasks where due_time has already passed
                      $q2->whereDate('due_date', today())
                         ->whereNotNull('due_time')
                         ->where('due_time', '<', now()->format('H:i:s'));
                  });
            })
            ->whereDoesntHave('notificationLogs', function ($q) {
                // Don't spam: only alert once per 2 hours per todo
                $q->telegram()
                    ->sent()
                    ->where(function ($q2) {
                        $q2->where('pesan', 'LIKE', '%Tugas Overdue%')
                           ->orWhere('pesan', 'LIKE', '%Melewati Deadline%');
                    })
                    ->where('created_at', '>', now()->subHours(2));
            })
            ->get();

        if ($overdueTodos->isEmpty()) {
            $this->line('  ⚠️ Overdue alert: tidak ada tugas overdue baru');
            return [0, 0];
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

        // Check if it's the right time to send (within 30min window of preferred time)
        $preferredTime = $user->getNotifPref('daily_summary_time', '07:00');
        $preferredHour = (int) explode(':', $preferredTime)[0];
        $preferredMinute = (int) (explode(':', $preferredTime)[1] ?? 0);
        $preferredMoment = now()->copy()->setTime($preferredHour, $preferredMinute);
        $minutesDiff = abs(now()->diffInMinutes($preferredMoment, false));

        if ($minutesDiff > 30) {
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
