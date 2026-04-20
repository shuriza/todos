<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Asisten Pintar</h2>
                <p class="text-sm text-gray-500">Bantu atur tugas, jadwal, dan produktivitas</p>
            </div>
            <button @click="$dispatch('clear-chat')" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Chat Baru
            </button>
        </div>
    </x-slot>

    <div class="flex flex-col h-[calc(100vh-140px)]" x-data="chatBot()" @clear-chat.window="clearChat()">
        {{-- Quick Actions (empty state) --}}
        <div class="px-4 lg:px-6 pt-6 flex-shrink-0" x-show="messages.length === 0" x-cloak>
            <div class="max-w-2xl mx-auto">
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Ada yang bisa dibantu?</h3>
                    <p class="text-sm text-gray-500">Pilih salah satu atau ketik langsung di bawah</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button @click="quickAction('daily-planning')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-100 transition-colors">
                                <svg class="w-4.5 h-4.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Rencana Hari Ini</p>
                                <p class="text-xs text-gray-500">Susun prioritas tugas untuk hari ini</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('productivity-tips')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                                <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Tips Produktivitas</p>
                                <p class="text-xs text-gray-500">Saran meningkatkan efisiensi belajar</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('task-breakdown')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-green-300 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-green-100 transition-colors">
                                <svg class="w-4.5 h-4.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Breakdown Tugas</p>
                                <p class="text-xs text-gray-500">Pecah tugas besar jadi langkah kecil</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('eisenhower-analysis')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-purple-300 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
                                <svg class="w-4.5 h-4.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Analisis Eisenhower</p>
                                <p class="text-xs text-gray-500">Evaluasi penempatan kuadran tugas</p>
                            </div>
                        </div>
                    </button>
                    <button @click="quickAction('create-tasks')" class="text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-orange-300 hover:shadow-sm transition-all group sm:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-orange-100 transition-colors">
                                <svg class="w-4.5 h-4.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Buat Tugas Otomatis</p>
                                <p class="text-xs text-gray-500">Buatkan tugas untuk beberapa hari ke depan, preview dulu sebelum disimpan</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Chat Messages Area --}}
        <div class="flex-1 overflow-y-auto px-4 lg:px-6 py-4" x-ref="chatContainer" x-show="messages.length > 0" x-cloak>
            <div class="max-w-2xl mx-auto space-y-4">
                <template x-for="msg in messages" :key="msg.id">
                    <div class="flex gap-3" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        {{-- Assistant Avatar --}}
                        <div x-show="msg.role === 'assistant'" class="w-7 h-7 bg-indigo-600 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>

                        {{-- Message Bubble --}}
                        <div class="rounded-2xl"
                             :class="msg.role === 'user'
                                 ? 'max-w-[75%] bg-indigo-600 text-white rounded-br-sm px-4 py-3'
                                 : 'max-w-[85%] bg-white border border-gray-200 text-gray-800 rounded-bl-sm px-4 py-3'">
                            <div class="text-sm leading-relaxed whitespace-pre-wrap" x-html="formatMessage(msg.message)"></div>

                            {{-- Task Preview Cards --}}
                            <template x-if="msg.tasks_preview && msg.tasks_preview.length > 0">
                                <div class="mt-3 space-y-2 border-t border-gray-100 pt-3">
                                    <p class="text-xs font-medium text-gray-500">Preview tugas — cek sebelum ditambahkan:</p>
                                    <template x-for="(task, ti) in msg.tasks_preview" :key="ti">
                                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 relative group">
                                            <button @click="removePreviewTask(msg.id, ti)" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-red-100 text-red-500 hover:bg-red-200 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs">&times;</button>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-sm text-gray-900" x-text="task.title"></p>
                                                <p class="text-xs text-gray-500 mt-0.5" x-show="task.description" x-text="task.description"></p>
                                                <div class="flex flex-wrap gap-1.5 mt-2">
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-medium" :class="priorityColor(task.priority)" x-text="task.priority"></span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-medium" x-text="kuadranLabel(task.kuadran)"></span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600" x-text="categoryLabel(task.category)"></span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600" x-show="task.due_date" x-text="formatDate(task.due_date)"></span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600" x-show="task.due_time" x-text="task.due_time"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="flex items-center gap-2 pt-1">
                                        <button @click="confirmTasks(msg.id)" :disabled="confirmingTasks" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-xs font-medium rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span x-text="confirmingTasks ? 'Menyimpan...' : 'Tambahkan Semua'"></span>
                                        </button>
                                        <span class="text-[10px] text-gray-400" x-text="msg.tasks_preview.length + ' tugas'"></span>
                                    </div>
                                </div>
                            </template>

                            {{-- Confirmed badge --}}
                            <template x-if="msg.tasks_confirmed">
                                <div class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 bg-green-50 text-green-700 text-xs rounded-lg border border-green-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="msg.tasks_created_count + ' tugas ditambahkan'"></span>
                                </div>
                            </template>

                            <div class="text-[10px] mt-1.5 opacity-50" x-text="msg.time"></div>
                        </div>

                        {{-- User Avatar --}}
                        <div x-show="msg.role === 'user'" class="w-7 h-7 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-gray-600 text-[10px] font-semibold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                        </div>
                    </div>
                </template>

                {{-- Typing Indicator --}}
                <div x-show="loading" x-cloak class="flex gap-3 justify-start">
                    <div class="w-7 h-7 bg-indigo-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-sm px-4 py-3">
                        <div class="flex items-center gap-1">
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Bar --}}
        <div class="flex-shrink-0 border-t border-gray-200 bg-white px-4 lg:px-6 py-3">
            <div class="max-w-2xl mx-auto">
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
                            class="w-10 h-10 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl flex items-center justify-center transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
                <p class="text-[10px] text-gray-400 mt-1.5 text-center">Enter untuk kirim, Shift+Enter baris baru</p>
            </div>
        </div>
    </div>
</x-app-layout>
