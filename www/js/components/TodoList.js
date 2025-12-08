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
        const toggleTodo = inject('toggleTodo');
        const deleteTodo = inject('deleteTodo');
        const showEditForm = inject('showEditForm');
        const parseLabels = inject('parseLabels');
        const onDrop = inject('onDrop');

        const isDragOver = ref(false);
        const dragPlacement = ref(null); // 'before', 'after', 'inside'

        const children = computed(() => {
            // Sort by position ASC, then ID ASC
            return props.allTodos
                .filter(t => t.parent_id == props.todo.id)
                .sort((a, b) => (a.position - b.position) || (a.id - b.id));
        });

        const onDragStart = (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', props.todo.id);
            e.target.classList.add('dragging');
        };

        const onDragEnd = (e) => {
            e.target.classList.remove('dragging');
            isDragOver.value = false;
            dragPlacement.value = null;
        };

        const onDragOver = (e) => {
            e.preventDefault(); // allow drop
            e.stopPropagation();

            const rect = e.currentTarget.getBoundingClientRect();
            const y = e.clientY - rect.top;
            const h = rect.height;

            // Logic: Top 25% = before, Bottom 25% = after, Middle 50% = inside (if allowed)
            // Or simpler: Top 50% = before, Bottom 50% = after?
            // "Inside" is useful for Reparenting. Let's do:
            // Top 1/3: Before
            // Bottom 1/3: After
            // Middle 1/3: Inside

            if (y < h / 3) {
                dragPlacement.value = 'before';
            } else if (y > (h * 2) / 3) {
                dragPlacement.value = 'after';
            } else {
                dragPlacement.value = 'inside';
            }
            isDragOver.value = true;
        };

        const onDragLeave = (e) => {
            // Only clear if leaving the element itself, not entering children
            // Simple check: clear styling
            isDragOver.value = false;
            dragPlacement.value = null;
        };

        const onDropHandler = (e) => {
            e.preventDefault();
            e.stopPropagation();
            isDragOver.value = false;
            const srcId = parseInt(e.dataTransfer.getData('text/plain'));
            if (srcId && onDrop) {
                onDrop(srcId, props.todo.id, dragPlacement.value);
            }
            dragPlacement.value = null;
        };

        return {
            children,
            toggleTodo,
            deleteTodo,
            showEditForm,
            parseLabels,
            onDragStart,
            onDragEnd,
            onDragOver,
            onDragLeave,
            onDropHandler,
            isDragOver,
            dragPlacement
        };
    },
    template: `
        <li 
            class="todo-item-wrapper"
            draggable="true"
            @dragstart="onDragStart"
            @dragend="onDragEnd"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDropHandler"
        >
            <div 
                class="todo-item" 
                :class="{ 
                    completed: todo.completed,
                    'drag-over-top': isDragOver && dragPlacement === 'before',
                    'drag-over-bottom': isDragOver && dragPlacement === 'after',
                    'drag-over-inside': isDragOver && dragPlacement === 'inside'
                }"
            >
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

        const handleDrop = async (srcId, targetId, position) => {
            // Find src and target todos
            const findTodo = (id) => todos.value.find(t => t.id === id);
            const srcTodo = findTodo(srcId);
            const targetTodo = findTodo(targetId);

            if (!srcTodo || !targetTodo) return;
            if (srcId === targetId) return;

            let newParentId = targetTodo.parent_id;
            let newPosition = 0;

            // Simple logic: 
            // If dropping ON target, make it child (if not already child of self)
            // But for reordering, we usually want "insert before" or "insert after"
            // Let's implement "insert after" target for simplicity, unless holding modifier?
            // Or better: Drag and Drop API doesn't give precise "between" without complexity.
            // Let's assume we drop "into" if target has kids, or "after" if it's a leaf?
            // Actually, best simple UX for now: Drop ON = Make Child.
            // But user asked for REORDERING.
            // Complex logic needed: detect top/bottom half of target.
            // This requires passing event Y coordinates.

            // Re-implementing TodoItem to emit dragover details?
            // Let's keep it simple first: 
            // We need `reorder(id, parentId, position)` API call.
            // We'll trust the child component to tell us WHERE it was dropped relative to target.
        };

        // Revised provided method to children
        const onDrop = async (draggedId, targetId, placement) => {
            // placement: 'before', 'after', 'inside'
            const findTodo = (id) => todos.value.find(t => t.id === id);
            const srcTodo = findTodo(draggedId);
            const targetTodo = findTodo(targetId);

            if (!draggedId || !targetId || draggedId === targetId) return;

            let newParentId = null;
            let newPosition = 0;

            if (placement === 'inside') {
                newParentId = targetId;
                // Add to end of children
                const siblings = todos.value.filter(t => t.parent_id == targetId);
                const maxPos = siblings.reduce((max, t) => Math.max(max, t.position || 0), 0);
                newPosition = maxPos + 1024;
            } else {
                newParentId = targetTodo.parent_id;
                const siblings = todos.value.filter(t => t.parent_id == newParentId).sort((a, b) => (a.position - b.position) || (a.id - b.id));
                const targetIdx = siblings.findIndex(t => t.id === targetId);

                let prevPos = -1024;
                let nextPos = 1000000;

                if (placement === 'before') {
                    if (targetIdx > 0) prevPos = siblings[targetIdx - 1].position;
                    nextPos = targetTodo.position;
                } else { // after
                    prevPos = targetTodo.position;
                    if (targetIdx < siblings.length - 1) nextPos = siblings[targetIdx + 1].position;
                }

                newPosition = (prevPos + nextPos) / 2;
            }

            // Optimistic Update
            srcTodo.parent_id = newParentId;
            srcTodo.position = newPosition;
            todos.value.sort((a, b) => (a.position - b.position) || (a.id - b.id));

            // API Call
            await fetch('/api/todos/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: draggedId,
                    parent_id: newParentId,
                    position: newPosition
                })
            });
        };

        // Provide actions to children
        provide('toggleTodo', toggleTodo);
        provide('deleteTodo', deleteTodo);
        provide('showEditForm', showEditForm);
        provide('parseLabels', parseLabels);
        provide('onDrop', onDrop);

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
