<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'category',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'tags',
        'order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'tags' => 'array',
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the todo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that owns the todo.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the AI conversations for the todo.
     */
    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    /**
     * Get the AI suggestions for the todo.
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    /**
     * Scope a query to only include todos by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include todos by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include overdue todos.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
    }
}
