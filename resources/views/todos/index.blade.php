{{-- 
    Fitur: Semua Tugas
    Halaman: Daftar tugas dengan filter, pencarian, drag-and-drop reorder, CRUD modal
    Controller: TodoController
    JS: resources/js/pages/todos.js
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Semua Tugas</h2>
                <p class="text-sm text-gray-500">Kelola semua tugas manual dan dari Google Classroom</p>
            </div>
            <button @click="$dispatch('open-add-task')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tugas
            </button>
        </div>
    </x-slot>

    @php
        $hasActiveFilters = !empty(array_filter($filters ?? [], fn($v) => $v && $v !== 'all'));
        $hasMultiplePages = $todos->hasPages();
        $canReorder = !$hasActiveFilters && !$hasMultiplePages;
    @endphp

    <div class="p-4 lg:p-6 space-y-5" x-data='todoPageApp({ canReorder: {{ $canReorder ? 'true' : 'false' }}, categories: @json($categoryOptions ?? []) })'>

        {{-- Search & Filter Bar --}}
        <form method="GET" action="{{ route('todos.index') }}" class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex flex-col lg:flex-row gap-3">
                {{-- Search --}}
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari tugas..." class="w-full pl-9 pr-4 py-2.5 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Filters --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" {{ ($filters['status'] ?? '') === 'all' || empty($filters['status'] ?? '') ? 'selected' : '' }}>Semua Status</option>
                        <option value="todo" {{ ($filters['status'] ?? '') === 'todo' ? 'selected' : '' }}>To Do</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    </select>

                    <select name="kuadran" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" {{ ($filters['kuadran'] ?? '') === 'all' || empty($filters['kuadran'] ?? '') ? 'selected' : '' }}>Semua Kuadran</option>
                        <option value="1" {{ ($filters['kuadran'] ?? '') === '1' ? 'selected' : '' }}>Q1 Lakukan Sekarang</option>
                        <option value="2" {{ ($filters['kuadran'] ?? '') === '2' ? 'selected' : '' }}>Q2 Jadwalkan</option>
                        <option value="3" {{ ($filters['kuadran'] ?? '') === '3' ? 'selected' : '' }}>Q3 Delegasikan</option>
                        <option value="4" {{ ($filters['kuadran'] ?? '') === '4' ? 'selected' : '' }}>Q4 Eliminasi</option>
                    </select>

                    <select name="category" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" {{ ($filters['category'] ?? '') === 'all' || empty($filters['category'] ?? '') ? 'selected' : '' }}>Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->name }}" {{ ($filters['category'] ?? '') === $category->name ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <select name="sumber" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" {{ ($filters['sumber'] ?? '') === 'all' || empty($filters['sumber'] ?? '') ? 'selected' : '' }}>Semua Sumber</option>
                        <option value="manual" {{ ($filters['sumber'] ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="google_classroom" {{ ($filters['sumber'] ?? '') === 'google_classroom' ? 'selected' : '' }}>Google Classroom</option>
                    </select>

                    {{-- Search submit button --}}
                    <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>

                    {{-- Reset filters --}}
                    @if (!empty(array_filter($filters ?? [])))
                        <a href="{{ route('todos.index') }}" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Category Manager --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Kategori Tugas</h3>
                    <p class="text-xs text-gray-500 mt-1">Kelola kategori untuk mengelompokkan tugas.</p>
                </div>

                <form @submit.prevent="saveCategory" class="flex flex-col sm:flex-row gap-2 lg:min-w-[400px]">
                    <input type="text" x-model="categoryForm.name" required maxlength="255" placeholder="Nama kategori (contoh: Organisasi)"
                           class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" :disabled="categorySaving"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-60 transition-colors"
                            x-text="categorySaving ? 'Menyimpan...' : (editingCategoryId ? 'Simpan' : 'Simpan Kategori')"></button>
                    <button type="button" x-show="editingCategoryId" @click="resetCategoryForm"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Batal</button>
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <template x-for="category in categories" :key="category.id">
                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50">
                        <span class="text-sm font-medium text-gray-800" x-text="category.name"></span>
                        <button type="button" @click="editCategory(category)" class="text-xs text-indigo-600 hover:text-indigo-800">Edit</button>
                        <button type="button" @click="deleteCategory(category)" class="text-xs text-red-600 hover:text-red-800">Hapus</button>
                    </div>
                </template>
                <div x-show="categories.length === 0" class="text-sm text-gray-400 py-2">Kategori default: Kuliah, Pekerjaan, Daily Activity.</div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                <div class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500">Total</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                <div class="text-xl font-bold text-green-600">{{ $stats['completed'] }}</div>
                <div class="text-xs text-gray-500">Selesai</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                <div class="text-xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                <div class="text-xs text-gray-500">Belum Selesai</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                <div class="text-xl font-bold text-red-600">{{ $stats['overdue'] }}</div>
                <div class="text-xs text-gray-500">Terlambat</div>
            </div>
        </div>

        {{-- Task Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
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
            <div id="todo-list-body" class="divide-y divide-gray-100">
                @forelse ($todos as $todo)
                    @php
                        $isOverdue = $todo->due_date && $todo->due_date->isPast() && $todo->status !== 'completed';
                        $kuadranColors = [1 => 'bg-red-100 text-red-700', 2 => 'bg-blue-100 text-blue-700', 3 => 'bg-yellow-100 text-yellow-700', 4 => 'bg-gray-100 text-gray-600'];
                        $kuadranNames = [1 => 'Lakukan Sekarang', 2 => 'Jadwalkan', 3 => 'Delegasikan', 4 => 'Eliminasi'];
                        $priorityColors = ['high' => 'bg-red-100 text-red-700', 'low' => 'bg-green-100 text-green-700'];
                        $priorityNames = ['high' => 'Tinggi', 'low' => 'Rendah'];
                    @endphp
                    <div id="todo-row-{{ $todo->id }}" data-todo-id="{{ $todo->id }}" class="px-5 py-4 hover:bg-gray-50 transition-colors group {{ $todo->status === 'completed' ? 'bg-gray-50/50' : '' }}">
                        <div class="lg:grid lg:grid-cols-12 gap-4 flex flex-col lg:flex-row items-start lg:items-center">
                            {{-- Drag Handle + Checkbox --}}
                            <div class="col-span-1 flex items-center gap-1.5">
                                @if ($canReorder)
                                    <div class="drag-handle cursor-grab active:cursor-grabbing p-0.5 text-gray-300 hover:text-gray-500 transition-colors flex-shrink-0" title="Seret untuk mengubah urutan">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/>
                                            <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                            <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                                        </svg>
                                    </div>
                                @endif
                                <button @click="toggleStatus({{ $todo->id }}, '{{ $todo->status }}')"
                                    class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors {{ $todo->status === 'completed' ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-400' }}">
                                    @if ($todo->status === 'completed')
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                            </div>

                            {{-- Title + Description --}}
                            <div class="col-span-4 min-w-0 cursor-pointer" @click="openDetail({{ json_encode($todo->toArray()) }})">
                                <p class="text-sm font-medium line-clamp-2 {{ $todo->status === 'completed' ? 'text-gray-400 line-through' : 'text-gray-900' }}">
                                    {{ $todo->title }}
                                </p>
                                @if ($todo->description)
                                    <p class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ $todo->description }}</p>
                                @endif
                                <div class="flex items-center flex-wrap gap-1.5 mt-1">
                                    @if ($todo->course)
                                        <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded max-w-[180px] truncate" title="{{ $todo->course->nama_course }}">
                                            {{ $todo->course->nama_course }}
                                        </span>
                                    @endif
                                    @if ($todo->category)
                                        <span class="inline-flex items-center text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ ucfirst(str_replace('_', ' ', $todo->category)) }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Kuadran badge --}}
                            <div class="col-span-2">
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md {{ $kuadranColors[$todo->kuadran] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $kuadranNames[$todo->kuadran] ?? '-' }}
                                </span>
                            </div>

                            {{-- Deadline --}}
                            <div class="col-span-2">
                                @if ($todo->due_date)
                                    <span class="text-sm {{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                        {{ $todo->due_date->format('d M Y') }}
                                        @if ($todo->due_time)
                                            <span class="text-gray-400">, {{ $todo->due_time }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Tidak ada</span>
                                @endif
                            </div>

                            {{-- Priority --}}
                            <div class="col-span-1">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-md {{ $priorityColors[$todo->priority] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $priorityNames[$todo->priority] ?? $todo->priority }}
                                </span>
                            </div>

                            {{-- Source --}}
                            <div class="col-span-1">
                                @if ($todo->sumber === 'google_classroom')
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-md">GC</span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md">Manual</span>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="col-span-1 flex items-center gap-1">
                                <button @click="editTodo({{ json_encode($todo->toArray()) }})" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 opacity-0 group-hover:opacity-100 transition-all" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="deleteTodo({{ $todo->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Empty State --}}
                    <div class="py-16 text-center">
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
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($todos->hasPages())
                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                    {{ $todos->links() }}
                </div>
            @elseif ($todos->total() > 0)
                <div class="px-5 py-3 border-t border-gray-100 text-sm text-gray-500">
                    Menampilkan semua {{ $todos->total() }} tugas
                </div>
            @endif
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
                        <div><span class="text-gray-500 block text-xs mb-1">Status</span><span class="font-medium" x-text="getStatusLabel(selectedTask?.status)"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Prioritas</span><span class="font-medium" x-text="getPriorityLabel(selectedTask?.priority)"></span></div>
                        <div><span class="text-gray-500 block text-xs mb-1">Deadline</span><span class="font-medium" x-text="selectedTask?.due_date ? formatDate(selectedTask.due_date) : 'Tidak ada'"></span></div>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        {{-- Buka tugas di Google Classroom (hanya untuk tugas bersumber Classroom yang punya link) --}}
                        <a x-show="selectedTask?.sumber === 'google_classroom' && selectedTask?.google_link"
                           :href="selectedTask?.google_link" target="_blank" rel="noopener noreferrer"
                           class="flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors bg-emerald-600 text-white hover:bg-emerald-700 inline-flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Buka di Classroom
                        </a>
                        <button @click="toggleStatus(selectedTask?.id, selectedTask?.status); showDetailModal = false" class="flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors"
                                :class="selectedTask?.status === 'completed' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-600 text-white hover:bg-green-700'">
                            <span x-text="selectedTask?.status === 'completed' ? 'Buka Kembali' : 'Tandai Selesai'"></span>
                        </button>
                        {{-- Tombol "Tidak Terselesaikan" hanya untuk tugas yang sudah lewat deadline & belum berstatus final --}}
                        <button x-show="isTaskOverdue(selectedTask) && selectedTask?.status !== 'completed' && selectedTask?.status !== 'unfinished'"
                                @click="markUnfinished(selectedTask?.id); showDetailModal = false"
                                class="flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors bg-red-600 text-white hover:bg-red-700">
                            Tidak Terselesaikan
                        </button>
                        {{-- Jika sudah unfinished, beri opsi buka kembali --}}
                        <button x-show="selectedTask?.status === 'unfinished'"
                                @click="toggleStatus(selectedTask?.id, 'unfinished'); showDetailModal = false"
                                class="flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Buka Kembali
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
                            <select x-model.number="form.category_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tanpa Kategori</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Prioritas</label>
                            <select x-model="form.priority" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="high">Tinggi</option>
                                <option value="low">Rendah</option>
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
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
