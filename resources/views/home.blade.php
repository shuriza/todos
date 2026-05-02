<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-sm text-gray-500">Kelola tugas kuliahmu dengan Matriks Eisenhower</p>
            </div>
            <button @click="$dispatch('open-add-task')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tugas
            </button>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6 space-y-5" x-data="dashboardApp()">

        {{-- Statistik --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-500 mb-1">Total Tugas</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-500 mb-1">Selesai</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-500 mb-1">Belum Selesai</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-500 mb-1">Terlambat</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['overdue'] }}</p>
            </div>
        </div>

        {{-- Header Matriks --}}
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">Matriks Eisenhower</h2>
            <a href="{{ route('todos.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
        </div>

        {{-- Matriks 2x2 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            {{-- Q1 --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between bg-red-50">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        <span class="text-sm font-semibold text-gray-800">Q1 Lakukan Sekarang</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ count($urgentImportant) }} tugas</span>
                </div>
                <div class="p-3 space-y-2 min-h-[100px] max-h-[280px] overflow-y-auto">
                    @forelse($urgentImportant as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'red'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas mendesak</p>
                    @endforelse
                </div>
            </div>

            {{-- Q2 --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between bg-blue-50">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="text-sm font-semibold text-gray-800">Q2 Jadwalkan</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ count($notUrgentImportant) }} tugas</span>
                </div>
                <div class="p-3 space-y-2 min-h-[100px] max-h-[280px] overflow-y-auto">
                    @forelse($notUrgentImportant as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'blue'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas terjadwal</p>
                    @endforelse
                </div>
            </div>

            {{-- Q3 --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between bg-yellow-50">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                        <span class="text-sm font-semibold text-gray-800">Q3 Delegasikan</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ count($urgentNotImportant) }} tugas</span>
                </div>
                <div class="p-3 space-y-2 min-h-[100px] max-h-[280px] overflow-y-auto">
                    @forelse($urgentNotImportant as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'yellow'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas untuk didelegasikan</p>
                    @endforelse
                </div>
            </div>

            {{-- Q4 --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                        <span class="text-sm font-semibold text-gray-800">Q4 Eliminasi</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ count($notUrgentNotImportant ?? []) }} tugas</span>
                </div>
                <div class="p-3 space-y-2 min-h-[100px] max-h-[280px] overflow-y-auto">
                    @forelse($notUrgentNotImportant ?? [] as $task)
                        @include('components.task-card', ['task' => $task, 'color' => 'gray'])
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Tidak ada tugas di kuadran ini</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Modal Detail Tugas --}}
        <div x-show="showDetailModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
            <div class="bg-white rounded-xl w-full max-w-md shadow-lg" @click.stop>
                <div class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-900 leading-tight" x-text="selectedTask?.title"></h3>
                        <button @click="showDetailModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 mb-3">
                        <span x-show="selectedTask?.course" class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded">
                            <span x-text="selectedTask?.course?.nama_course"></span>
                        </span>
                        <span x-show="selectedTask?.sumber === 'google_classroom'" class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded">Classroom</span>
                        <span x-show="selectedTask?.sumber === 'manual'" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Manual</span>
                    </div>

                    <div x-show="selectedTask?.description" class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700 leading-relaxed" x-text="selectedTask?.description"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm mb-5">
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Kuadran</p>
                            <p class="font-medium text-gray-800" x-text="getKuadranName(selectedTask?.kuadran)"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Status</p>
                            <p class="font-medium text-gray-800 capitalize" x-text="selectedTask?.status?.replace('_', ' ')"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Prioritas</p>
                            <p class="font-medium text-gray-800 capitalize" x-text="selectedTask?.priority"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Deadline</p>
                            <p class="font-medium text-gray-800" x-text="selectedTask?.due_date ? formatDate(selectedTask.due_date) : 'Tidak ada'"></p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="toggleComplete(selectedTask)" class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="selectedTask?.status === 'completed' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-600 text-white hover:bg-green-700'">
                            <span x-text="selectedTask?.status === 'completed' ? 'Buka Kembali' : 'Tandai Selesai'"></span>
                        </button>
                        <button @click="showDetailModal = false" class="px-4 py-2 border border-gray-200 rounded-lg text-gray-600 text-sm font-medium hover:bg-gray-50">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Tugas --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showAddModal = false" @open-add-task.window="showAddModal = true">
            <div class="bg-white rounded-xl w-full max-w-md shadow-lg" @click.stop>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Tambah Tugas Baru</h3>
                    <button @click="showAddModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="addTask" class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Tugas *</label>
                        <input type="text" x-model="newTask.title" required placeholder="Contoh: Laporan Praktikum Basis Data"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea x-model="newTask.description" rows="2" placeholder="Detail tugas..."
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select x-model="newTask.category" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="kuliah">Kuliah</option>
                                <option value="pekerjaan">Pekerjaan</option>
                                <option value="daily_activity">Aktivitas Harian</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                            <select x-model="newTask.priority" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="high">Tinggi</option>
                                <option value="medium">Sedang</option>
                                <option value="low">Rendah</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                            <input type="date" x-model="newTask.due_date" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu</label>
                            <input type="time" x-model="newTask.due_time" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="showAddModal = false" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg text-gray-600 text-sm font-medium hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="addSaving" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span x-show="!addSaving">Simpan</span>
                            <span x-show="addSaving">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
