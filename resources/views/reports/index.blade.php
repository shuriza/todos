{{-- 
    Fitur: Laporan & Analitik
    Halaman: Dashboard statistik ringkas dengan tren utama dan ringkasan distribusi
    Controller: ReportController
    JS: resources/js/pages/report.js
--}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Laporan & Analitik</h2>
            <p class="text-sm text-gray-500">Ringkasan progres dan pola penyelesaian tugas kamu</p>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6" x-data="reportApp()">

        {{-- Filter Periode + Export --}}
        <div class="mb-5 bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Periode laporan</p>
                    <p class="text-xs text-gray-500 mt-0.5">Pilih rentang waktu untuk melihat progres tugas.</p>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <template x-for="p in periods" :key="p.value">
                            <button @click="changePeriod(p.value)"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                    :class="period === p.value
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-gray-50 text-gray-600 border border-gray-200 hover:bg-gray-100'"
                                    x-text="p.label">
                            </button>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <div x-show="loading" class="text-indigo-600">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <a :href="'/laporan/export/pdf?period=' + period"
                           class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 font-medium">Total Tugas</p>
                <p class="mt-2 text-2xl font-bold text-gray-900" x-text="data.overview.total">0</p>
                <p class="mt-1 text-xs text-gray-400">Semua tugas dalam periode</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 font-medium">Tugas Selesai</p>
                <p class="mt-2 text-2xl font-bold text-green-600" x-text="data.overview.completed">0</p>
                <p class="mt-1 text-xs text-gray-400"><span x-text="data.overview.completion_rate">0</span>% dari total tugas</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 font-medium">Belum Selesai</p>
                <p class="mt-2 text-2xl font-bold text-yellow-600" x-text="data.overview.pending">0</p>
                <p class="mt-1 text-xs text-gray-400">Masih perlu ditindaklanjuti</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 font-medium">Terlambat</p>
                <p class="mt-2 text-2xl font-bold text-red-600" x-text="data.overview.overdue">0</p>
                <p class="mt-1 text-xs text-gray-400">Lewat dari deadline</p>
            </div>
        </div>

        {{-- Fokus Utama --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
            <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">Tren Produktivitas</h3>
                        <p class="text-xs text-gray-500 mt-1">Perbandingan tugas dibuat dan diselesaikan.</p>
                    </div>
                    <span class="text-xs text-gray-400" x-text="currentPeriodLabel()"></span>
                </div>
                <div class="relative" style="height: 300px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800">Sorotan Periode</h3>
                <p class="text-xs text-gray-500 mt-1 mb-4">Angka penting yang paling cepat dibaca.</p>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Tingkat selesai</span>
                        <span class="text-sm font-bold text-gray-900"><span x-text="data.overview.completion_rate">0</span>%</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Tepat waktu</span>
                        <span class="text-sm font-bold text-gray-900" x-text="data.overview.on_time_rate !== null ? data.overview.on_time_rate + '%' : '-'">-</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Tugas selesai</span>
                        <span class="text-sm font-bold text-green-600" x-text="data.overview.completed">0</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Tugas terlambat</span>
                        <span class="text-sm font-bold text-red-600" x-text="data.overview.overdue">0</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Tidak terselesaikan</span>
                        <span class="text-sm font-bold text-gray-700" x-text="data.overview.unfinished ?? 0">0</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rincian Distribusi --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Kuadran Eisenhower</h3>
                <div class="space-y-3">
                    <template x-for="item in kuadranSummary()" :key="item.label">
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600" x-text="item.label"></span>
                                <span class="font-semibold text-gray-900" x-text="item.value"></span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" :class="item.color" :style="`width: ${item.percent}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Prioritas</h3>
                <div class="space-y-3">
                    <template x-for="item in prioritySummary()" :key="item.label">
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5">
                            <span class="text-sm text-gray-600" x-text="item.label"></span>
                            <span class="text-sm font-bold" :class="item.textColor" x-text="item.value"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Sumber & Kategori</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Manual</span>
                        <span class="text-sm font-bold text-indigo-600" x-text="data.source.manual || 0"></span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5">
                        <span class="text-sm text-gray-600">Google Classroom</span>
                        <span class="text-sm font-bold text-amber-600" x-text="data.source.google_classroom || 0"></span>
                    </div>
                    <div class="pt-2 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 mb-2">Kategori terbanyak</p>
                        <template x-for="item in topCategories()" :key="item.label">
                            <div class="flex items-center justify-between text-sm py-1.5">
                                <span class="text-gray-600" x-text="item.label"></span>
                                <span class="font-semibold text-gray-900" x-text="item.value"></span>
                            </div>
                        </template>
                        <p x-show="topCategories().length === 0" class="text-sm text-gray-400">Belum ada data kategori.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script id="report-data" type="application/json">
        @json($chartData)
    </script>
    @endpush
</x-app-layout>
