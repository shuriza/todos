/**
 * ============================================
 * Entry Point Aplikasi — app.js
 * ============================================
 * File utama JavaScript yang di-bundle oleh Vite.
 * Menginisialisasi Alpine.js, mengimpor semua komponen halaman,
 * dan mendaftarkan toast notification manager global.
 */

import './bootstrap';

import Alpine from 'alpinejs';

/**
 * Import page-specific Alpine.js components
 * Setiap file mendaftarkan fungsi global (window.xxx)
 * yang digunakan oleh x-data di Blade views
 */
import './pages/dashboard';
import './pages/todos';
import './pages/calendar';
import './pages/telegram';
import './pages/ai-chat';
import './pages/report';

/**
 * Toast Notification Manager
 * Digunakan oleh container di layouts/app.blade.php.
 * Trigger dari mana saja via: window.toast(message, type)
 */
window.toastManager = function () {
    return {
        toasts: [],

        add({ message, type = 'success', duration = 3000, id }) {
            const toast = { id: id ?? Date.now(), message, type, visible: true };
            this.toasts.push(toast);
            if (duration > 0) {
                setTimeout(() => this.remove(toast.id), duration);
            }
        },

        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
