{{-- 
    Fitur: Arsip Tugas
    Halaman: Daftar tugas selesai dengan filter periode dan export PDF
    Controller: ArchiveController
    JS: -
--}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Arsip Tugas</h2>
            <p class="text-sm text-gray-500">Riwayat tugas yang sudah kamu selesaikan sebagai bukti portofolio akademik</p>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6">

        {{-- Flash Messages --}}
        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3" x-data="{ show: true }" x-show="show">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        {{-- Filter Bar --}}
        <div class="mb-6 bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-4">
            {{-- Periode (link-based, bawa semua filter saat ini) --}}
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-600">Periode:</span>
                @foreach ([
                    'all'  => 'Semua',
                    '7d'   => '7 Hari',
                    '30d'  => '30 Hari',
                    '90d'  => '3 Bulan',
                    '180d' => '6 Bulan',
                    '365d' => '1 Tahun',
                ] as $value => $label)
                    <a href="{{ route('archive.index', array_merge(
                            array_filter($filters, fn($v) => $v !== null && $v !== ''),
                            ['period' => $value]
                        )) }}"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
                              {{ $filters['period'] === $value
                                   ? 'bg-indigo-600 text-white shadow-sm'
                                   : 'bg-gray-50 text-gray-600 border border-gray-200 hover:bg-gray-100' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Search + Course Filter + Sort --}}
            <form method="GET" action="{{ route('archive.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <input type="hidden" name="period" value="{{ $filters['period'] }}">

                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           placeholder="Cari judul atau deskripsi..."
                           class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mata Kuliah</label>
                    <select name="course_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Mata Kuliah</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected($filters['course_id'] == $course->id)>
                                {{ $course->nama_course }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua</option>
                        <option value="completed" @selected(($filters['status'] ?? null) === 'completed')>Selesai</option>
                        <option value="unfinished" @selected(($filters['status'] ?? null) === 'unfinished')>Tidak Terselesaikan</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Urutkan</label>
                    <select name="sort" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="latest" @selected($filters['sort'] === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Terlama</option>
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end">
                    <button type="submit" class="w-full px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Filter
                    </button>
                </div>
            </div>
            </form>
        </div>

        {{-- Export Bar --}}
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-600">
                Menampilkan <span class="font-semibold text-gray-900">{{ $tasks->firstItem() ?? 0 }}</span>&ndash;<span class="font-semibold text-gray-900">{{ $tasks->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-gray-900">{{ $tasks->total() }}</span> tugas terarsip
            </p>
            <a href="{{ route('archive.export.pdf', ['period' => $filters['period']]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export PDF Portofolio
            </a>
        </div>

        {{-- Kartu Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Tugas Selesai --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Total Selesai</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Dari Classroom --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Dari Classroom</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $summary['from_classroom'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Tugas Pribadi --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Tugas Pribadi</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $summary['from_manual'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Tepat Waktu --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Tepat Waktu</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {!! $summary['on_time_rate'] !== null ? $summary['on_time_rate'] . '%' : '&mdash;' !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- List Tugas --}}
        @if ($tasks->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-gray-500 font-medium">Belum ada tugas yang diarsipkan</p>
                <p class="text-sm text-gray-400 mt-1">
                    @if ($filters['search'] || $filters['course_id'] || $filters['period'] !== 'all')
                        Coba ubah atau reset filter di atas
                    @else
                        Tandai tugas sebagai "Selesai" di halaman Semua Tugas untuk mulai mengisi arsip
                    @endif
                </p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" x-data="archivePageApp()">
                <ul class="divide-y divide-gray-100">
                    @foreach ($tasks as $task)
                        @php $isUnfinished = $task->status === 'unfinished'; @endphp
                        <li id="archive-row-{{ $task->id }}" class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-3">
                                {{-- Status Icon: hijau centang (selesai) / merah silang (tidak terselesaikan) --}}
                                @if ($isUnfinished)
                                    <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mt-0.5">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mt-0.5">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                @endif

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <h4 class="text-sm font-semibold {{ $isUnfinished ? 'text-gray-700' : 'text-gray-900' }} line-clamp-1">{{ $task->title }}</h4>
                                            @if ($isUnfinished)
                                                <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">Tidak Terselesaikan</span>
                                            @else
                                                <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Selesai</span>
                                            @endif
                                            @if ($task->is_late)
                                                <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full" title="Diserahkan setelah tenggat">Terlambat</span>
                                            @endif
                                        </div>
                                        <span class="flex-shrink-0 text-xs text-gray-500">
                                            {{ $task->completed_at?->translatedFormat('d M Y') }}
                                        </span>
                                    </div>

                                    @if ($task->description)
                                        <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $task->description }}</p>
                                    @endif

                                    {{-- Meta Row --}}
                                    <div class="flex items-center flex-wrap gap-2 mt-2">
                                        @if ($task->course)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded-full">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                                {{ $task->course->nama_course }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-700 text-xs rounded-full">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                Tugas Pribadi
                                            </span>
                                        @endif

                                        @php
                                            $priorityColor = [
                                                'high'   => 'bg-red-50 text-red-700',
                                                'low'    => 'bg-gray-50 text-gray-700',
                                            ][$task->priority] ?? 'bg-gray-50 text-gray-700';
                                            $priorityLabel = [
                                                'high'   => 'Tinggi',
                                                'low'    => 'Rendah',
                                            ][$task->priority] ?? $task->priority;
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 {{ $priorityColor }} text-xs rounded-full">
                                            {{ $priorityLabel }}
                                        </span>

                                        {{-- Buka kembali ke daftar aktif: hanya untuk tugas Tidak Terselesaikan
                                             (membatalkan salah tandai). Tugas Selesai tetap final agar
                                             tanggal selesai di portofolio tidak hilang. --}}
                                        @if ($isUnfinished)
                                            <button type="button" @click="reopenTask({{ $task->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs rounded-full hover:bg-indigo-100 transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Buka Kembali
                                            </button>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $tasks->links() }}
            </div>
        @endif

    </div>

</x-app-layout>
