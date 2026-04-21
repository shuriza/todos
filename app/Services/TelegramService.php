<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $apiBaseUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?? '';
    }

    /**
     * Check if bot token is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken);
    }

    /**
     * Send a message to a Telegram chat.
     */
    public function sendMessage(string $chatId, string $message, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $options);

            $response = Http::timeout(10)
                ->post("{$this->apiBaseUrl}{$this->botToken}/sendMessage", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false)) {
                return ['ok' => true, 'data' => $data['result'] ?? []];
            }

            Log::warning('Telegram API error', [
                'response' => $data,
                'chat_id' => $chatId,
            ]);

            return [
                'ok' => false,
                'error' => $data['description'] ?? 'Gagal mengirim pesan',
                'error_code' => $data['error_code'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Telegram send failed', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test connection to a chat ID.
     */
    public function testConnection(string $chatId): array
    {
        $message = "<b>Koneksi Telegram Berhasil</b>\n\n"
            . "Bot akan mengirim notifikasi sesuai preferensi kamu:\n"
            . "• Pengingat sebelum deadline\n"
            . "• Peringatan tugas lewat deadline\n"
            . "• Rangkuman harian\n"
            . "• Notifikasi sinkronisasi Classroom\n\n"
            . "Atur jenis notifikasi yang diterima di halaman Profil.";

        return $this->sendMessage($chatId, $message);
    }

    /**
     * Send deadline reminder notification for a specific todo.
     */
    public function sendDeadlineReminder(User $user, Todo $todo): ?NotificationLog
    {
        if (!$user->hasTelegram()) {
            return null;
        }

        $deadline = $todo->deadline?->translatedFormat('d M Y, H:i') ?? '-';
        $kuadranLabel = Todo::KUADRAN_LABELS[$todo->kuadran] ?? 'Belum ditentukan';
        $source = $todo->sumber === 'google_classroom' ? 'Google Classroom' : 'dibuat manual';

        $message = "<b>Pengingat Deadline</b>\n\n"
            . "<b>{$todo->title}</b>\n"
            . "Tenggat: {$deadline}\n"
            . "Kuadran Q{$todo->kuadran} — {$kuadranLabel}\n"
            . "Sumber: {$source}\n";

        if ($todo->description) {
            $desc = mb_substr(strip_tags($todo->description), 0, 120);
            $message .= "\n<i>{$desc}</i>\n";
        }

        if ($todo->course) {
            $message .= "Mata kuliah: {$todo->course->nama_course}\n";
        }

        $keyboard = [
            [
                ['text' => 'Tandai Selesai', 'callback_data' => "complete_task_{$todo->id}"],
            ],
        ];

        $result = $this->sendMessage($user->telegram_chat_id, $message, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);

        // Log notification
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'todo_id' => $todo->id,
            'tipe_notifikasi' => 'telegram',
            'status_kirim' => $result['ok'] ? 'sent' : 'failed',
            'pesan' => $message,
            'waktu_kirim' => $result['ok'] ? now() : null,
        ]);

        return $log;
    }

    /**
     * Send overdue alert for a todo.
     */
    public function sendOverdueAlert(User $user, Todo $todo): ?NotificationLog
    {
        if (!$user->hasTelegram()) {
            return null;
        }

        $deadline = $todo->deadline?->translatedFormat('d M Y, H:i') ?? '-';
        $kuadranLabel = Todo::KUADRAN_LABELS[$todo->kuadran] ?? '-';
        $daysLate = $todo->deadline ? (int) now()->startOfDay()->diffInDays($todo->deadline->startOfDay(), false) : 0;
        $lateText = $daysLate < 0 ? abs($daysLate) . ' hari lalu' : 'baru saja';

        $message = "<b>Tugas Lewat Deadline</b>\n\n"
            . "<b>{$todo->title}</b>\n"
            . "Tenggat: {$deadline}\n"
            . "Terlambat: {$lateText}\n"
            . "Kuadran Q{$todo->kuadran} — {$kuadranLabel}";

        $keyboard = [
            [
                ['text' => 'Tandai Selesai', 'callback_data' => "complete_task_{$todo->id}"],
            ],
        ];

        $result = $this->sendMessage($user->telegram_chat_id, $message, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);

        return NotificationLog::create([
            'user_id' => $user->id,
            'todo_id' => $todo->id,
            'tipe_notifikasi' => 'telegram',
            'status_kirim' => $result['ok'] ? 'sent' : 'failed',
            'pesan' => $message,
            'waktu_kirim' => $result['ok'] ? now() : null,
        ]);
    }

    /**
     * Send daily summary to a user.
     */
    public function sendDailySummary(User $user): ?NotificationLog
    {
        if (!$user->hasTelegram()) {
            return null;
        }

        $todayTodos = $user->todos()
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', today())
            ->orderBy('kuadran')
            ->get();

        $overdueTodos = $user->todos()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $totalPending = $user->todos()
            ->where('status', '!=', 'completed')
            ->count();

        $completedToday = $user->todos()
            ->where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();

        $message = "<b>Rangkuman Harian</b>\n"
            . now()->translatedFormat('l, d F Y') . "\n\n";

        if ($todayTodos->count() > 0) {
            $message .= "<b>Deadline Hari Ini</b> ({$todayTodos->count()})\n";
            foreach ($todayTodos->take(5) as $i => $todo) {
                $message .= "<b>[Q{$todo->kuadran}]</b> " . ($i + 1) . ". {$todo->title}\n";
            }
            if ($todayTodos->count() > 5) {
                $message .= "<i>+" . ($todayTodos->count() - 5) . " lainnya</i>\n";
            }
            $message .= "\n";
        } else {
            $message .= "<i>Tidak ada tugas yang jatuh tempo hari ini.</i>\n\n";
        }

        $message .= "<b>Progres</b>\n"
            . "Belum selesai: {$totalPending}\n"
            . "Lewat deadline: {$overdueTodos}\n"
            . "Diselesaikan hari ini: {$completedToday}";

        $result = $this->sendMessage($user->telegram_chat_id, $message);

        return NotificationLog::create([
            'user_id' => $user->id,
            'todo_id' => null,
            'tipe_notifikasi' => 'telegram',
            'status_kirim' => $result['ok'] ? 'sent' : 'failed',
            'pesan' => $message,
            'waktu_kirim' => $result['ok'] ? now() : null,
        ]);
    }

    /**
     * Send overdue summary: satu pesan ringkasan untuk banyak tugas overdue sekaligus.
     * Dipakai saat user punya >3 tugas overdue aktif (hindari spam per-tugas).
     *
     * @param \Illuminate\Support\Collection<Todo> $todos
     */
    public function sendOverdueSummary(User $user, $todos): ?NotificationLog
    {
        if (!$user->hasTelegram() || $todos->isEmpty()) {
            return null;
        }

        $total = $todos->count();
        $message = "<b>Ringkasan Tugas Terlambat</b>\n"
            . "Ada <b>{$total} tugas</b> yang sudah melewati tenggat.\n\n";

        foreach ($todos->take(5) as $i => $todo) {
            $deadline = $todo->deadline;
            $daysLate = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->startOfDay(), false) : 0;
            $lateText = $daysLate < 0 ? abs($daysLate) . ' hari lalu' : 'hari ini';
            $title = htmlspecialchars($todo->title, ENT_QUOTES, 'UTF-8');
            $message .= "<b>[Q{$todo->kuadran}]</b> " . ($i + 1) . ". {$title}\n"
                . "     <i>Terlambat {$lateText}</i>\n";
        }

        if ($total > 5) {
            $remaining = $total - 5;
            $message .= "\n<i>+{$remaining} tugas lainnya</i>\n";
        }

        $message .= "\n<i>Tandai selesai via /selesai atau buka aplikasi untuk kelola.</i>";

        $result = $this->sendMessage($user->telegram_chat_id, $message);

        return NotificationLog::create([
            'user_id' => $user->id,
            'todo_id' => null,
            'tipe_notifikasi' => 'telegram',
            'status_kirim' => $result['ok'] ? 'sent' : 'failed',
            'pesan' => $message,
            'waktu_kirim' => $result['ok'] ? now() : null,
        ]);
    }

    /**
     * Send classroom sync notification.
     */
    public function sendClassroomSyncNotification(User $user, int $newTasks, int $updatedTasks): ?NotificationLog
    {
        if (!$user->hasTelegram() || ($newTasks === 0 && $updatedTasks === 0)) {
            return null;
        }

        $message = "<b>Sinkronisasi Google Classroom Selesai</b>\n\n";

        if ($newTasks > 0) {
            $message .= "Tugas baru ditambahkan: {$newTasks}\n";
        }
        if ($updatedTasks > 0) {
            $message .= "Tugas diperbarui: {$updatedTasks}\n";
        }

        $message .= "\n<i>Lihat detail: /tugas</i>";

        $result = $this->sendMessage($user->telegram_chat_id, $message);

        return NotificationLog::create([
            'user_id' => $user->id,
            'todo_id' => null,
            'tipe_notifikasi' => 'telegram',
            'status_kirim' => $result['ok'] ? 'sent' : 'failed',
            'pesan' => $message,
            'waktu_kirim' => $result['ok'] ? now() : null,
        ]);
    }

    /**
     * Get bot info to verify token.
     */
    public function getBotInfo(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        try {
            $response = Http::timeout(10)
                ->get("{$this->apiBaseUrl}{$this->botToken}/getMe");

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false)) {
                return ['ok' => true, 'data' => $data['result'] ?? []];
            }

            return ['ok' => false, 'error' => $data['description'] ?? 'Token invalid'];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a message with an inline keyboard.
     */
    public function sendMessageWithKeyboard(string $chatId, string $message, array $inlineKeyboard, array $options = []): array
    {
        return $this->sendMessage($chatId, $message, array_merge($options, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $inlineKeyboard,
            ]),
        ]));
    }

    /**
     * Send a message with a persistent reply keyboard (buttons at bottom of chat).
     */
    public function sendMessageWithReplyKeyboard(string $chatId, string $message, array $keyboard, bool $resize = true, bool $persistent = true): array
    {
        return $this->sendMessage($chatId, $message, [
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => $resize,
                'is_persistent' => $persistent,
            ]),
        ]);
    }

    /**
     * Remove the reply keyboard.
     */
    public function removeReplyKeyboard(string $chatId, string $message): array
    {
        return $this->sendMessage($chatId, $message, [
            'reply_markup' => json_encode([
                'remove_keyboard' => true,
            ]),
        ]);
    }

    /**
     * Answer a callback query (acknowledge button press).
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBaseUrl}{$this->botToken}/answerCallbackQuery", [
                    'callback_query_id' => $callbackQueryId,
                    'text' => $text,
                    'show_alert' => $showAlert,
                ]);

            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Edit an existing message's text (for updating after callback).
     */
    public function editMessageText(string $chatId, int $messageId, string $text, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $response = Http::timeout(10)
                ->post("{$this->apiBaseUrl}{$this->botToken}/editMessageText", $payload);

            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Register webhook URL with Telegram.
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        try {
            $payload = ['url' => $url];

            if ($secretToken) {
                $payload['secret_token'] = $secretToken;
            }

            // Register bot commands at the same time
            $this->registerBotCommands();

            $response = Http::timeout(15)
                ->post("{$this->apiBaseUrl}{$this->botToken}/setWebhook", $payload);

            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete webhook.
     */
    public function deleteWebhook(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBaseUrl}{$this->botToken}/deleteWebhook");

            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Register bot commands menu in Telegram.
     */
    public function registerBotCommands(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot token belum dikonfigurasi'];
        }

        $commands = [
            ['command' => 'start', 'description' => 'Mulai bot & tampilkan menu'],
            ['command' => 'tugas', 'description' => 'Daftar semua tugas aktif'],
            ['command' => 'hari_ini', 'description' => 'Tugas deadline hari ini'],
            ['command' => 'mendesak', 'description' => 'Tugas mendesak (Kuadran 1)'],
            ['command' => 'selesai', 'description' => 'Tandai tugas selesai'],
            ['command' => 'statistik', 'description' => 'Statistik tugas'],
            ['command' => 'planning', 'description' => 'Daily planning dari AI'],
            ['command' => 'baru', 'description' => 'Mulai sesi chat AI baru'],
            ['command' => 'help', 'description' => 'Bantuan & daftar perintah'],
        ];

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBaseUrl}{$this->botToken}/setMyCommands", [
                    'commands' => $commands,
                ]);

            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
