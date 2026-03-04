<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_id',
        'tipe_notifikasi',
        'status_kirim',
        'pesan',
        'waktu_kirim',
    ];

    protected $casts = [
        'waktu_kirim' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the user that owns the notification log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the todo associated with this notification.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    // ===== SCOPES =====

    /**
     * Scope: only telegram notifications.
     */
    public function scopeTelegram($query)
    {
        return $query->where('tipe_notifikasi', 'telegram');
    }

    /**
     * Scope: only email notifications.
     */
    public function scopeEmail($query)
    {
        return $query->where('tipe_notifikasi', 'email');
    }

    /**
     * Scope: only successfully sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status_kirim', 'sent');
    }

    /**
     * Scope: only failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status_kirim', 'failed');
    }

    /**
     * Scope: only pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status_kirim', 'pending');
    }

    // ===== HELPERS =====

    /**
     * Check if notification was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status_kirim === 'sent';
    }

    /**
     * Check if notification failed.
     */
    public function isFailed(): bool
    {
        return $this->status_kirim === 'failed';
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status_kirim' => 'sent',
            'waktu_kirim' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status_kirim' => 'failed',
        ]);
    }
}
