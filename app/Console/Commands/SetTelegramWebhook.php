<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:set-webhook
                            {url? : Custom webhook URL (default: APP_URL/telegram/webhook)}
                            {--delete : Delete the current webhook instead of setting one}
                            {--info : Show current webhook info}';

    /**
     * The console command description.
     */
    protected $description = 'Register/delete/info Telegram bot webhook & bot commands';

    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return self::FAILURE;
        }

        // --info: show bot info
        if ($this->option('info')) {
            return $this->showInfo($telegram);
        }

        // --delete: remove webhook
        if ($this->option('delete')) {
            return $this->deleteWebhook($telegram);
        }

        // Set webhook
        return $this->setWebhook($telegram);
    }

    protected function setWebhook(TelegramService $telegram): int
    {
        // Safety guard: di local env, tolak set webhook tanpa URL eksplisit.
        // Ini mencegah tidak sengaja menimpa webhook production.
        if (app()->environment('local') && !$this->argument('url')) {
            $this->error('🚫 Menolak set webhook di environment local tanpa URL eksplisit.');
            $this->warn('   Webhook Telegram hanya boleh 1 per bot — menjalankan ini akan mematikan bot production.');
            $this->warn('   Jika memang perlu (misal via ngrok), jalankan:');
            $this->warn('   php artisan telegram:set-webhook https://your-ngrok-url.ngrok.io/telegram/webhook');
            return self::FAILURE;
        }

        $url = $this->argument('url')
            ?? rtrim(config('app.url'), '/') . '/telegram/webhook';

        $secret = config('services.telegram.webhook_secret');

        $this->info("Setting webhook to: {$url}");

        if ($secret) {
            $this->info("Using secret token verification ✓");
        } else {
            $this->warn("No TELEGRAM_WEBHOOK_SECRET set — webhook requests won't be verified.");
            $this->warn("Consider adding TELEGRAM_WEBHOOK_SECRET to your .env for security.");
        }

        // Set webhook
        $result = $telegram->setWebhook($url, $secret);

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook registered successfully!');
            $this->newLine();

            // Bot commands are already registered inside setWebhook()
            $this->info('✅ Bot commands registered!');

            // Show bot info
            $botInfo = $telegram->getBotInfo();
            if ($botInfo['ok'] ?? false) {
                $bot = $botInfo['data'];
                $this->newLine();
                $this->info("Bot: @{$bot['username']} ({$bot['first_name']})");
                $this->info("Webhook: {$url}");
            }

            return self::SUCCESS;
        }

        $this->error('❌ Failed to set webhook: ' . ($result['description'] ?? json_encode($result)));
        return self::FAILURE;
    }

    protected function deleteWebhook(TelegramService $telegram): int
    {
        $this->info('Deleting webhook...');

        $result = $telegram->deleteWebhook();

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook deleted successfully!');
            return self::SUCCESS;
        }

        $this->error('❌ Failed to delete webhook: ' . ($result['description'] ?? json_encode($result)));
        return self::FAILURE;
    }

    protected function showInfo(TelegramService $telegram): int
    {
        $botInfo = $telegram->getBotInfo();

        if ($botInfo['ok'] ?? false) {
            $bot = $botInfo['data'];
            $this->info("🤖 Bot Info:");
            $this->info("  Name: {$bot['first_name']}");
            $this->info("  Username: @{$bot['username']}");
            $this->info("  Bot ID: {$bot['id']}");
            $this->info("  Can join groups: " . ($bot['can_join_groups'] ?? false ? 'Yes' : 'No'));
            return self::SUCCESS;
        }

        $this->error('❌ Failed to get bot info: ' . ($botInfo['error'] ?? 'unknown'));
        return self::FAILURE;
    }
}
