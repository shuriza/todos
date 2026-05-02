<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Laporan & Analitik</h2>
            <p class="text-sm text-gray-500">Pantau produktivitas dan progres tugas kamu</p>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6" x-data="reportApp()">

        {{-- Filter Periode + Export Buttons --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-gray-600">Periode:</span>
            <template x-for="p in periods" :key="p.value">
                <button @click="changePeriod(p.value)"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="period === p.value
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                        x-text="p.label">
                </button>
            </template>
            <div x-show="loading" class="ml-2">
                <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            </div>

            {{-- Export Buttons --}}
            <div class="flex items-center gap-2">
                <a :href="'/laporan/export/pdf?period=' + period"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </a>
                <a :href="'/laporan/export/excel?period=' + period"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
            </div>
        </div>

        {{-- Baris 1: Kartu Ringkasan (4 kartu) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Tugas --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Total Tugas</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="data.overview.total">0</p>
                    </div>
                </div>
            </div>

            {{-- Completion Rate --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Tingkat Selesai</p>
                        <p class="text-2xl font-bold text-gray-900"><span x-text="data.overview.completion_rate">0</span>%</p>
                    </div>
                </div>
            </div>

            {{-- Tepat Waktu --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Tepat Waktu</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="data.overview.on_time_rate !== null ? data.overview.on_time_rate + '%' : '-'">-</p>
                    </div>
                </div>
            </div>

            {{-- Streak --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Streak Saat Ini</p>
                        <p class="text-2xl font-bold text-gray-900"><span x-text="data.streak.current">0</span> <span class="text-sm font-normal text-gray-500">hari</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris 2: Tren Produktivitas + Distribusi Kuadran --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- Line Chart: Tren Produktivitas (2/3 width) --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Tren Produktivitas</h3>
                <div class="relative" style="height: 280px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            {{-- Doughnut Chart: Distribusi Kuadran (1/3 width) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Distribusi Kuadran</h3>
                <div class="relative" style="height: 280px;">
                    <canvas id="kuadranChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Baris 3: Distribusi Prioritas + Distribusi Kategori --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Bar Chart: Per Prioritas --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Distribusi Prioritas</h3>
                <div class="relative" style="height: 250px;">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>

            {{-- Horizontal Bar Chart: Per Kategori --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Distribusi Kategori</h3>
                <div class="relative" style="height: 250px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Baris 4: Calendar Heatmap (full width) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-gray-800">Aktivitas Penyelesaian Tugas</h3>
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span>Sedikit</span>
                    <span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-200"></span>
                    <span class="w-3 h-3 rounded-sm bg-green-200"></span>
                    <span class="w-3 h-3 rounded-sm bg-green-400"></span>
                    <span class="w-3 h-3 rounded-sm bg-green-600"></span>
                    <span>Banyak</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <div id="heatmapContainer" class="min-w-[720px]"></div>
            </div>
        </div>

        {{-- Baris 5: Sumber Tugas + Tabel Task Terlama --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Pie Chart: Manual vs Classroom --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Sumber Tugas</h3>
                <div class="relative" style="height: 250px;">
                    <canvas id="sourceChart"></canvas>
                </div>
            </div>

            {{-- Tabel: 10 Task Terlama Diselesaikan --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Tugas Terlama Diselesaikan</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" x-show="data.slowest && data.slowest.length > 0">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Judul</th>
                                <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Prioritas</th>
                                <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(task, idx) in data.slowest" :key="idx">
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2.5 px-3">
                                        <span class="font-medium text-gray-800 truncate block max-w-[200px]" x-text="task.title"></span>
                                        <span class="text-xs text-gray-400" x-text="task.created_at + ' - ' + task.completed_at"></span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium"
                                              :class="{
                                                  'bg-red-100 text-red-700': task.priority === 'high',
                                                  'bg-yellow-100 text-yellow-700': task.priority === 'medium',
                                                  'bg-green-100 text-green-700': task.priority === 'low'
                                              }"
                                              x-text="task.priority === 'high' ? 'Tinggi' : task.priority === 'medium' ? 'Sedang' : 'Rendah'">
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-600" x-text="translateCategory(task.category)"></td>
                                    <td class="py-2.5 px-3 text-right font-medium text-gray-800" x-text="formatHours(task.hours)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="!data.slowest || data.slowest.length === 0" class="text-center py-8">
                        <p class="text-sm text-gray-400">Belum ada tugas yang diselesaikan dalam periode ini</p>
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
