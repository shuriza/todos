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
import './pages/archive';

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

/**
 * Confirm Dialog Manager
 * Pengganti window.confirm() bawaan browser.
 * Menangkap CustomEvent 'confirm' dari helper confirmDialog().
 */
window.confirmManager = function () {
    return {
        open: false,
        title: '',
        message: '',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'danger',
        _resolve: null,

        show(detail) {
            this.title = detail.title;
            this.message = detail.message;
            this.confirmText = detail.confirmText;
            this.cancelText = detail.cancelText;
            this.variant = detail.variant;
            this._resolve = detail.resolve;
            this.open = true;
        },

        confirm() {
            this._settle(true);
        },

        cancel() {
            this._settle(false);
        },

        _settle(result) {
            if (this._resolve) {
                this._resolve(result);
                this._resolve = null;
            }
            this.open = false;
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
