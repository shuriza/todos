/**
 * ============================================
 * Dashboard Page — dashboardApp()
 * ============================================
 * Halaman: home.blade.php
 * Fitur: Matriks Eisenhower, detail tugas, tambah tugas, pindah kuadran
 */

import { apiHeaders, formatDate, getKuadranLabel } from '../helpers';

window.dashboardApp = function () {
    return {
        // State
        showDetailModal: false,
        showAddModal: false,
        selectedTask: null,
        newTask: {
            title: '',
            description: '',
            category: 'kuliah',
            priority: 'medium',
            due_date: '',
            due_time: '',
        },

        // --- Detail Modal ---
        openDetail(task) {
            this.selectedTask = task;
            this.showDetailModal = true;
        },

        getKuadranName(k) {
            return getKuadranLabel(k);
        },

        formatDate(d) {
            return formatDate(d);
        },

        // --- Actions ---
        async toggleComplete(task) {
            if (!task) return;
            const newStatus = task.status === 'completed' ? 'todo' : 'completed';
            try {
                const res = await fetch(`/todos/${task.id}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ status: newStatus }),
                });
                if (res.ok) location.reload();
                else {
                    const data = await res.json();
                    alert(data.message || 'Gagal mengubah status');
                }
            } catch (e) {
                console.error(e);
            }
        },

        async moveToKuadran(taskId, kuadran) {
            try {
                const res = await fetch(`/todos/${taskId}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ kuadran }),
                });
                if (res.ok) location.reload();
                else {
                    const data = await res.json();
                    alert(data.message || 'Gagal pindah kuadran');
                }
            } catch (e) {
                console.error(e);
            }
        },

        async addTask() {
            try {
                const res = await fetch('/todos', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify(this.newTask),
                });
                if (res.ok) {
                    this.showAddModal = false;
                    location.reload();
                } else {
                    const data = await res.json();
                    alert(
                        data.message ||
                        Object.values(data.errors || {}).flat().join('\n') ||
                        'Gagal menambah tugas'
                    );
                }
            } catch (e) {
                console.error(e);
                alert('Gagal menambah tugas');
            }
        },
    };
};
