<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AiConversation;
use App\Models\AiSuggestion;
use App\Models\Todo;

class AiAssistantService
{
    protected string $apiKey;
    protected string $model;
    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey    = config('services.gemini.api_key') ?? '';
        $this->model     = config('services.gemini.model') ?? 'gemini-2.5-flash';
        $this->maxTokens = (int) (config('services.gemini.max_tokens') ?? 2000);
    }

    /**
     * Build the native Gemini API URL for current model.
     */
    protected function apiUrl(): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
    }

    /**
     * Send a message to the AI assistant.
     * Returns ['success', 'message', 'session_id', 'tasks_preview'?]
     */
    public function chat(string $message, int $userId, ?string $sessionId = null, ?int $todoId = null): array
    {
        $sessionId = $sessionId ?? uniqid('session_', true);

        // Conversation history
        $history = AiConversation::bySession($sessionId)
            ->where('user_id', $userId)
            ->get()
            ->map(fn($conv) => [
                'role'    => $conv->role,
                'content' => $conv->message,
            ])
            ->toArray();

        $history[] = [
            'role'    => 'user',
            'content' => $message,
        ];

        // System prompt — enriched with user's tasks context
        $systemPrompt = $this->buildSystemPrompt($userId);

        try {
            // Convert OpenAI-style messages to native Gemini format
            // 'assistant' role in history becomes 'model' in Gemini
            $contents = array_map(function ($msg) {
                return [
                    'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]],
                ];
            }, $history);

            $payload = [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents'          => $contents,
                'generationConfig'  => [
                    'maxOutputTokens' => $this->maxTokens,
                    'temperature'     => 0.7,
                ],
            ];

            $headers = ['Content-Type' => 'application/json'];

            // Auto-retry on 429 (rate limit) — max 2 retries with 5s delay
            $response = null;
            $maxRetries = 2;
            for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
                $response = Http::withHeaders($headers)
                    ->timeout(60)
                    ->post($this->apiUrl(), $payload);

                if ($response->status() !== 429 || $attempt === $maxRetries) {
                    break;
                }
                Log::info("Gemini rate limited (429), retrying in 5s... (attempt " . ($attempt + 1) . "/$maxRetries)");
                sleep(5);
            }

            if ($response->successful()) {
                $data       = $response->json();
                $rawMessage = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Strip <think>...</think> blocks (jika ada)
                $rawMessage = $this->stripThinkingBlocks($rawMessage);

                // Save conversation
                $this->saveConversation($userId, $sessionId, 'user', $message, $todoId);
                $this->saveConversation($userId, $sessionId, 'assistant', $rawMessage, $todoId, [
                    'model'    => $this->model,
                    'provider' => 'gemini',
                    'tokens'   => $data['usageMetadata'] ?? null,
                ]);

                // Try to extract task previews from AI response
                $parsed = $this->parseTasksFromResponse($rawMessage);

                $result = [
                    'success'    => true,
                    'message'    => $parsed['message'],
                    'session_id' => $sessionId,
                ];

                if (!empty($parsed['tasks'])) {
                    $result['tasks_preview'] = $parsed['tasks'];
                }

                return $result;
            }

            Log::error('Gemini API Error', [
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            // Detect quota-0 (API key dari Cloud Console, bukan AI Studio)
            if ($response->status() === 429) {
                $body = $response->json();
                $rawMsg = $body['error']['message'] ?? '';
                if (str_contains($rawMsg, 'limit: 0')) {
                    return [
                        'success' => false,
                        'error'   => '⚠️ API key Gemini kamu tidak memiliki free tier quota. Buat API key baru dari https://aistudio.google.com/apikey (bukan dari Google Cloud Console), lalu ganti GEMINI_API_KEY di .env.',
                    ];
                }
                return [
                    'success' => false,
                    'error'   => '⏳ AI sedang sibuk (rate limit). Tunggu 1 menit lalu coba lagi.',
                ];
            }

            return [
                'success' => false,
                'error'   => 'Gagal mendapatkan respons dari AI. Status: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('Gemini AI Error', [
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => 'Terjadi kesalahan saat memproses permintaan: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm and create tasks from AI preview.
     */
    public function confirmTasks(array $tasks, int $userId): array
    {
        $created = [];
        $errors  = [];

        foreach ($tasks as $i => $taskData) {
            try {
                // Try to match category string to an existing Category model
                $categoryId = null;
                $categoryStr = $taskData['category'] ?? 'kuliah';
                $existingCategory = \App\Models\Category::where('user_id', $userId)
                    ->whereRaw('LOWER(name) = ?', [strtolower($categoryStr)])
                    ->first();
                if ($existingCategory) {
                    $categoryId = $existingCategory->id;
                }

                $todo = Todo::create([
                    'user_id'          => $userId,
                    'category_id'      => $categoryId,
                    'title'            => $taskData['title'],
                    'description'      => $taskData['description'] ?? null,
                    'category'         => $categoryStr,
                    'priority'         => $taskData['priority'] ?? 'medium',
                    'kuadran'          => $taskData['kuadran'] ?? 2,
                    'status'           => 'todo',
                    'sumber'           => 'manual',
                    'due_date'         => $taskData['due_date'] ?? null,
                    'due_time'         => $taskData['due_time'] ?? null,
                    'reminder_minutes' => $taskData['reminder_minutes'] ?? null,
                ]);
                $created[] = $todo->load('course');
            } catch (\Exception $e) {
                Log::error('Failed to create AI task', [
                    'task'  => $taskData,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "Tugas #" . ($i + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'success' => count($created) > 0,
            'created' => $created,
            'count'   => count($created),
            'errors'  => $errors,
        ];
    }

    /**
     * Generate AI suggestions for a todo.
     */
    public function generateSuggestions(int $todoId, int $userId): array
    {
        $todo = Todo::with('categoryModel')->find($todoId);

        if (!$todo) {
            return ['success' => false, 'error' => 'Todo not found'];
        }

        $prompt = "Analyze this todo item and provide helpful suggestions:\n\n"
            . "Title: {$todo->title}\n"
            . "Description: {$todo->description}\n"
            . "Priority: {$todo->priority}\n"
            . "Status: {$todo->status}\n"
            . "Due Date: {$todo->due_date}\n\n"
            . "Please provide:\n"
            . "1. Task breakdown (if complex)\n"
            . "2. Time estimate\n"
            . "3. Priority recommendation\n"
            . "4. Related suggestions";

        $response = $this->chat($prompt, $userId, null, $todoId);

        if ($response['success']) {
            AiSuggestion::create([
                'user_id'    => $userId,
                'todo_id'    => $todoId,
                'type'       => 'task_analysis',
                'suggestion' => $response['message'],
            ]);
        }

        return $response;
    }

    /**
     * Get daily planning assistance.
     */
    public function getDailyPlanning(int $userId): array
    {
        $todos = Todo::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->get();

        $todoList = $todos->map(fn($t) => "- [{$t->priority}] {$t->title} (Due: {$t->due_date})")->join("\n");

        $prompt = "Based on these todos, help me plan my day effectively:\n\n{$todoList}\n\n"
            . "Please provide:\n"
            . "1. Prioritized action plan\n"
            . "2. Time management suggestions\n"
            . "3. Productivity tips\n"
            . "4. Focus areas for today";

        return $this->chat($prompt, $userId);
    }

    // ========================================
    // Private Helpers
    // ========================================

    /**
     * Build system prompt enriched with user's current tasks.
     */
    protected function buildSystemPrompt(int $userId): string
    {
        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i');
        $dayName = now()->locale('id')->dayName;

        // Fetch user's active tasks for context
        $activeTasks = Todo::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->limit(30)
            ->get();

        $taskContext = '';
        if ($activeTasks->isNotEmpty()) {
            $taskLines = $activeTasks->map(function ($t) {
                $due = $t->due_date ? $t->due_date->format('Y-m-d') : 'no deadline';
                $time = $t->due_time ?? '';
                return "- [{$t->priority}][Q{$t->kuadran}] {$t->title} (deadline: {$due} {$time}, status: {$t->status}, kategori: {$t->category})";
            })->join("\n");
            $taskContext = "\n\n=== TUGAS AKTIF USER SAAT INI ===\n{$taskLines}\n=== END TUGAS ===";
        }

        return <<<PROMPT
Kamu adalah AI Assistant cerdas untuk aplikasi manajemen tugas mahasiswa. Hari ini adalah {$dayName}, {$today}. Waktu sekarang: {$currentTime} WIB.

PENTING: Gunakan waktu {$currentTime} WIB sebagai acuan. JANGAN berasumsi jam berapa sekarang. Jika user bilang "5 menit lagi" maka hitung dari {$currentTime}.

KEMAMPUAN UTAMA:
1. **Chat biasa** — Jawab pertanyaan, beri tips produktivitas, bantu planning
2. **Buat tugas** — Jika user meminta untuk membuat/menambahkan/ingatkan tugas, BUAT PREVIEW tugas dalam format khusus

ATURAN PEMBUATAN TUGAS:
- Jika user meminta untuk **membuat**, **tambahkan**, **ingatkan**, **jadwalkan**, **buatkan** tugas → kamu WAJIB menyertakan blok <!--TASKS_START-->...<!--TASKS_END-->
- Setiap tugas HARUS berisi: title, description, category, priority, kuadran, due_date
- Format due_date: YYYY-MM-DD (berdasarkan hari ini: {$today})
- category: "kuliah" | "pekerjaan" | "daily_activity"
- priority: "high" | "medium" | "low"
- kuadran: 1 (Do Now - Mendesak & Penting), 2 (Schedule - Penting tapi tidak mendesak), 3 (Delegate - Mendesak tapi tidak penting), 4 (Eliminate - Tidak keduanya)
- due_time: format HH:MM atau null
- reminder_minutes: angka menit pengingat sebelum deadline (opsional). Contoh: 1, 5, 10, 15, 30, 60. Jika user bilang "ingatkan 5 menit sebelumnya" → reminder_minutes: 5. Jika user bilang "ingatkan 1 jam sebelumnya" → reminder_minutes: 60. Jika tidak disebutkan → null (pakai setting global user).

FORMAT RESPONS SAAT MEMBUAT TUGAS:
Tulis pesan penjelasan biasa dulu, lalu sertakan blok tugas. Contoh:

Baik, saya buatkan 3 tugas untuk mata kuliah Basis Data:

<!--TASKS_START-->
[
  {"title": "Review materi normalisasi database", "description": "Baca ulang materi normalisasi 1NF, 2NF, 3NF dari slide pertemuan 5-7", "category": "kuliah", "priority": "high", "kuadran": 1, "due_date": "2026-02-24", "due_time": null, "reminder_minutes": null},
  {"title": "Latihan soal query SQL JOIN", "description": "Kerjakan 10 soal latihan SQL JOIN dari modul praktikum", "category": "kuliah", "priority": "medium", "kuadran": 2, "due_date": "2026-02-26", "due_time": "14:00", "reminder_minutes": 30}
]
<!--TASKS_END-->

Tugas sudah saya atur prioritasnya. Kamu bisa konfirmasi untuk menambahkan ke daftar tugas, atau minta saya ubah dulu.

ATURAN UMUM:
- Jawab dalam Bahasa Indonesia yang natural dan ramah
- Gunakan emoji secukupnya untuk kesan friendly
- Jika user tanya hal di luar manajemen tugas, tetap jawab dengan helpful
- Perhatikan tugas-tugas user yang sudah ada agar tidak membuat duplikat
- Jika user bilang "ingatkan" atau "reminder", buatkan tugas dengan deadline sesuai permintaan
{$taskContext}
PROMPT;
    }

    /**
     * Parse AI response to extract task preview JSON.
     */
    protected function parseTasksFromResponse(string $raw): array
    {
        $tasks = [];
        $cleanMessage = $raw;

        if (preg_match('/<!--TASKS_START-->\s*(.*?)\s*<!--TASKS_END-->/s', $raw, $matches)) {
            $jsonStr = trim($matches[1]);

            $decoded = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tasks = $this->validateTaskPreviews($decoded);
            } else {
                Log::warning('AI returned invalid task JSON', [
                    'json'  => $jsonStr,
                    'error' => json_last_error_msg(),
                ]);
            }

            // Remove the task block from displayed message
            $cleanMessage = preg_replace('/<!--TASKS_START-->.*?<!--TASKS_END-->/s', '', $raw);
            $cleanMessage = trim($cleanMessage);
        }

        return [
            'message' => $cleanMessage,
            'tasks'   => $tasks,
        ];
    }

    /**
     * Validate and normalize task preview data.
     */
    protected function validateTaskPreviews(array $tasks): array
    {
        $validated = [];
        $allowedCategories = ['kuliah', 'pekerjaan', 'daily_activity'];
        $allowedPriorities = ['high', 'medium', 'low'];

        foreach ($tasks as $task) {
            if (empty($task['title'])) continue;

            $validated[] = [
                'title'            => (string) ($task['title'] ?? ''),
                'description'      => (string) ($task['description'] ?? ''),
                'category'         => in_array($task['category'] ?? '', $allowedCategories) ? $task['category'] : 'kuliah',
                'priority'         => in_array($task['priority'] ?? '', $allowedPriorities) ? $task['priority'] : 'medium',
                'kuadran'          => in_array((int)($task['kuadran'] ?? 2), [1, 2, 3, 4]) ? (int)$task['kuadran'] : 2,
                'due_date'         => $this->parseDateSafe($task['due_date'] ?? null),
                'due_time'         => $this->parseTimeSafe($task['due_time'] ?? null),
                'reminder_minutes' => isset($task['reminder_minutes']) && is_numeric($task['reminder_minutes']) ? (int) $task['reminder_minutes'] : null,
            ];
        }

        return $validated;
    }

    protected function parseDateSafe(?string $date): ?string
    {
        if (!$date) return null;
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseTimeSafe(?string $time): ?string
    {
        if (!$time) return null;
        try {
            return \Carbon\Carbon::parse($time)->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Strip <think>...</think> blocks from reasoning model outputs.
     */
    protected function stripThinkingBlocks(string $text): string
    {
        return trim(preg_replace('/<think>.*?<\/think>/s', '', $text));
    }

    /**
     * Save conversation to database.
     */
    protected function saveConversation(
        int $userId,
        string $sessionId,
        string $role,
        string $message,
        ?int $todoId = null,
        ?array $metadata = null
    ): void {
        AiConversation::create([
            'user_id'    => $userId,
            'todo_id'    => $todoId,
            'session_id' => $sessionId,
            'role'       => $role,
            'message'    => $message,
            'metadata'   => $metadata,
        ]);
    }
}
