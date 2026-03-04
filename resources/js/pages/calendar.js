/**
 * ============================================
 * Calendar Page — calendarApp()
 * ============================================
 * Halaman: calendar/index.blade.php
 * Fitur: Kalender bulanan, navigasi bulan, event dari API, detail modal
 * Data: Membaca dari <script id="calendar-data"> JSON block
 */

import {
    formatDate,
    formatDateFull,
    getKuadranLabel,
    getKuadranShort,
    getKuadranBadgeClass,
    readJsonData,
} from '../helpers';

window.calendarApp = function () {
    const data = readJsonData('calendar-data') || {};

    return {
        // State
        month: data.month || new Date().getMonth() + 1,
        year: data.year || new Date().getFullYear(),
        events: [],
        overdue: data.overdue || [],
        upcoming: data.upcoming || [],
        selectedDate: null,
        showDetailModal: false,
        detailTask: null,

        // Constants
        monthNames: [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ],

        // --- Lifecycle ---
        init() {
            this.loadEvents();
        },

        // --- API ---
        async loadEvents() {
            // Load events for the full 6-week grid range (includes prev/next month padding)
            const firstDay = new Date(this.year, this.month - 1, 1);
            let startWeekday = firstDay.getDay();
            startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

            const gridStart = new Date(this.year, this.month - 1, 1 - startWeekday);
            const gridEnd = new Date(gridStart);
            gridEnd.setDate(gridEnd.getDate() + 41); // 42 cells total

            const url = `/calendar/events?start=${this.fmtDate(gridStart)}&end=${this.fmtDate(gridEnd)}`;
            try {
                const res = await fetch(url);
                this.events = await res.json();
            } catch (e) {
                console.error(e);
            }
        },

        // --- Calendar Grid ---
        fmtDate(d) {
            return (
                d.getFullYear() +
                '-' +
                String(d.getMonth() + 1).padStart(2, '0') +
                '-' +
                String(d.getDate()).padStart(2, '0')
            );
        },

        get calendarCells() {
            const cells = [];
            const firstDay = new Date(this.year, this.month - 1, 1);
            const lastDay = new Date(this.year, this.month, 0);

            // Konversi ke Monday=0 (default JS: Sunday=0)
            let startWeekday = firstDay.getDay();
            startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Tanggal bulan sebelumnya
            const prevLastDay = new Date(this.year, this.month - 1, 0).getDate();
            for (let i = startWeekday - 1; i >= 0; i--) {
                const day = prevLastDay - i;
                const date = new Date(this.year, this.month - 2, day);
                const dateStr = this.fmtDate(date);
                const tasks = this.events.filter((e) => e.date === dateStr);
                cells.push({
                    day,
                    isCurrentMonth: false,
                    isToday: false,
                    date: dateStr,
                    tasks,
                });
            }

            // Tanggal bulan ini
            for (let d = 1; d <= lastDay.getDate(); d++) {
                const date = new Date(this.year, this.month - 1, d);
                const dateStr = this.fmtDate(date);
                const tasks = this.events.filter((e) => e.date === dateStr);
                cells.push({
                    day: d,
                    isCurrentMonth: true,
                    isToday: date.getTime() === today.getTime(),
                    date: dateStr,
                    tasks,
                });
            }

            // Tanggal bulan berikutnya (isi sampai 42 cell = 6 minggu)
            const remaining = 42 - cells.length;
            for (let d = 1; d <= remaining; d++) {
                const date = new Date(this.year, this.month, d);
                const dateStr = this.fmtDate(date);
                const tasks = this.events.filter((e) => e.date === dateStr);
                cells.push({
                    day: d,
                    isCurrentMonth: false,
                    isToday: false,
                    date: dateStr,
                    tasks,
                });
            }

            return cells;
        },

        get selectedDateTasks() {
            if (!this.selectedDate) return [];
            return this.events.filter((e) => e.date === this.selectedDate);
        },

        // --- Navigation ---
        selectDate(d) {
            this.selectedDate = d;
        },

        openDetail(task) {
            this.detailTask = task;
            this.showDetailModal = true;
        },

        prevMonth() {
            if (this.month === 1) {
                this.month = 12;
                this.year--;
            } else {
                this.month--;
            }
            this.loadEvents();
            this.selectedDate = null;
        },

        nextMonth() {
            if (this.month === 12) {
                this.month = 1;
                this.year++;
            } else {
                this.month++;
            }
            this.loadEvents();
            this.selectedDate = null;
        },

        // --- Formatters ---
        formatDate(d) {
            return formatDate(d);
        },

        formatDateFull(d) {
            return formatDateFull(d);
        },

        getKuadranLabel(k) {
            return getKuadranLabel(k);
        },

        getKuadranShort(k) {
            return getKuadranShort(k);
        },

        getKuadranClass(k) {
            return getKuadranBadgeClass(k);
        },
    };
};
