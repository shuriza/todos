/**
 * ============================================
 * Todos Page — todoListApp()
 * ============================================
 * Halaman: todos/index.blade.php
 * Fitur: CRUD tugas, filter, search, detail modal, edit/add modal
 * Data: Membaca dari <script id="todos-data"> JSON block
 */

import {
    apiHeaders,
    formatDate,
    getKuadranLabel,
    getKuadranBadgeClass,
    getKuadranDotClass,
    readJsonData,
} from '../helpers';

window.todoListApp = function () {
    return {
        // Data
        todos: readJsonData('todos-data') || [],

        // Filter & Search
        search: '',
        statusFilter: 'all',
        kuadranFilter: 'all',
        categoryFilter: 'all',
        sumberFilter: 'all',

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

        // --- Computed ---
        get filteredTodos() {
            let filtered = this.todos;

            if (this.search) {
                const keyword = this.search.toLowerCase();
                filtered = filtered.filter(
                    (t) =>
                        t.title.toLowerCase().includes(keyword) ||
                        (t.description && t.description.toLowerCase().includes(keyword))
                );
            }
            if (this.statusFilter !== 'all') {
                filtered = filtered.filter((t) => t.status === this.statusFilter);
            }
            if (this.kuadranFilter !== 'all') {
                filtered = filtered.filter((t) => t.kuadran == this.kuadranFilter);
            }
            if (this.categoryFilter !== 'all') {
                filtered = filtered.filter((t) => (t.category || 'pekerjaan') === this.categoryFilter);
            }
            if (this.sumberFilter !== 'all') {
                filtered = filtered.filter((t) => (t.sumber || 'manual') === this.sumberFilter);
            }

            return filtered;
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
                    if (this.editingId) {
                        const idx = this.todos.findIndex((t) => t.id === this.editingId);
                        if (idx !== -1) this.todos[idx] = data.todo;
                    } else {
                        this.todos.unshift(data.todo);
                    }
                    this.showAddModal = false;
                    this.resetForm();
                } else {
                    alert(data.message || 'Gagal menyimpan tugas');
                }
            } catch (e) {
                console.error(e);
                alert('Gagal menyimpan tugas');
            } finally {
                this.saving = false;
            }
        },

        async toggleStatus(task) {
            const newStatus = task.status === 'completed' ? 'todo' : 'completed';
            try {
                const res = await fetch(`/todos/${task.id}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ status: newStatus }),
                });
                const data = await res.json();
                if (data.success) task.status = newStatus;
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
                if (data.success) this.todos = this.todos.filter((t) => t.id !== id);
            } catch (e) {
                console.error(e);
            }
        },

        // --- Helpers ---
        isOverdue(task) {
            return task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed';
        },

        getCategoryLabel(category) {
            return {
                kuliah: ' Kuliah',
                pekerjaan: ' Pekerjaan',
                daily_activity: ' Daily',
            }[category] || category;
        },

        getKuadranName(k) {
            return { 1: 'Do Now', 2: 'Schedule', 3: 'Delegate', 4: 'Eliminate' }[k] || '-';
        },

        getKuadranLabel(k) {
            return getKuadranLabel(k);
        },

        getKuadranBadgeClass(k) {
            return getKuadranBadgeClass(k);
        },

        getKuadranDotClass(k) {
            return getKuadranDotClass(k);
        },

        formatDate(d) {
            return formatDate(d);
        },
    };
};
