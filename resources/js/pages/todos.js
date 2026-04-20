/**
 * ============================================
 * Todos Page — todoPageApp()
 * ============================================
 * Halaman: todos/index.blade.php
 * Fitur: CRUD tugas, detail modal, edit/add modal
 * Data di-render server-side via Blade. JS hanya handle modal & API actions.
 */

import { apiHeaders, formatDate, getKuadranLabel } from '../helpers';

window.todoPageApp = function () {
    return {
        // Modal state
        showAddModal: false,
        showDetailModal: false,
        selectedTask: null,
        editingId: null,
        saving: false,
        form: {
            title: '',
            description: '',
            category: 'kuliah',
            priority: 'medium',
            due_date: '',
            due_time: '',
        },

        // --- Form ---
        resetForm() {
            this.editingId = null;
            this.form = {
                title: '',
                description: '',
                category: 'kuliah',
                priority: 'medium',
                due_date: '',
                due_time: '',
            };
        },

        openDetail(task) {
            this.selectedTask = task;
            this.showDetailModal = true;
        },

        editTodo(task) {
            this.editingId = task.id;
            this.form = {
                title: task.title,
                description: task.description || '',
                category: task.category || 'kuliah',
                priority: task.priority,
                due_date: task.due_date || '',
                due_time: task.due_time || '',
            };
            this.showAddModal = true;
        },

        // --- API Actions ---
        async saveTodo() {
            if (this.saving) return;
            this.saving = true;
            const url = this.editingId ? `/todos/${this.editingId}` : '/todos';
            const method = this.editingId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: apiHeaders(),
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (data.success) {
                    this.showAddModal = false;
                    this.resetForm();
                    location.reload();
                } else {
                    alert(
                        data.message ||
                        Object.values(data.errors || {}).flat().join('\n') ||
                        'Gagal menyimpan tugas'
                    );
                }
            } catch (e) {
                console.error(e);
                alert('Gagal menyimpan tugas');
            } finally {
                this.saving = false;
            }
        },

        async toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'completed' ? 'todo' : 'completed';
            try {
                const res = await fetch(`/todos/${id}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ status: newStatus }),
                });
                const data = await res.json();
                if (data.success) location.reload();
            } catch (e) {
                console.error(e);
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
                if (data.success) location.reload();
            } catch (e) {
                console.error(e);
            }
        },

        // --- Helpers ---
        getKuadranLabel(k) {
            return getKuadranLabel(k);
        },

        formatDate(d) {
            return formatDate(d);
        },
    };
};
