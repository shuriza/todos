<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    Todos Management �
                </h2>
                <p class="text-sm text-gray-600 mt-1">Manage your tasks efficiently with filters and categories.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="todoApp()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Todos Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6">
                    <!-- Page Title -->
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-gray-900">Todos</h1>
                        <p class="text-gray-600 text-sm mt-1">Manage your tasks efficiently</p>
                    </div>

                    <!-- Date Filter Tabs -->
                    <div class="flex items-center gap-3 mb-6 overflow-x-auto pb-2">
                        <button 
                            @click="dateFilter = 'today'" 
                            :class="dateFilter === 'today' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-6 py-3 rounded-xl font-medium whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>❤️</span>
                            <span>Hari Ini</span>
                        </button>
                        <button 
                            @click="dateFilter = '7days'" 
                            :class="dateFilter === '7days' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-6 py-3 rounded-xl font-medium whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>📅</span>
                            <span>7 Hari</span>
                        </button>
                        <button 
                            @click="dateFilter = '30days'" 
                            :class="dateFilter === '30days' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-6 py-3 rounded-xl font-medium whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>📊</span>
                            <span>30 Hari</span>
                        </button>
                        <button 
                            @click="dateFilter = 'all'" 
                            :class="dateFilter === 'all' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-6 py-3 rounded-xl font-medium whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>📋</span>
                            <span>Semua</span>
                        </button>
                    </div>

                    <!-- Category Pills -->
                    <div class="flex items-center gap-3 mb-6 overflow-x-auto pb-2">
                        <button 
                            @click="categoryFilter = null" 
                            :class="categoryFilter === null ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-all"
                        >
                            All Tasks
                        </button>
                        <button 
                            @click="categoryFilter = 'daily_activity'" 
                            :class="categoryFilter === 'daily_activity' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>📊</span>
                            <span>daily activity</span>
                        </button>
                        <button 
                            @click="categoryFilter = 'pekerjaan'" 
                            :class="categoryFilter === 'pekerjaan' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>💼</span>
                            <span>pekerjaan</span>
                        </button>
                        <button 
                            @click="categoryFilter = 'kuliah'" 
                            :class="categoryFilter === 'kuliah' ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-all flex items-center gap-2"
                        >
                            <span>🎓</span>
                            <span>kuliah</span>
                        </button>
                    </div>

                    <!-- Add Todo Button -->
                    <button 
                        @click="showAddModal = true" 
                        class="mb-6 w-full md:w-auto px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-medium transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add New Todo</span>
                    </button>

                    <!-- Todo List -->
                    <div class="space-y-3">
                        <template x-for="todo in filteredTodos" :key="todo.id">
                            <div class="group p-4 border border-gray-200 rounded-xl hover:border-blue-300 hover:shadow-md transition-all bg-white">
                                <div class="flex items-start gap-4">
                                    <!-- Checkbox -->
                                    <input 
                                        type="checkbox" 
                                        :checked="todo.status === 'completed'"
                                        @change="toggleStatus(todo)"
                                        class="w-5 h-5 mt-1 rounded-md border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    >
                                    
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Title and Badges -->
                                        <div class="flex items-start justify-between gap-3 mb-2">
                                            <h3 
                                                class="font-semibold text-gray-900 text-lg"
                                                :class="todo.status === 'completed' ? 'line-through text-gray-400' : ''"
                                                x-text="todo.title"
                                            ></h3>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <!-- Priority Badge -->
                                                <span 
                                                    x-show="todo.priority === 'high'"
                                                    class="px-3 py-1 text-xs font-bold rounded-md bg-red-100 text-red-700 uppercase"
                                                >
                                                    HIGH
                                                </span>
                                                <span 
                                                    x-show="todo.priority === 'medium'"
                                                    class="px-3 py-1 text-xs font-bold rounded-md bg-yellow-100 text-yellow-700 uppercase"
                                                >
                                                    MEDIUM
                                                </span>
                                                <!-- Actions -->
                                                <button 
                                                    @click="editTodo(todo)"
                                                    class="opacity-0 group-hover:opacity-100 p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                                    title="Edit"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button 
                                                    @click="deleteTodo(todo.id)"
                                                    class="opacity-0 group-hover:opacity-100 p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                                    title="Delete"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                        <!-- Category and Time -->
                        <div class="flex items-center gap-3 text-sm mb-2">
                            <span class="flex items-center gap-1 text-gray-600">
                                <span x-text="getCategoryIcon(todo.category)"></span>
                                <span x-text="todo.category || 'pekerjaan'"></span>
                            </span>
                            <span x-show="todo.due_date" class="flex items-center gap-1 text-gray-600">
                                <span>🕐</span>
                                <span x-text="formatDate(todo.due_date)"></span>
                            </span>
                        </div>                                        <!-- Description -->
                                        <p 
                                            x-show="todo.description" 
                                            class="text-sm text-gray-600 leading-relaxed"
                                            :class="todo.status === 'completed' ? 'line-through text-gray-400' : ''"
                                            x-text="todo.description"
                                        ></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div x-show="filteredTodos.length === 0" class="text-center py-12">
                            <div class="text-6xl mb-4">📝</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No todos found</h3>
                            <p class="text-gray-600 mb-4">Start by creating your first task!</p>
                            <button 
                                @click="showAddModal = true"
                                class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors"
                            >
                                Add Todo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Todo Modal -->
            <div 
                x-show="showAddModal" 
                x-cloak
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="showAddModal = false"
            >
                <div class="bg-white rounded-2xl p-6 w-full max-w-md" @click.stop>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Add New Todo</h3>
                    <form @submit.prevent="addTodo" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input 
                                type="text" 
                                x-model="newTodo.title" 
                                placeholder="What needs to be done?"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea 
                                x-model="newTodo.description" 
                                placeholder="Add details..."
                                rows="3"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select 
                                    x-model="newTodo.category" 
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="pekerjaan">💼 Pekerjaan</option>
                                    <option value="kuliah">🎓 Kuliah</option>
                                    <option value="daily_activity">📊 Daily Activity</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                <select 
                                    x-model="newTodo.priority" 
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                            <input 
                                type="date" 
                                x-model="newTodo.due_date" 
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button 
                                type="button"
                                @click="showAddModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors"
                            >
                                Add Todo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function todoApp() {
            return {
                todos: {!! json_encode($todos ?? []) !!},
                filter: 'all',
                dateFilter: 'all',
                categoryFilter: null,
                showAddModal: false,
                stats: {
                    total: 0,
                    todo: 0,
                    completed: 0,
                    in_progress: 0,
                    overdue: 0
                },
                newTodo: {
                    title: '',
                    description: '',
                    priority: 'medium',
                    due_date: '',
                    category: 'pekerjaan'
                },
                
                init() {
                    this.loadStats();
                },

                get filteredTodos() {
                    let filtered = this.todos;

                    // Filter by status
                    if (this.filter !== 'all') {
                        filtered = filtered.filter(todo => todo.status === this.filter);
                    }

                    // Filter by date
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (this.dateFilter === 'today') {
                        filtered = filtered.filter(todo => {
                            if (!todo.due_date) return false;
                            const dueDate = new Date(todo.due_date);
                            dueDate.setHours(0, 0, 0, 0);
                            return dueDate.getTime() === today.getTime();
                        });
                    } else if (this.dateFilter === '7days') {
                        const next7Days = new Date(today);
                        next7Days.setDate(next7Days.getDate() + 7);
                        filtered = filtered.filter(todo => {
                            if (!todo.due_date) return false;
                            const dueDate = new Date(todo.due_date);
                            return dueDate >= today && dueDate <= next7Days;
                        });
                    } else if (this.dateFilter === '30days') {
                        const next30Days = new Date(today);
                        next30Days.setDate(next30Days.getDate() + 30);
                        filtered = filtered.filter(todo => {
                            if (!todo.due_date) return false;
                            const dueDate = new Date(todo.due_date);
                            return dueDate >= today && dueDate <= next30Days;
                        });
                    }

                    // Filter by category
                    if (this.categoryFilter) {
                        filtered = filtered.filter(todo => 
                            (todo.category || 'pekerjaan') === this.categoryFilter
                        );
                    }

                    return filtered;
                },

                async loadStats() {
                    try {
                        const response = await fetch('/todos/statistics');
                        this.stats = await response.json();
                    } catch (error) {
                        console.error('Failed to load stats:', error);
                    }
                },

                async addTodo() {
                    try {
                        const response = await fetch('/todos', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.newTodo)
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.todos.unshift(data.todo);
                            this.newTodo = { 
                                title: '', 
                                description: '',
                                priority: 'medium', 
                                due_date: '',
                                category: 'pekerjaan'
                            };
                            this.showAddModal = false;
                            this.loadStats();
                        }
                    } catch (error) {
                        console.error('Failed to add todo:', error);
                        alert('Failed to add todo. Please try again.');
                    }
                },

                async toggleStatus(todo) {
                    const newStatus = todo.status === 'completed' ? 'todo' : 'completed';
                    
                    try {
                        const response = await fetch(`/todos/${todo.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ status: newStatus })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            todo.status = newStatus;
                            if (newStatus === 'completed') {
                                todo.completed_at = new Date().toISOString();
                            }
                            this.loadStats();
                        }
                    } catch (error) {
                        console.error('Failed to update todo:', error);
                    }
                },

                editTodo(todo) {
                    this.newTodo = {
                        id: todo.id,
                        title: todo.title,
                        description: todo.description || '',
                        priority: todo.priority,
                        due_date: todo.due_date || '',
                        category: todo.category || 'pekerjaan'
                    };
                    this.showAddModal = true;
                },

                async deleteTodo(id) {
                    if (!confirm('Are you sure you want to delete this todo?')) return;
                    
                    try {
                        const response = await fetch(`/todos/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.todos = this.todos.filter(t => t.id !== id);
                            this.loadStats();
                        }
                    } catch (error) {
                        console.error('Failed to delete todo:', error);
                    }
                },

                async getSuggestions(todoId) {
                    try {
                        const response = await fetch(`/ai/suggestions/${todoId}`);
                        const data = await response.json();
                        
                        if (data.success) {
                            alert(data.message);
                        }
                    } catch (error) {
                        console.error('Failed to get suggestions:', error);
                    }
                },

                getCategoryIcon(category) {
                    const icons = {
                        'pekerjaan': '💼',
                        'kuliah': '🎓',
                        'daily_activity': '📊'
                    };
                    return icons[category] || '💼';
                },

                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    const options = { day: 'numeric', month: 'short' };
                    return date.toLocaleDateString('id-ID', options);
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @endpush
</x-app-layout>
