/**
 * report.js - Alpine.js component untuk halaman Laporan & Analitik
 * 
 * Alur:
 *   1. init() -> parse data JSON dari server (embedded di Blade via <script type="application/json">)
 *   2. renderAllCharts() -> render chart tren utama
 *   3. changePeriod() -> AJAX fetch ke /laporan/chart-data -> update data dan chart
 * 
 * Dependencies:
 *   - Chart.js (imported via npm)
 *   - Alpine.js (global, dari app.js)
 *   - Axios (global, dari bootstrap.js)
 * 
 * Chart instances disimpan di this.charts{} agar bisa di-destroy saat re-render.
 * Halaman sengaja dibuat ringkas: satu chart utama dan ringkasan angka penting.
 */

import Chart from 'chart.js/auto';

window.reportApp = function () {
    return {
        // State
        period: '30d',
        loading: false,
        data: {
            overview: { total: 0, completed: 0, pending: 0, overdue: 0, completion_rate: 0, on_time_rate: null },
            trend: [],
            kuadran: { q1: 0, q2: 0, q3: 0, q4: 0 },
            priority: { high: 0, low: 0 },
            category: {},
            source: { manual: 0, google_classroom: 0 },
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
                    // Parse error — data tetap pakai default
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
                // Fetch error — biarkan data lama
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
        // HELPER FUNCTIONS
        // =====================================================================

        currentPeriodLabel() {
            return this.periods.find(p => p.value === this.period)?.label || '30 Hari';
        },

        kuadranSummary() {
            const k = this.data.kuadran || {};
            const items = [
                { label: 'Q1 Lakukan Sekarang', value: k.q1 || 0, color: 'bg-red-500' },
                { label: 'Q2 Jadwalkan', value: k.q2 || 0, color: 'bg-blue-500' },
                { label: 'Q3 Delegasikan', value: k.q3 || 0, color: 'bg-yellow-500' },
                { label: 'Q4 Eliminasi', value: k.q4 || 0, color: 'bg-gray-500' },
            ];
            const total = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => ({
                ...item,
                percent: total > 0 ? Math.round((item.value / total) * 100) : 0,
            }));
        },

        prioritySummary() {
            const p = this.data.priority || {};
            return [
                { label: 'Tinggi', value: p.high || 0, textColor: 'text-red-600' },
                { label: 'Rendah', value: p.low || 0, textColor: 'text-green-600' },
            ];
        },

        topCategories() {
            const cat = this.data.category || {};
            return Object.entries(cat)
                .map(([label, value]) => ({ label: this.translateCategory(label), value }))
                .sort((a, b) => b.value - a.value)
                .slice(0, 3);
        },

        translateCategory(cat) {
            const map = {
                'kuliah': 'Kuliah',
                'pekerjaan': 'Pekerjaan',
                'daily_activity': 'Daily Activity',
                '': 'Tanpa Kategori',
            };
            return map[cat] || cat || 'Tanpa Kategori';
        },
    };
};
