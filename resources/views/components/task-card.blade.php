@props(['task', 'color' => 'gray'])

@php
    $colors = [
        'red' => ['bg' => 'bg-white', 'border' => 'border-l-red-500', 'badge' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-400'],
        'blue' => ['bg' => 'bg-white', 'border' => 'border-l-blue-500', 'badge' => 'bg-blue-100 text-blue-700', 'dot' => 'bg-blue-400'],
        'yellow' => ['bg' => 'bg-white', 'border' => 'border-l-yellow-500', 'badge' => 'bg-yellow-100 text-yellow-700', 'dot' => 'bg-yellow-400'],
        'gray' => ['bg' => 'bg-white', 'border' => 'border-l-gray-400', 'badge' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400'],
    ];
    $c = $colors[$color] ?? $colors['gray'];
    $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed';
@endphp

<div class="{{ $c['bg'] }} rounded-lg border border-gray-200 border-l-4 {{ $c['border'] }} p-3 hover:shadow-md transition-shadow cursor-pointer group"
     @click="openDetail({{ json_encode($task->toArray()) }})"
     x-data="{ menuOpen: false }">
    <div class="flex items-start gap-3">
        {{-- Checkbox --}}
        <button @click.stop="toggleComplete({{ json_encode($task->toArray()) }})"
                class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors
                       {{ $task->status === 'completed' ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-400' }}">
            @if($task->status === 'completed')
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            @endif
        </button>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                {{ $task->title }}
            </p>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                @if($task->course)
                    <span class="inline-flex items-center gap-1 text-xs {{ $c['badge'] }} px-1.5 py-0.5 rounded">
                        <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }}"></span>
                        {{ Str::limit($task->course->nama_course, 20) }}
                    </span>
                @endif
                @if($task->due_date)
                    <span class="inline-flex items-center gap-1 text-xs {{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ \Carbon\Carbon::parse($task->due_date)->format('d M') }}
                        @if($task->due_time), {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }}@endif
                    </span>
                @endif
                @if($task->sumber === 'google_classroom')
                    <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">GC</span>
                @endif
            </div>
        </div>

        {{-- 3-dot menu --}}
        <div class="relative" @click.stop>
            <button @click="menuOpen = !menuOpen" class="p-1 text-gray-400 hover:text-gray-600 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
            </button>
            <div x-show="menuOpen" @click.away="menuOpen = false" x-cloak
                 class="absolute right-0 top-8 w-52 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-30">
                <div class="px-3 py-1.5 text-xs text-gray-400 font-medium uppercase tracking-wider">Pindah ke</div>
                @if(($task->kuadran ?? 0) !== 1)
                    <button @click="moveToKuadran({{ $task->id }}, 1); menuOpen = false" class="w-full text-left px-3 py-2 text-sm hover:bg-red-50 text-gray-700 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Q1 — Do Now
                    </button>
                @endif
                @if(($task->kuadran ?? 0) !== 2)
                    <button @click="moveToKuadran({{ $task->id }}, 2); menuOpen = false" class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 text-gray-700 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Q2 — Schedule
                    </button>
                @endif
                @if(($task->kuadran ?? 0) !== 3)
                    <button @click="moveToKuadran({{ $task->id }}, 3); menuOpen = false" class="w-full text-left px-3 py-2 text-sm hover:bg-yellow-50 text-gray-700 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Q3 — Delegate
                    </button>
                @endif
                @if(($task->kuadran ?? 0) !== 4)
                    <button @click="moveToKuadran({{ $task->id }}, 4); menuOpen = false" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 text-gray-700 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span> Q4 — Eliminate
                    </button>
                @endif
                <div class="border-t border-gray-100 my-1"></div>
                <button @click="if(confirm('Hapus tugas ini?')) { fetch('/todos/{{ $task->id }}', {method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(()=>location.reload()); } menuOpen = false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-red-50 text-red-600 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Tugas
                </button>
            </div>
        </div>
    </div>
</div>
