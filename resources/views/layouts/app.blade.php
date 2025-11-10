<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }">
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-indigo-600 to-indigo-800 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
                   :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
                
                <!-- Logo -->
                <div class="flex items-center justify-center h-20 border-b border-indigo-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                            <span class="text-2xl">📝</span>
                        </div>
                        <span class="text-white text-xl font-bold">Todo × AI</span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="mt-8 px-4 space-y-2">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white' : 'text-indigo-100 hover:bg-white/10' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>

                    <!-- Todos -->
                    <a href="{{ route('todos.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('todos.*') ? 'bg-white/20 text-white' : 'text-indigo-100 hover:bg-white/10' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span class="font-medium">Todos</span>
                    </a>

                    <!-- Calendar (Coming Soon) -->
                    <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-300 opacity-50 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium">Calendar</span>
                        <span class="ml-auto text-xs bg-indigo-900 px-2 py-0.5 rounded">Soon</span>
                    </div>

                    <!-- Ideas (Coming Soon) -->
                    <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-300 opacity-50 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <span class="font-medium">Ideas</span>
                        <span class="ml-auto text-xs bg-indigo-900 px-2 py-0.5 rounded">Soon</span>
                    </div>

                    <!-- AI Assistant -->
                    <a href="{{ route('ai.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('ai.*') ? 'bg-white/20 text-white' : 'text-indigo-100 hover:bg-white/10' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <span class="font-medium">AI Assistant</span>
                        <span class="ml-auto">
                            <span class="flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                        </span>
                    </a>
                </nav>

                <!-- User Profile at Bottom -->
                <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-indigo-700">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-white/10">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                            <span class="text-indigo-600 font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-indigo-200 truncate">{{ Auth::user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-indigo-200 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Mobile overlay -->
            <div x-show="sidebarOpen" 
                 @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-600 bg-opacity-75 lg:hidden"
                 style="display: none;">
            </div>

            <!-- Main content -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Top bar -->
                <header class="bg-white border-b border-gray-200 z-10">
                    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <!-- Page title -->
                        @isset($header)
                            <div class="flex-1">
                                {{ $header }}
                            </div>
                        @endisset

                        <!-- Right side items -->
                        <div class="flex items-center gap-4">
                            <!-- Profile dropdown -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center gap-2 text-sm hover:bg-gray-100 rounded-lg px-3 py-2">
                                    <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-semibold text-xs">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                    </div>
                                    <span class="hidden md:block text-gray-700 font-medium">{{ Auth::user()->name }}</span>
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Dropdown menu -->
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50"
                                     style="display: none;">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Profile Settings
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Main content area -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
