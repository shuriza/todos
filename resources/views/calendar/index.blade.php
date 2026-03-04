<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Kalender</h2>
                <p class="text-sm text-gray-500">Lihat jadwal tugas berdasarkan deadline</p>
            </div>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6" x-data="calendarApp()">
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

            {{-- Calendar Grid --}}
            <div class="xl:col-span-3">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Month Navigation --}}
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <button @click="prevMonth()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <h3 class="text-lg font-bold text-gray-900" x-text="monthNames[month - 1] + ' ' + year"></h3>
                        <button @click="nextMonth()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    {{-- Day Headers --}}
                    <div class="grid grid-cols-7 border-b border-gray-200">
                        <template x-for="day in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="day">
                            <div class="px-2 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider" x-text="day"></div>
                        </template>
                    </div>

                    {{-- Calendar Days --}}
                    <div class="grid grid-cols-7">
                        <template x-for="(cell, idx) in calendarCells" :key="idx">
                            <div class="min-h-[60px] sm:min-h-[90px] border-b border-r border-gray-100 p-1.5 sm:p-2 relative cursor-pointer transition-colors"
                                 :class="{
                                     'bg-gray-50/50': !cell.isCurrentMonth,
                                     'bg-indigo-50/40': cell.isToday,
                                     'hover:bg-gray-50': cell.isCurrentMonth && !cell.isToday,
                                     'ring-2 ring-indigo-400 ring-inset': selectedDate === cell.date
                                 }"
                                 @click="cell.date && selectDate(cell.date)">
                                <span class="text-xs sm:text-sm font-medium block text-center"
                                      :class="{
                                          'text-gray-300': !cell.isCurrentMonth,
                                          'bg-indigo-600 text-white w-6 h-6 sm:w-7 sm:h-7 text-xs sm:text-sm inline-flex items-center justify-center rounded-full': cell.isToday,
                                          'text-gray-700': cell.isCurrentMonth && !cell.isToday
                                      }"
                                      x-text="cell.day"></span>
                                <div x-show="cell.tasks && cell.tasks.length > 0" class="absolute bottom-1 left-1/2 -translate-x-1/2">
                                    <span class="block w-1.5 h-1.5 rounded-full"
                                          :class="{
                                              'bg-red-500': cell.tasks && cell.tasks.some(t => t.kuadran == 1),
                                              'bg-blue-500': cell.tasks && !cell.tasks.some(t => t.kuadran == 1) && cell.tasks.some(t => t.kuadran == 2),
                                              'bg-yellow-500': cell.tasks && !cell.tasks.some(t => t.kuadran <= 2) && cell.tasks.some(t => t.kuadran == 3),
                                              'bg-gray-400': cell.tasks && !cell.tasks.some(t => t.kuadran <= 3)
                                          }"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="xl:col-span-1 space-y-5">
                {{-- Overdue --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" x-show="overdue.length > 0">
                    <div class="px-4 py-3 bg-red-50 border-b border-red-200 flex items-center gap-2">
                        <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                        </div>
                        <span class="text-sm font-bold text-red-800">Terlambat</span>
                        <span class="ml-auto text-xs font-bold bg-red-200 text-red-800 px-2 py-0.5 rounded-full" x-text="overdue.length"></span>
                    </div>
                    <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                        <template x-for="task in overdue" :key="task.id">
                            <div class="p-2 bg-red-50 rounded-lg border border-red-100 cursor-pointer hover:bg-red-100 transition-colors" @click="openDetail(task)">
                                <p class="text-xs font-medium text-gray-800 truncate" x-text="task.title"></p>
                                <p class="text-xs text-red-600 mt-0.5" x-text="formatDate(task.due_date)"></p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Upcoming 7 days --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-200 flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-500 rounded flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-sm font-bold text-indigo-800">7 Hari Ke Depan</span>
                        <span class="ml-auto text-xs font-bold bg-indigo-200 text-indigo-800 px-2 py-0.5 rounded-full" x-text="upcoming.length"></span>
                    </div>
                    <div class="p-3 space-y-2 max-h-80 overflow-y-auto">
                        <template x-for="task in upcoming" :key="task.id">
                            <div class="p-2 rounded-lg border border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors flex items-start gap-2" @click="openDetail(task)">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
                                      :class="{
                                          'bg-red-500': task.kuadran == 1,
                                          'bg-blue-500': task.kuadran == 2,
                                          'bg-yellow-500': task.kuadran == 3,
                                          'bg-gray-400': !task.kuadran || task.kuadran == 4
                                      }"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-800 truncate" x-text="task.title"></p>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="formatDate(task.due_date)"></p>
                                </div>
                            </div>
                        </template>
                        <p x-show="upcoming.length === 0" class="text-sm text-gray-400 text-center py-4">Tidak ada tugas mendatang</p>
                    </div>
                </div>

                {{-- Selected Date Detail --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" x-show="selectedDate">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <span class="text-sm font-bold text-gray-800" x-text="selectedDate ? formatDateFull(selectedDate) : ''"></span>
                    </div>
                    <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                        <template x-for="task in selectedDateTasks" :key="task.id">
                            <div class="p-2 rounded-lg border border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors" @click="openDetail(task)">
                                <p class="text-xs font-medium text-gray-800 truncate" x-text="task.title"></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs px-1.5 py-0.5 rounded-md"
                                          :class="getKuadranClass(task.kuadran)"
                                          x-text="getKuadranShort(task.kuadran)"></span>
                                    <span x-show="task.due_time" class="text-xs text-gray-500" x-text="task.due_time"></span>
                                </div>
                            </div>
                        </template>
                        <p x-show="selectedDateTasks.length === 0" class="text-sm text-gray-400 text-center py-4">Tidak ada tugas di tanggal ini</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Task Detail Modal --}}
        <div x-show="showDetailModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900" x-text="detailTask?.title"></h3>
                        <button @click="showDetailModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div x-show="detailTask?.description" class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700" x-text="detailTask?.description"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="block text-xs text-gray-500 mb-1">Kuadran</span><span class="font-medium" x-text="getKuadranLabel(detailTask?.kuadran)"></span></div>
                        <div><span class="block text-xs text-gray-500 mb-1">Status</span><span class="font-medium capitalize" x-text="detailTask?.status?.replace('_',' ')"></span></div>
                        <div><span class="block text-xs text-gray-500 mb-1">Prioritas</span><span class="font-medium capitalize" x-text="detailTask?.priority"></span></div>
                        <div><span class="block text-xs text-gray-500 mb-1">Deadline</span><span class="font-medium" x-text="detailTask?.due_date ? formatDate(detailTask.due_date) + (detailTask.due_time ? ' ' + detailTask.due_time : '') : '-'"></span></div>
                        <div x-show="detailTask?.course"><span class="block text-xs text-gray-500 mb-1">Mata Kuliah</span><span class="font-medium" x-text="detailTask?.course?.nama_course || detailTask?.course"></span></div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <a :href="'/todos'" class="flex-1 text-center px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Lihat di Todos</a>
                        <button @click="showDetailModal = false" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @php
        $calendarData = ['month' => $month, 'year' => $year, 'overdue' => $overdue, 'upcoming' => $upcoming];
    @endphp
    <script id="calendar-data" type="application/json">
        {!! json_encode($calendarData) !!}
    </script>
    {{-- JS loaded from resources/js/pages/calendar.js --}}
    @endpush
</x-app-layout>
