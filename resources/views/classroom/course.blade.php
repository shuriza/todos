<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('classroom.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="text-xl font-bold text-gray-800">{{ $course->nama_course }}</h2>
            @if ($course->google_course_id)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Classroom</span>
            @endif
        </div>
    </x-slot>

    <div class="p-6 space-y-6">
        {{-- Course Info --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="h-2" @style(['background-color: ' . ($course->color ?? '#4285F4')])></div>
            <div class="p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $course->nama_course }}</h1>
                        @if ($course->deskripsi_ruang)
                            <p class="text-gray-500 mt-1">{{ $course->deskripsi_ruang }}</p>
                        @endif
                    </div>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('classroom.sync-tasks') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Sync Tugas
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Course Stats --}}
                <div class="mt-6 grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                        <div class="text-xs text-gray-500">Total</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                        <div class="text-xs text-gray-500">Pending</div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-blue-600">{{ $stats['in_progress'] }}</div>
                        <div class="text-xs text-gray-500">Dikerjakan</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-green-600">{{ $stats['completed'] }}</div>
                        <div class="text-xs text-gray-500">Selesai</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-red-600">{{ $stats['overdue'] }}</div>
                        <div class="text-xs text-gray-500">Terlambat</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Tasks List --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Daftar Tugas</h2>

            @if ($todos->count() > 0)
                <div class="space-y-3">
                    @foreach ($todos as $todo)
                        @php
                            $kuadranColors = [
                                1 => 'border-l-red-500',
                                2 => 'border-l-blue-500',
                                3 => 'border-l-yellow-500',
                                4 => 'border-l-gray-400',
                            ];
                            $kuadranNames = [1 => 'DO NOW', 2 => 'SCHEDULE', 3 => 'DELEGATE', 4 => 'ELIMINATE'];
                            $kuadranBadgeColors = [
                                1 => 'bg-red-100 text-red-700',
                                2 => 'bg-blue-100 text-blue-700',
                                3 => 'bg-yellow-100 text-yellow-700',
                                4 => 'bg-gray-100 text-gray-600',
                            ];
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 {{ $kuadranColors[$todo->kuadran] ?? 'border-l-gray-300' }} shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        @if ($todo->status === 'completed')
                                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 flex-shrink-0"></div>
                                        @endif
                                        <h3 class="font-medium text-gray-900 {{ $todo->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                            {{ $todo->title }}
                                        </h3>
                                    </div>
                                    @if ($todo->description)
                                        <p class="text-sm text-gray-500 ml-7 line-clamp-2">{{ $todo->description }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $kuadranBadgeColors[$todo->kuadran] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $kuadranNames[$todo->kuadran] ?? '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-3 ml-7 flex items-center gap-4 text-sm">
                                @if ($todo->due_date)
                                    <span class="inline-flex items-center gap-1 {{ $todo->isOverdue() ? 'text-red-600' : 'text-gray-500' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $todo->due_date->format('d M Y') }}
                                        @if ($todo->due_time) {{ $todo->due_time }} @endif
                                    </span>
                                    <span class="text-xs {{ $todo->isOverdue() ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                                        {{ $todo->time_left }}
                                    </span>
                                @endif

                                @php
                                    $statusColors = [
                                        'todo' => 'text-yellow-600',
                                        'in_progress' => 'text-blue-600',
                                        'completed' => 'text-green-600',
                                    ];
                                    $statusLabels = [
                                        'todo' => 'Belum Dikerjakan',
                                        'in_progress' => 'Dikerjakan',
                                        'completed' => 'Selesai',
                                    ];
                                @endphp
                                <span class="{{ $statusColors[$todo->status] ?? 'text-gray-500' }} font-medium">
                                    {{ $statusLabels[$todo->status] ?? $todo->status }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                    <p class="text-gray-500">Belum ada tugas untuk mata kuliah ini.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
