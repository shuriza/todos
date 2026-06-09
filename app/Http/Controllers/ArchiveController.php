<?php

namespace App\Http\Controllers;

use App\Services\ArchiveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ArchiveController
 *
 * Menangani halaman Arsip Tugas — daftar tugas yang sudah diselesaikan
 * mahasiswa sebagai bukti portofolio akademik.
 *
 * Alur:
 *   Route -> ArchiveController -> ArchiveService -> Database
 *                              -> View (Blade) atau PDF (DomPDF)
 *
 * Endpoints:
 *   GET /arsip             -> index()      -> halaman utama (filter + list + pagination)
 *   GET /arsip/export/pdf  -> exportPdf()  -> download PDF portofolio
 */
class ArchiveController extends Controller
{
    public function __construct(
        private ArchiveService $archiveService
    ) {}

    /**
     * Halaman utama Arsip Tugas.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $period   = $this->archiveService->validatePeriod($request->get('period'));
        $search   = $request->get('search');
        $courseId = $request->filled('course_id') ? (int) $request->course_id : null;
        $sort     = in_array($request->get('sort'), ['latest', 'oldest'], true)
            ? $request->get('sort')
            : 'latest';
        $status   = in_array($request->get('status'), ['completed', 'unfinished'], true)
            ? $request->get('status')
            : null;

        $perPage = (int) config('todos.per_page', 15);

        $tasks    = $this->archiveService->getArchivedTasks(
            $userId, $period, $search, $courseId, $sort, $perPage, $status
        );
        $summary  = $this->archiveService->getSummary($userId, $period);
        $courses  = $this->archiveService->getCoursesWithArchived($userId);

        $filters = [
            'period'    => $period,
            'search'    => $search,
            'course_id' => $courseId,
            'sort'      => $sort,
            'status'    => $status,
        ];

        return view('archive.index', compact('tasks', 'summary', 'courses', 'filters'));
    }

    /**
     * Export arsip tugas sebagai PDF portofolio.
     * Dikelompokkan per mata kuliah.
     */
    public function exportPdf(Request $request)
    {
        $userId = Auth::id();
        $user   = Auth::user();
        $period = $this->archiveService->validatePeriod($request->get('period'));

        $grouped = $this->archiveService->getArchivedGroupedByCourse($userId, $period);

        // Hentikan ekspor bila arsip kosong agar tidak menghasilkan PDF tanpa data.
        if ($grouped->isEmpty()) {
            return redirect()->route('archive.index')
                ->with('error', 'Arsip masih kosong. Belum ada tugas selesai yang dapat dicetak.');
        }

        $summary = $this->archiveService->getSummary($userId, $period);

        $pdf = Pdf::loadView('archive.pdf', [
            'user'        => $user,
            'period'      => $period,
            'periodLabel' => $this->getPeriodLabel($period),
            'grouped'     => $grouped,
            'summary'     => $summary,
            'generatedAt' => now()->format('d M Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'arsip-tugas-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function getPeriodLabel(string $period): string
    {
        return match ($period) {
            '7d'   => '7 Hari Terakhir',
            '30d'  => '30 Hari Terakhir',
            '90d'  => '3 Bulan Terakhir',
            '180d' => '6 Bulan Terakhir',
            '365d' => '1 Tahun Terakhir',
            'all'  => 'Seluruh Periode',
            default => 'Seluruh Periode',
        };
    }
}
