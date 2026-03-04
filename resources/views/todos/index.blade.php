<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Semua Tugas</h2>
                <p class="text-sm text-gray-500">Kelola semua tugas manual dan dari Google Classroom</p>
            </div>
            <button @click="$dispatch('open-add-task')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tugas
            </button>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6 space-y-5" x-data="todoListApp()">

        {{-- Search & Filter Bar --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex flex-col lg:flex-row gap-3">
                {{-- Search --}}
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Cari tugas..." class="w-full pl-9 pr-4 py-2.5 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Filter pills --}}
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Status filter --}}
                    <select x-model="statusFilter" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">Semua Status</option>
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Selesai</option>
                    </select>

                    {{-- Kuadran filter --}}
                    <select x-model="kuadranFilter" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">Semua Kuadran</option>
                        <option value="1">Q1 — Do Now</option>
                        <option value="2">Q2 — Schedule</option>
                        <option value="3">Q3 — Delegate</option>
                        <option value="4">Q4 — Eliminate</option>
                    </select>

                    {{-- Category filter --}}
                    <select x-model="categoryFilter" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">Semua Kategori</option>
                        <option value="kuliah">🎓 Kuliah</option>
                        <option value="pekerjaan">💼 Pekerjaan</option>
                        <option value="daily_activity"> Daily Activity</option>
                    </select>

                    {{-- Source filter --}}
                    <select x-model="sumberFilter" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">Semua Sumber</option>
                        <option value="manual">Manual</option>
                        <option value="google_classroom">Google Classroom</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center shadow-sm">
                <div class="text-xl font-bold text-gray-900" x-text="filteredTodos.length"></div>
                <div class="text-xs text-gray-500">Ditampilkan</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center shadow-sm">
                <div class="text-xl font-bold text-green-600" x-text="todos.filter(t => t.status === 'completed').length"></div>
                <div class="text-xs text-gray-500">Selesai</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center shadow-sm">
                <div class="text-xl font-bold text-yellow-600" x-text="todos.filter(t => t.status !== 'completed').length"></div>
                <div class="text-xs text-gray-500">Belum Selesai</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center shadow-sm">
                <div class="text-xl font-bold text-red-600" x-text="todos.filter(t => t.due_date && new Date(t.due_date) < new Date() && t.status !== 'completed').length"></div>
                <div class="text-xs text-gray-500">Terlambat</div>
            </div>
        </div>

        {{-- Task Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            {{-- Table header --}}
            <div class="hidden lg:grid lg:grid-cols-12 gap-4 px-5 py-3 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <div class="col-span-1">Status</div>
                <div class="col-span-4">Tugas</div>
                <div class="col-span-2">Kuadran</div>
                <div class="col-span-2">Deadline</div>
                <div class="col-span-1">Prioritas</div>
                <div class="col-span-1">Sumber</div>
                <div class="col-span-1">Aksi</div>
            </div>

            {{-- Table body --}}
            <div class="divide-y divide-gray-100">
                <template x-for="todo in filteredTodos" :key="todo.id">
                    <div class="px-5 py-4 hover:bg-gray-50 transition-colors group"
                         :class="todo.status === 'completed' ? 'bg-gray-50/50' : ''">
                        <div class="lg:grid lg:grid-cols-12 gap-4 flex flex-col lg:flex-row items-start lg:items-center">
                            {{-- Checkbox --}}
                            <div class="col-span-1 flex items-center">
                                <button @click="toggleStatus(todo)"
                                    class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                    :class="todo.status === 'completed' ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-400'">
                                    <svg x-show="todo.status === 'completed'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>

                            {{-- Title + Description --}}
                            <div class="col-span-4 min-w-0 cursor-pointer" @click="openDetail(todo)">
                                <p class="text-sm font-medium truncate"
                                   :class="todo.status === 'completed' ? 'text-gray-400 line-through' : 'text-gray-900'">
                                    <span x-text="todo.title"></span>
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span x-show="todo.course" class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded">
                                        <span x-text="todo.course?.nama_course"></span>
                                    </span>
                                    <span x-show="todo.category" class="text-xs text-gray-500" x-text="getCategoryLabel(todo.category)"></span>
                                </div>
                            </div>

                            {{-- Kuadran badge --}}
                            <div class="col-span-2">
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md"
                                      :class="getKuadranBadgeClass(todo.kuadran)">
                                    <span class="w-2 h-2 rounded-full" :class="getKuadranDotClass(todo.kuadran)"></span>
                                    <span x-text="getKuadranName(todo.kuadran)"></span>
                                </span>
                            </div>

                            {{-- Deadline --}}
                            <div class="col-span-2">
                                <template x-if="todo.due_date">
                                    <span class="text-sm" :class="isOverdue(todo) ? 'text-red-600 font-medium' : 'text-gray-600'">
                                        <span x-text="formatDate(todo.due_date)"></span>
                                        <span x-show="todo.due_time" class="text-gray-400" x-text="', ' + todo.due_time"></span>
                                    </span>
                                </template>
                                <template x-if="!todo.due_date">
                                    <span class="text-xs text-gray-400">Tidak ada</span>
                                </template>
                            </div>

                            {{-- Priority --}}
                            <div class="col-span-1">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-md uppercase"
                                      :class="{
                                          'bg-red-100 text-red-700': todo.priority === 'high',
                                          'bg-yellow-100 text-yellow-700': todo.priority === 'medium',
                                          'bg-green-100 text-green-700': todo.priority === 'low'
                                      }"
                                      x-text="todo.priority === 'high' ? 'Tinggi' : (todo.priority === 'medium' ? 'Sedang' : 'Rendah')">
                                </span>
                            </div>

                            {{-- Source --}}
                            <div class="col-span-1">
                                <span x-show="todo.sumber === 'google_classroom'" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-md">GC</span>
                                <span x-show="todo.sumber !== 'google_classroom'" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md">Manual</span>
                            </div>

                            {{-- Actions --}}
                            <div class="col-span-1 flex items-center gap-1">
                                <button @click="editTodo(todo)" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 opacity-0 group-hover:opacity-100 transition-all" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="deleteTodo(todo.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Empty State --}}
                <div x-show="filteredTodos.length === 0" class="py-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Tidak ada tugas ditemukan</h3>
                    <p class="text-sm text-gray-500 mb-4">Coba ubah filter atau tambah tugas baru</p>
                    <button @click="$dispatch('open-add-task')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Tugas
                    </button>
                </div>
            </div>
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
                        <div><span class="text-gray-500 block text-xs mb-1">Kuadran</span><span class="font-medium" x-text="getKuadranLabel(selectedTask?.kuadran)"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Status</span><span class="font-medium capitalize" x-text="selectedTask?.status?.replace('_', ' ')"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Prioritas</span><span class="font-medium capitalize" x-text="selectedTask?.priority"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Deadline</span><span class="font-medium" x-text="selectedTask?.due_date ? formatDate(selectedTask.due_date) : 'Tidak ada'"></span></div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="toggleStatus(selectedTask); showDetailModal = false" class="flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors"
                                :class="selectedTask?.status === 'completed' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-600 text-white hover:bg-green-700'">
                            <span x-text="selectedTask?.status === 'completed' ? 'Buka Kembali' : 'Tandai Selesai'"></span>
                        </button>
                        <button @click="showDetailModal = false" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add/Edit Task Modal --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showAddModal = false" @open-add-task.window="resetForm(); showAddModal = true">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900" x-text="editingId ? 'Edit Tugas' : 'Tambah Tugas Baru'"></h3>
                    <button @click="showAddModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="saveTodo" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Tugas *</label>
                        <input type="text" x-model="form.title" required placeholder="Contoh: Laporan Praktikum Basis Data"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                        <textarea x-model="form.description" rows="3" placeholder="Detail tugas..."
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
                            <select x-model="form.category" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="kuliah"> Kuliah</option>
                                <option value="pekerjaan"> Pekerjaan</option>
                                <option value="daily_activity"> Daily Activity</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Prioritas</label>
                            <select x-model="form.priority" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="high">🔴 Tinggi</option>
                                <option value="medium">🟡 Sedang</option>
                                <option value="low">🟢 Rendah</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Deadline</label>
                            <input type="date" x-model="form.due_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Waktu</label>
                            <input type="time" x-model="form.due_time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showAddModal = false" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:bg-indigo-400 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!saving" x-text="editingId ? 'Simpan Perubahan' : 'Tambah Tugas'"></span>
                            <span x-show="saving" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script id="todos-data" type="application/json">
        @json($todos)
    </script>
    {{-- JS loaded from resources/js/pages/todos.js --}}
    @endpush
</x-app-layout>
