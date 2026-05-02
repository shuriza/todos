/**
 * ============================================
 * Todos Page — todoPageApp()
 * ============================================
 * Halaman: todos/index.blade.php
 * Fitur: CRUD tugas, detail modal, edit/add modal, drag-and-drop reorder
 * Data di-render server-side via Blade. JS handle modal, API actions, dan DOM update.
 */

import Sortable from 'sortablejs';
import { apiHeaders, formatDate, getKuadranLabel, toast } from '../helpers';

window.todoPageApp = function (config = {}) {
    return {
        // Reorder state
        canReorder: config.canReorder ?? false,
        sortableInstance: null,

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

        // --- Lifecycle ---
        init() {
            if (this.canReorder) {
                this.$nextTick(() => this.initSortable());
            }
        },

        // --- Drag & Drop Reorder ---
        initSortable() {
            const container = document.getElementById('todo-list-body');
            if (!container || !container.children.length) return;

            this.sortableInstance = Sortable.create(container, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: () => this.saveOrder(),
            });
        },

        async saveOrder() {
            const container = document.getElementById('todo-list-body');
            if (!container) return;

            const rows = container.querySelectorAll('[data-todo-id]');
            const todos = Array.from(rows).map((row, index) => ({
                id: parseInt(row.dataset.todoId),
                order: index,
            }));

            try {
                const res = await fetch('/todos/reorder', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify({ todos }),
                });
                const data = await res.json();
                if (data.success) {
                    toast('Urutan berhasil diperbarui');
                } else {
                    toast(data.message || 'Gagal menyimpan urutan', 'error');
                }
            } catch {
                toast('Gagal menyimpan urutan', 'error');
            }
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
                due_date: task.due_date ? task.due_date.substring(0, 10) : '',
                due_time: task.due_time || '',
            };
            this.showAddModal = true;
        },

        // --- API Actions ---

        async saveTodo() {
            if (this.saving) return;
            this.saving = true;
            const isEdit = !!this.editingId;
            const url = isEdit ? `/todos/${this.editingId}` : '/todos';
            const method = isEdit ? 'PUT' : 'POST';
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
                    toast(isEdit ? 'Tugas berhasil diperbarui' : 'Tugas berhasil dibuat!');
                    // Daftar berubah (create/update) — reload untuk sinkronisasi server-side rendering
                    setTimeout(() => location.reload(), 500);
                } else {
                    const msg = data.message
                        || Object.values(data.errors || {}).flat().join(', ')
                        || 'Gagal menyimpan tugas';
                    toast(msg, 'error');
                }
            } catch {
                toast('Gagal menyimpan tugas', 'error');
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
                if (data.success) {
                    toast(newStatus === 'completed' ? 'Tugas diselesaikan! ✓' : 'Tugas dibuka kembali');
                    // Update visual langsung tanpa reload
                    this._updateRowStatus(id, newStatus);
                } else {
                    toast(data.message || 'Gagal mengubah status', 'error');
                }
            } catch {
                toast('Gagal mengubah status', 'error');
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
                    // Hapus baris dari DOM langsung tanpa reload
                    const row = document.getElementById(`todo-row-${id}`);
                    if (row) {
                        row.style.transition = 'opacity 0.2s, height 0.2s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 200);
                    }
                    toast('Tugas berhasil dihapus');
                    this.showDetailModal = false;
                } else {
                    toast(data.message || 'Gagal menghapus tugas', 'error');
                }
            } catch {
                toast('Gagal menghapus tugas', 'error');
            }
        },

        // --- Helpers ---

        _updateRowStatus(id, newStatus) {
            const row = document.getElementById(`todo-row-${id}`);
            if (!row) return;

            const isCompleted = newStatus === 'completed';

            // Checkbox button
            const checkbox = row.querySelector('button[class*="rounded-full"]');
            if (checkbox) {
                if (isCompleted) {
                    checkbox.className = checkbox.className
                        .replace('border-gray-300 hover:border-green-400', '')
                        + ' bg-green-500 border-green-500';
                    checkbox.innerHTML = `<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>`;
                } else {
                    checkbox.className = checkbox.className
                        .replace('bg-green-500 border-green-500', 'border-gray-300 hover:border-green-400');
                    checkbox.innerHTML = '';
                }
            }

            // Title text (line-through + color)
            const title = row.querySelector('p.font-medium');
            if (title) {
                if (isCompleted) {
                    title.classList.add('text-gray-400', 'line-through');
                    title.classList.remove('text-gray-900');
                } else {
                    title.classList.remove('text-gray-400', 'line-through');
                    title.classList.add('text-gray-900');
                }
            }

            // Row background
            if (isCompleted) {
                row.classList.add('bg-gray-50/50');
            } else {
                row.classList.remove('bg-gray-50/50');
            }
        },

        getKuadranLabel(k) {
            return getKuadranLabel(k);
        },

        formatDate(d) {
            return formatDate(d);
        },
    };
};
