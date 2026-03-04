<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-800">Google Classroom</h2>
    </x-slot>

    <div class="p-6 space-y-6">
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

        {{-- Header & Sync Buttons --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Google Classroom</h1>
                <p class="text-gray-500 mt-1">Sinkronisasi tugas dari Google Classroom ke Eisenhower Matrix</p>
            </div>

            @if ($hasGoogleAccess)
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('auth.google.reconnect') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Hubungkan Ulang Google
                    </a>
                    <form method="POST" action="{{ route('classroom.sync-courses') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Sync Mata Kuliah
                        </button>
                    </form>
                    <form method="POST" action="{{ route('classroom.sync-tasks') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Sync Semua Tugas
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Error with reconnect hint --}}
        @if (session('error') && str_contains(session('error'), 'Hubungkan Ulang'))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-800 mb-1">Izin Google Classroom Diperlukan</h3>
                        <p class="text-sm text-amber-700 mb-4">{{ session('error') }}</p>
                        <div class="space-y-3">
                            <a href="{{ route('auth.google.reconnect') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
                                <svg class="w-4 h-4" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity="0.7"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity="0.5"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity="0.8"/>
                                </svg>
                                Hubungkan Ulang Google
                            </a>
                            <div class="text-xs text-amber-600 space-y-1">
                                <p><strong>Langkah:</strong></p>
                                <ol class="list-decimal list-inside space-y-0.5">
                                    <li>Klik tombol "Hubungkan Ulang Google" di atas</li>
                                    <li>Pilih akun Google yang terhubung ke Classroom</li>
                                    <li>Centang semua izin yang diminta (termasuk Google Classroom)</li>
                                    <li>Setelah berhasil, coba sync ulang</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Google Cloud Console setup hint for admins --}}
        @if (session('error') && str_contains(session('error'), 'Google Cloud Console'))
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-blue-800 mb-1">Setup Google Cloud Console</h3>
                        <p class="text-sm text-blue-700 mb-3">{{ session('error') }}</p>
                        <div class="text-xs text-blue-600 space-y-1">
                            <p><strong>Yang perlu dilakukan di Google Cloud Console:</strong></p>
                            <ol class="list-decimal list-inside space-y-0.5">
                                <li>Buka <a href="https://console.cloud.google.com/apis/library/classroom.googleapis.com" target="_blank" class="underline font-medium">Google Classroom API</a></li>
                                <li>Klik "Enable" / "Aktifkan"</li>
                                <li>Buka <a href="https://console.cloud.google.com/apis/credentials/consent" target="_blank" class="underline font-medium">OAuth Consent Screen</a></li>
                                <li>Pastikan akun Google kamu terdaftar sebagai <strong>Test User</strong> (jika masih mode "Testing")</li>
                                <li>Tunggu 1-2 menit, lalu coba hubungkan ulang</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- No Google Access Warning --}}
        @if (!$hasGoogleAccess)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">Akun Google Belum Terhubung</h3>
                <p class="text-yellow-600 mb-6 max-w-md mx-auto">Untuk menggunakan fitur Google Classroom, Anda perlu login menggunakan akun Google terlebih dahulu.</p>
                <a href="{{ route('auth.google.reconnect') }}" class="inline-flex items-center gap-3 px-6 py-3 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
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

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="text-2xl font-bold text-indigo-600">{{ $stats['total_courses'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Mata Kuliah</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_tasks'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Total Tugas</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Pending</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Selesai</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="text-2xl font-bold text-red-600">{{ $stats['overdue'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Terlambat</div>
            </div>
        </div>

        {{-- Courses Grid --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Mata Kuliah</h2>
            @if ($courses->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($courses as $course)
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            <div class="h-2" @style(['background-color: ' . ($course->color ?? '#4285F4')])></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-900 truncate">{{ $course->nama_course }}</h3>
                                        @if ($course->deskripsi_ruang)
                                            <p class="text-sm text-gray-500 mt-1 truncate">{{ $course->deskripsi_ruang }}</p>
                                        @endif
                                    </div>
                                    @if ($course->google_course_id)
                                        <span class="ml-2 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Classroom
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center gap-4 text-sm">
                                    <span class="text-gray-500">
                                        <span class="font-medium text-gray-700">{{ $course->todos_count }}</span> tugas
                                    </span>
                                    @if ($course->pending_todos_count > 0)
                                        <span class="text-yellow-600">
                                            <span class="font-medium">{{ $course->pending_todos_count }}</span> pending
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <a href="{{ route('classroom.course', $course) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                        Lihat Tugas &rarr;
                                    </a>
                                    <form method="POST" action="{{ route('classroom.course.destroy', $course) }}" onsubmit="return confirm('Hapus mata kuliah ini beserta tugasnya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-500 hover:text-red-700 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 mb-4">Belum ada mata kuliah. Klik "Sync Mata Kuliah" untuk mengambil data dari Google Classroom.</p>
                </div>
            @endif
        </div>

        {{-- Recent Classroom Tasks --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Tugas dari Google Classroom</h2>
            @if ($classroomTodos->count() > 0)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[35%]">Tugas</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[22%]">Mata Kuliah</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[18%]">Deadline</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[12%]">Kuadran</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[13%]">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($classroomTodos as $todo)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 text-sm leading-snug line-clamp-2">{{ $todo->title }}</div>
                                            @if ($todo->description)
                                                <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ Str::limit($todo->description, 60) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 max-w-0">
                                            @if ($todo->course)
                                                <div class="flex items-center gap-1.5 min-w-0" title="{{ $todo->course->nama_course }}">
                                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" @style(['background-color: ' . ($todo->course->color ?? '#4285F4')])></span>
                                                    <span class="text-sm text-gray-800 truncate">{{ $todo->course->nama_course }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($todo->due_date)
                                                <div class="{{ $todo->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                                    {{ $todo->due_date->format('d M Y') }}
                                                </div>
                                                @if ($todo->due_time)
                                                    <div class="text-xs text-gray-400">{{ $todo->due_time }}</div>
                                                @endif
                                                <div class="text-xs {{ $todo->isOverdue() ? 'text-red-500' : 'text-gray-400' }}">
                                                    {{ $todo->time_left }}
                                                </div>
                                            @else
                                                <span class="text-gray-400">Tidak ada</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $kuadranColors = [
                                                    1 => 'bg-red-100 text-red-700',
                                                    2 => 'bg-blue-100 text-blue-700',
                                                    3 => 'bg-yellow-100 text-yellow-700',
                                                    4 => 'bg-gray-100 text-gray-600',
                                                ];
                                                $kuadranNames = [
                                                    1 => 'DO NOW',
                                                    2 => 'SCHEDULE',
                                                    3 => 'DELEGATE',
                                                    4 => 'ELIMINATE',
                                                ];
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $kuadranColors[$todo->kuadran] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ $kuadranNames[$todo->kuadran] ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColors = [
                                                    'todo' => 'bg-yellow-100 text-yellow-700',
                                                    'in_progress' => 'bg-blue-100 text-blue-700',
                                                    'completed' => 'bg-green-100 text-green-700',
                                                ];
                                                $statusLabels = [
                                                    'todo' => 'Belum Dikerjakan',
                                                    'in_progress' => 'Dikerjakan',
                                                    'completed' => 'Selesai',
                                                ];
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$todo->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ $statusLabels[$todo->status] ?? $todo->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 mb-4">Belum ada tugas dari Google Classroom. Klik "Sync Semua Tugas" untuk mengambil assignment.</p>
                </div>
            @endif
        </div>

        @endif
    </div>
</x-app-layout>
