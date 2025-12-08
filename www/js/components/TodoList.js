import { ref, onMounted, computed, provide, inject } from 'vue';

// Recursive Todo Item Component
const TodoItem = {
    name: 'TodoItem',
    props: {
        todo: Object,
        allTodos: Array,
        level: { type: Number, default: 0 }
    },
    setup(props) {
        // Inject actions provided by parent
        const toggleTodo = inject('toggleTodo');
        const deleteTodo = inject('deleteTodo');
        const showEditForm = inject('showEditForm');
        const parseLabels = inject('parseLabels');

        const children = computed(() => {
            return props.allTodos.filter(t => t.parent_id == props.todo.id);
        });

        return {
            children,
            toggleTodo,
            deleteTodo,
            showEditForm,
            parseLabels
        };
    },
    template: `
        <li :class="{ completed: todo.completed }" class="todo-item">
            <div class="todo-content" :style="{ paddingLeft: (level * 20) + 'px' }">
                <span v-if="level > 0" class="child-indicator">↳</span>
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
                <button @click="showEditForm(todo)" class="btn-small" title="Edit">✎</button>
                <button @click="deleteTodo(todo.id)" class="delete-btn" title="Delete">×</button>
            </div>
        </li>
        <!-- Recursively render children -->
        <todo-item 
            v-for="child in children" 
            :key="child.id" 
            :todo="child" 
            :all-todos="allTodos"
            :level="level + 1"
        ></todo-item>
    `
};

export default {
    components: { TodoItem },
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
                    <option v-for="todo in todos" :key="todo.id" :value="todo.id">
                        {{ todo.title }} (ID: {{ todo.id }})
                    </option>
                </select>
                <button @click="addTodo">Add</button>
            </div>
            
            <!-- Todo list with hierarchy -->
            <ul class="todo-list">
                <template v-for="todo in filteredRootTodos" :key="todo.id">
                    <todo-item :todo="todo" :all-todos="todos"></todo-item>
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
                                v-for="todo in todos.filter(t => t.id !== editingTodo.id)" 
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

        const parseLabels = (labelsString) => {
            if (!labelsString) return [];
            return labelsString.split(',').map(l => l.trim()).filter(l => l);
        };

        const allLabels = computed(() => {
            const labels = new Set();
            todos.value.forEach(todo => {
                if (todo.labels) {
                    parseLabels(todo.labels).forEach(label => labels.add(label));
                }
            });
            return Array.from(labels).sort();
        });

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

            try {
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
                } else {
                    const error = await res.json();
                    alert('Failed to add todo: ' + (error.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Connection error: ' + e.message);
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
                // Update local state is tricky with references, easier to refetch or find & update
                // Given we rely on 'todos' for everything, updating the object in 'todos' works
                // But finding it might be needed if objects were replaced
                // Object.assign works if the object reference is the same
                // We are passing objects from 'todos.value', so reference holds
                Object.assign(editingTodo.value, editForm.value);
                // Also need to handle parent_id change affecting hierarchy!
                cancelEdit();
            }
        };

        const cancelEdit = () => {
            editingTodo.value = null;
            editForm.value = {};
        };

        // Provide actions to children
        provide('toggleTodo', toggleTodo);
        provide('deleteTodo', deleteTodo);
        provide('showEditForm', showEditForm);
        provide('parseLabels', parseLabels);

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
            addTodo,
            saveEdit,
            cancelEdit
        };
    }
};
