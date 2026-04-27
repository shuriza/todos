/**
 * report.js - Alpine.js component untuk halaman Laporan & Analitik
 * 
 * Alur:
 *   1. init() -> parse data JSON dari server (embedded di Blade via <script type="application/json">)
 *   2. renderAllCharts() -> render semua Chart.js instances + heatmap
 *   3. changePeriod() -> AJAX fetch ke /laporan/chart-data -> update semua chart
 * 
 * Dependencies:
 *   - Chart.js (imported via npm)
 *   - Alpine.js (global, dari app.js)
 *   - Axios (global, dari bootstrap.js)
 * 
 * Chart instances disimpan di this.charts{} agar bisa di-destroy saat re-render.
 */

import Chart from 'chart.js/auto';

window.reportApp = function () {
    return {
        // State
        period: '30d',
        loading: false,
        data: {
            overview: { total: 0, completed: 0, pending: 0, overdue: 0, completion_rate: 0, avg_completion_hours: null },
            trend: [],
            kuadran: { q1: 0, q2: 0, q3: 0, q4: 0 },
            priority: { high: 0, medium: 0, low: 0 },
            category: {},
            source: { manual: 0, google_classroom: 0 },
            heatmap: [],
            streak: { current: 0, longest: 0 },
            slowest: [],
        },
        charts: {},

        // Pilihan periode
        periods: [
            { value: '7d', label: '7 Hari' },
            { value: '30d', label: '30 Hari' },
            { value: '90d', label: '3 Bulan' },
            { value: '180d', label: '6 Bulan' },
            { value: '365d', label: '1 Tahun' },
        ],

        // =====================================================================
        // LIFECYCLE
        // =====================================================================

        init() {
            // Parse data dari server (embedded JSON di Blade)
            const el = document.getElementById('report-data');
            if (el) {
                try {
                    this.data = JSON.parse(el.textContent);
                } catch (e) {
                    console.error('Failed to parse report data:', e);
                }
            }

            // Parse period dari URL jika ada
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('period')) {
                this.period = urlParams.get('period');
            }

            // Render chart setelah DOM ready
            this.$nextTick(() => {
                this.renderAllCharts();
            });
        },

        // =====================================================================
        // PERIOD CHANGE (AJAX)
        // =====================================================================

        async changePeriod(newPeriod) {
            if (this.period === newPeriod || this.loading) return;

            this.period = newPeriod;
            this.loading = true;

            try {
                const response = await axios.get('/laporan/chart-data', {
                    params: { period: newPeriod }
                });
                this.data = response.data;

                // Update URL tanpa reload
                const url = new URL(window.location);
                url.searchParams.set('period', newPeriod);
                window.history.replaceState({}, '', url);

                // Re-render semua chart
                this.$nextTick(() => {
                    this.destroyCharts();
                    this.renderAllCharts();
                });
            } catch (error) {
                console.error('Failed to fetch chart data:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'Gagal memuat data laporan', type: 'error' }
                }));
            } finally {
                this.loading = false;
            }
        },

        // =====================================================================
        // RENDER ALL CHARTS
        // =====================================================================

        renderAllCharts() {
            this.renderTrendChart();
            this.renderKuadranChart();
            this.renderPriorityChart();
            this.renderCategoryChart();
            this.renderSourceChart();
            this.renderHeatmap();
        },

        destroyCharts() {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            this.charts = {};
        },

        // =====================================================================
        // CHART 1: Tren Produktivitas (Line Chart)
        // =====================================================================

        renderTrendChart() {
            const ctx = document.getElementById('trendChart');
            if (!ctx) return;

            const trend = this.data.trend || [];
            const labels = trend.map(t => t.date);
            const created = trend.map(t => t.created);
            const completed = trend.map(t => t.completed);

            this.charts.trend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Dibuat',
                            data: created,
                            borderColor: '#818cf8',
                            backgroundColor: 'rgba(129, 140, 248, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: trend.length > 60 ? 0 : 3,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                        },
                        {
                            label: 'Diselesaikan',
                            data: completed,
                            borderColor: '#34d399',
                            backgroundColor: 'rgba(52, 211, 153, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: trend.length > 60 ? 0 : 3,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } },
                        tooltip: { backgroundColor: '#1f2937', titleFont: { size: 11 }, bodyFont: { size: 11 }, padding: 10, cornerRadius: 8 },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, maxRotation: 45, maxTicksLimit: 15 },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' },
                            ticks: { font: { size: 10 }, stepSize: 1 },
                        },
                    },
                },
            });
        },

        // =====================================================================
        // CHART 2: Distribusi Kuadran (Doughnut Chart)
        // =====================================================================

        renderKuadranChart() {
            const ctx = document.getElementById('kuadranChart');
            if (!ctx) return;

            const k = this.data.kuadran || {};

            this.charts.kuadran = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Q1 - Do Now', 'Q2 - Schedule', 'Q3 - Delegate', 'Q4 - Eliminate'],
                    datasets: [{
                        data: [k.q1 || 0, k.q2 || 0, k.q3 || 0, k.q4 || 0],
                        backgroundColor: ['#ef4444', '#3b82f6', '#eab308', '#6b7280'],
                        borderWidth: 0,
                        hoverOffset: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 10 } } },
                        tooltip: { backgroundColor: '#1f2937', padding: 10, cornerRadius: 8 },
                    },
                },
            });
        },

        // =====================================================================
        // CHART 3: Distribusi Prioritas (Bar Chart)
        // =====================================================================

        renderPriorityChart() {
            const ctx = document.getElementById('priorityChart');
            if (!ctx) return;

            const p = this.data.priority || {};

            this.charts.priority = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Tinggi', 'Sedang', 'Rendah'],
                    datasets: [{
                        label: 'Jumlah Tugas',
                        data: [p.high || 0, p.medium || 0, p.low || 0],
                        backgroundColor: ['#ef4444', '#eab308', '#22c55e'],
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1f2937', padding: 10, cornerRadius: 8 },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, stepSize: 1 } },
                    },
                },
            });
        },

        // =====================================================================
        // CHART 4: Distribusi Kategori (Horizontal Bar Chart)
        // =====================================================================

        renderCategoryChart() {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;

            const cat = this.data.category || {};
            const categoryLabels = {
                'kuliah': 'Kuliah',
                'pekerjaan': 'Pekerjaan',
                'daily_activity': 'Aktivitas Harian',
            };
            const colors = ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd'];

            const labels = Object.keys(cat).map(k => categoryLabels[k] || k);
            const values = Object.values(cat);

            this.charts.category = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Jumlah Tugas',
                        data: values,
                        backgroundColor: colors.slice(0, labels.length),
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.6,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1f2937', padding: 10, cornerRadius: 8 },
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, stepSize: 1 } },
                        y: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                    },
                },
            });
        },

        // =====================================================================
        // CHART 5: Sumber Tugas (Pie Chart)
        // =====================================================================

        renderSourceChart() {
            const ctx = document.getElementById('sourceChart');
            if (!ctx) return;

            const s = this.data.source || {};

            this.charts.source = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Manual', 'Google Classroom'],
                    datasets: [{
                        data: [s.manual || 0, s.google_classroom || 0],
                        backgroundColor: ['#6366f1', '#f59e0b'],
                        borderWidth: 0,
                        hoverOffset: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } },
                        tooltip: { backgroundColor: '#1f2937', padding: 10, cornerRadius: 8 },
                    },
                },
            });
        },

        // =====================================================================
        // HEATMAP (Calendar Heatmap mirip GitHub)
        // =====================================================================

        renderHeatmap() {
            const container = document.getElementById('heatmapContainer');
            if (!container) return;

            container.innerHTML = '';

            const heatmapData = this.data.heatmap || [];
            if (heatmapData.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">Belum ada data aktivitas</p>';
                return;
            }

            // Buat map date -> count
            const dateMap = {};
            let maxCount = 0;
            heatmapData.forEach(d => {
                dateMap[d.date] = d.count;
                if (d.count > maxCount) maxCount = d.count;
            });

            // Hitung grid: 53 minggu x 7 hari
            const today = new Date();
            const startDate = new Date(today);
            startDate.setFullYear(startDate.getFullYear() - 1);
            // Mulai dari hari Senin
            startDate.setDate(startDate.getDate() - ((startDate.getDay() + 6) % 7));

            const weeks = [];
            let currentDate = new Date(startDate);

            while (currentDate <= today) {
                const weekIdx = weeks.length === 0 ? 0 : weeks.length - 1;
                const dayOfWeek = (currentDate.getDay() + 6) % 7; // Senin = 0

                if (dayOfWeek === 0 || weeks.length === 0) {
                    weeks.push([]);
                }

                const dateStr = currentDate.toISOString().split('T')[0];
                const count = dateMap[dateStr] || 0;

                weeks[weeks.length - 1].push({
                    date: dateStr,
                    count: count,
                    dayOfWeek: dayOfWeek,
                });

                currentDate.setDate(currentDate.getDate() + 1);
            }

            // Render grid
            const cellSize = 12;
            const cellGap = 2;
            const labelWidth = 28;
            const totalWidth = labelWidth + (weeks.length * (cellSize + cellGap));
            const totalHeight = 7 * (cellSize + cellGap) + 20; // +20 untuk label bulan

            const dayLabels = ['Sen', '', 'Rab', '', 'Jum', '', 'Min'];
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            let svg = `<svg width="${totalWidth}" height="${totalHeight}" class="block">`;

            // Label hari (kiri)
            dayLabels.forEach((label, i) => {
                if (label) {
                    const y = 20 + i * (cellSize + cellGap) + cellSize - 2;
                    svg += `<text x="0" y="${y}" class="fill-gray-400" style="font-size:9px">${label}</text>`;
                }
            });

            // Label bulan (atas) + cells
            let lastMonth = -1;
            weeks.forEach((week, weekIdx) => {
                const x = labelWidth + weekIdx * (cellSize + cellGap);

                // Label bulan
                if (week.length > 0) {
                    const firstDay = new Date(week[0].date);
                    const month = firstDay.getMonth();
                    if (month !== lastMonth && firstDay.getDate() <= 7) {
                        svg += `<text x="${x}" y="12" class="fill-gray-400" style="font-size:9px">${monthNames[month]}</text>`;
                        lastMonth = month;
                    }
                }

                // Cells
                week.forEach(day => {
                    const y = 20 + day.dayOfWeek * (cellSize + cellGap);
                    const color = this.getHeatmapColor(day.count, maxCount);
                    const title = `${day.date}: ${day.count} tugas selesai`;
                    svg += `<rect x="${x}" y="${y}" width="${cellSize}" height="${cellSize}" rx="2" fill="${color}" class="hover:stroke-gray-400 hover:stroke-1 cursor-pointer"><title>${title}</title></rect>`;
                });
            });

            svg += '</svg>';
            container.innerHTML = svg;
        },

        getHeatmapColor(count, maxCount) {
            if (count === 0) return '#f3f4f6';
            if (maxCount === 0) return '#f3f4f6';

            const intensity = count / maxCount;
            if (intensity <= 0.25) return '#bbf7d0'; // green-200
            if (intensity <= 0.5) return '#4ade80';  // green-400
            if (intensity <= 0.75) return '#16a34a'; // green-600
            return '#15803d';                         // green-700
        },

        // =====================================================================
        // HELPER FUNCTIONS
        // =====================================================================

        formatAvgTime(hours) {
            if (hours === null || hours === undefined) return '-';
            if (hours < 1) return Math.round(hours * 60) + 'm';
            if (hours < 24) return Math.round(hours * 10) / 10 + 'j';
            return Math.round((hours / 24) * 10) / 10 + 'h';
        },

        formatHours(hours) {
            if (hours < 1) return Math.round(hours * 60) + ' menit';
            if (hours < 24) return Math.round(hours * 10) / 10 + ' jam';
            return Math.round((hours / 24) * 10) / 10 + ' hari';
        },

        translateCategory(cat) {
            const map = {
                'kuliah': 'Kuliah',
                'pekerjaan': 'Pekerjaan',
                'daily_activity': 'Aktivitas Harian',
            };
            return map[cat] || cat;
        },
    };
};
