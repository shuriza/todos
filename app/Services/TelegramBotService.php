<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected TelegramService $telegram;
    protected AiAssistantService $ai;

    public function __construct(TelegramService $telegram, AiAssistantService $ai)
    {
        $this->telegram = $telegram;
        $this->ai = $ai;
    }

    // =========================================================================
    // Main Entry Point
    // =========================================================================

    /**
     * Handle an incoming Telegram update.
     */
    public function handleUpdate(array $update): void
    {
        try {
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return;
            }

            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
                return;
            }
        } catch (\Exception $e) {
            Log::error('TelegramBot handleUpdate error', [
                'error' => $e->getMessage(),
                'update_id' => $update['update_id'] ?? null,
            ]);
        }
    }

    // =========================================================================
    // Message Handling
    // =========================================================================

    protected function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim($message['text'] ?? '');

        if (empty($chatId) || empty($text)) {
            return;
        }

        // Find user by chat ID
        $user = User::where('telegram_chat_id', $chatId)->first();

        // Bot commands (entities type=bot_command)
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text, $user);
            return;
        }

        // Handle reply keyboard button presses (plain text matching)
        if ($user && $this->handleReplyKeyboardText($chatId, $text, $user)) {
            return;
        }

        // Free-text → AI Chat
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $this->handleAiChat($chatId, $text, $user);
    }

    // =========================================================================
    // Command Handlers
    // =========================================================================

    protected function handleCommand(string $chatId, string $text, ?User $user): void
    {
        // Parse command — strip @botname suffix and extract first word
        $parts = explode(' ', $text, 2);
        $command = strtolower(explode('@', $parts[0])[0]);
        $args = $parts[1] ?? '';

        match ($command) {
            '/start' => $this->commandStart($chatId, $user),
            '/help' => $this->commandHelp($chatId),
            '/tugas', '/tasks' => $this->commandTugas($chatId, $user),
            '/hari_ini', '/today' => $this->commandHariIni($chatId, $user),
            '/mendesak', '/urgent' => $this->commandMendesak($chatId, $user),
            '/selesai', '/done' => $this->commandSelesai($chatId, $user),
            '/statistik', '/stats' => $this->commandStatistik($chatId, $user),
            '/planning' => $this->commandPlanning($chatId, $user),
            '/baru', '/new' => $this->commandBaruSession($chatId, $user),
            default => $this->commandHelp($chatId),
        };
    }

    /**
     * /start — Welcome message + persistent reply keyboard.
     */
    protected function commandStart(string $chatId, ?User $user, ?int $editMessageId = null): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $name = $user->name;

        $message = "Halo <b>{$name}</b>.\n\n"
            . "Ini <b>Asisten Tugas Polinema</b>. Saya bisa bantu:\n"
            . "• Lihat daftar tugas &amp; status\n"
            . "• Tandai tugas selesai\n"
            . "• Buat tugas baru (cukup ketik apa yang ingin dikerjakan)\n"
            . "• Bikin planning harian\n\n"
            . "<b>Cara pakai:</b>\n"
            . "Pilih tombol di bawah, atau <b>ketik pesan apa saja</b> untuk bertanya/ngobrol dengan AI.\n\n"
            . "Ketik /help kalau butuh penjelasan lengkap.";

        // Reply keyboard (collapsible - toggle via keyboard icon)
        $replyKeyboard = [
            [
                ['text' => 'Tugas'],
                ['text' => 'Hari Ini'],
                ['text' => 'Mendesak'],
            ],
            [
                ['text' => 'Selesaikan'],
                ['text' => 'Statistik'],
                ['text' => 'Planning'],
            ],
            [
                ['text' => 'Refresh'],
                ['text' => 'Bantuan'],
            ],
        ];

        if ($editMessageId) {
            // Can't change reply keyboard via edit, just edit the text
            $this->telegram->editMessageText($chatId, $editMessageId, $message);
        } else {
            // Send with collapsible reply keyboard (is_persistent=false)
            $this->telegram->sendMessageWithReplyKeyboard($chatId, $message, $replyKeyboard, true, false);
        }
    }

    /**
     * /help — List of available commands.
     */
    protected function commandHelp(string $chatId): void
    {
        $message = "<b>Panduan Bot</b>\n\n"

            . "<b>Perintah</b>\n"
            . "/start — Menu utama\n"
            . "/tugas — Daftar tugas aktif\n"
            . "/hari_ini — Deadline hari ini\n"
            . "/mendesak — Tugas penting &amp; overdue\n"
            . "/selesai — Tandai tugas selesai\n"
            . "/statistik — Ringkasan angka\n"
            . "/planning — Saran urutan kerja dari AI\n"
            . "/baru — Mulai sesi chat baru\n\n"

            . "<b>Chat dengan AI</b>\n"
            . "Ketik pesan apa saja untuk bertanya atau membuat tugas. Contoh:\n"
            . "<i>• Apa yang harus saya kerjakan duluan?</i>\n"
            . "<i>• Buat tugas belajar algoritma besok jam 10</i>\n"
            . "<i>• Bantu breakdown skripsi jadi beberapa tugas kecil</i>\n\n"

            . "<b>Tentang Kuadran (Q1–Q4)</b>\n"
            . "Q1 — <b>Do Now</b>: penting &amp; mendesak\n"
            . "Q2 — <b>Schedule</b>: penting tapi belum mendesak\n"
            . "Q3 — <b>Delegate</b>: mendesak tapi kurang penting\n"
            . "Q4 — <b>Eliminate</b>: tidak penting &amp; tidak mendesak\n\n"
            . "<i>Kuadran otomatis ditentukan dari deadline &amp; prioritas tugas.</i>";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * /tugas — All active (incomplete) tasks.
     */
    protected function commandTugas(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $todos = $user->todos()
            ->with('course')
            ->where('status', '!=', 'completed')
            ->orderBy('kuadran')
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        if ($todos->isEmpty()) {
            $this->telegram->sendMessage($chatId, "Tidak ada tugas aktif. Semua sudah selesai.");
            return;
        }

        $message = "<b>Tugas Aktif</b> ({$todos->count()})\n"
            . "<i>Diurutkan berdasarkan kuadran &amp; deadline</i>\n\n";

        foreach ($todos as $i => $todo) {
            $due = $todo->due_date ? $todo->due_date->format('d/m/Y') : 'tanpa deadline';
            $progress = $todo->status === 'in_progress' ? ' · <i>dikerjakan</i>' : '';
            $message .= "<b>[Q{$todo->kuadran}]</b> " . ($i + 1) . ". {$todo->title}\n"
                . "     {$due}{$progress}\n";
        }

        $total = $user->todos()->where('status', '!=', 'completed')->count();
        if ($total > 20) {
            $message .= "\n<i>+" . ($total - 20) . " tugas lainnya — buka web untuk lihat semua</i>";
        }

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * /hari_ini — Tasks due today.
     */
    protected function commandHariIni(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $todos = $user->todos()
            ->with('course')
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', today())
            ->orderBy('kuadran')
            ->get();

        if ($todos->isEmpty()) {
            $this->telegram->sendMessage($chatId, "Tidak ada deadline hari ini.\nLihat semua tugas: /tugas");
            return;
        }

        $message = "<b>Tugas Hari Ini</b>\n"
            . now()->translatedFormat('l, d F Y') . "\n\n";

        foreach ($todos as $i => $todo) {
            $timeStr = $todo->due_time ? " · pukul {$todo->due_time}" : '';
            $progress = $todo->status === 'in_progress' ? ' · <i>dikerjakan</i>' : '';
            $message .= "<b>[Q{$todo->kuadran}]</b> " . ($i + 1) . ". {$todo->title}{$timeStr}{$progress}\n";

            if ($todo->course) {
                $message .= "     <i>dari {$todo->course->nama_course}</i>\n";
            }
        }

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * /mendesak — Quadrant 1 (Urgent & Important) tasks.
     */
    protected function commandMendesak(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $todos = $user->todos()
            ->where('status', '!=', 'completed')
            ->where('kuadran', 1)
            ->orderBy('due_date')
            ->limit(15)
            ->get();

        // Also get overdue tasks
        $overdue = $user->todos()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get();

        if ($todos->isEmpty() && $overdue->isEmpty()) {
            $this->telegram->sendMessage($chatId, "Tidak ada tugas mendesak saat ini.");
            return;
        }

        $message = "<b>Tugas Mendesak</b>\n"
            . "<i>Tugas lewat deadline &amp; kuadran 1 (penting + mendesak)</i>\n\n";

        if ($overdue->isNotEmpty()) {
            $message .= "<b>Lewat Deadline</b> ({$overdue->count()})\n";
            foreach ($overdue->take(5) as $i => $todo) {
                $due = $todo->due_date ? $todo->due_date->format('d/m/Y') : '-';
                $daysLate = $todo->due_date ? now()->startOfDay()->diffInDays($todo->due_date->startOfDay()) : 0;
                $lateStr = $daysLate > 0 ? " ({$daysLate} hari lalu)" : '';
                $message .= ($i + 1) . ". {$todo->title}\n"
                    . "     Deadline: {$due}{$lateStr}\n";
            }
            if ($overdue->count() > 5) {
                $message .= "<i>+" . ($overdue->count() - 5) . " lainnya</i>\n";
            }
            $message .= "\n";
        }

        if ($todos->isNotEmpty()) {
            $message .= "<b>Kuadran 1 — Do Now</b> ({$todos->count()})\n";
            foreach ($todos as $i => $todo) {
                $due = $todo->due_date ? $todo->due_date->format('d/m/Y') : 'tanpa deadline';
                $message .= ($i + 1) . ". {$todo->title} — {$due}\n";
            }
        }

        $message .= "\nTandai selesai: /selesai";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * /statistik — Task statistics summary.
     */
    protected function commandStatistik(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $totalActive = $user->todos()->where('status', '!=', 'completed')->count();
        $totalCompleted = $user->todos()->where('status', 'completed')->count();
        $todayDue = $user->todos()->where('status', '!=', 'completed')->whereDate('due_date', today())->count();
        $overdue = $user->todos()->where('status', '!=', 'completed')->where('due_date', '<', now()->toDateString())->count();
        $completedToday = $user->todos()->where('status', 'completed')->whereDate('updated_at', today())->count();
        $inProgress = $user->todos()->where('status', 'in_progress')->count();

        // Quadrant breakdown
        $q1 = $user->todos()->where('status', '!=', 'completed')->where('kuadran', 1)->count();
        $q2 = $user->todos()->where('status', '!=', 'completed')->where('kuadran', 2)->count();
        $q3 = $user->todos()->where('status', '!=', 'completed')->where('kuadran', 3)->count();
        $q4 = $user->todos()->where('status', '!=', 'completed')->where('kuadran', 4)->count();

        $total = $totalActive + $totalCompleted;
        $pctDone = $total > 0 ? round($totalCompleted / $total * 100) : 0;

        $message = "<b>Statistik Tugas</b>\n"
            . now()->translatedFormat('l, d F Y') . "\n\n"

            . "<b>Ringkasan</b>\n"
            . "Total tugas: {$total}\n"
            . "Selesai: {$totalCompleted} ({$pctDone}%)\n"
            . "Aktif: {$totalActive} · Sedang dikerjakan: {$inProgress}\n"
            . "Hari ini: {$todayDue} · Lewat deadline: {$overdue}\n"
            . "Diselesaikan hari ini: {$completedToday}\n\n"

            . "<b>Kuadran</b>\n"
            . "Q1 Do Now (penting &amp; mendesak): {$q1}\n"
            . "Q2 Schedule (penting): {$q2}\n"
            . "Q3 Delegate (mendesak): {$q3}\n"
            . "Q4 Eliminate (lainnya): {$q4}";

        if ($overdue > 0) {
            $message .= "\n\n<i>Ada {$overdue} tugas lewat deadline — /mendesak untuk detail</i>";
        }

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * /planning — AI daily planning.
     */
    protected function commandPlanning(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $activeTodos = $user->todos()->where('status', '!=', 'completed')->count();
        if ($activeTodos === 0) {
            $this->telegram->sendMessage($chatId, "Belum ada tugas aktif untuk direncanakan.");
            return;
        }

        // Send "typing" indicator
        $this->sendTypingAction($chatId);

        $result = $this->ai->getDailyPlanning($user->id);

        if ($result['success']) {
            $formatted = $this->formatAiResponse($result['message']);
            if (mb_strlen($formatted) > 4000) {
                $formatted = mb_substr($formatted, 0, 3950) . "\n\n<i>... (terpotong, buka web untuk lengkap)</i>";
            }
            $this->telegram->sendMessage($chatId, "<b>Daily Planning</b>\n\n" . $formatted);
        } else {
            $this->telegram->sendMessage($chatId, "Gagal membuat planning. Coba lagi nanti.\n" . ($result['error'] ?? ''));
        }
    }

    /**
     * /selesai — Show active tasks with complete buttons.
     */
    protected function commandSelesai(string $chatId, ?User $user, int $page = 0, ?int $editMessageId = null): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        $perPage = 8;
        $allTodos = $user->todos()
            ->with('course')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->orderBy('kuadran')
            ->get();

        if ($allTodos->isEmpty()) {
            $this->telegram->sendMessage($chatId, "Tidak ada tugas aktif. Semua selesai!");
            return;
        }

        $totalPages = (int) ceil($allTodos->count() / $perPage);
        $page = max(0, min($page, $totalPages - 1));
        $todos = $allTodos->slice($page * $perPage, $perPage);

        $message = "<b>Selesaikan Tugas</b>\n"
            . "Tap tombol di bawah untuk menyelesaikan\n"
            . "────────────────────\n";

        foreach ($todos->values() as $i => $todo) {
            $num = ($page * $perPage) + $i + 1;
            $due = $todo->due_date ? $todo->due_date->format('d/m/Y') : '-';
            $overdue = ($todo->due_date && $todo->due_date->isPast()) ? ' (Overdue)' : '';
            $message .= "{$num}. {$todo->title}\n"
                . "    Deadline: {$due}{$overdue}\n";
        }

        $message .= "────────────────────";
        if ($totalPages > 1) {
            $message .= "\nHal. " . ($page + 1) . "/{$totalPages} | Total: {$allTodos->count()} tugas";
        }

        // Build inline keyboard: two buttons per row for compact layout
        $keyboard = [];
        $row = [];
        foreach ($todos->values() as $i => $todo) {
            $num = ($page * $perPage) + $i + 1;
            $label = mb_substr($todo->title, 0, 20);
            $row[] = ['text' => "[{$num}] {$label}", 'callback_data' => "complete_task_{$todo->id}"];
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) {
            $keyboard[] = $row;
        }

        // Pagination buttons
        $navRow = [];
        if ($page > 0) {
            $navRow[] = ['text' => 'Sebelumnya', 'callback_data' => "selesai_page_" . ($page - 1)];
        }
        if ($page < $totalPages - 1) {
            $navRow[] = ['text' => 'Berikutnya', 'callback_data' => "selesai_page_" . ($page + 1)];
        }
        if (!empty($navRow)) {
            $keyboard[] = $navRow;
        }

        $replyMarkup = json_encode(['inline_keyboard' => $keyboard]);

        if ($editMessageId) {
            // Edit existing message in-place (no new message)
            $this->telegram->editMessageText($chatId, $editMessageId, $message, [
                'reply_markup' => $replyMarkup,
            ]);
        } else {
            $this->telegram->sendMessageWithKeyboard($chatId, $message, $keyboard);
        }
    }

    /**
     * /baru — Reset AI chat session.
     */

    protected function commandBaruSession(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendNotRegistered($chatId);
            return;
        }

        // Clear session from cache
        Cache::forget("telegram_session:{$chatId}");
        Cache::forget("telegram_tasks:{$chatId}");

        $this->telegram->sendMessage($chatId, "<b>Sesi chat baru</b>\n\nRiwayat percakapan sebelumnya dihapus. Kirim pesan apa saja untuk mulai chat dengan AI.");
    }

    // =========================================================================
    // Reply Keyboard Handler
    // =========================================================================

    /**
     * Handle text from reply keyboard buttons.
     * Returns true if matched, false to pass to AI chat.
     */
    protected function handleReplyKeyboardText(string $chatId, string $text, User $user): bool
    {
        return match ($text) {
            'Tugas' => $this->replyAction($chatId, $user, 'tugas'),
            'Hari Ini' => $this->replyAction($chatId, $user, 'hari_ini'),
            'Mendesak' => $this->replyAction($chatId, $user, 'mendesak'),
            'Selesaikan' => $this->replyAction($chatId, $user, 'selesai'),
            'Statistik' => $this->replyAction($chatId, $user, 'statistik'),
            'Planning' => $this->replyAction($chatId, $user, 'planning'),
            'Refresh' => $this->replyAction($chatId, $user, 'start'),
            'Bantuan' => $this->replyAction($chatId, $user, 'help'),
            default => false,
        };
    }

    protected function replyAction(string $chatId, User $user, string $action): bool
    {
        match ($action) {
            'tugas' => $this->commandTugas($chatId, $user),
            'hari_ini' => $this->commandHariIni($chatId, $user),
            'mendesak' => $this->commandMendesak($chatId, $user),
            'selesai' => $this->commandSelesai($chatId, $user),
            'statistik' => $this->commandStatistik($chatId, $user),
            'planning' => $this->commandPlanning($chatId, $user),
            'start' => $this->commandStart($chatId, $user),
            'help' => $this->commandHelp($chatId),
            default => null,
        };
        return true;
    }

    // =========================================================================
    // AI Chat Handler
    // =========================================================================

    protected function handleAiChat(string $chatId, string $text, User $user): void
    {
        // Send typing indicator
        $this->sendTypingAction($chatId);

        // Get or create session ID
        $sessionId = Cache::get("telegram_session:{$chatId}");
        if (!$sessionId) {
            $sessionId = 'tg_' . uniqid('', true);
            Cache::put("telegram_session:{$chatId}", $sessionId, now()->addHours(24));
        }

        $result = $this->ai->chat($text, $user->id, $sessionId);

        if (!$result['success']) {
            $this->telegram->sendMessage($chatId, "<b>Gagal memproses pesan</b>\n" . ($result['error'] ?? 'Coba kirim ulang sebentar lagi.'));
            return;
        }

        // Format AI response for Telegram
        $formatted = $this->formatAiResponse($result['message']);

        // Check if AI suggested tasks
        if (!empty($result['tasks_preview'])) {
            // Store tasks in cache for confirmation
            Cache::put("telegram_tasks:{$chatId}", $result['tasks_preview'], now()->addMinutes(15));

            // Build task preview message
            $taskMsg = $this->buildTaskPreviewMessage($result['tasks_preview']);
            $fullMessage = $formatted . "\n\n" . $taskMsg;

            if (mb_strlen($fullMessage) > 4000) {
                // Send in two messages
                $this->sendLongMessage($chatId, $formatted);

                $keyboard = [
                    [
                        ['text' => 'Simpan Tugas', 'callback_data' => 'confirm_tasks'],
                        ['text' => 'Batal', 'callback_data' => 'cancel_tasks'],
                    ],
                ];

                $this->telegram->sendMessageWithKeyboard($chatId, $taskMsg, $keyboard);
            } else {
                $keyboard = [
                    [
                        ['text' => 'Simpan Tugas', 'callback_data' => 'confirm_tasks'],
                        ['text' => 'Batal', 'callback_data' => 'cancel_tasks'],
                    ],
                ];

                $this->telegram->sendMessageWithKeyboard($chatId, $fullMessage, $keyboard);
            }
        } else {
            $this->sendLongMessage($chatId, $formatted);
        }
    }

    // =========================================================================
    // Callback Query Handler
    // =========================================================================

    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'] ?? '';
        $data = $callbackQuery['data'] ?? '';
        $chatId = (string) ($callbackQuery['message']['chat']['id'] ?? '');
        $messageId = $callbackQuery['message']['message_id'] ?? 0;

        if (empty($chatId)) return;

        $user = User::where('telegram_chat_id', $chatId)->first();

        match (true) {
            // Menu buttons
            $data === 'menu_hari_ini' => $this->handleMenuCallback($callbackId, $chatId, $user, 'hari_ini'),
            $data === 'menu_mendesak' => $this->handleMenuCallback($callbackId, $chatId, $user, 'mendesak'),
            $data === 'menu_selesai' => $this->handleMenuCallback($callbackId, $chatId, $user, 'selesai'),
            $data === 'menu_statistik' => $this->handleMenuCallback($callbackId, $chatId, $user, 'statistik'),
            $data === 'menu_tugas' => $this->handleMenuCallback($callbackId, $chatId, $user, 'tugas'),
            $data === 'menu_planning' => $this->handleMenuCallback($callbackId, $chatId, $user, 'planning'),
            $data === 'menu_help' => $this->handleMenuCallback($callbackId, $chatId, $user, 'help'),
            $data === 'menu_start' => $this->handleMenuCallback($callbackId, $chatId, $user, 'start'),
            $data === 'menu_refresh' => $this->handleRefreshCallback($callbackId, $chatId, $messageId, $user),

            // Complete task
            str_starts_with($data, 'complete_task_') => $this->handleCompleteTask($callbackId, $chatId, $messageId, $user, $data),

            // Undo complete task
            str_starts_with($data, 'undo_complete_') => $this->handleUndoComplete($callbackId, $chatId, $messageId, $user, $data),

            // Selesai pagination
            str_starts_with($data, 'selesai_page_') => $this->handleSelesaiPage($callbackId, $chatId, $messageId, $user, $data),

            // Task confirmation
            $data === 'confirm_tasks' => $this->handleConfirmTasks($callbackId, $chatId, $messageId, $user),
            $data === 'cancel_tasks' => $this->handleCancelTasks($callbackId, $chatId, $messageId),

            default => $this->telegram->answerCallbackQuery($callbackId, 'Perintah tidak dikenali'),
        };
    }

    protected function handleMenuCallback(string $callbackId, string $chatId, ?User $user, string $action): void
    {
        $this->telegram->answerCallbackQuery($callbackId);

        match ($action) {
            'hari_ini' => $this->commandHariIni($chatId, $user),
            'mendesak' => $this->commandMendesak($chatId, $user),
            'selesai' => $this->commandSelesai($chatId, $user),
            'statistik' => $this->commandStatistik($chatId, $user),
            'tugas' => $this->commandTugas($chatId, $user),
            'planning' => $this->commandPlanning($chatId, $user),
            'help' => $this->commandHelp($chatId),
            'start' => $this->commandStart($chatId, $user),
            default => null,
        };
    }

    protected function handleRefreshCallback(string $callbackId, string $chatId, int $messageId, ?User $user): void
    {
        $this->telegram->answerCallbackQuery($callbackId, 'Refreshed!');
        $this->commandStart($chatId, $user, $messageId);
    }

    protected function handleConfirmTasks(string $callbackId, string $chatId, int $messageId, ?User $user): void
    {
        if (!$user) {
            $this->telegram->answerCallbackQuery($callbackId, 'User tidak ditemukan', true);
            return;
        }

        $tasks = Cache::get("telegram_tasks:{$chatId}");

        if (empty($tasks)) {
            $this->telegram->answerCallbackQuery($callbackId, 'Tugas sudah kadaluarsa, silakan buat ulang.', true);
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId, 'Menyimpan tugas...');

        $result = $this->ai->confirmTasks($tasks, $user->id);

        // Clear cache
        Cache::forget("telegram_tasks:{$chatId}");

        if ($result['success']) {
            $count = $result['count'];
            $msg = "<b>{$count} tugas berhasil disimpan</b>\n\n";

            foreach ($result['created'] as $i => $todo) {
                $due = $todo->due_date ? " — {$todo->due_date->format('d/m/Y')}" : '';
                $msg .= ($i + 1) . ". {$todo->title}{$due}\n";
            }

            $msg .= "\n<i>Lihat daftar lengkap: /tugas</i>";

            // Edit original message to remove buttons
            $this->telegram->editMessageText($chatId, $messageId, $msg);
        } else {
            $errorMsg = "<b>Gagal menyimpan tugas</b>\n";
            if (!empty($result['errors'])) {
                $errorMsg .= implode("\n", $result['errors']);
            }
            $this->telegram->editMessageText($chatId, $messageId, $errorMsg);
        }
    }

    protected function handleCancelTasks(string $callbackId, string $chatId, int $messageId): void
    {
        Cache::forget("telegram_tasks:{$chatId}");
        $this->telegram->answerCallbackQuery($callbackId, 'Dibatalkan');
        $this->telegram->editMessageText($chatId, $messageId, "Tugas dibatalkan.");
    }

    /**
     * Handle complete_task_{id} callback — mark a task as completed.
     */
    protected function handleCompleteTask(string $callbackId, string $chatId, int $messageId, ?User $user, string $data): void
    {
        if (!$user) {
            $this->telegram->answerCallbackQuery($callbackId, 'User tidak ditemukan', true);
            return;
        }

        $todoId = (int) str_replace('complete_task_', '', $data);
        $todo = Todo::where('id', $todoId)->where('user_id', $user->id)->first();

        if (!$todo) {
            $this->telegram->answerCallbackQuery($callbackId, 'Tugas tidak ditemukan', true);
            return;
        }

        if ($todo->status === 'completed') {
            $this->telegram->answerCallbackQuery($callbackId, 'Tugas sudah selesai sebelumnya', true);
            return;
        }

        // Mark as completed
        $todo->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->telegram->answerCallbackQuery($callbackId, 'Tugas diselesaikan!');

        // Update the message to show completion
        $due = $todo->due_date ? $todo->due_date->format('d/m/Y') : '-';
        $completedMsg = "<b>Tugas Diselesaikan</b>\n"
            . "────────────────────\n"
            . "<s>{$todo->title}</s>\n"
            . "Deadline: {$due}\n"
            . "Selesai: " . now()->translatedFormat('d M Y, H:i');

        // Add keyboard: undo option + navigation
        $remaining = $user->todos()->where('status', '!=', 'completed')->count();
        $keyboard = [
            [
                ['text' => 'Batalkan (undo)', 'callback_data' => "undo_complete_{$todo->id}"],
            ],
        ];
        if ($remaining > 0) {
            $keyboard[] = [
                ['text' => "Sisa tugas ({$remaining})", 'callback_data' => 'menu_selesai'],
            ];
        }
        $keyboard[] = [
            ['text' => 'Menu Utama', 'callback_data' => 'menu_start'],
        ];

        $replyMarkup = json_encode(['inline_keyboard' => $keyboard]);
        $this->telegram->editMessageText($chatId, $messageId, $completedMsg, [
            'reply_markup' => $replyMarkup,
        ]);
    }

    /**
     * Handle undo_complete_{id} callback — revert a completed task back to todo.
     */
    protected function handleUndoComplete(string $callbackId, string $chatId, int $messageId, ?User $user, string $data): void
    {
        if (!$user) {
            $this->telegram->answerCallbackQuery($callbackId, 'User tidak ditemukan', true);
            return;
        }

        $todoId = (int) str_replace('undo_complete_', '', $data);
        $todo = Todo::where('id', $todoId)->where('user_id', $user->id)->first();

        if (!$todo) {
            $this->telegram->answerCallbackQuery($callbackId, 'Tugas tidak ditemukan', true);
            return;
        }

        if ($todo->status !== 'completed') {
            $this->telegram->answerCallbackQuery($callbackId, 'Tugas sudah aktif, tidak perlu dibatalkan', true);
            return;
        }

        // Revert to todo status
        $todo->update([
            'status' => 'todo',
            'completed_at' => null,
        ]);

        $this->telegram->answerCallbackQuery($callbackId, 'Penyelesaian dibatalkan');

        // Update the message to show reverted state
        $due = $todo->due_date ? $todo->due_date->format('d/m/Y') : '-';
        $revertedMsg = "<b>Tugas Dikembalikan ke Aktif</b>\n"
            . "────────────────────\n"
            . "<b>{$todo->title}</b>\n"
            . "Deadline: {$due}\n"
            . "Kuadran: Q{$todo->kuadran}\n\n"
            . "<i>Tugas kembali ke daftar aktif.</i>";

        $keyboard = [
            [
                ['text' => 'Selesaikan Lagi', 'callback_data' => "complete_task_{$todo->id}"],
            ],
            [
                ['text' => 'Daftar Selesaikan', 'callback_data' => 'menu_selesai'],
                ['text' => 'Menu Utama', 'callback_data' => 'menu_start'],
            ],
        ];

        $replyMarkup = json_encode(['inline_keyboard' => $keyboard]);
        $this->telegram->editMessageText($chatId, $messageId, $revertedMsg, [
            'reply_markup' => $replyMarkup,
        ]);
    }

    /**
     * Handle selesai_page_{n} callback — paginate the selesai list.
     */
    protected function handleSelesaiPage(string $callbackId, string $chatId, int $messageId, ?User $user, string $data): void
    {
        $this->telegram->answerCallbackQuery($callbackId);
        $page = (int) str_replace('selesai_page_', '', $data);
        $this->commandSelesai($chatId, $user, $page, $messageId);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Send "not registered" message to an unknown chat.
     */
    protected function sendNotRegistered(string $chatId): void
    {
        $appUrl = config('app.url', 'https://your-app.com');

        $message = "<b>Akun belum terhubung</b>\n\n"
            . "Chat ID kamu: <code>{$chatId}</code>\n\n"
            . "Cara menghubungkan:\n"
            . "1. Login di {$appUrl}\n"
            . "2. Buka Profil → Notifikasi Telegram\n"
            . "3. Paste Chat ID di atas\n"
            . "4. Kembali ke sini, ketik /start";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Send typing indicator.
     */
    protected function sendTypingAction(string $chatId): void
    {
        try {
            $botToken = config('services.telegram.bot_token');
            \Illuminate\Support\Facades\Http::timeout(5)
                ->post("https://api.telegram.org/bot{$botToken}/sendChatAction", [
                    'chat_id' => $chatId,
                    'action' => 'typing',
                ]);
        } catch (\Exception $e) {
            // Ignore typing indicator failures
        }
    }

    /**
     * Build a task preview message from AI-suggested tasks.
     */
    protected function buildTaskPreviewMessage(array $tasks): string
    {
        $count = count($tasks);
        $msg = "<b>Usulan Tugas</b> ({$count})\n"
            . "<i>Periksa dulu sebelum disimpan</i>\n\n";

        foreach ($tasks as $i => $task) {
            $title = htmlspecialchars($task['title'] ?? 'Tanpa judul', ENT_NOQUOTES, 'UTF-8');
            $kuadran = $task['kuadran'] ?? 2;
            $msg  .= "<b>[Q{$kuadran}]</b> " . ($i + 1) . ". <b>{$title}</b>\n";

            if (!empty($task['description'])) {
                $desc = htmlspecialchars(mb_substr($task['description'], 0, 120), ENT_NOQUOTES, 'UTF-8');
                $msg .= "     <i>{$desc}</i>\n";
            }

            $meta = [];
            if (!empty($task['due_date'])) {
                $dateStr = $task['due_date'];
                if (!empty($task['due_time'])) {
                    $dateStr .= ' pukul ' . $task['due_time'];
                }
                $meta[] = "deadline {$dateStr}";
            }

            $priorityLabel = match ($task['priority'] ?? 'medium') {
                'high'   => 'prioritas tinggi',
                'low'    => 'prioritas rendah',
                default  => 'prioritas sedang',
            };
            $meta[] = $priorityLabel;

            if (!empty($task['reminder_minutes'])) {
                $rm = (int) $task['reminder_minutes'];
                $rmLabel = $rm >= 60 ? 'ingatkan ' . round($rm / 60) . ' jam sebelum' : "ingatkan {$rm} menit sebelum";
                $meta[] = $rmLabel;
            }

            $msg .= '     ' . implode(' · ', $meta) . "\n\n";
        }

        return trim($msg);
    }

    /**
     * Send a long message, splitting if necessary (Telegram 4096 char limit).
     */
    protected function sendLongMessage(string $chatId, string $message): void
    {
        $maxLen = 4000;

        if (mb_strlen($message) <= $maxLen) {
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        // Split by double newline to keep paragraphs intact
        $chunks = [];
        $current = '';

        foreach (explode("\n\n", $message) as $paragraph) {
            if (mb_strlen($current . "\n\n" . $paragraph) > $maxLen) {
                if (!empty($current)) {
                    $chunks[] = trim($current);
                }
                $current = $paragraph;
            } else {
                $current .= (empty($current) ? '' : "\n\n") . $paragraph;
            }
        }

        if (!empty($current)) {
            $chunks[] = trim($current);
        }

        foreach ($chunks as $chunk) {
            $this->telegram->sendMessage($chatId, $chunk);
        }
    }

    /**
     * Format AI response (markdown) → Telegram HTML.
     *
     * Strategi pemrosesan:
     *  1. Ekstrak code block (```…```) & inline code (`…`) dulu dengan placeholder
     *     agar markdown di dalamnya tidak ikut di-transform.
     *  2. Escape HTML special chars di sisa text.
     *  3. Konversi markdown → tag Telegram.
     *  4. Substitusi kembali code block yang sudah di-escape.
     */
    protected function formatAiResponse(string $text): string
    {
        $codeBlocks = [];
        $inlineCodes = [];

        // 1a. Simpan code block (```…```) → placeholder
        $text = preg_replace_callback('/```(?:\w+)?\n?(.*?)```/s', function ($m) use (&$codeBlocks) {
            $idx = count($codeBlocks);
            $codeBlocks[$idx] = htmlspecialchars($m[1], ENT_NOQUOTES, 'UTF-8');
            return "\x00CB{$idx}\x00";
        }, $text);

        // 1b. Simpan inline code (`…`) → placeholder
        $text = preg_replace_callback('/`([^`\n]+)`/', function ($m) use (&$inlineCodes) {
            $idx = count($inlineCodes);
            $inlineCodes[$idx] = htmlspecialchars($m[1], ENT_NOQUOTES, 'UTF-8');
            return "\x00IC{$idx}\x00";
        }, $text);

        // 2. Escape sisa HTML
        $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

        // 3. Markdown → Telegram HTML
        //    Bold: **text** atau __text__
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text);
        $text = preg_replace('/__(.+?)__/s', '<b>$1</b>', $text);

        //    Italic: *text* (bukan bagian dari **) & _text_ (bukan bagian dari __)
        $text = preg_replace('/(?<!\*)\*(?!\*)([^\*\n]+?)(?<!\*)\*(?!\*)/s', '<i>$1</i>', $text);
        $text = preg_replace('/(?<![A-Za-z0-9_])_([^_\n]+)_(?![A-Za-z0-9_])/s', '<i>$1</i>', $text);

        //    Strikethrough: ~~text~~
        $text = preg_replace('/~~(.+?)~~/s', '<s>$1</s>', $text);

        //    Headers: # … → bold
        $text = preg_replace('/^#{1,6}\s+(.+)$/m', '<b>$1</b>', $text);

        //    Bullet lists: - item / * item → • item
        $text = preg_replace('/^[\-\*]\s+/m', '• ', $text);

        //    Normalisasi blank line
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 4. Restore code placeholders
        $text = preg_replace_callback('/\x00CB(\d+)\x00/', function ($m) use ($codeBlocks) {
            return '<pre>' . ($codeBlocks[(int) $m[1]] ?? '') . '</pre>';
        }, $text);

        $text = preg_replace_callback('/\x00IC(\d+)\x00/', function ($m) use ($inlineCodes) {
            return '<code>' . ($inlineCodes[(int) $m[1]] ?? '') . '</code>';
        }, $text);

        return trim($text);
    }
}
