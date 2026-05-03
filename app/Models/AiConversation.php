<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiConversation
 *
 * Model percakapan AI berbasis sesi (session-based).
 * Menyimpan riwayat pesan antara pengguna dan AI Assistant,
 * termasuk role (user/assistant), isi pesan, dan metadata tambahan.
 *
 * Fitur: Asisten Pintar (AI Chat History)
 *
 * Field penting: session_id, role, message, metadata, todo_id
 *
 * Method utama:
 *  - user(), todo()       Relasi ke model User dan Todo
 *  - scopeBySession()     Scope query berdasarkan session_id (urut waktu)
 */
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
