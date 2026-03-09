/**
 * ============================================
 * AI Chat Page — chatBot()
 * ============================================
 * Halaman: ai/index.blade.php
 * Fitur: Chat dengan AI, quick actions, format markdown, clear chat,
 *        task preview cards (confirm/remove sebelum dimasukkan ke daftar tugas)
 */

import { apiHeaders } from '../helpers';

window.chatBot = function () {
    return {
        // State
        messages: [],
        newMessage: '',
        loading: false,
        sessionId: null,
        confirmingTasks: false,

        // --- Lifecycle ---
        init() {
            this.sessionId = 'session_' + Date.now();
        },

        // --- Send Message ---
        async sendMessage() {
            if (!this.newMessage.trim() || this.loading) return;

            const text = this.newMessage.trim();
            this.messages.push({
                id: Date.now(),
                role: 'user',
                message: text,
                time: this._getTime(),
            });
            this.newMessage = '';
            this.loading = true;
            this._scrollToBottom();

            try {
                const res = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify({
                        message: text,
                        session_id: this.sessionId,
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    this.sessionId = data.session_id;

                    const msgObj = {
                        id: Date.now() + 1,
                        role: 'assistant',
                        message: data.message,
                        time: this._getTime(),
                    };

                    // Attach task previews if AI suggested tasks
                    if (data.tasks_preview && data.tasks_preview.length > 0) {
                        msgObj.tasks_preview = data.tasks_preview;
                    }

                    this.messages.push(msgObj);
                } else {
                    this._addErrorMessage(data.error || 'Maaf, terjadi kesalahan. Coba lagi ya.');
                }
            } catch (e) {
                console.error(e);
                this._addErrorMessage('Koneksi gagal. Periksa API key dan coba lagi.');
            } finally {
                this.loading = false;
                this._scrollToBottom();
            }
        },

        // --- Confirm all preview tasks ---
        async confirmTasks(msgId) {
            const msg = this.messages.find(m => m.id === msgId);
            if (!msg || !msg.tasks_preview || msg.tasks_preview.length === 0) return;

            this.confirmingTasks = true;

            try {
                const res = await fetch('/ai/confirm-tasks', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify({ tasks: msg.tasks_preview }),
                });
                const data = await res.json();

                if (data.success) {
                    // Replace tasks_preview with success state
                    msg.tasks_confirmed = true;
                    msg.tasks_created_count = data.count;
                    delete msg.tasks_preview;

                    this.messages.push({
                        id: Date.now() + 2,
                        role: 'assistant',
                        message: `✅ **${data.count} tugas berhasil ditambahkan** ke daftar tugas kamu!`,
                        time: this._getTime(),
                    });
                } else {
                    this._addErrorMessage('Gagal menambahkan tugas. Silakan coba lagi.');
                }
            } catch (e) {
                console.error(e);
                this._addErrorMessage('Koneksi gagal saat menambahkan tugas.');
            } finally {
                this.confirmingTasks = false;
                this._scrollToBottom();
            }
        },

        // --- Remove a single preview task ---
        removePreviewTask(msgId, taskIndex) {
            const msg = this.messages.find(m => m.id === msgId);
            if (!msg || !msg.tasks_preview) return;
            msg.tasks_preview.splice(taskIndex, 1);
        },

        // --- Quick Actions ---
        async quickAction(action) {
            const prompts = {
                'daily-planning':
                    'Tolong bantu saya merencanakan hari ini. Lihat tugas-tugas saya dan buat rencana prioritas yang efektif.',
                'productivity-tips':
                    'Berikan 5 tips produktivitas untuk mahasiswa yang bisa saya terapkan hari ini.',
                'task-breakdown':
                    'Bantu saya memecah tugas-tugas kompleks menjadi langkah-langkah kecil yang mudah dikerjakan.',
                'eisenhower-analysis':
                    'Analisis tugas-tugas saya menggunakan Matriks Eisenhower. Evaluasi apakah penempatan kuadran sudah tepat.',
                'create-tasks':
                    'Buatkan saya tugas-tugas untuk 7 hari kedepan berdasarkan tugas aktif saya saat ini.',
            };
            this.newMessage = prompts[action] || '';
            await this.sendMessage();
        },

        // --- Clear Chat ---
        clearChat() {
            if (this.messages.length > 0 && !confirm('Mulai chat baru? Percakapan saat ini akan hilang.')) return;
            this.messages = [];
            this.sessionId = 'session_' + Date.now();
        },

        // --- Format Markdown ---
        formatMessage(text) {
            if (!text) return '';
            const escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
            return escaped
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code class="bg-gray-100 text-indigo-700 px-1 rounded text-xs">$1</code>')
                .replace(/\n/g, '<br>');
        },

        // --- Priority helpers ---
        priorityColor(priority) {
            const colors = {
                high: 'bg-red-100 text-red-700',
                medium: 'bg-yellow-100 text-yellow-700',
                low: 'bg-green-100 text-green-700',
            };
            return colors[priority] || colors.medium;
        },

        kuadranLabel(k) {
            const labels = {
                1: 'Q1 — Do Now',
                2: 'Q2 — Schedule',
                3: 'Q3 — Delegate',
                4: 'Q4 — Eliminate',
            };
            return labels[k] || 'Q2 — Schedule';
        },

        categoryLabel(cat) {
            const labels = {
                kuliah: '📚 Kuliah',
                pekerjaan: '💼 Pekerjaan',
                daily_activity: '🏠 Daily',
            };
            return labels[cat] || '📚 Kuliah';
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
            });
        },

        // --- Private Helpers ---
        _getTime() {
            return new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        _scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) container.scrollTop = container.scrollHeight;
            });
        },

        _addErrorMessage(text) {
            this.messages.push({
                id: Date.now() + 1,
                role: 'assistant',
                message: text,
                time: this._getTime(),
            });
        },
    };
};
