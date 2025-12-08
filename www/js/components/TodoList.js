import { ref, onMounted, computed } from 'vue';

export default {
    template: `
        <div class="todo-container">
            <h2>My Todos</h2>
            
            <!-- Filter by label -->
            <div class="todo-filters" v-if="allLabels.length > 0">
                <label>Filter by label:</label>
                <button 
                    @click="selectedLabel = null" 
                    :class="{ active: selectedLabel === null }"
                    class="label-filter"
                >All</button>
                <button 
                    v-for="label in allLabels" 
                    :key="label"
                    @click="selectedLabel = label"
                    :class="{ active: selectedLabel === label }"
                    class="label-filter"
                >{{ label }}</button>
            </div>
            
            <!-- Add new todo -->
            <div class="add-todo">
                <input 
                    v-model="newTodo" 
                    @keyup.enter="addTodo" 
                    placeholder="Add new todo..."
                    class="todo-input"
                >
                <input 
                    v-model="newLabels" 
                    placeholder="Labels (comma-separated)"
                    class="labels-input"
                >
                <select v-model="newParentId" class="parent-select">
                    <option :value="null">No parent</option>
                    <option v-for="todo in rootTodos" :key="todo.id" :value="todo.id">
                        ↳ {{ todo.title }}
                    </option>
                </select>
                <button @click="addTodo">Add</button>
            </div>
            
            <!-- Todo list with hierarchy -->
            <ul class="todo-list">
                <template v-for="todo in filteredRootTodos" :key="todo.id">
                    <li :class="{ completed: todo.completed }" class="todo-item">
                        <div class="todo-content">
                            <span @click="toggleTodo(todo)" class="todo-title">
                                {{ todo.title }}
                            </span>
                            <span v-if="todo.labels" class="todo-labels">
                                <span v-for="label in parseLabels(todo.labels)" :key="label" class="label-tag">
                                    {{ label }}
                                </span>
                            </span>
                        </div>
                        <div class="todo-actions">
                            <button 
                                @click="showEditForm(todo)" 
                                class="btn-small"
                                title="Edit"
                            >✎</button>
                            <button 
                                @click="deleteTodo(todo.id)" 
                                class="delete-btn"
                                title="Delete"
                            >×</button>
                        </div>
                    </li>
                    
                    <!-- Child todos -->
                    <li 
                        v-for="child in getChildren(todo.id)" 
                        :key="child.id"
                        :class="{ completed: child.completed }"
                        class="todo-item child-todo"
                    >
                        <div class="todo-content">
                            <span class="child-indicator">↳</span>
                            <span @click="toggleTodo(child)" class="todo-title">
                                {{ child.title }}
                            </span>
                            <span v-if="child.labels" class="todo-labels">
                                <span v-for="label in parseLabels(child.labels)" :key="label" class="label-tag">
                                    {{ label }}
                                </span>
                            </span>
                        </div>
                        <div class="todo-actions">
                            <button 
                                @click="showEditForm(child)" 
                                class="btn-small"
                                title="Edit"
                            >✎</button>
                            <button 
                                @click="deleteTodo(child.id)" 
                                class="delete-btn"
                                title="Delete"
                            >×</button>
                        </div>
                    </li>
                </template>
            </ul>
            
            <!-- Edit modal -->
            <div v-if="editingTodo" class="modal-overlay" @click="cancelEdit">
                <div class="modal-content" @click.stop>
                    <h3>Edit Todo</h3>
                    <div class="form-group">
                        <label>Title:</label>
                        <input v-model="editForm.title" class="todo-input">
                    </div>
                    <div class="form-group">
                        <label>Labels:</label>
                        <input v-model="editForm.labels" placeholder="comma-separated" class="labels-input">
                    </div>
                    <div class="form-group">
                        <label>Parent:</label>
                        <select v-model="editForm.parent_id" class="parent-select">
                            <option :value="null">No parent</option>
                            <option 
                                v-for="todo in rootTodos.filter(t => t.id !== editingTodo.id)" 
                                :key="todo.id" 
                                :value="todo.id"
                            >
                                {{ todo.title }}
                            </option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button @click="saveEdit" class="btn-primary">Save</button>
                        <button @click="cancelEdit" class="btn-secondary">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const todos = ref([]);
        const newTodo = ref('');
        const newLabels = ref('');
        const newParentId = ref(null);
        const selectedLabel = ref(null);
        const editingTodo = ref(null);
        const editForm = ref({});

        const rootTodos = computed(() => {
            return todos.value.filter(t => !t.parent_id);
        });

        const filteredRootTodos = computed(() => {
            if (!selectedLabel.value) {
                return rootTodos.value;
            }
            return rootTodos.value.filter(t =>
                t.labels && t.labels.includes(selectedLabel.value)
            );
        });

        const allLabels = computed(() => {
            const labels = new Set();
            todos.value.forEach(todo => {
                if (todo.labels) {
                    parseLabels(todo.labels).forEach(label => labels.add(label));
                }
            });
            return Array.from(labels).sort();
        });

        const parseLabels = (labelsString) => {
            if (!labelsString) return [];
            return labelsString.split(',').map(l => l.trim()).filter(l => l);
        };

        const getChildren = (parentId) => {
            return todos.value.filter(t => t.parent_id === parentId);
        };

        const fetchTodos = async () => {
            const res = await fetch('/api/todos');
            if (res.ok) todos.value = await res.json();
        };

        const addTodo = async () => {
            if (!newTodo.value.trim()) return;

            const data = {
                title: newTodo.value,
                parent_id: newParentId.value,
                labels: newLabels.value || null
            };

            const res = await fetch('/api/todos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                const todo = await res.json();
                todos.value.push(todo);
                newTodo.value = '';
                newLabels.value = '';
                newParentId.value = null;
            }
        };

        const toggleTodo = async (todo) => {
            const updated = !todo.completed;
            const res = await fetch(`/api/todos/${todo.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ completed: updated })
            });
            if (res.ok) {
                todo.completed = updated ? 1 : 0;
            }
        };

        const deleteTodo = async (id) => {
            if (!confirm('Delete this todo?')) return;

            const res = await fetch(`/api/todos/${id}`, { method: 'DELETE' });
            if (res.ok) {
                todos.value = todos.value.filter(t => t.id !== id);
            }
        };

        const showEditForm = (todo) => {
            editingTodo.value = todo;
            editForm.value = {
                title: todo.title,
                labels: todo.labels || '',
                parent_id: todo.parent_id
            };
        };

        const saveEdit = async () => {
            const res = await fetch(`/api/todos/${editingTodo.value.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(editForm.value)
            });

            if (res.ok) {
                Object.assign(editingTodo.value, editForm.value);
                cancelEdit();
            }
        };

        const cancelEdit = () => {
            editingTodo.value = null;
            editForm.value = {};
        };

        onMounted(fetchTodos);

        return {
            todos,
            newTodo,
            newLabels,
            newParentId,
            selectedLabel,
            editingTodo,
            editForm,
            rootTodos,
            filteredRootTodos,
            allLabels,
            parseLabels,
            getChildren,
            addTodo,
            toggleTodo,
            deleteTodo,
            showEditForm,
            saveEdit,
            cancelEdit
        };
    }
};
