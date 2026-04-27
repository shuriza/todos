<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ReportController
 * 
 * Menangani semua request terkait halaman Laporan & Analitik.
 * 
 * Alur:
 *   Route -> ReportController -> ReportService -> Database
 *                              -> View (Blade) / PDF (DomPDF) / Excel (Maatwebsite)
 * 
 * Endpoints:
 *   GET  /laporan            -> index()      -> Halaman utama laporan
 *   GET  /laporan/chart-data -> chartData()  -> JSON data untuk AJAX (filter periode)
 *   GET  /laporan/export/pdf -> exportPdf()  -> Download file PDF
 *   GET  /laporan/export/excel -> exportExcel() -> Download file Excel
 */
class ReportController extends Controller
{
    private const VALID_PERIODS = ['7d', '30d', '90d', '180d', '365d'];

    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Halaman utama Laporan & Analitik.
     * Menampilkan semua chart, statistik, heatmap, dan tabel.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $period = $this->validatePeriod($request->get('period', '30d'));

        // Kumpulkan semua data dari ReportService
        $overview  = $this->reportService->getOverviewStats($userId, $period);
        $trend     = $this->reportService->getCompletionTrend($userId, $period);
        $kuadran   = $this->reportService->getKuadranDistribution($userId, $period);
        $priority  = $this->reportService->getPriorityDistribution($userId, $period);
        $category  = $this->reportService->getCategoryDistribution($userId, $period);
        $source    = $this->reportService->getSourceDistribution($userId, $period);
        $heatmap   = $this->reportService->getHeatmapData($userId);
        $streak    = $this->reportService->getStreakInfo($userId);
        $slowest   = $this->reportService->getSlowestTasks($userId, $period);

        // Data chart dikemas dalam satu object JSON untuk Alpine.js
        $chartData = compact(
            'overview', 'trend', 'kuadran', 'priority',
            'category', 'source', 'heatmap', 'streak', 'slowest'
        );

        return view('reports.index', [
            'period'    => $period,
            'chartData' => $chartData,
            'overview'  => $overview,
            'streak'    => $streak,
        ]);
    }

    /**
     * JSON endpoint untuk AJAX.
     * Dipanggil saat user mengganti filter periode tanpa reload halaman.
     */
    public function chartData(Request $request)
    {
        $userId = Auth::id();
        $period = $this->validatePeriod($request->get('period', '30d'));

        $overview  = $this->reportService->getOverviewStats($userId, $period);
        $trend     = $this->reportService->getCompletionTrend($userId, $period);
        $kuadran   = $this->reportService->getKuadranDistribution($userId, $period);
        $priority  = $this->reportService->getPriorityDistribution($userId, $period);
        $category  = $this->reportService->getCategoryDistribution($userId, $period);
        $source    = $this->reportService->getSourceDistribution($userId, $period);
        $heatmap   = $this->reportService->getHeatmapData($userId);
        $streak    = $this->reportService->getStreakInfo($userId);
        $slowest   = $this->reportService->getSlowestTasks($userId, $period);

        return response()->json(compact(
            'overview', 'trend', 'kuadran', 'priority',
            'category', 'source', 'heatmap', 'streak', 'slowest'
        ));
    }

    /**
     * Export laporan ke PDF.
     * Menggunakan DomPDF untuk render Blade view ke file PDF.
     */
    public function exportPdf(Request $request)
    {
        $userId = Auth::id();
        $user   = Auth::user();
        $period = $this->validatePeriod($request->get('period', '30d'));

        $data = $this->reportService->getExportData($userId, $period);
        $data['user']   = $user;
        $data['period'] = $period;
        $data['periodLabel'] = $this->getPeriodLabel($period);
        $data['generatedAt'] = now()->format('d M Y H:i');

        $pdf = Pdf::loadView('reports.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'laporan-produktivitas-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export laporan ke Excel.
     * Menggunakan Maatwebsite/Excel dengan class ReportExport.
     */
    public function exportExcel(Request $request)
    {
        $userId = Auth::id();
        $period = $this->validatePeriod($request->get('period', '30d'));

        $filename = 'laporan-produktivitas-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ReportExport($this->reportService, $userId, $period),
            $filename
        );
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function validatePeriod(string $period): string
    {
        return in_array($period, self::VALID_PERIODS) ? $period : '30d';
    }

    private function getPeriodLabel(string $period): string
    {
        return match ($period) {
            '7d'   => '7 Hari Terakhir',
            '30d'  => '30 Hari Terakhir',
            '90d'  => '3 Bulan Terakhir',
            '180d' => '6 Bulan Terakhir',
            '365d' => '1 Tahun Terakhir',
            default => '30 Hari Terakhir',
        };
    }
}
