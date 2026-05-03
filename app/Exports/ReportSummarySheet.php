<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet 1: Ringkasan Statistik
 * 
 * Berisi overview stats, distribusi kuadran, prioritas, dan streak info
 * dalam format tabel key-value yang mudah dibaca.
 */
class ReportSummarySheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function __construct(
        private ReportService $reportService,
        private int $userId,
        private string $period,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return ['Metrik', 'Nilai'];
    }

    public function array(): array
    {
        $data = $this->reportService->getExportData($this->userId, $this->period);
        $overview = $data['overview'];
        $kuadran  = $data['kuadran'];
        $priority = $data['priority'];
        $source   = $data['source'];
        $streak   = $data['streak'];

        $onTimeRate = $overview['on_time_rate'] !== null
            ? $overview['on_time_rate'] . '%'
            : '-';

        return [
            // Overview
            ['--- STATISTIK UMUM ---', ''],
            ['Total Tugas', $overview['total']],
            ['Tugas Selesai', $overview['completed']],
            ['Tugas Pending', $overview['pending']],
            ['Tugas Terlambat', $overview['overdue']],
            ['Tingkat Penyelesaian', $overview['completion_rate'] . '%'],
            ['Tingkat Ketepatan Waktu', $onTimeRate],
            ['', ''],

            // Streak
            ['--- STREAK ---', ''],
            ['Streak Saat Ini', $streak['current'] . ' hari'],
            ['Streak Terpanjang', $streak['longest'] . ' hari'],
            ['', ''],

            // Kuadran
            ['--- DISTRIBUSI KUADRAN ---', ''],
            ['Q1 Lakukan Sekarang (Mendesak & Penting)', $kuadran['q1']],
            ['Q2 Jadwalkan (Tidak Mendesak & Penting)', $kuadran['q2']],
            ['Q3 Delegasikan (Mendesak & Tidak Penting)', $kuadran['q3']],
            ['Q4 Eliminasi (Tidak Mendesak & Tidak Penting)', $kuadran['q4']],
            ['', ''],

            // Prioritas
            ['--- DISTRIBUSI PRIORITAS ---', ''],
            ['Tinggi', $priority['high']],
            ['Sedang', $priority['medium']],
            ['Rendah', $priority['low']],
            ['', ''],

            // Sumber
            ['--- SUMBER TUGAS ---', ''],
            ['Manual', $source['manual']],
            ['Google Classroom', $source['google_classroom']],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
