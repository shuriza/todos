<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Send test notification to user's Telegram.
     */
    public function testTelegram(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasTelegram()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat ID belum diatur. Silakan masukkan Telegram Chat ID terlebih dahulu.',
            ], 422);
        }

        if (!$this->telegramService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram Bot belum dikonfigurasi oleh admin. Hubungi administrator.',
            ], 500);
        }

        $result = $this->telegramService->testConnection($user->telegram_chat_id);

        if ($result['ok']) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi test berhasil dikirim! Cek Telegram kamu.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim: ' . ($result['error'] ?? 'Unknown error') . '. Pastikan Chat ID benar dan sudah mengirim /start ke bot.',
        ], 422);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deadline_reminder' => 'required|boolean',
            'daily_summary' => 'required|boolean',
            'overdue_alert' => 'required|boolean',
            'classroom_sync' => 'required|boolean',
            'reminder_hours' => 'required|numeric|min:0.0167|max:48',
            'daily_summary_time' => 'required|string|date_format:H:i',
        ]);

        $validated['reminder_hours'] = (float) $validated['reminder_hours'];

        $user = $request->user();
        $user->notification_preferences = $validated;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferensi notifikasi berhasil disimpan!',
            'preferences' => $validated,
        ]);
    }

    /**
     * Get notification history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $logs = NotificationLog::where('user_id', $user->id)
            ->with('todo:id,title')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get notification stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total' => $user->notificationLogs()->count(),
            'sent' => $user->notificationLogs()->sent()->count(),
            'failed' => $user->notificationLogs()->failed()->count(),
            'pending' => $user->notificationLogs()->pending()->count(),
            'telegram' => $user->notificationLogs()->telegram()->count(),
            'today' => $user->notificationLogs()->whereDate('created_at', today())->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Save Telegram Chat ID via AJAX.
     */
    public function saveChatId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_chat_id' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $user->telegram_chat_id = $validated['telegram_chat_id'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Chat ID berhasil disimpan!',
        ]);
    }

    /**
     * Disconnect Telegram.
     */
    public function disconnectTelegram(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->telegram_chat_id = null;
        $user->notification_preferences = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Telegram berhasil diputuskan.',
        ]);
    }
}
