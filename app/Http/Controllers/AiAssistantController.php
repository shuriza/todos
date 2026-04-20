<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ai\ChatRequest;
use App\Http\Requests\Ai\ConfirmTasksRequest;
use App\Models\AiConversation;
use App\Models\Todo;
use App\Services\AiAssistantService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    public function __construct(protected AiAssistantService $aiService)
    {
    }

    /**
     * Display AI assistant interface.
     */
    public function index()
    {
        return view('ai.index');
    }

    /**
     * Send a message to AI assistant.
     * Ownership todo_id divalidasi lewat ChatRequest (OwnedByUser).
     */
    public function chat(ChatRequest $request)
    {
        $validated = $request->validated();

        $response = $this->aiService->chat(
            $validated['message'],
            Auth::id(),
            $validated['session_id'] ?? null,
            $validated['todo_id'] ?? null
        );

        return response()->json($response);
    }

    /**
     * Get conversation history. History di-paginate untuk menghindari load 500+ msg.
     */
    public function history(Request $request, string $sessionId)
    {
        $limit = min((int) $request->input('limit', config('ai.history.max_messages', 50)), 200);

        $conversations = AiConversation::bySession($sessionId)
            ->where('user_id', Auth::id())
            ->limit($limit)
            ->get();

        return response()->json($conversations);
    }

    /**
     * Get AI suggestions for a todo.
     * Cek ownership via Todo model + Policy sebelum minta suggestion.
     */
    public function suggestions(Request $request, int $todoId)
    {
        $todo = Todo::find($todoId);

        if (!$todo) {
            return ApiResponse::notFound('Tugas tidak ditemukan');
        }

        if ($todo->user_id !== Auth::id()) {
            return ApiResponse::forbidden();
        }

        $response = $this->aiService->generateSuggestions($todoId, Auth::id());

        return response()->json($response);
    }

    /**
     * Get daily planning assistance.
     */
    public function dailyPlanning()
    {
        $response = $this->aiService->getDailyPlanning(Auth::id());

        return response()->json($response);
    }

    /**
     * Confirm and create tasks from AI preview.
     */
    public function confirmTasks(ConfirmTasksRequest $request)
    {
        $result = $this->aiService->confirmTasks($request->validated()['tasks'], Auth::id());

        return response()->json($result);
    }

    /**
     * Get all sessions for current user.
     */
    public function sessions()
    {
        $sessions = AiConversation::where('user_id', Auth::id())
            ->selectRaw('session_id, MIN(created_at) as first_created_at')
            ->groupBy('session_id')
            ->orderByDesc('first_created_at')
            ->get()
            ->map(function ($item) {
                $firstMessage = AiConversation::where('session_id', $item->session_id)
                    ->where('role', 'user')
                    ->first();

                return [
                    'session_id' => $item->session_id,
                    'preview' => $firstMessage ? substr($firstMessage->message, 0, 50) . '...' : '',
                    'created_at' => $firstMessage?->created_at ?? $item->first_created_at,
                ];
            });

        return response()->json($sessions);
    }
}
