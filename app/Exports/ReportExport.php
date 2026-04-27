<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * ReportExport
 * 
 * Class utama untuk export Excel laporan.
 * Menggunakan WithMultipleSheets untuk membuat 2 sheet:
 *   Sheet 1: Ringkasan statistik
 *   Sheet 2: Daftar semua tugas
 * 
 * Alur:
 *   ReportController@exportExcel -> new ReportExport(...) -> Excel::download()
 *     -> Sheet 1: ReportSummarySheet (data dari ReportService::getExportData)
 *     -> Sheet 2: ReportTasksSheet   (data dari ReportService::getAllTasksForExport)
 */
class ReportExport implements WithMultipleSheets
{
    public function __construct(
        private ReportService $reportService,
        private int $userId,
        private string $period,
    ) {}

    public function sheets(): array
    {
        return [
            new ReportSummarySheet($this->reportService, $this->userId, $this->period),
            new ReportTasksSheet($this->reportService, $this->userId, $this->period),
        ];
    }
}
