<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AiConversation;
use App\Models\AiSuggestion;
use App\Models\Todo;

/**
 * AiAssistantService
 *
 * Service untuk fitur Asisten Pintar berbasis Google Gemini API.
 * Menangani percakapan chat dengan AI, parsing tugas dari response,
 * perencanaan harian otomatis, dan saran cerdas untuk pengguna.
 *
 * Fitur: Asisten Pintar (AI Assistant)
 *
 * Method utama:
 *  - chat()              Kirim pesan ke AI dan terima balasan beserta preview tugas
 *  - apiUrl()            Bangun URL endpoint Gemini API
 *  - parseTasksFromAI()  Ekstrak daftar tugas dari response AI
 *  - getDailyPlanning()  Buat rencana harian berdasarkan tugas aktif
 *  - getSuggestions()    Ambil saran AI untuk manajemen tugas
 */
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
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error'   => 'API key Gemini belum dikonfigurasi. Tambahkan GEMINI_API_KEY di file .env.',
            ];
        }

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

                if (trim($rawMessage) === '') {
                    Log::warning('Gemini returned empty content', ['data' => $data]);
                    return [
                        'success' => false,
                        'error'   => 'AI tidak mengembalikan respons. Coba lagi atau ubah pertanyaan.',
                    ];
                }

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

                $priority = $taskData['priority'] ?? 'high';
                $dueDate  = $taskData['due_date'] ?? null;
                $dueTime  = $taskData['due_time'] ?? null;

                // Hitung ulang kuadran dari priority + deadline. Jangan percaya
                // nilai 'kuadran' dari AI agar konsisten dengan algoritma sistem.
                $deadlineForKuadran = $dueDate
                    ? trim($dueDate . ' ' . ($dueTime ?? ''))
                    : null;

                $todo = Todo::create([
                    'user_id'          => $userId,
                    'category_id'      => $categoryId,
                    'title'            => $taskData['title'],
                    'description'      => $taskData['description'] ?? null,
                    'category'         => $categoryStr,
                    'priority'         => $priority,
                    'kuadran'          => Todo::hitungKuadran($priority, $deadlineForKuadran),
                    'status'           => 'todo',
                    'sumber'           => 'manual',
                    'due_date'         => $dueDate,
                    'due_time'         => $dueTime,
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

        if (count($created) > 0) {
            \App\Support\TodoCache::flush($userId);
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
        $todo = Todo::with('categoryModel')
            ->where('user_id', $userId)
            ->find($todoId);

        if (!$todo) {
            return ['success' => false, 'error' => 'Todo not found'];
        }

        $prompt = "Tolong analisis tugas ini dan berikan saran yang membantu secara singkat dan bersahabat:\n\n"
            . "Judul: {$todo->title}\n"
            . "Deskripsi: {$todo->description}\n"
            . "Prioritas: {$todo->priority}\n"
            . "Status: {$todo->status}\n"
            . "Deadline: {$todo->due_date}\n\n"
            . "Tolong berikan (dalam pandangan seorang asisten belajar):\n"
            . "1. Pemecahan tugas (jika terlihat cukup kompleks)\n"
            . "2. Perkiraan waktu pengerjaan\n"
            . "3. Rekomendasi prioritas\n"
            . "4. Saran terkait lainnya\n"
            . "Ingat, hindari kata-kata kaku seperti 'Sebagai AI Assistant' dan jawab dengan bahasa Indonesia yang interaktif.";

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
            ->whereNotIn('status', ['completed', 'unfinished'])
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->orderBy('due_date', 'asc')
            ->get();

        $todoList = $todos->map(fn($t) => "- [{$t->priority}] {$t->title} (Due: {$t->due_date})")->join("\n");

        $prompt = "Berdasarkan daftar tugas ini, tolong bantu saya merencanakan hari saya secara efektif:\n\n{$todoList}\n\n"
            . "Tolong berikan (sebagai asisten belajar personal):\n"
            . "1. Rencana aksi yang sudah diprioritaskan\n"
            . "2. Saran manajemen waktu\n"
            . "3. Tips produktivitas\n"
            . "4. Fokus utama untuk hari ini\n"
            . "Ingat, hindari kata-kata kaku seperti 'Sebagai AI Assistant' dan jawab dengan bahasa Indonesia yang natural dan ramah.";

        return $this->chat($prompt, $userId);
    }

    // ========================================
    // Private Helpers
    // ========================================

    /**
     * Build system prompt enriched with user's current tasks.
     * Context tugas di-cache per user (lihat config/ai.php context.cache_ttl).
     */
    protected function buildSystemPrompt(int $userId): string
    {
        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i');
        $dayName = now()->locale('id')->dayName;

        $taskContext = $this->buildTaskContext($userId);

        return <<<PROMPT
Kamu adalah asisten belajar personal cerdas untuk mahasiswa. Hari ini adalah {$dayName}, {$today}. Waktu sekarang: {$currentTime} WIB.

PENTING: Gunakan waktu {$currentTime} WIB sebagai acuan. JANGAN berasumsi jam berapa sekarang. Jika user bilang "5 menit lagi" maka hitung dari {$currentTime}.

KEMAMPUAN UTAMA:
1. **Chat biasa** — Jawab pertanyaan, beri tips produktivitas, bantu planning
2. **Buat tugas** — Jika user meminta untuk membuat/menambahkan/ingatkan tugas, BUAT PREVIEW tugas dalam format khusus

ATURAN PEMBUATAN TUGAS:
- Jika user meminta untuk **membuat**, **tambahkan**, **ingatkan**, **jadwalkan**, **buatkan** tugas → kamu WAJIB menyertakan blok <!--TASKS_START-->...<!--TASKS_END-->
- Setiap tugas HARUS berisi: title, description, category, priority, kuadran, due_date
- Format due_date: YYYY-MM-DD (berdasarkan hari ini: {$today})
- category: "kuliah" | "pekerjaan" | "daily_activity"
- priority: "high" (Penting) | "low" (Tidak Penting)
- kuadran: 1 (Do Now - Mendesak & Penting), 2 (Schedule - Penting tapi tidak mendesak), 3 (Delegate - Mendesak tapi tidak penting), 4 (Eliminate - Tidak keduanya)
- due_time: format HH:MM atau null
- reminder_minutes: angka menit pengingat sebelum deadline (opsional). Contoh: 1, 5, 10, 15, 30, 60. Jika user bilang "ingatkan 5 menit sebelumnya" → reminder_minutes: 5. Jika user bilang "ingatkan 1 jam sebelumnya" → reminder_minutes: 60. Jika tidak disebutkan → null (pakai setting global user).

FORMAT RESPONS SAAT MEMBUAT TUGAS:
Tulis pesan penjelasan biasa dulu, lalu sertakan blok tugas. Contoh:

Baik, saya buatkan 3 tugas untuk mata kuliah Basis Data:

<!--TASKS_START-->
[
  {"title": "Review materi normalisasi database", "description": "Baca ulang materi normalisasi 1NF, 2NF, 3NF dari slide pertemuan 5-7", "category": "kuliah", "priority": "high", "kuadran": 1, "due_date": "2026-02-24", "due_time": null, "reminder_minutes": null},
  {"title": "Latihan soal query SQL JOIN", "description": "Kerjakan 10 soal latihan SQL JOIN dari modul praktikum", "category": "kuliah", "priority": "low", "kuadran": 2, "due_date": "2026-02-26", "due_time": "14:00", "reminder_minutes": 30}
]
<!--TASKS_END-->

Tugas sudah saya atur prioritasnya. Kamu bisa konfirmasi untuk menambahkan ke daftar tugas, atau minta saya ubah dulu.

ATURAN UMUM:
- Jawab dalam Bahasa Indonesia yang natural dan ramah, hindari kata-kata kaku.
- PENTING: DILARANG KERAS mengatakan "Sebagai AI", "Saya adalah AI", "Sebagai asisten", "Sebagai model bahasa", atau kalimat serupa yang mengidentifikasi dirimu sebagai AI. Bertingkahlah seolah-olah kamu adalah seorang asisten belajar personal manusia yang ramah.
- JANGAN gunakan emoji sama sekali dalam respons. Tulis dengan teks biasa yang bersih dan profesional.
- Jika user tanya hal di luar manajemen tugas, tetap jawab dengan helpful
- Perhatikan tugas-tugas user yang sudah ada agar tidak membuat duplikat
- Jika user bilang "ingatkan" atau "reminder", buatkan tugas dengan deadline sesuai permintaan
{$taskContext}
PROMPT;
    }

    /**
     * Build — dan cache — task context yang disisipkan ke system prompt.
     * TTL dikonfigurasi di config/ai.php (context.cache_ttl).
     */
    protected function buildTaskContext(int $userId): string
    {
        $ttl   = (int) config('ai.context.cache_ttl', 300);
        $limit = (int) config('ai.context.active_task_limit', 30);

        $resolver = function () use ($userId, $limit) {
            $activeTasks = Todo::where('user_id', $userId)
                ->whereNotIn('status', ['completed', 'unfinished'])
                ->orderBy('due_date', 'asc')
                ->limit($limit)
                ->get(['id', 'title', 'priority', 'kuadran', 'due_date', 'due_time', 'status', 'category']);

            if ($activeTasks->isEmpty()) {
                return '';
            }

            $taskLines = $activeTasks->map(function ($t) {
                $due  = $t->due_date ? $t->due_date->format('Y-m-d') : 'no deadline';
                $time = $t->due_time ?? '';
                return "- [{$t->priority}][Q{$t->kuadran}] {$t->title} (deadline: {$due} {$time}, status: {$t->status}, kategori: {$t->category})";
            })->join("\n");

            return "\n\n=== TUGAS AKTIF USER SAAT INI ===\n{$taskLines}\n=== END TUGAS ===";
        };

        if ($ttl <= 0) {
            return $resolver();
        }

        return Cache::remember("user:{$userId}:ai_task_context", $ttl, $resolver);
    }

    /**
     * Invalidate cache context AI untuk user (dipanggil setelah create/update/delete todo).
     */
    public static function forgetTaskContextCache(int $userId): void
    {
        Cache::forget("user:{$userId}:ai_task_context");
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
            
            // Clean up possible markdown code blocks inside the string (e.g. ```json ... ```)
            $jsonStr = preg_replace('/^```(?:json)?\s*(.*?)\s*```$/s', '$1', $jsonStr);

            $decoded = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Normalize single task object to array of tasks
                if (isset($decoded['title'])) {
                    $decoded = [$decoded];
                }
                $tasks = $this->validateTaskPreviews($decoded);
            } else {
                Log::warning('AI returned invalid task JSON', [
                    'json'  => $jsonStr,
                    'error' => json_last_error_msg(),
                ]);
            }

            // Remove the task block from displayed message, including any wrapping markdown if present
            $cleanMessage = preg_replace('/```(?:json)?\s*<!--TASKS_START-->.*?<!--TASKS_END-->\s*```/s', '', $raw);
            $cleanMessage = preg_replace('/<!--TASKS_START-->.*?<!--TASKS_END-->/s', '', $cleanMessage);
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
        $allowedPriorities = ['high', 'low'];

        foreach ($tasks as $task) {
            if (empty($task['title'])) continue;

            $validated[] = [
                'title'            => (string) ($task['title'] ?? ''),
                'description'      => (string) ($task['description'] ?? ''),
                'category'         => in_array($task['category'] ?? '', $allowedCategories) ? $task['category'] : 'kuliah',
                'priority'         => in_array($task['priority'] ?? '', $allowedPriorities) ? $task['priority'] : 'high',
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
