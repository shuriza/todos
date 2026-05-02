<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Asisten Pintar</h2>
                <p class="text-sm text-gray-500">Tanya apa saja tentang tugas dan produktivitas</p>
            </div>
            <button @click="$dispatch('clear-chat')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Chat Baru
            </button>
        </div>
    </x-slot>

    <div class="flex flex-col h-[calc(100vh-140px)]" x-data="chatBot()" @clear-chat.window="clearChat()">

        {{-- Empty State: Quick Actions --}}
        <div class="flex-1 flex items-center justify-center px-4" x-show="messages.length === 0" x-cloak>
            <div class="max-w-lg w-full">
                <p class="text-center text-gray-500 text-sm mb-6">Pilih topik atau ketik langsung di bawah</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button @click="quickAction('daily-planning')" class="text-left px-4 py-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition-colors">
                        <p class="text-sm font-medium text-gray-800">Rencana Hari Ini</p>
                        <p class="text-xs text-gray-500 mt-0.5">Susun prioritas tugas hari ini</p>
                    </button>
                    <button @click="quickAction('productivity-tips')" class="text-left px-4 py-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors">
                        <p class="text-sm font-medium text-gray-800">Tips Produktivitas</p>
                        <p class="text-xs text-gray-500 mt-0.5">Saran meningkatkan efisiensi</p>
                    </button>
                    <button @click="quickAction('task-breakdown')" class="text-left px-4 py-3 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50/50 transition-colors">
                        <p class="text-sm font-medium text-gray-800">Breakdown Tugas</p>
                        <p class="text-xs text-gray-500 mt-0.5">Pecah tugas besar jadi langkah kecil</p>
                    </button>
                    <button @click="quickAction('eisenhower-analysis')" class="text-left px-4 py-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50/50 transition-colors">
                        <p class="text-sm font-medium text-gray-800">Analisis Eisenhower</p>
                        <p class="text-xs text-gray-500 mt-0.5">Evaluasi penempatan kuadran</p>
                    </button>
                    <button @click="quickAction('create-tasks')" class="text-left px-4 py-3 rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50/50 transition-colors sm:col-span-2">
                        <p class="text-sm font-medium text-gray-800">Buat Tugas Otomatis</p>
                        <p class="text-xs text-gray-500 mt-0.5">Buatkan tugas untuk beberapa hari ke depan</p>
                    </button>
                </div>
            </div>
        </div>

        {{-- Chat Messages --}}
        <div class="flex-1 overflow-y-auto px-4 lg:px-6 py-4" x-ref="chatContainer" x-show="messages.length > 0" x-cloak>
            <div class="max-w-2xl mx-auto space-y-3">
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start gap-2.5'">
                        {{-- Assistant indicator --}}
                        <div x-show="msg.role === 'assistant'" class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>

                        {{-- Bubble --}}
                        <div :class="msg.role === 'user'
                                ? 'max-w-[75%] bg-indigo-600 text-white rounded-2xl rounded-br-md px-4 py-2.5'
                                : 'max-w-[85%] bg-gray-50 border border-gray-200 text-gray-800 rounded-2xl rounded-bl-md px-4 py-2.5'">
                            <div class="text-sm leading-relaxed whitespace-pre-wrap" x-html="formatMessage(msg.message)"></div>

                            {{-- Task Preview --}}
                            <template x-if="msg.tasks_preview && msg.tasks_preview.length > 0">
                                <div class="mt-2.5 space-y-1.5 border-t pt-2.5" :class="msg.role === 'user' ? 'border-indigo-500' : 'border-gray-200'">
                                    <p class="text-xs text-gray-500 font-medium">Preview tugas:</p>
                                    <template x-for="(task, ti) in msg.tasks_preview" :key="ti">
                                        <div class="bg-white rounded-lg p-2.5 border border-gray-200 relative group">
                                            <button @click="removePreviewTask(msg.id, ti)" class="absolute top-1 right-1 w-4 h-4 rounded-full bg-gray-100 text-gray-400 hover:bg-red-100 hover:text-red-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-[10px]">&times;</button>
                                            <p class="font-medium text-sm text-gray-900 pr-5" x-text="task.title"></p>
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                <span class="text-[10px] px-1.5 py-0.5 rounded" :class="priorityColor(task.priority)" x-text="task.priority"></span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600" x-text="kuadranLabel(task.kuadran)"></span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600" x-show="task.due_date" x-text="formatDate(task.due_date)"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <button @click="confirmTasks(msg.id)" :disabled="confirmingTasks" class="mt-1 inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-xs font-medium rounded-lg transition-colors">
                                        <span x-text="confirmingTasks ? 'Menyimpan...' : 'Tambahkan ' + msg.tasks_preview.length + ' tugas'"></span>
                                    </button>
                                </div>
                            </template>

                            {{-- Confirmed --}}
                            <template x-if="msg.tasks_confirmed">
                                <div class="mt-2 inline-flex items-center gap-1 px-2 py-1 bg-green-50 text-green-700 text-xs rounded border border-green-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="msg.tasks_created_count + ' tugas ditambahkan'"></span>
                                </div>
                            </template>

                            <p class="text-[10px] mt-1 opacity-40" x-text="msg.time"></p>
                        </div>
                    </div>
                </template>

                {{-- Typing --}}
                <div x-show="loading" x-cloak class="flex gap-2.5 justify-start">
                    <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl rounded-bl-md px-4 py-3">
                        <div class="flex items-center gap-1">
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="flex-shrink-0 border-t border-gray-100 bg-white px-4 lg:px-6 py-3">
            <div class="max-w-2xl mx-auto">
                <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                    <textarea
                        x-model="newMessage"
                        placeholder="Ketik pesan..."
                        rows="1"
                        class="flex-1 rounded-lg border-gray-200 text-sm resize-none py-2.5 px-3 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 max-h-28"
                        @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                        @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 112) + 'px'"
                    ></textarea>
                    <button type="submit"
                            :disabled="!newMessage.trim() || loading"
                            class="px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-200 disabled:text-gray-400 text-white rounded-lg transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
