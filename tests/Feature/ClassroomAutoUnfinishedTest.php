<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regresi: auto-detect "tidak terselesaikan" untuk tugas Google Classroom
 * yang belum dikirim dan sudah lewat tenggat (melebihi grace), bersifat
 * reversible (status_locked tidak ditimpa sync).
 */
class ClassroomAutoUnfinishedTest extends TestCase
{
    use RefreshDatabase;

    private function fakeClassroom(string $dueDateIso, array $submissions = []): void
    {
        $due = \Carbon\Carbon::parse($dueDateIso);
        Http::fake([
            // Pola spesifik (studentSubmissions) HARUS sebelum courseWork*,
            // karena Laravel memakai pattern pertama yang cocok dan glob
            // 'courseWork*' juga match URL studentSubmissions.
            'classroom.googleapis.com/v1/courses/course-1/courseWork/task-1/studentSubmissions*' => Http::response([
                'studentSubmissions' => $submissions,
            ]),
            'classroom.googleapis.com/v1/courses/course-1/courseWork*' => Http::response([
                'courseWork' => [[
                    'id' => 'task-1',
                    'title' => 'Tugas GC',
                    'description' => 'Tugas uji',
                    'dueDate' => ['year' => (int) $due->year, 'month' => (int) $due->month, 'day' => (int) $due->day],
                    'dueTime' => ['hours' => 23, 'minutes' => 59],
                ]],
            ]),
            'classroom.googleapis.com/v1/courses?*' => Http::response([
                'courses' => [[
                    'id' => 'course-1',
                    'name' => 'Pemrograman Web',
                    'section' => '',
                    'room' => '',
                ]],
            ]),
        ]);
    }

    private function makeUserAndCourse(): array
    {
        config(['todos.unfinished_grace_days' => 1]);
        $user = User::factory()->create(['google_access_token' => 'google-token']);
        $course = Course::create([
            'user_id' => $user->id,
            'google_course_id' => 'course-1',
            'nama_course' => 'Pemrograman Web',
        ]);

        return [$user, $course];
    }

    public function test_tugas_lewat_tenggat_belum_dikirim_otomatis_unfinished(): void
    {
        [$user] = $this->makeUserAndCourse();
        // Deadline 5 hari lalu, tidak ada submission -> lewat grace
        $this->fakeClassroom(now()->subDays(5)->toDateString(), []);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'unfinished',
        ]);
    }

    public function test_tugas_dalam_grace_belum_ditandai_unfinished(): void
    {
        [$user] = $this->makeUserAndCourse();
        // Deadline kemarin; grace 1 hari -> cutoff = kemarin akhir hari + 1 hari = besok.
        // Sekarang belum melewati cutoff, jadi masih 'todo'.
        $this->fakeClassroom(now()->subDay()->toDateString(), []);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'todo',
        ]);
    }

    public function test_tugas_sudah_dikirim_tidak_ditandai_unfinished(): void
    {
        [$user] = $this->makeUserAndCourse();
        // Lewat tenggat jauh tapi sudah TURNED_IN -> tetap completed
        $this->fakeClassroom(now()->subDays(5)->toDateString(), [
            ['state' => 'TURNED_IN'],
        ]);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'completed',
        ]);
    }

    public function test_status_terkunci_tidak_ditimpa_saat_sync(): void
    {
        [$user, $course] = $this->makeUserAndCourse();

        // Mahasiswa sudah membuka kembali tugas (status_locked = true)
        Todo::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'title' => 'Tugas GC',
            'priority' => 'high',
            'status' => 'in_progress',
            'status_locked' => true,
            'kuadran' => 1,
            'sumber' => 'google_classroom',
            'google_task_id' => 'task-1',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        // Sync melihat tugas lewat tenggat & belum dikirim (harusnya unfinished),
        // tapi karena terkunci, status manual dipertahankan.
        $this->fakeClassroom(now()->subDays(5)->toDateString(), []);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'in_progress',
        ]);
    }
}
