{{-- 
    Fitur: Export PDF Laporan
    Halaman: Template PDF untuk laporan produktivitas
    Controller: ReportController@exportPdf
    JS: -
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Produktivitas</title>
    <style>
        /* DomPDF membutuhkan inline/embedded CSS */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        
        .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #4f46e5; margin-bottom: 25px; }
        .header h1 { font-size: 20px; color: #4f46e5; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #6b7280; }
        
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #4f46e5; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 12px; }
        
        .stats-grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .stats-grid td { padding: 8px 12px; border: 1px solid #e5e7eb; }
        .stats-grid .label { background-color: #f9fafb; font-weight: 600; width: 45%; color: #374151; }
        .stats-grid .value { text-align: right; font-weight: 700; color: #111827; }
        
        .two-col { width: 100%; border-collapse: collapse; }
        .two-col td { width: 50%; vertical-align: top; padding: 0 8px; }
        
        table.data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.data-table thead th { background-color: #4f46e5; color: white; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.data-table tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        table.data-table tbody tr:nth-child(even) { background-color: #f9fafb; }
        
        .badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; }
        .badge-high { background-color: #fee2e2; color: #991b1b; }
        .badge-low { background-color: #d1fae5; color: #065f46; }
        
        .footer { text-align: center; padding-top: 15px; border-top: 1px solid #e5e7eb; margin-top: 25px; font-size: 9px; color: #9ca3af; }
        
        .highlight { font-size: 22px; font-weight: 800; color: #4f46e5; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>Laporan Produktivitas</h1>
        <p><strong>{{ $user->name }}</strong> &mdash; {{ $periodLabel }} &mdash; Digenerate: {{ $generatedAt }}</p>
    </div>

    {{-- Statistik Umum --}}
    <div class="section">
        <div class="section-title">Statistik Umum</div>
        <table class="stats-grid">
            <tr>
                <td class="label">Total Tugas</td>
                <td class="value">{{ $overview['total'] }}</td>
            </tr>
            <tr>
                <td class="label">Tugas Selesai</td>
                <td class="value">{{ $overview['completed'] }}</td>
            </tr>
            <tr>
                <td class="label">Tugas Pending</td>
                <td class="value">{{ $overview['pending'] }}</td>
            </tr>
            <tr>
                <td class="label">Tugas Terlambat</td>
                <td class="value">{{ $overview['overdue'] }}</td>
            </tr>
            <tr>
                <td class="label">Tingkat Penyelesaian</td>
                <td class="value">{{ $overview['completion_rate'] }}%</td>
            </tr>
            <tr>
                <td class="label">Tingkat Ketepatan Waktu</td>
                <td class="value">{{ $overview['on_time_rate'] !== null ? $overview['on_time_rate'] . '%' : '-' }}</td>
            </tr>

        </table>
    </div>

    {{-- Distribusi Kuadran & Prioritas (2 kolom) --}}
    <table class="two-col">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Distribusi Kuadran Eisenhower</div>
                    <table class="stats-grid">
                        <tr>
                            <td class="label">Q1 Lakukan Sekarang</td>
                            <td class="value">{{ $kuadran['q1'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Q2 Jadwalkan</td>
                            <td class="value">{{ $kuadran['q2'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Q3 Delegasikan</td>
                            <td class="value">{{ $kuadran['q3'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Q4 Eliminasi</td>
                            <td class="value">{{ $kuadran['q4'] }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Distribusi Prioritas</div>
                    <table class="stats-grid">
                        <tr>
                            <td class="label">Tinggi</td>
                            <td class="value">{{ $priority['high'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Rendah</td>
                            <td class="value">{{ $priority['low'] }}</td>
                        </tr>
                    </table>

                    <div class="section-title" style="margin-top: 15px;">Sumber Tugas</div>
                    <table class="stats-grid">
                        <tr>
                            <td class="label">Manual</td>
                            <td class="value">{{ $source['manual'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Google Classroom</td>
                            <td class="value">{{ $source['google_classroom'] }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        TaskManager - Polinema Smart Assistant | Laporan digenerate pada {{ $generatedAt }}
    </div>
</body>
</html>
