<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Google Classroom</h2>
                <p class="text-sm text-gray-500">Sinkronisasi tugas dari Google Classroom</p>
            </div>
            @if ($hasGoogleAccess)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('auth.google.reconnect') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Hubungkan Ulang
                    </a>
                    <form method="POST" action="{{ route('classroom.sync-tasks') }}" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" :disabled="loading" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span x-text="loading ? 'Sinkronisasi...' : 'Sinkronisasi'"></span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="p-4 lg:p-6 space-y-5" x-data="classroomPage()">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3" x-data="{ show: true }" x-show="show">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3" x-data="{ show: true }" x-show="show">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        {{-- Error with reconnect hint --}}
        @if (session('error') && str_contains(session('error'), 'Hubungkan Ulang'))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-800 text-sm">Izin Google Classroom Diperlukan</h3>
                        <p class="text-sm text-amber-700 mt-1">Klik "Hubungkan Ulang" di atas, pilih akun Google, dan centang semua izin yang diminta.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- No Google Access --}}
        @if (!$hasGoogleAccess)
            <div class="bg-white border border-gray-200 rounded-xl p-8 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Akun Google Belum Terhubung</h3>
                <p class="text-gray-500 mb-6 max-w-md mx-auto text-sm">Hubungkan akun Google untuk menyinkronkan tugas dari Google Classroom.</p>
                <a href="{{ route('auth.google.reconnect') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Hubungkan dengan Google
                </a>
            </div>
        @else

        {{-- Statistics --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-2xl font-bold text-indigo-600">{{ $stats['total_courses'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Mata Kuliah</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_tasks'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Tugas</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Belum Selesai</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Selesai</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-2xl font-bold text-red-600">{{ $stats['overdue'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Terlambat</div>
            </div>
        </div>

        {{-- Tab Bar + Task Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Tabs --}}
            <div class="border-b border-gray-200 overflow-x-auto">
                <nav class="flex -mb-px min-w-max px-4" aria-label="Tabs">
                    <button @click="activeTab = 'all'"
                            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="activeTab === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        Semua
                        <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full"
                              :class="activeTab === 'all' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'"
                              x-text="todos.length"></span>
                    </button>
                    @foreach ($courses as $course)
                        <button @click="activeTab = '{{ $course->id }}'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                                :class="activeTab === '{{ $course->id }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $course->color ?? '#4285F4' }}"></span>
                                {{ Str::limit($course->nama_course, 25) }}
                            </span>
                            @php $pendingCount = $course->pending_todos_count ?? 0; @endphp
                            @if ($pendingCount > 0)
                                <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full"
                                      :class="activeTab === '{{ $course->id }}' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'">{{ $pendingCount }}</span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tugas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Kuliah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kuadran</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="todo in filteredTodos" :key="todo.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 text-sm leading-snug" x-text="todo.title"></div>
                                    <div x-show="todo.description" class="text-xs text-gray-500 mt-0.5 truncate max-w-xs" x-text="todo.description ? todo.description.substring(0, 60) : ''"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="todo.course">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background-color:' + (todo.course.color || '#4285F4')"></span>
                                            <span class="text-sm text-gray-800 truncate" x-text="todo.course.nama_course"></span>
                                        </div>
                                    </template>
                                    <template x-if="!todo.course">
                                        <span class="text-gray-400 text-sm">-</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <template x-if="todo.due_date">
                                        <div>
                                            <div :class="isOverdue(todo) ? 'text-red-600 font-medium' : 'text-gray-700'" x-text="formatDate(todo.due_date)"></div>
                                            <div x-show="todo.due_time" class="text-xs text-gray-400" x-text="todo.due_time"></div>
                                        </div>
                                    </template>
                                    <template x-if="!todo.due_date">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium"
                                          :class="kuadranBadge(todo.kuadran)"
                                          x-text="kuadranName(todo.kuadran)"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium"
                                          :class="statusBadge(todo.status)"
                                          x-text="statusName(todo.status)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Empty state --}}
            <div x-show="filteredTodos.length === 0" x-cloak class="py-12 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-gray-500 text-sm" x-text="activeTab === 'all' ? 'Belum ada tugas. Klik Sinkronisasi untuk mengambil tugas dari Google Classroom.' : 'Belum ada tugas untuk mata kuliah ini.'"></p>
            </div>
        </div>

        @endif
    </div>

    @push('scripts')
    <script id="classroom-todos-data" type="application/json">
        @json($classroomTodos)
    </script>
    <script>
        function classroomPage() {
            const el = document.getElementById('classroom-todos-data');
            const todos = el ? JSON.parse(el.textContent) : [];

            return {
                activeTab: 'all',
                todos: todos,

                get filteredTodos() {
                    if (this.activeTab === 'all') return this.todos;
                    return this.todos.filter(t => String(t.course_id) === String(this.activeTab));
                },

                isOverdue(todo) {
                    return todo.due_date && new Date(todo.due_date) < new Date() && todo.status !== 'completed';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                },

                kuadranName(k) {
                    return { 1: 'Lakukan Sekarang', 2: 'Jadwalkan', 3: 'Delegasikan', 4: 'Eliminasi' }[k] || '-';
                },

                kuadranBadge(k) {
                    return { 1: 'bg-red-100 text-red-700', 2: 'bg-blue-100 text-blue-700', 3: 'bg-yellow-100 text-yellow-700', 4: 'bg-gray-100 text-gray-600' }[k] || 'bg-gray-100 text-gray-600';
                },

                statusName(s) {
                    return { todo: 'Belum Dikerjakan', in_progress: 'Dikerjakan', completed: 'Selesai' }[s] || s;
                },

                statusBadge(s) {
                    return { todo: 'bg-yellow-100 text-yellow-700', in_progress: 'bg-blue-100 text-blue-700', completed: 'bg-green-100 text-green-700' }[s] || 'bg-gray-100 text-gray-600';
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
