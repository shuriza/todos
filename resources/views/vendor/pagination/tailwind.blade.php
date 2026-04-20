@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-4" role="navigation" aria-label="Pagination Navigation">

        {{-- Info kiri --}}
        <p class="text-sm text-gray-500 hidden sm:block">
            {!! __('Menampilkan') !!}
            <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>
            &ndash;
            <span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
            {!! __('dari') !!}
            <span class="font-medium text-gray-700">{{ $paginator->total() }}</span>
            {!! __('tugas') !!}
        </p>

        {{-- Navigasi halaman --}}
        <div class="flex items-center gap-1">

            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-300 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    {{-- Ellipsis --}}
                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400 select-none">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white text-sm font-semibold shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-300 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif

        </div>
    </nav>
@endif
