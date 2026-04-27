<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet 2: Daftar Tugas
 * 
 * Berisi semua task user dalam periode yang dipilih
 * dengan kolom: Judul, Deskripsi, Status, Prioritas, Kuadran,
 * Kategori, Sumber, Deadline, Dibuat, Diselesaikan.
 */
class ReportTasksSheet implements FromCollection, WithTitle, WithHeadings, WithStyles
{
    public function __construct(
        private ReportService $reportService,
        private int $userId,
        private string $period,
    ) {}

    public function title(): string
    {
        return 'Daftar Tugas';
    }

    public function headings(): array
    {
        return [
            'Judul',
            'Deskripsi',
            'Status',
            'Prioritas',
            'Kuadran',
            'Kategori',
            'Sumber',
            'Deadline',
            'Dibuat',
            'Diselesaikan',
        ];
    }

    public function collection()
    {
        return $this->reportService->getAllTasksForExport($this->userId, $this->period);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
