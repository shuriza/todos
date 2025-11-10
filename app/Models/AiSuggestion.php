<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_id',
        'type',
        'suggestion',
        'is_applied',
        'applied_at',
    ];

    protected $casts = [
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
    ];

    /**
     * Get the user that owns the suggestion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the todo associated with the suggestion.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * Apply the suggestion.
     */
    public function apply()
    {
        $this->update([
            'is_applied' => true,
            'applied_at' => now(),
        ]);
    }
}
