<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-sm text-gray-500">Kelola tugas kuliahmu dengan Matriks Eisenhower</p>
            </div>
            <button @click="$dispatch('open-add-task')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tugas
            </button>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6 space-y-6" x-data="dashboardApp()">

        {{-- Statistics Row --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                        <div class="text-xs text-gray-500">Total Tugas</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['completed'] }}</div>
                        <div class="text-xs text-gray-500">Selesai</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</div>
                        <div class="text-xs text-gray-500">Pending</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['overdue'] }}</div>
                        <div class="text-xs text-gray-500">Terlambat</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['courses'] }}</div>
                        <div class="text-xs text-gray-500">Mata Kuliah</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['classroom'] }}</div>
                        <div class="text-xs text-gray-500">Dari Classroom</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Eisenhower Matrix Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Matriks Eisenhower</h2>
                <p class="text-sm text-gray-500">Klik tugas untuk detail, gunakan menu ⋮ untuk pindah kuadran</p>
            </div>
            <a href="{{ route('todos.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">Lihat Semua &rarr;</a>
        </div>

        {{-- Eisenhower 2x2 Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Q1: DO NOW --}}
            <div class="bg-red-50 rounded-xl border-2 border-red-200 overflow-hidden">
                <div class="px-5 py-3 bg-red-100/60 border-b border-red-200 flex items-center gap-3">
                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-red-900 text-sm">Q1 — DO NOW</h3>
                        <p class="text-xs text-red-700">Mendesak & Penting</p>
                    </div>
                    <span class="ml-auto bg-red-200 text-red-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($urgentImportant) }}</span>
                </div>
                <div class="p-4 space-y-2 min-h-[120px] max-h-[320px] overflow-y-auto">
                    @forelse($urgentImportant as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'red'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas mendesak</p>
                    @endforelse
                </div>
            </div>

            {{-- Q2: SCHEDULE --}}
            <div class="bg-blue-50 rounded-xl border-2 border-blue-200 overflow-hidden">
                <div class="px-5 py-3 bg-blue-100/60 border-b border-blue-200 flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-blue-900 text-sm">Q2 — SCHEDULE</h3>
                        <p class="text-xs text-blue-700">Tidak Mendesak & Penting</p>
                    </div>
                    <span class="ml-auto bg-blue-200 text-blue-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($notUrgentImportant) }}</span>
                </div>
                <div class="p-4 space-y-2 min-h-[120px] max-h-[320px] overflow-y-auto">
                    @forelse($notUrgentImportant as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'blue'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas terjadwal</p>
                    @endforelse
                </div>
            </div>

            {{-- Q3: DELEGATE --}}
            <div class="bg-yellow-50 rounded-xl border-2 border-yellow-200 overflow-hidden">
                <div class="px-5 py-3 bg-yellow-100/60 border-b border-yellow-200 flex items-center gap-3">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-yellow-900 text-sm">Q3 — DELEGATE</h3>
                        <p class="text-xs text-yellow-700">Mendesak & Tidak Penting</p>
                    </div>
                    <span class="ml-auto bg-yellow-200 text-yellow-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($urgentNotImportant) }}</span>
                </div>
                <div class="p-4 space-y-2 min-h-[120px] max-h-[320px] overflow-y-auto">
                    @forelse($urgentNotImportant as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'yellow'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas untuk didelegasikan</p>
                    @endforelse
                </div>
            </div>

            {{-- Q4: ELIMINATE --}}
            <div class="bg-gray-50 rounded-xl border-2 border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-100/60 border-b border-gray-200 flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-700 text-sm">Q4 — ELIMINATE</h3>
                        <p class="text-xs text-gray-500">Tidak Mendesak & Tidak Penting</p>
                    </div>
                    <span class="ml-auto bg-gray-200 text-gray-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($notUrgentNotImportant ?? []) }}</span>
                </div>
                <div class="p-4 space-y-2 min-h-[120px] max-h-[320px] overflow-y-auto">
                    @forelse($notUrgentNotImportant ?? [] as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'gray'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas di kuadran ini</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('classroom.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Google Classroom</p>
                    <p class="text-xs text-gray-500">Sync tugas dari kelas</p>
                </div>
            </a>
            <a href="{{ route('ai.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">AI Assistant</p>
                    <p class="text-xs text-gray-500">Bantu prioritaskan tugas</p>
                </div>
            </a>
            <a href="{{ route('profile.edit') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Profil & Pengaturan</p>
                    <p class="text-xs text-gray-500">Kelola akun & Telegram</p>
                </div>
            </a>
        </div>

        {{-- Task Detail Modal --}}
        <div x-show="showDetailModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900" x-text="selectedTask?.title"></h3>
                            <div class="flex items-center gap-2 mt-2">
                                <span x-show="selectedTask?.course" class="inline-flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <span x-text="selectedTask?.course?.nama_course"></span>
                                </span>
                                <span x-show="selectedTask?.sumber === 'google_classroom'" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Classroom</span>
                                <span x-show="selectedTask?.sumber === 'manual'" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">Manual</span>
                            </div>
                        </div>
                        <button @click="showDetailModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div x-show="selectedTask?.description" class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700 leading-relaxed" x-text="selectedTask?.description"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-500 block text-xs mb-1">Kuadran</span><span class="font-medium" x-text="getKuadranName(selectedTask?.kuadran)"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Status</span><span class="font-medium capitalize" x-text="selectedTask?.status?.replace('_', ' ')"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Prioritas</span><span class="font-medium capitalize" x-text="selectedTask?.priority"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Deadline</span><span class="font-medium" x-text="selectedTask?.due_date ? formatDate(selectedTask.due_date) : 'Tidak ada'"></span></div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="toggleComplete(selectedTask)" class="flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors"
                                :class="selectedTask?.status === 'completed' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-600 text-white hover:bg-green-700'">
                            <span x-text="selectedTask?.status === 'completed' ? 'Buka Kembali' : 'Tandai Selesai'"></span>
                        </button>
                        <button @click="showDetailModal = false" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Task Modal --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showAddModal = false" @open-add-task.window="showAddModal = true">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Tugas Baru</h3>
                    <button @click="showAddModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="addTask" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Tugas *</label>
                        <input type="text" x-model="newTask.title" required placeholder="Contoh: Laporan Praktikum Basis Data"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                        <textarea x-model="newTask.description" rows="3" placeholder="Detail tugas..."
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
                            <select x-model="newTask.category" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="kuliah"> Kuliah</option>
                                <option value="pekerjaan"> Pekerjaan</option>
                                <option value="daily_activity"> Daily Activity</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Prioritas</label>
                            <select x-model="newTask.priority" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="high">🔴 Tinggi</option>
                                <option value="medium">🟡 Sedang</option>
                                <option value="low">🟢 Rendah</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Deadline</label>
                            <input type="date" x-model="newTask.due_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Waktu</label>
                            <input type="time" x-model="newTask.due_time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showAddModal = false" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Simpan Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Scripts loaded from resources/js/pages/dashboard.js --}}
</x-app-layout>
