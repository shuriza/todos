<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Tugas - Portofolio Akademik</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.5; }

        .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #059669; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #059669; margin-bottom: 4px; }
        .header .subtitle { font-size: 11px; color: #6b7280; }

        .student-info { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; }
        .student-info table { width: 100%; }
        .student-info td { padding: 3px 0; font-size: 11px; }
        .student-info .label { font-weight: 600; color: #374151; width: 130px; }
        .student-info .value { color: #111827; }

        .summary { display: table; width: 100%; margin-bottom: 20px; }
        .summary-cell { display: table-cell; width: 25%; padding: 10px; text-align: center; border: 1px solid #e5e7eb; background-color: #f9fafb; }
        .summary-cell .num { font-size: 18px; font-weight: 700; color: #059669; display: block; }
        .summary-cell .lbl { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; display: block; }

        .course-block { margin-bottom: 18px; page-break-inside: avoid; }
        .course-title { background-color: #059669; color: white; padding: 7px 12px; font-size: 12px; font-weight: 700; border-radius: 4px 4px 0 0; }
        .course-title .count { float: right; font-weight: 400; font-size: 10px; }

        table.tasks { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.tasks thead th { background-color: #f0fdf4; color: #065f46; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #bbf7d0; }
        table.tasks tbody td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        table.tasks tbody tr:nth-child(even) { background-color: #fafafa; }

        .col-no { width: 32px; text-align: center; color: #6b7280; }
        .col-title { font-weight: 600; color: #111827; }
        .col-title .desc { font-weight: 400; font-size: 9px; color: #6b7280; margin-top: 2px; }
        .col-date { width: 85px; color: #374151; }
        .col-duration { width: 70px; color: #6b7280; }
        .col-priority { width: 60px; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8.5px; font-weight: 600; }
        .badge-high { background-color: #fee2e2; color: #991b1b; }
        .badge-medium { background-color: #fef3c7; color: #92400e; }
        .badge-low { background-color: #f3f4f6; color: #4b5563; }

        .empty { text-align: center; padding: 30px; color: #9ca3af; font-style: italic; }

        .signature { margin-top: 40px; padding-top: 18px; border-top: 1px solid #e5e7eb; }
        .signature table { width: 100%; }
        .signature td { width: 50%; text-align: center; padding: 0 20px; font-size: 10px; color: #374151; }
        .signature .sign-space { height: 55px; }
        .signature .name { font-weight: 700; }

        .footer { text-align: center; padding-top: 14px; border-top: 1px solid #e5e7eb; margin-top: 30px; font-size: 8.5px; color: #9ca3af; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>Arsip Tugas &mdash; Portofolio Akademik</h1>
        <p class="subtitle">Rekapitulasi Tugas yang Telah Diselesaikan</p>
    </div>

    {{-- Info Mahasiswa --}}
    <div class="student-info">
        <table>
            <tr>
                <td class="label">Nama Mahasiswa</td>
                <td class="value">: {{ $user->name }}</td>
            </tr>
            @if (!empty($user->nim))
                <tr>
                    <td class="label">NIM</td>
                    <td class="value">: {{ $user->nim }}</td>
                </tr>
            @endif
            @if (!empty($user->prodi))
                <tr>
                    <td class="label">Program Studi</td>
                    <td class="value">: {{ $user->prodi }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Periode</td>
                <td class="value">: {{ $periodLabel }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Cetak</td>
                <td class="value">: {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    {{-- Ringkasan --}}
    <div class="summary">
        <div class="summary-cell">
            <span class="num">{{ $summary['total'] }}</span>
            <span class="lbl">Total Selesai</span>
        </div>
        <div class="summary-cell">
            <span class="num">{{ $summary['from_classroom'] }}</span>
            <span class="lbl">Dari Classroom</span>
        </div>
        <div class="summary-cell">
            <span class="num">{{ $summary['from_manual'] }}</span>
            <span class="lbl">Tugas Pribadi</span>
        </div>
        <div class="summary-cell">
            <span class="num">{{ $summary['course_count'] }}</span>
            <span class="lbl">Mata Kuliah</span>
        </div>
    </div>

    {{-- List per Mata Kuliah --}}
    @if ($grouped->isEmpty())
        <div class="empty">Belum ada tugas yang diarsipkan pada periode ini.</div>
    @else
        @foreach ($grouped as $courseName => $tasks)
            <div class="course-block">
                <div class="course-title">
                    {{ $courseName }}
                    <span class="count">{{ count($tasks) }} tugas</span>
                </div>
                <table class="tasks">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th>Judul Tugas</th>
                            <th class="col-date">Diselesaikan</th>
                            <th class="col-duration">Durasi</th>
                            <th class="col-priority">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $i => $task)
                            @php
                                $minutes = $task->created_at && $task->completed_at
                                    ? abs($task->created_at->diffInMinutes($task->completed_at))
                                    : null;
                                if ($minutes !== null) {
                                    if ($minutes < 60) {
                                        $duration = $minutes . ' menit';
                                    } elseif ($minutes < 1440) {
                                        $duration = round($minutes / 60, 1) . ' jam';
                                    } else {
                                        $duration = round($minutes / 1440, 1) . ' hari';
                                    }
                                } else {
                                    $duration = '-';
                                }
                                $priorityBadge = [
                                    'high'   => ['badge-high', 'Tinggi'],
                                    'medium' => ['badge-medium', 'Sedang'],
                                    'low'    => ['badge-low', 'Rendah'],
                                ][$task->priority] ?? ['badge-low', $task->priority];
                            @endphp
                            <tr>
                                <td class="col-no">{{ $i + 1 }}</td>
                                <td class="col-title">
                                    {{ $task->title }}
                                    @if ($task->description)
                                        <div class="desc">{{ \Illuminate\Support\Str::limit($task->description, 120) }}</div>
                                    @endif
                                </td>
                                <td class="col-date">{{ $task->completed_at?->translatedFormat('d M Y') }}</td>
                                <td class="col-duration">{{ $duration }}</td>
                                <td class="col-priority">
                                    <span class="badge {{ $priorityBadge[0] }}">{{ $priorityBadge[1] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    {{-- Tanda Tangan --}}
    <div class="signature">
        <table>
            <tr>
                <td></td>
                <td>
                    Malang, {{ now()->translatedFormat('d F Y') }}<br>
                    Mahasiswa,
                    <div class="sign-space"></div>
                    <div class="name">{{ $user->name }}</div>
                    @if (!empty($user->nim))
                        <div>NIM. {{ $user->nim }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Dokumen ini digenerate otomatis oleh Aplikasi Asisten Pribadi Cerdas &mdash; {{ $generatedAt }}
    </div>
</body>
</html>
