<?php

namespace App\Http\Controllers;

use App\Services\AiAssistantService;
use App\Models\AiConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    protected $aiService;

    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
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
     */
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string',
            'todo_id' => 'nullable|exists:todos,id',
        ]);

        $response = $this->aiService->chat(
            $validated['message'],
            Auth::id(),
            $validated['session_id'] ?? null,
            $validated['todo_id'] ?? null
        );

        return response()->json($response);
    }

    /**
     * Get conversation history.
     */
    public function history(Request $request, string $sessionId)
    {
        $conversations = AiConversation::bySession($sessionId)
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($conversations);
    }

    /**
     * Get AI suggestions for a todo.
     */
    public function suggestions(Request $request, int $todoId)
    {
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
    public function confirmTasks(Request $request)
    {
        $validated = $request->validate([
            'tasks'   => 'required|array|min:1',
            'tasks.*.title'       => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.category'    => 'nullable|string|in:kuliah,pekerjaan,daily_activity',
            'tasks.*.priority'    => 'nullable|string|in:high,medium,low',
            'tasks.*.kuadran'     => 'nullable|integer|in:1,2,3,4',
            'tasks.*.due_date'    => 'nullable|date',
            'tasks.*.due_time'    => 'nullable|string',
            'tasks.*.reminder_minutes' => 'nullable|integer|min:1|max:2880',
        ]);

        $result = $this->aiService->confirmTasks($validated['tasks'], Auth::id());

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
