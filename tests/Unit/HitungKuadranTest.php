<?php

namespace Tests\Unit;

use App\Models\Todo;
use Tests\TestCase;

/**
 * White Box Testing - Basis Path Testing untuk Todo::hitungKuadran().
 *
 * Fungsi hitungKuadran() memiliki 2 variabel keputusan biner:
 *   - isImportant : priority === 'high'
 *   - isUrgent    : deadline <= urgency_days (default 1 hari)
 *
 * Kombinasi keduanya menghasilkan 4 kuadran (independent paths):
 *   Path 1: Urgent + Important       -> Kuadran 1 (DO NOW)
 *   Path 2: Tidak Urgent + Important -> Kuadran 2 (SCHEDULE)
 *   Path 3: Urgent + Tidak Important -> Kuadran 3 (DELEGATE)
 *   Path 4: Tidak keduanya           -> Kuadran 4 (ELIMINATE)
 *   Path 5: dueDate null             -> isUrgent=false (cabang if dilewati)
 *
 * Cyclomatic Complexity V(G) = jumlah predikat + 1.
 * Predikat: (dueDate), (isUrgent && isImportant), (!isUrgent && isImportant),
 * (isUrgent && !isImportant) = 4 predikat -> V(G) = 5 independent paths.
 */
class HitungKuadranTest extends TestCase
{
    /**
     * Path 1: Penting + Mendesak -> Kuadran 1 (DO NOW).
     * priority=high, deadline besok (<= 1 hari).
     */
    public function test_path1_penting_mendesak_menghasilkan_kuadran_1(): void
    {
        $dueDate = now()->addHours(12)->format('Y-m-d');
        $this->assertSame(1, Todo::hitungKuadran('high', $dueDate));
    }

    /**
     * Path 2: Penting + Tidak Mendesak -> Kuadran 2 (SCHEDULE).
     * priority=high, deadline 10 hari lagi.
     */
    public function test_path2_penting_tidak_mendesak_menghasilkan_kuadran_2(): void
    {
        $dueDate = now()->addDays(10)->format('Y-m-d');
        $this->assertSame(2, Todo::hitungKuadran('high', $dueDate));
    }

    /**
     * Path 3: Tidak Penting + Mendesak -> Kuadran 3 (DELEGATE).
     * priority=low, deadline besok.
     */
    public function test_path3_tidak_penting_mendesak_menghasilkan_kuadran_3(): void
    {
        $dueDate = now()->addHours(12)->format('Y-m-d');
        $this->assertSame(3, Todo::hitungKuadran('low', $dueDate));
    }

    /**
     * Path 4: Tidak Penting + Tidak Mendesak -> Kuadran 4 (ELIMINATE).
     * priority=low, deadline 10 hari lagi.
     */
    public function test_path4_tidak_penting_tidak_mendesak_menghasilkan_kuadran_4(): void
    {
        $dueDate = now()->addDays(10)->format('Y-m-d');
        $this->assertSame(4, Todo::hitungKuadran('low', $dueDate));
    }

    /**
     * Path 5: dueDate null -> isUrgent=false (cabang if dilewati).
     * priority=high tanpa deadline -> Kuadran 2 (Penting, Tidak Mendesak).
     */
    public function test_path5_tanpa_deadline_priority_high_menghasilkan_kuadran_2(): void
    {
        $this->assertSame(2, Todo::hitungKuadran('high', null));
    }

    /**
     * Path 5 (varian): dueDate null + priority low -> Kuadran 4.
     */
    public function test_path5_tanpa_deadline_priority_low_menghasilkan_kuadran_4(): void
    {
        $this->assertSame(4, Todo::hitungKuadran('low', null));
    }

    /**
     * Boundary value: deadline tepat 1 hari (batas urgency_days default).
     * <= 1 hari dianggap mendesak, priority high -> Kuadran 1.
     */
    public function test_boundary_deadline_tepat_satu_hari_dianggap_mendesak(): void
    {
        $dueDate = now()->addDay()->format('Y-m-d');
        $this->assertSame(1, Todo::hitungKuadran('high', $dueDate));
    }
}
