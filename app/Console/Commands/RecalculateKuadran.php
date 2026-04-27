<?php

namespace App\Console\Commands;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Re-kalkulasi kuadran Eisenhower untuk semua tugas aktif.
 *
 * Kuadran bersifat time-sensitive: tugas yang awalnya di Q2 (Schedule)
 * harus otomatis pindah ke Q1 (Do Now) saat deadline tinggal <= 24 jam.
 *
 * Command ini dijadwalkan setiap jam di production (routes/console.php).
 * Bisa juga dijalankan manual: php artisan todos:recalculate-kuadran
 */
class RecalculateKuadran extends Command
{
    protected $signature = 'todos:recalculate-kuadran
                            {--user= : Hanya untuk user ID tertentu}
                            {--dry-run : Preview tanpa update}';

    protected $description = 'Re-kalkulasi kuadran Eisenhower berdasarkan waktu saat ini';

    public function handle(): int
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        }

        $totalUpdated = 0;
        $totalChecked = 0;

        $query->chunk(50, function ($users) use ($dryRun, &$totalUpdated, &$totalChecked) {
            foreach ($users as $user) {
                $todos = Todo::where('user_id', $user->id)
                    ->where('status', '!=', 'completed')
                    ->whereNotNull('due_date')
                    ->get(['id', 'title', 'priority', 'due_date', 'kuadran']);

                $totalChecked += $todos->count();

                foreach ($todos as $todo) {
                    $newKuadran = Todo::hitungKuadran($todo->priority, $todo->due_date);

                    if ($newKuadran !== $todo->kuadran) {
                        $oldLabel = Todo::KUADRAN_LABELS[$todo->kuadran] ?? "Q{$todo->kuadran}";
                        $newLabel = Todo::KUADRAN_LABELS[$newKuadran] ?? "Q{$newKuadran}";

                        if ($dryRun) {
                            $this->line("  [DRY RUN] {$todo->title}: Q{$todo->kuadran} ({$oldLabel}) → Q{$newKuadran} ({$newLabel})");
                        } else {
                            $todo->updateQuietly(['kuadran' => $newKuadran]);
                        }

                        $totalUpdated++;
                    }
                }
            }
        });

        $action = $dryRun ? 'akan diperbarui' : 'diperbarui';
        $this->info("✅ Selesai! {$totalUpdated} dari {$totalChecked} tugas {$action}.");

        return self::SUCCESS;
    }
}
