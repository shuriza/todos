<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi revisi penguji #1: tugas berstatus 'unfinished' (tidak terselesaikan)
 * harus diperlakukan sebagai status FINAL — hilang dari daftar aktif (Semua
 * Tugas & Dashboard) dan tidak dihitung sebagai tugas pending, namun tetap
 * muncul di Arsip.
 */
class UnfinishedTaskExclusionTest extends TestCase
{
    use RefreshDatabase;

    private function makeTodo(User $user, string $status, array $extra = []): Todo
    {
        return Todo::create(array_merge([
            'user_id' => $user->id,
            'title' => "Tugas {$status}",
            'priority' => 'high',
            'status' => $status,
            'kuadran' => 1,
            'sumber' => 'manual',
            'due_date' => now()->subDay()->toDateString(),
        ], $extra));
    }

    /**
     * Seed cache stats agar query agregat berbasis CURDATE() (MySQL-only)
     * tidak dieksekusi pada SQLite test. Pola sama dengan test lain.
     */
    private function seedStatsCache(User $user): void
    {
        $stats = [
            'total' => 0, 'completed' => 0, 'pending' => 0, 'overdue' => 0,
            'in_progress' => 0, 'todo' => 0, 'pri_high' => 0, 'pri_low' => 0,
            'classroom' => 0, 'courses' => 0,
        ];
        cache()->put("user:{$user->id}:todo_stats:basic", $stats, 60);
        cache()->put("user:{$user->id}:todo_stats:full", $stats, 60);
        cache()->put("user:{$user->id}:home_dashboard", $stats, 60);
    }

    public function test_unfinished_task_tidak_muncul_di_daftar_semua_tugas(): void
    {
        $user = User::factory()->create();
        $this->seedStatsCache($user);
        $this->makeTodo($user, 'todo', ['title' => 'Tugas Aktif']);
        $this->makeTodo($user, 'unfinished', ['title' => 'Tugas Gagal']);

        $response = $this->actingAs($user)->get('/todos');

        $response->assertOk();
        $response->assertSee('Tugas Aktif');
        $response->assertDontSee('Tugas Gagal');
    }

    public function test_unfinished_task_tetap_muncul_di_arsip(): void
    {
        $user = User::factory()->create();
        $this->seedStatsCache($user);
        $this->makeTodo($user, 'unfinished', [
            'title' => 'Tugas Gagal',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/arsip');

        $response->assertOk();
        $response->assertSee('Tugas Gagal');
    }

    public function test_unfinished_task_tidak_dihitung_sebagai_pending(): void
    {
        $user = User::factory()->create();
        $this->makeTodo($user, 'todo');
        $this->makeTodo($user, 'unfinished');
        $this->makeTodo($user, 'completed', ['completed_at' => now()]);

        // Scope incomplete = definisi kanonik "tugas aktif"
        $aktif = Todo::where('user_id', $user->id)->incomplete()->count();

        $this->assertSame(1, $aktif, 'Hanya tugas todo yang dihitung aktif, unfinished & completed dikecualikan');
    }

    public function test_filter_status_eksplisit_unfinished_tetap_bisa_dilihat(): void
    {
        $user = User::factory()->create();
        $this->seedStatsCache($user);
        $this->makeTodo($user, 'unfinished', ['title' => 'Tugas Gagal']);

        // Saat user memilih filter status=unfinished secara eksplisit,
        // tugas tetap ditampilkan (override default exclude).
        $response = $this->actingAs($user)->get('/todos?status=unfinished');

        $response->assertOk();
        $response->assertSee('Tugas Gagal');
    }

    public function test_overdue_scope_mengecualikan_unfinished(): void
    {
        $user = User::factory()->create();
        $this->makeTodo($user, 'todo');         // overdue aktif
        $this->makeTodo($user, 'unfinished');   // sudah ditutup, bukan overdue lagi

        $overdue = Todo::where('user_id', $user->id)->overdue()->count();

        $this->assertSame(1, $overdue);
    }
}
