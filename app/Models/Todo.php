<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Todo extends Model
{
    use HasFactory;

    /**
     * Label untuk setiap kuadran Eisenhower.
     */
    const KUADRAN_DO_NOW = 1;      // Mendesak & Penting
    const KUADRAN_SCHEDULE = 2;    // Tidak Mendesak & Penting
    const KUADRAN_DELEGATE = 3;    // Mendesak & Tidak Penting
    const KUADRAN_ELIMINATE = 4;   // Tidak Mendesak & Tidak Penting

    const KUADRAN_LABELS = [
        1 => 'DO NOW - Mendesak & Penting',
        2 => 'SCHEDULE - Tidak Mendesak & Penting',
        3 => 'DELEGATE - Mendesak & Tidak Penting',
        4 => 'ELIMINATE - Tidak Mendesak & Tidak Penting',
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'course_id',
        'category',
        'title',
        'description',
        'priority',
        'status',
        'kuadran',
        'sumber',
        'google_task_id',
        'due_date',
        'due_time',
        'reminder_minutes',
        'completed_at',
        'tags',
        'order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'tags' => 'array',
        'order' => 'integer',
        'kuadran' => 'integer',
        'reminder_minutes' => 'integer',
    ];

    /**
     * Get the user that owns the todo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category model that owns the todo.
     * Named categoryModel() to avoid collision with 'category' string attribute.
     */
    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the course (mata kuliah) for this todo.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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
     * Get the notification logs for this todo.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    // ===== SCOPES =====

    /**
     * Scope: filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: filter by kuadran Eisenhower.
     */
    public function scopeByKuadran($query, int $kuadran)
    {
        return $query->where('kuadran', $kuadran);
    }

    /**
     * Scope: only overdue todos.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
    }

    /**
     * Scope: only from Google Classroom.
     */
    public function scopeFromClassroom($query)
    {
        return $query->where('sumber', 'google_classroom');
    }

    /**
     * Scope: only manual tasks.
     */
    public function scopeManual($query)
    {
        return $query->where('sumber', 'manual');
    }

    /**
     * Scope: incomplete tasks.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    /**
     * Scope: tasks due today.
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    /**
     * Scope: tasks due this week.
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    // ===== ACCESSORS =====

    /**
     * Get the full deadline (date + time) as datetime.
     */
    public function getDeadlineAttribute(): ?Carbon
    {
        if (!$this->due_date) return null;
        
        if ($this->due_time) {
            return Carbon::parse($this->due_date->format('Y-m-d') . ' ' . $this->due_time);
        }
        
        return $this->due_date->endOfDay();
    }

    /**
     * Get time remaining until deadline.
     */
    public function getTimeLeftAttribute(): ?string
    {
        if (!$this->deadline) return null;
        
        $now = now();
        $deadline = $this->deadline;
        
        if ($deadline->isPast()) {
            $diff = $deadline->diffForHumans($now);
            return 'Terlewat ' . $diff;
        }
        
        $days = $now->diffInDays($deadline);
        $hours = $now->diffInHours($deadline) % 24;
        
        if ($days > 0) {
            return $days . ' hari lagi';
        }
        
        if ($hours > 0) {
            return $hours . ' jam lagi';
        }
        
        return 'Kurang dari 1 jam';
    }

    /**
     * Get kuadran label.
     */
    public function getKuadranLabelAttribute(): string
    {
        return self::KUADRAN_LABELS[$this->kuadran] ?? 'Unknown';
    }

    /**
     * Check if task is from Google Classroom.
     */
    public function isFromClassroom(): bool
    {
        return $this->sumber === 'google_classroom';
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'completed';
    }

    // ===== EISENHOWER ALGORITHM =====

    /**
     * Hitung kuadran Eisenhower otomatis berdasarkan priority dan deadline.
     * Algoritma:
     * - Mendesak = deadline <= 3 hari
     * - Penting = priority high/medium
     * 
     * Kuadran 1 (DO NOW): Mendesak + Penting
     * Kuadran 2 (SCHEDULE): Tidak Mendesak + Penting
     * Kuadran 3 (DELEGATE): Mendesak + Tidak Penting
     * Kuadran 4 (ELIMINATE): Tidak Mendesak + Tidak Penting
     */
    public static function hitungKuadran(string $priority, ?string $dueDate): int
    {
        $isUrgent = false;
        $isImportant = in_array($priority, ['high', 'medium']);
        
        if ($dueDate) {
            $deadline = Carbon::parse($dueDate);
            $daysUntilDeadline = now()->diffInDays($deadline, false);
            $isUrgent = $daysUntilDeadline <= 3; // 3 hari atau kurang = mendesak
        }
        
        if ($isUrgent && $isImportant) return self::KUADRAN_DO_NOW;
        if (!$isUrgent && $isImportant) return self::KUADRAN_SCHEDULE;
        if ($isUrgent && !$isImportant) return self::KUADRAN_DELEGATE;
        return self::KUADRAN_ELIMINATE;
    }
}
