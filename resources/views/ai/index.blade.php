<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('AI Assistant') }}
            </h2>
            <a href="{{ route('todos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                Back to Todos
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="aiAssistant()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Quick Actions -->
                <div class="p-4 border-b bg-gradient-to-r from-purple-50 to-blue-50">
                    <div class="flex gap-2 flex-wrap">
                        <button 
                            @click="quickAction('daily-planning')"
                            class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            📅 Daily Planning
                        </button>
                        <button 
                            @click="quickAction('productivity-tips')"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            💡 Productivity Tips
                        </button>
                        <button 
                            @click="quickAction('task-breakdown')"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            ✂️ Break Down Tasks
                        </button>
                        <button 
                            @click="clearChat()"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            🗑️ New Chat
                        </button>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="h-96 overflow-y-auto p-6 space-y-4" x-ref="chatContainer">
                    <template x-for="msg in messages" :key="msg.id">
                        <div 
                            class="flex gap-3"
                            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <div 
                                class="max-w-[80%] rounded-lg p-4"
                                :class="msg.role === 'user' 
                                    ? 'bg-indigo-500 text-white' 
                                    : 'bg-gray-100 text-gray-900'"
                            >
                                <div class="text-xs opacity-75 mb-1" x-text="msg.role === 'user' ? 'You' : 'AI Assistant'"></div>
                                <div class="whitespace-pre-wrap" x-html="formatMessage(msg.message)"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Loading indicator -->
                    <div x-show="loading" class="flex gap-3 justify-start">
                        <div class="bg-gray-100 text-gray-900 rounded-lg p-4">
                            <div class="flex gap-2">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Form -->
                <div class="p-4 border-t">
                    <form @submit.prevent="sendMessage" class="flex gap-2">
                        <textarea 
                            x-model="newMessage" 
                            placeholder="Ask me anything about your tasks, planning, or productivity..."
                            class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"
                            rows="2"
                            @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                        ></textarea>
                        <button 
                            type="submit"
                            :disabled="!newMessage.trim() || loading"
                            class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Send
                        </button>
                    </form>
                    <div class="text-xs text-gray-500 mt-2">
                        💡 Tip: Press Enter to send, Shift+Enter for new line
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="mt-4 bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 class="font-semibold text-purple-900 mb-2">✨ What can I help you with?</h3>
                <ul class="text-sm text-purple-800 space-y-1">
                    <li>• Plan your day effectively with AI reasoning</li>
                    <li>• Break down complex tasks into manageable steps</li>
                    <li>• Get productivity tips and time management advice</li>
                    <li>• Analyze your task priorities</li>
                    <li>• Brainstorm ideas and solutions</li>
                </ul>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function aiAssistant() {
            return {
                messages: [],
                newMessage: '',
                loading: false,
                sessionId: null,

                init() {
                    this.sessionId = 'session_' + Date.now();
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.loading) return;

                    const userMessage = this.newMessage.trim();
                    this.messages.push({
                        id: Date.now(),
                        role: 'user',
                        message: userMessage
                    });
                    
                    this.newMessage = '';
                    this.loading = true;
                    this.scrollToBottom();

                    try {
                        const response = await fetch('/ai/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                message: userMessage,
                                session_id: this.sessionId
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.sessionId = data.session_id;
                            this.messages.push({
                                id: Date.now() + 1,
                                role: 'assistant',
                                message: data.message
                            });
                        } else {
                            this.messages.push({
                                id: Date.now() + 1,
                                role: 'assistant',
                                message: 'Sorry, I encountered an error. Please try again.'
                            });
                        }
                    } catch (error) {
                        console.error('Failed to send message:', error);
                        this.messages.push({
                            id: Date.now() + 1,
                            role: 'assistant',
                            message: 'Sorry, something went wrong. Please check your API key and try again.'
                        });
                    } finally {
                        this.loading = false;
                        this.scrollToBottom();
                    }
                },

                async quickAction(action) {
                    const prompts = {
                        'daily-planning': 'Help me plan my day. Look at my current tasks and suggest a prioritized action plan.',
                        'productivity-tips': 'Give me 5 productivity tips that can help me complete my tasks more efficiently today.',
                        'task-breakdown': 'Help me break down my complex tasks into smaller, manageable steps.'
                    };

                    this.newMessage = prompts[action];
                    await this.sendMessage();
                },

                clearChat() {
                    if (this.messages.length > 0 && !confirm('Start a new chat? Current conversation will be lost.')) {
                        return;
                    }
                    this.messages = [];
                    this.sessionId = 'session_' + Date.now();
                },

                formatMessage(text) {
                    // Simple markdown-like formatting
                    return text
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/\n/g, '<br>');
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.chatContainer;
                        container.scrollTop = container.scrollHeight;
                    });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
