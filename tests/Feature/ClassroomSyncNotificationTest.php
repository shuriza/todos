<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClassroomSyncNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_sync_sends_telegram_notification_even_when_no_tasks_changed(): void
    {
        config(['services.telegram.bot_token' => 'telegram-token']);

        $user = User::factory()->create([
            'google_access_token' => 'google-token',
            'telegram_chat_id' => '123456789',
            'notification_preferences' => array_merge(User::defaultNotificationPreferences(), [
                'classroom_sync' => true,
            ]),
        ]);

        $course = Course::create([
            'user_id' => $user->id,
            'google_course_id' => 'course-1',
            'nama_course' => 'Pemrograman Web',
        ]);

        Todo::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'title' => 'Tugas Lama',
            'description' => 'Tidak berubah',
            'priority' => 'high',
            'status' => 'todo',
            'kuadran' => 2,
            'sumber' => 'google_classroom',
            'google_task_id' => 'task-1',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Http::fake([
            'classroom.googleapis.com/v1/courses*' => Http::response([
                'courses' => [[
                    'id' => 'course-1',
                    'name' => 'Pemrograman Web',
                    'section' => '',
                    'room' => '',
                ]],
            ]),
            'classroom.googleapis.com/v1/courses/course-1/courseWork*' => Http::response([
                'courseWork' => [[
                    'id' => 'task-1',
                    'title' => 'Tugas Lama',
                    'description' => 'Tidak berubah',
                    'dueDate' => now()->addDays(2)->toArray(),
                ]],
            ]),
            'classroom.googleapis.com/v1/courses/course-1/courseWork/task-1/studentSubmissions*' => Http::response([
                'studentSubmissions' => [],
            ]),
            'api.telegram.org/bottelegram-token/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 1],
            ]),
        ]);

        $this->artisan('classroom:sync', ['--user' => $user->id])
            ->expectsOutputToContain('Notifikasi Telegram terkirim')
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'todo_id' => null,
            'tipe_notifikasi' => 'telegram',
            'status_kirim' => 'sent',
        ]);

        $this->assertStringContainsString(
            'Tidak ada perubahan tugas baru.',
            $user->notificationLogs()->latest()->first()->pesan
        );
    }
}
