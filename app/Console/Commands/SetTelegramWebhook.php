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

            // Register commands
            $this->info('Registering bot commands...');
            $cmdResult = $telegram->registerBotCommands();
            if ($cmdResult['ok'] ?? false) {
                $this->info('✅ Bot commands registered!');
            } else {
                $this->warn('⚠️ Failed to register bot commands: ' . ($cmdResult['description'] ?? 'unknown'));
            }

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
