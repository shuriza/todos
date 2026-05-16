/**
 * ============================================
 * Telegram Settings — telegramSettings()
 * ============================================
 * Halaman: profile/edit.blade.php
 * Fitur: Simpan Chat ID, tes notifikasi, preferensi, riwayat notifikasi
 * Data: Membaca dari <script id="telegram-data"> JSON block
 */

import { readJsonData, formatDateTime, toast } from '../helpers';

window.telegramSettings = function () {
    const data = readJsonData('telegram-data') || {};

    return {
        // Connection state
        chatId: data.chatId || '',
        connected: data.connected || false,

        // Save Chat ID
        saving: false,

        // Test notification
        testing: false,
        testMessage: '',
        testSuccess: false,

        // Preferences
        prefMessage: '',
        prefSuccess: false,
        prefTimeout: null,
        prefs: data.prefs || {
            deadline_reminder: true,
            daily_summary: false,
            overdue_alert: true,
            classroom_sync: true,
            reminder_hours: 2,
            overdue_max_days: 7,
            overdue_cooldown_hours: 24,
            daily_summary_time: '07:00',
        },

        // History & Stats
        stats: { sent: 0, failed: 0, today: 0 },
        historyItems: [],
        loadingHistory: false,

        // Routes (dari server)
        routes: data.routes || {},

        // --- Lifecycle ---
        init() {
            if (this.connected) {
                this.loadStats();
                this.loadHistory();
            }
        },

        // --- Save Chat ID ---
        async saveChatId() {
            this.saving = true;
            try {
                const res = await fetch(this.routes.saveChatId, {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ telegram_chat_id: this.chatId }),
                });
                const result = await res.json();
                if (res.ok && result.success) {
                    toast(result.message || 'Chat ID berhasil disimpan!');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toast(result.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                toast('Terjadi kesalahan jaringan', 'error');
            }
            this.saving = false;
        },

        // --- Test Notification ---
        async testNotification() {
            this.testing = true;
            this.testMessage = '';
            try {
                const res = await fetch(this.routes.test, {
                    method: 'POST',
                    headers: this._headers(),
                });
                const result = await res.json();
                this.testMessage = result.message;
                this.testSuccess = res.ok && result.success;
                if (this.testSuccess) this.loadStats();
            } catch (e) {
                this.testMessage = 'Terjadi kesalahan jaringan';
                this.testSuccess = false;
            }
            this.testing = false;
            setTimeout(() => { this.testMessage = ''; }, 5000);
        },

        // --- Preferences ---
        autoSavePrefs() {
            clearTimeout(this.prefTimeout);
            this.prefTimeout = setTimeout(() => this.savePrefs(), 500);
        },

        async savePrefs() {
            this.prefMessage = '';
            try {
                const res = await fetch(this.routes.preferences, {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify(this.prefs),
                });
                const result = await res.json();
                if (res.ok && result.success) {
                    this.prefMessage = '✓ Preferensi disimpan';
                    this.prefSuccess = true;
                } else {
                    this.prefMessage = result.message || 'Gagal menyimpan preferensi';
                    this.prefSuccess = false;
                }
            } catch (e) {
                this.prefMessage = 'Terjadi kesalahan jaringan';
                this.prefSuccess = false;
            }
            setTimeout(() => { this.prefMessage = ''; }, 3000);
        },

        // --- Stats & History ---
        async loadStats() {
            try {
                const res = await fetch(this.routes.stats, {
                    headers: { 'Accept': 'application/json' },
                });
                const result = await res.json();
                if (result.success) this.stats = result.stats;
            } catch (e) {
                // silent fail
            }
        },

        async loadHistory() {
            this.loadingHistory = true;
            try {
                const res = await fetch(this.routes.history, {
                    headers: { 'Accept': 'application/json' },
                });
                const result = await res.json();
                if (result.success) {
                    this.historyItems = result.data.data.slice(0, 10);
                }
            } catch (e) {
                // silent fail
            }
            this.loadingHistory = false;
        },

        // --- Disconnect ---
        async disconnectTelegram() {
            try {
                const res = await fetch(this.routes.disconnect, {
                    method: 'POST',
                    headers: this._headers(),
                });
                const result = await res.json();
                if (res.ok && result.success) {
                    toast('Telegram berhasil diputuskan');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toast(result.message || 'Gagal memutuskan Telegram', 'error');
                }
            } catch (e) {
                toast('Terjadi kesalahan jaringan', 'error');
            }
        },

        // --- Helpers ---
        notificationTitle(item) {
            if (item.todo?.title) return item.todo.title;

            const message = (item.pesan || '').replace(/<[^>]*>/g, '').trim();
            const firstLine = message.split('\n').find(line => line.trim())?.trim();

            return firstLine || 'Notifikasi Telegram';
        },

        formatDate(dateStr) {
            return formatDateTime(dateStr);
        },

        _headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            };
        },
    };
};
