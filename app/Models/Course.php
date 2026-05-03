<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course
 *
 * Model mata kuliah yang disinkronisasi dari Google Classroom.
 * Menyimpan data kelas seperti nama, deskripsi ruang, dan warna,
 * serta relasi ke tugas-tugas (todos) yang berasal dari kelas tersebut.
 *
 * Fitur: Google Classroom Sync
 *
 * Field penting: google_course_id, nama_course, deskripsi_ruang, color
 *
 * Method utama:
 *  - user()                  Relasi ke pemilik (User)
 *  - todos()                 Relasi ke daftar tugas dari course ini
 *  - isFromGoogleClassroom() Cek apakah course berasal dari Google Classroom
 *  - scopeFromClassroom()    Scope query hanya course dari Classroom
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_course_id',
        'nama_course',
        'deskripsi_ruang',
        'color',
    ];

    /**
     * Get the user that owns the course.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the todos/tasks for this course.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class, 'course_id');
    }

    /**
     * Check if this course is synced from Google Classroom.
     */
    public function isFromGoogleClassroom(): bool
    {
        return !empty($this->google_course_id);
    }

    /**
     * Scope: only Google Classroom courses.
     */
    public function scopeFromClassroom($query)
    {
        return $query->whereNotNull('google_course_id');
    }

    /**
     * Scope: only manual courses.
     */
    public function scopeManual($query)
    {
        return $query->whereNull('google_course_id');
    }
}
