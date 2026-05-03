<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\TelegramService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationController
 *
 * Menangani pengaturan notifikasi Telegram pengguna, termasuk penyimpanan
 * Chat ID, pengujian pengiriman pesan, preferensi notifikasi, dan riwayat notifikasi.
 *
 * Endpoints:
 *   POST /notifikasi/telegram/save-chat-id -> saveChatId()          -> Simpan Telegram Chat ID
 *   POST /notifikasi/telegram/test         -> testTelegram()        -> Kirim notifikasi percobaan
 *   POST /notifikasi/telegram/disconnect   -> disconnectTelegram()  -> Putuskan koneksi Telegram
 *   POST /notifikasi/preferences           -> updatePreferences()   -> Perbarui preferensi notifikasi
 *   GET  /notifikasi/history               -> history()             -> Riwayat notifikasi (JSON)
 *   GET  /notifikasi/stats                 -> stats()               -> Statistik notifikasi (JSON)
 */
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
            return ApiResponse::error('Chat ID belum diatur. Silakan masukkan Telegram Chat ID terlebih dahulu.', 422);
        }

        if (!$this->telegramService->isConfigured()) {
            return ApiResponse::error('Telegram Bot belum dikonfigurasi oleh admin. Hubungi administrator.', 500);
        }

        $result = $this->telegramService->testConnection($user->telegram_chat_id);

        if ($result['ok']) {
            return ApiResponse::ok(null, 'Notifikasi test berhasil dikirim! Cek Telegram kamu.');
        }

        return ApiResponse::error(
            'Gagal mengirim: ' . ($result['error'] ?? 'Unknown error')
            . '. Pastikan Chat ID benar dan sudah mengirim /start ke bot.',
            422
        );
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deadline_reminder'       => 'required|boolean',
            'daily_summary'           => 'required|boolean',
            'overdue_alert'           => 'required|boolean',
            'classroom_sync'          => 'required|boolean',
            'reminder_hours'          => 'required|numeric|min:0.0167|max:48',
            'overdue_max_days'        => 'required|integer|min:1|max:30',
            'overdue_cooldown_hours'  => 'required|integer|min:1|max:72',
            'daily_summary_time'      => 'required|string|date_format:H:i',
        ]);

        $validated['reminder_hours'] = (float) $validated['reminder_hours'];
        $validated['overdue_max_days'] = (int) $validated['overdue_max_days'];
        $validated['overdue_cooldown_hours'] = (int) $validated['overdue_cooldown_hours'];

        $user = $request->user();
        $user->notification_preferences = $validated;
        $user->save();

        return ApiResponse::ok(
            ['preferences' => $validated],
            'Preferensi notifikasi berhasil disimpan!'
        );
    }

    /**
     * Get notification history (paginated).
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $logs = NotificationLog::where('user_id', $user->id)
            ->with('todo:id,title')
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::ok($logs);
    }

    /**
     * Get notification stats (single aggregate query).
     * Sebelumnya: 6 count query terpisah.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $row = NotificationLog::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status_kirim = 'sent' THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status_kirim = 'failed' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN status_kirim = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN tipe_notifikasi = 'telegram' THEN 1 ELSE 0 END) AS telegram,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
            ")
            ->first();

        $stats = [
            'total'    => (int) ($row->total ?? 0),
            'sent'     => (int) ($row->sent ?? 0),
            'failed'   => (int) ($row->failed ?? 0),
            'pending'  => (int) ($row->pending ?? 0),
            'telegram' => (int) ($row->telegram ?? 0),
            'today'    => (int) ($row->today ?? 0),
        ];

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }

    /**
     * Save Telegram Chat ID via AJAX.
     *
     * Validation:
     *  - Harus numeric (Telegram chat ID selalu integer, bisa negatif utk group).
     *  - Tidak boleh dipakai user lain (unik per user).
     */
    public function saveChatId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_chat_id' => [
                'required',
                'string',
                'max:32',
                'regex:/^-?\d+$/',
            ],
        ], [
            'telegram_chat_id.regex' => 'Chat ID harus berupa angka (contoh: 123456789).',
        ]);

        $chatId = $validated['telegram_chat_id'];
        $user = $request->user();

        // Pastikan chat_id ini belum dipakai user lain
        $usedByOther = User::where('telegram_chat_id', $chatId)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($usedByOther) {
            return ApiResponse::error(
                'Chat ID ini sudah terhubung dengan akun lain. Gunakan Chat ID yang berbeda.',
                422
            );
        }

        $user->telegram_chat_id = $chatId;
        $user->save();

        return ApiResponse::ok(null, 'Chat ID berhasil disimpan!');
    }

    /**
     * Disconnect Telegram — reset chat ID dan preferensi notifikasi.
     */
    public function disconnectTelegram(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->telegram_chat_id = null;
        $user->notification_preferences = null;
        $user->save();

        return ApiResponse::ok(null, 'Telegram berhasil diputuskan.');
    }
}
