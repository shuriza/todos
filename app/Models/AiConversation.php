<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_id',
        'session_id',
        'role',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the todo associated with the conversation.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * Scope a query to only include messages by session.
     */
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId)
                    ->orderBy('created_at', 'asc');
    }
}
