/**
 * ============================================
 * Dashboard Page — dashboardApp()
 * ============================================
 * Halaman: home.blade.php
 * Fitur: Matriks Eisenhower, detail tugas, tambah tugas, pindah kuadran
 */

import { apiHeaders, formatDate, getKuadranLabel, toast } from '../helpers';

window.dashboardApp = function () {
    return {
        // State
        showDetailModal: false,
        showAddModal: false,
        selectedTask: null,
        addSaving: false,
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
                const data = await res.json();
                if (data.success) {
                    if (newStatus === 'completed') {
                        // Hapus dari kuadran (dashboard hanya tampilkan yang belum selesai)
                        document.getElementById(`dash-todo-${task.id}`)?.remove();
                        toast('Tugas diselesaikan! ✓');
                    } else {
                        toast('Tugas dibuka kembali');
                        setTimeout(() => location.reload(), 500);
                    }
                    this.showDetailModal = false;
                } else {
                    toast(data.message || 'Gagal mengubah status', 'error');
                }
            } catch {
                toast('Gagal mengubah status', 'error');
            }
        },

        async moveToKuadran(taskId, kuadran) {
            try {
                const res = await fetch(`/todos/${taskId}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ kuadran }),
                });
                const data = await res.json();
                if (data.success) {
                    toast('Kuadran berhasil diperbarui');
                    // Card perlu pindah ke kotak kuadran lain — reload halaman
                    setTimeout(() => location.reload(), 400);
                } else {
                    toast(data.message || 'Gagal pindah kuadran', 'error');
                }
            } catch {
                toast('Gagal pindah kuadran', 'error');
            }
        },

        async addTask() {
            if (this.addSaving) return;
            this.addSaving = true;
            try {
                const res = await fetch('/todos', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify(this.newTask),
                });
                const data = await res.json();
                if (data.success || res.ok) {
                    this.showAddModal = false;
                    this.newTask = { title: '', description: '', category: 'kuliah', priority: 'medium', due_date: '', due_time: '' };
                    toast('Tugas berhasil ditambahkan!');
                    // Perlu reload untuk menempatkan tugas di kuadran yang tepat
                    setTimeout(() => location.reload(), 500);
                } else {
                    const msg = data.message
                        || Object.values(data.errors || {}).flat().join(', ')
                        || 'Gagal menambah tugas';
                    toast(msg, 'error');
                }
            } catch {
                toast('Gagal menambah tugas', 'error');
            } finally {
                this.addSaving = false;
            }
        },

        async deleteTodo(id) {
            if (!confirm('Hapus tugas ini?')) return;
            try {
                const res = await fetch(`/todos/${id}`, {
                    method: 'DELETE',
                    headers: apiHeaders(false),
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById(`dash-todo-${id}`)?.remove();
                    toast('Tugas berhasil dihapus');
                    this.showDetailModal = false;
                } else {
                    toast(data.message || 'Gagal menghapus tugas', 'error');
                }
            } catch {
                toast('Gagal menghapus tugas', 'error');
            }
        },
    };
};
