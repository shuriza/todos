<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_access_token',
        'google_refresh_token',
        'avatar',
        'nim',
        'prodi',
        'telegram_chat_id',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_access_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Get the todos for the user.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get the courses for the user (from Google Classroom).
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get the categories for the user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the notification logs for the user.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Get AI conversations for the user.
     */
    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    /**
     * Check if user has Google Classroom connected.
     */
    public function hasGoogleClassroom(): bool
    {
        return !empty($this->google_access_token);
    }

    /**
     * Check if user has Telegram connected.
     */
    public function hasTelegram(): bool
    {
        return !empty($this->telegram_chat_id);
    }

    /**
     * Get default notification preferences.
     */
    public static function defaultNotificationPreferences(): array
    {
        return [
            'deadline_reminder' => true,
            'daily_summary' => false,
            'overdue_alert' => true,
            'classroom_sync' => true,
            'reminder_hours' => 2,       // Jam sebelum deadline
            'daily_summary_time' => '07:00',
        ];
    }

    /**
     * Get notification preference value (with defaults).
     */
    public function getNotifPref(string $key, $default = null)
    {
        $prefs = $this->notification_preferences ?? self::defaultNotificationPreferences();
        return $prefs[$key] ?? $default ?? (self::defaultNotificationPreferences()[$key] ?? null);
    }

    /**
     * Check if a specific notification type is enabled.
     */
    public function isNotifEnabled(string $key): bool
    {
        return (bool) $this->getNotifPref($key, false);
    }

    /**
     * Get user initials for avatar.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }
}
