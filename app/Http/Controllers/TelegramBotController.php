<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Verify secret token if configured
        $expectedSecret = config('services.telegram.webhook_secret');
        if ($expectedSecret) {
            $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if ($headerSecret !== $expectedSecret) {
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

        // Register processing to run AFTER the 200 response is sent to Telegram.
        // This means Telegram gets the 200 instantly and stops waiting,
        // dramatically reducing perceived latency for callbacks and commands.
        $botService = $this->botService;
        app()->terminating(function () use ($botService, $update) {
            $botService->handleUpdate($update);
        });

        // Return 200 immediately — Telegram stops waiting right away
        return response()->json(['ok' => true]);
    }
}
