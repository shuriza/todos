<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regresi: sinkronisasi Google Classroom TIDAK pernah auto-menandai tugas
 * sebagai "tidak terselesaikan".
 *
 * Alasan: Google Classroom API tidak mengekspos status "ditutup", dan
 * submission yang gagal terbaca (mis. cakupan izin/scope belum lengkap)
 * akan keliru dianggap "belum dikirim" — sehingga tugas yang sudah
 * diserahkan tepat waktu bisa salah ditandai. Penandaan "tidak
 * terselesaikan" kini sepenuhnya keputusan manual mahasiswa (reversible).
 */
class ClassroomSyncStatusTest extends TestCase
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
        $user = User::factory()->create(['google_access_token' => 'google-token']);
        $course = Course::create([
            'user_id' => $user->id,
            'google_course_id' => 'course-1',
            'nama_course' => 'Pemrograman Web',
        ]);

        return [$user, $course];
    }

    public function test_tugas_lewat_tenggat_belum_dikirim_tetap_todo(): void
    {
        [$user] = $this->makeUserAndCourse();
        // Lewat tenggat jauh & submission tidak terbaca (kosong). Sync TIDAK
        // boleh auto-menandai unfinished — ini bug yang dilaporkan penguji.
        $this->fakeClassroom(now()->subDays(5)->toDateString(), []);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'todo',
        ]);
        $this->assertDatabaseMissing('todos', [
            'google_task_id' => 'task-1',
            'status' => 'unfinished',
        ]);
    }

    public function test_tugas_sudah_dikirim_jadi_completed(): void
    {
        [$user] = $this->makeUserAndCourse();
        // Lewat tenggat jauh tapi sudah TURNED_IN -> completed.
        $this->fakeClassroom(now()->subDays(5)->toDateString(), [
            ['state' => 'TURNED_IN'],
        ]);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'completed',
        ]);
    }

    public function test_tugas_terlambat_ditandai_is_late(): void
    {
        [$user] = $this->makeUserAndCourse();
        // Sudah diserahkan tapi terlambat (late=true) -> completed + is_late.
        $this->fakeClassroom(now()->subDays(5)->toDateString(), [
            ['state' => 'TURNED_IN', 'late' => true],
        ]);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'completed',
            'is_late' => true,
        ]);
    }

    public function test_status_terkunci_tidak_ditimpa_saat_sync(): void
    {
        [$user, $course] = $this->makeUserAndCourse();

        // Mahasiswa sudah membuka kembali tugas (status_locked = true).
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

        $this->fakeClassroom(now()->subDays(5)->toDateString(), []);

        $this->artisan('classroom:sync', ['--user' => $user->id])->assertSuccessful();

        $this->assertDatabaseHas('todos', [
            'google_task_id' => 'task-1',
            'status' => 'in_progress',
        ]);
    }
}
