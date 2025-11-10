<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AiConversation;
use App\Models\AiSuggestion;

class AiAssistantService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1/chat/completions';
    protected $model;
    protected $maxTokens;
    
    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = (int) config('services.openai.max_tokens', 1000);
    }

    /**
     * Send a message to the AI assistant
     */
    public function chat(string $message, int $userId, ?string $sessionId = null, ?int $todoId = null): array
    {
        $sessionId = $sessionId ?? uniqid('session_', true);
        
        // Get conversation history for this session
        $history = AiConversation::bySession($sessionId)
            ->where('user_id', $userId)
            ->get()
            ->map(fn($conv) => [
                'role' => $conv->role,
                'content' => $conv->message
            ])
            ->toArray();

        // Add new user message to history
        $history[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Prepare system prompt
        $systemPrompt = $this->getSystemPrompt();

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => $this->maxTokens,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $assistantMessage = $data['choices'][0]['message']['content'] ?? '';
                
                // Save conversation
                $this->saveConversation($userId, $sessionId, 'user', $message, $todoId);
                $this->saveConversation($userId, $sessionId, 'assistant', $assistantMessage, $todoId, [
                    'model' => $this->model,
                    'tokens' => $data['usage'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => $assistantMessage,
                    'session_id' => $sessionId,
                ];
            }

            Log::error('OpenAI API Error', ['response' => $response->body()]);
            return [
                'success' => false,
                'error' => 'Failed to get response from AI assistant',
            ];

        } catch (\Exception $e) {
            Log::error('AI Assistant Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'An error occurred while processing your request',
            ];
        }
    }

    /**
     * Generate AI suggestions for a todo
     */
    public function generateSuggestions(int $todoId, int $userId): array
    {
        $todo = \App\Models\Todo::with('category')->find($todoId);
        
        if (!$todo) {
            return ['success' => false, 'error' => 'Todo not found'];
        }

        $prompt = "Analyze this todo item and provide helpful suggestions:\n\n" .
                  "Title: {$todo->title}\n" .
                  "Description: {$todo->description}\n" .
                  "Priority: {$todo->priority}\n" .
                  "Status: {$todo->status}\n" .
                  "Due Date: {$todo->due_date}\n\n" .
                  "Please provide:\n" .
                  "1. Task breakdown (if complex)\n" .
                  "2. Time estimate\n" .
                  "3. Priority recommendation\n" .
                  "4. Related suggestions";

        $response = $this->chat($prompt, $userId, null, $todoId);

        if ($response['success']) {
            // Save as suggestion
            AiSuggestion::create([
                'user_id' => $userId,
                'todo_id' => $todoId,
                'type' => 'task_analysis',
                'suggestion' => $response['message'],
            ]);
        }

        return $response;
    }

    /**
     * Get daily planning assistance
     */
    public function getDailyPlanning(int $userId): array
    {
        $todos = \App\Models\Todo::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->get();

        $todoList = $todos->map(fn($t) => "- [{$t->priority}] {$t->title} (Due: {$t->due_date})")->join("\n");

        $prompt = "Based on these todos, help me plan my day effectively:\n\n{$todoList}\n\n" .
                  "Please provide:\n" .
                  "1. Prioritized action plan\n" .
                  "2. Time management suggestions\n" .
                  "3. Productivity tips\n" .
                  "4. Focus areas for today";

        return $this->chat($prompt, $userId);
    }

    /**
     * Save conversation to database
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
            'user_id' => $userId,
            'todo_id' => $todoId,
            'session_id' => $sessionId,
            'role' => $role,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get system prompt for AI assistant
     */
    protected function getSystemPrompt(): string
    {
        return "You are a helpful AI assistant for a todo and planning application. " .
               "Your role is to help users organize their tasks, provide productivity advice, " .
               "break down complex tasks, suggest priorities, and assist with daily planning. " .
               "Be concise, practical, and encouraging in Indonesian and English. " .
               "Always consider time management, task dependencies, and user's wellbeing. " .
               "Respond in a friendly and professional manner.";
    }
}
