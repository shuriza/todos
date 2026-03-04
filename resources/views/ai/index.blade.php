<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">AI Assistant</h2>
                    <p class="text-xs text-green-600 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        Online — Siap membantu
                    </p>
                </div>
            </div>
            <button @click="$dispatch('clear-chat')" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Chat Baru
            </button>
        </div>
    </x-slot>

    <div class="flex flex-col h-[calc(100vh-140px)]" x-data="chatBot()" @clear-chat.window="clearChat()">
        {{-- Quick Actions --}}
        <div class="px-4 lg:px-6 pt-4 flex-shrink-0" x-show="messages.length === 0">
            <div class="max-w-3xl mx-auto">
                {{-- Welcome --}}
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Halo! Saya AI Assistant 👋</h3>
                    <p class="text-sm text-gray-500">Siap membantu Anda mengatur tugas, jadwal, dan produktivitas</p>
                </div>

                {{-- Quick Action Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button @click="quickAction('daily-planning')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-200 transition-colors">
                                <span class="text-lg">📅</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Rencana Hari Ini</p>
                                <p class="text-xs text-gray-500">Buat prioritas tugas untuk hari ini</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('productivity-tips')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-md transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-blue-200 transition-colors">
                                <span class="text-lg">💡</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Tips Produktivitas</p>
                                <p class="text-xs text-gray-500">Saran meningkatkan efisiensi</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('task-breakdown')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-green-300 hover:shadow-md transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-green-200 transition-colors">
                                <span class="text-lg">✂️</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Breakdown Tugas</p>
                                <p class="text-xs text-gray-500">Pecah tugas menjadi langkah kecil</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('eisenhower-analysis')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-purple-300 hover:shadow-md transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-purple-200 transition-colors">
                                <span class="text-lg">📊</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Analisis Eisenhower</p>
                                <p class="text-xs text-gray-500">Evaluasi prioritas matriks tugas</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('create-tasks')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-orange-300 hover:shadow-md transition-all group sm:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-orange-200 transition-colors">
                                <span class="text-lg">✨</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Buat Tugas dengan AI</p>
                                <p class="text-xs text-gray-500">Minta AI membuatkan tugas untuk beberapa hari ke depan — preview dulu sebelum disimpan</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Chat Messages Area --}}
        <div class="flex-1 overflow-y-auto px-4 lg:px-6 py-4" x-ref="chatContainer" x-show="messages.length > 0">
            <div class="max-w-3xl mx-auto space-y-4">
                <template x-for="msg in messages" :key="msg.id">
                    <div class="flex gap-3" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        {{-- AI Avatar --}}
                        <div x-show="msg.role === 'assistant'" class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>

                        {{-- Message Bubble --}}
                        <div class="rounded-2xl shadow-sm"
                             :class="msg.role === 'user'
                                 ? 'max-w-[75%] bg-indigo-600 text-white rounded-br-md px-4 py-3'
                                 : 'max-w-[85%] bg-white border border-gray-200 text-gray-800 rounded-bl-md px-4 py-3'">
                            <div class="text-[13px] leading-relaxed whitespace-pre-wrap" x-html="formatMessage(msg.message)"></div>

                            {{-- Task Preview Cards --}}
                            <template x-if="msg.tasks_preview && msg.tasks_preview.length > 0">
                                <div class="mt-3 space-y-2">
                                    <p class="text-xs font-semibold text-indigo-600 flex items-center gap-1">📋 Preview Tugas — cek dulu sebelum ditambahkan:</p>
                                    <template x-for="(task, ti) in msg.tasks_preview" :key="ti">
                                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 relative group">
                                            {{-- Remove button --}}
                                            <button @click="removePreviewTask(msg.id, ti)" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-red-100 text-red-500 hover:bg-red-200 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs">&times;</button>
                                            <div class="flex items-start gap-2">
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-semibold text-sm text-gray-900" x-text="task.title"></p>
                                                    <p class="text-xs text-gray-500 mt-0.5" x-show="task.description" x-text="task.description"></p>
                                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium" :class="priorityColor(task.priority)" x-text="task.priority"></span>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium" x-text="kuadranLabel(task.kuadran)"></span>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-600" x-text="categoryLabel(task.category)"></span>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-700" x-show="task.due_date" x-text="'📅 ' + formatDate(task.due_date)"></span>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-700" x-show="task.due_time" x-text="'⏰ ' + (task.due_time || '')"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    {{-- Confirm / Cancel --}}
                                    <div class="flex items-center gap-2 pt-1">
                                        <button @click="confirmTasks(msg.id)" :disabled="confirmingTasks" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white text-xs font-semibold rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span x-text="confirmingTasks ? 'Menyimpan...' : 'Tambahkan Semua'"></span>
                                        </button>
                                        <span class="text-[10px] text-gray-400" x-text="msg.tasks_preview.length + ' tugas'"></span>
                                    </div>
                                </div>
                            </template>

                            {{-- Confirmed badge --}}
                            <template x-if="msg.tasks_confirmed">
                                <div class="mt-2 inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="msg.tasks_created_count + ' tugas ditambahkan'"></span>
                                </div>
                            </template>

                            <div class="text-[10px] mt-1.5 opacity-60" x-text="msg.time"></div>
                        </div>

                        {{-- User Avatar --}}
                        <div x-show="msg.role === 'user'" class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-indigo-600 text-xs font-bold">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        </div>
                    </div>
                </template>

                {{-- Typing Indicator --}}
                <div x-show="loading" class="flex gap-3 justify-start">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-md px-5 py-3 shadow-sm">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Bar --}}
        <div class="flex-shrink-0 border-t border-gray-200 bg-white px-4 lg:px-6 py-3">
            <div class="max-w-3xl mx-auto">
                <form @submit.prevent="sendMessage" class="flex items-end gap-3">
                    <div class="flex-1 relative">
                        <textarea
                            x-model="newMessage"
                            placeholder="Ketik pesan..."
                            rows="1"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none pr-4 py-3 max-h-32"
                            @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                            @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"
                        ></textarea>
                    </div>
                    <button type="submit"
                            :disabled="!newMessage.trim() || loading"
                            class="w-11 h-11 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl flex items-center justify-center transition-colors flex-shrink-0 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
                <p class="text-[10px] text-gray-400 mt-1.5 text-center">Enter untuk kirim, Shift+Enter baris baru. Powered by AI (Gemini)</p>
            </div>
        </div>
    </div>

    {{-- JS loaded from resources/js/pages/ai-chat.js --}}
</x-app-layout>
