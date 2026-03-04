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

window.Alpine = Alpine;

Alpine.start();
