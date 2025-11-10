<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Tasks -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="flex items-center text-green-600 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            <span>+12%</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['total'] }}</div>
                    <div class="text-sm text-gray-600">Total Tasks</div>
                </div>

                <!-- Completed Tasks -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex items-center text-green-600 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            <span>+8%</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['completed'] }}</div>
                    <div class="text-sm text-gray-600">Completed</div>
                </div>

                <!-- Calendar Events -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex items-center text-green-600 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            <span>+5%</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['in_progress'] }}</div>
                    <div class="text-sm text-gray-600">Calendar Events</div>
                </div>

                <!-- Saved Ideas -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div class="flex items-center text-green-600 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            <span>+8%</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['overdue'] }}</div>
                    <div class="text-sm text-gray-600">Saved Ideas</div>
                </div>
            </div>
            <!-- Quick Actions & AI Assistant -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="font-semibold text-lg text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('todos.index') }}" class="w-full flex items-center gap-3 px-4 py-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors text-left">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Add New Todo</div>
                                <div class="text-xs text-gray-600">Create a new task</div>
                            </div>
                        </a>

                        <div class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-lg text-left opacity-50 cursor-not-allowed">
                            <div class="w-10 h-10 bg-cyan-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Schedule Event</div>
                                <div class="text-xs text-gray-600">Add to your calendar</div>
                            </div>
                        </div>

                        <div class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-lg text-left opacity-50 cursor-not-allowed">
                            <div class="w-10 h-10 bg-orange-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Save New Idea</div>
                                <div class="text-xs text-gray-600">Capture your thoughts</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Assistant Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="font-semibold text-lg text-gray-900">AI Assistant</h3>
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-xs font-medium">New!</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-6">Let AI help you create detailed todos, events, and ideas from your brief inputs.</p>
                    
                    <a href="{{ route('ai.index') }}" class="inline-block w-full text-center px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors mb-6">
                        Try AI Assistant
                    </a>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-blue-600">Fast</div>
                            <div class="text-xs text-gray-500">Quick Input</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-green-600">Smart</div>
                            <div class="text-xs text-gray-500">AI Powered</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-purple-600">Learn</div>
                            <div class="text-xs text-gray-500">Adapts to You</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
