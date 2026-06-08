<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Todo
 *
 * Model utama untuk data tugas pengguna.
 * Menyimpan informasi lengkap tugas termasuk judul, deskripsi, prioritas,
 * status, kuadran Eisenhower, deadline, dan sumber tugas (manual/classroom).
 *
 * Fitur: Manajemen Tugas
 *
 * Field penting: title, description, priority, status, kuadran, due_date,
 *                due_time, sumber, course_id, completed_at
 *
 * Method utama:
 *  - hitungKuadran()          Hitung kuadran Eisenhower berdasarkan prioritas & deadline
 *  - refreshKuadranForUser()  Perbarui kuadran semua tugas aktif milik user
 *  - user(), course()         Relasi ke model User dan Course
 */
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
        1 => 'Lakukan Sekarang',
        2 => 'Jadwalkan',
        3 => 'Delegasikan',
        4 => 'Eliminasi',
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
        'status_locked',
        'kuadran',
        'sumber',
        'google_task_id',
        'google_link',
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
        'status_locked' => 'boolean',
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
     *
     * Status final (completed & unfinished) dikecualikan karena tugas tersebut
     * sudah ditutup/diarsipkan dan tidak lagi dianggap aktif.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'unfinished'])
            ->where(function ($q) {
                // Lewat tenggat: tanggal sudah lewat, ATAU jatuh hari ini
                // tapi jam tenggat (due_time) sudah terlewati. due_time null
                // dianggap akhir hari (belum overdue sampai besok), selaras
                // dengan accessor isOverdue() yang memakai deadline penuh.
                $q->whereDate('due_date', '<', today())
                    ->orWhere(function ($q2) {
                        $q2->whereDate('due_date', '=', today())
                            ->whereNotNull('due_time')
                            ->where('due_time', '<', now()->format('H:i:s'));
                    });
            });
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
     * Scope: incomplete (aktif) tasks.
     *
     * Mengecualikan kedua status final: completed (selesai) dan unfinished
     * (tidak terselesaikan). Keduanya sudah diarsipkan dan tidak aktif lagi.
     */
    public function scopeIncomplete($query)
    {
        return $query->whereNotIn('status', ['completed', 'unfinished']);
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
        
        $days = (int) floor($now->diffInDays($deadline));
        $hours = (int) floor($now->diffInHours($deadline)) % 24;
        
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
        return $this->deadline && $this->deadline->isPast()
            && !in_array($this->status, ['completed', 'unfinished'], true);
    }

    // ===== EISENHOWER ALGORITHM =====

    /**
     * Hitung kuadran Eisenhower otomatis berdasarkan priority dan deadline.
     * Algoritma (sesuai Tabel 3.1):
     * - Mendesak (High Urgency) = deadline <= config('todos.urgency_days') hari (default 1 = 24 jam)
     * - Penting (High Importance) = priority high
     *
     * Prioritas bersifat biner sesuai sumbu importance Matriks Eisenhower:
     *   high = Penting, low = Tidak Penting.
     *
     * Kuadran 1 (DO NOW): Mendesak + Penting
     * Kuadran 2 (SCHEDULE): Tidak Mendesak + Penting
     * Kuadran 3 (DELEGATE): Mendesak + Tidak Penting
     * Kuadran 4 (ELIMINATE): Tidak Mendesak + Tidak Penting
     */
    public static function hitungKuadran(string $priority, ?string $dueDate): int
    {
        $isUrgent = false;
        $isImportant = ($priority === 'high');

        if ($dueDate) {
            $deadline = Carbon::parse($dueDate);
            $daysUntilDeadline = now()->diffInDays($deadline, false);
            $urgencyDays = (int) config('todos.urgency_days', 1);
            $isUrgent = $daysUntilDeadline <= $urgencyDays;
        }

        if ($isUrgent && $isImportant) return self::KUADRAN_DO_NOW;
        if (!$isUrgent && $isImportant) return self::KUADRAN_SCHEDULE;
        if ($isUrgent && !$isImportant) return self::KUADRAN_DELEGATE;
        return self::KUADRAN_ELIMINATE;
    }

    /**
     * Re-kalkulasi kuadran untuk semua tugas aktif milik user.
     *
     * Kuadran bersifat time-sensitive (urgency berubah seiring waktu mendekati deadline).
     * Method ini memastikan kolom `kuadran` di DB selalu up-to-date.
     *
     * Dipanggil dari:
     *   - HomeController::index() dan TodoController::index() (saat halaman dibuka)
     *   - Artisan command `todos:recalculate-kuadran` (scheduled setiap jam)
     *
     * @return int Jumlah tugas yang kuadrannya berubah
     */
    public static function refreshKuadranForUser(int $userId): int
    {
        $todos = static::where('user_id', $userId)
            ->whereNotIn('status', ['completed', 'unfinished'])
            ->whereNotNull('due_date')
            ->get(['id', 'priority', 'due_date', 'due_time', 'kuadran']);

        $updated = 0;

        foreach ($todos as $todo) {
            // Gunakan deadline penuh (date + time) agar urgensi presisi.
            // Algoritma hitungKuadran tidak berubah; hanya input yang lebih akurat.
            $deadline = $todo->deadline?->format('Y-m-d H:i:s');
            $newKuadran = static::hitungKuadran($todo->priority, $deadline);

            if ($newKuadran !== $todo->kuadran) {
                $todo->updateQuietly(['kuadran' => $newKuadran]);
                $updated++;
            }
        }

        return $updated;
    }
}
