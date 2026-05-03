<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * TelegramBotController
 *
 * Menangani webhook dari Telegram Bot — menerima dan memproses update
 * yang dikirim oleh server Telegram (pesan, command, dsb).
 * Endpoint ini tidak memerlukan autentikasi pengguna maupun CSRF,
 * proteksi dilakukan melalui header X-Telegram-Bot-Api-Secret-Token.
 *
 * Endpoints:
 *   POST /telegram/webhook -> handleWebhook() -> Terima update dari Telegram
 */
class TelegramBotController extends Controller
{
    protected TelegramBotService $botService;

    public function __construct(TelegramBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Handle incoming Telegram webhook updates.
     *
     * POST /telegram/webhook
     * No auth, no CSRF — this is called by Telegram servers.
     * Proteksi dilakukan via X-Telegram-Bot-Api-Secret-Token header.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Enforce webhook secret di production. Di env local/testing boleh kosong
        // untuk memudahkan dev. Lihat config/telegram.php.
        $expectedSecret = config('telegram.webhook_secret') ?? config('services.telegram.webhook_secret');
        $optionalEnvs   = config('telegram.webhook_secret_optional_envs', ['local', 'testing']);

        if (empty($expectedSecret) && !in_array(app()->environment(), $optionalEnvs, true)) {
            Log::error('Telegram webhook: TELEGRAM_WEBHOOK_SECRET belum di-set di production');
            return response()->json(['ok' => false, 'error' => 'webhook not configured'], 403);
        }

        if (!empty($expectedSecret)) {
            $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (!is_string($headerSecret) || !hash_equals($expectedSecret, $headerSecret)) {
                Log::warning('Telegram webhook: invalid secret token', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['ok' => false], 403);
            }
        }

        $update = $request->all();

        if (empty($update)) {
            return response()->json(['ok' => true]);
        }

        Log::debug('Telegram webhook received', [
            'update_id' => $update['update_id'] ?? null,
            'has_message' => isset($update['message']),
            'has_callback' => isset($update['callback_query']),
        ]);

        // Di local env (artisan serve), terminating callback tidak selalu fire.
        // Proses langsung secara sinkron agar bot tetap respond saat development.
        if (app()->environment('local', 'testing')) {
            $this->botService->handleUpdate($update);
            return response()->json(['ok' => true]);
        }

        // Di production: proses SETELAH response 200 dikirim ke Telegram.
        // Telegram berhenti menunggu langsung, mengurangi perceived latency.
        $botService = $this->botService;
        app()->terminating(function () use ($botService, $update) {
            $botService->handleUpdate($update);
        });

        return response()->json(['ok' => true]);
    }
}
