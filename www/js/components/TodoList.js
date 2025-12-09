import { ref, onMounted, onActivated, computed, provide, inject } from 'vue';
import CalendarView from './CalendarView.js';
import GanttView from './GanttView.js';
import ColorPicker from './ColorPicker.js';

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
                :style="{ borderLeft: todo.color ? '4px solid ' + todo.color : '' }"
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
                    <span v-if="todo.start_date || todo.end_date" class="todo-dates">
                        <span v-if="todo.start_date" class="date-tag" title="Start Date">{{ todo.start_date }}</span>
                        <span v-if="todo.start_date && todo.end_date"> - </span>
                        <span v-if="todo.end_date" class="date-tag" title="End Date">{{ todo.end_date }}</span>
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
    components: { TodoItem, CalendarView, GanttView, ColorPicker },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">My Todos</h2>
                <div class="nav-tabs">
                    <button @click="viewMode = 'list'" :class="{ active: viewMode === 'list' }">List</button>
                    <button @click="viewMode = 'calendar'" :class="{ active: viewMode === 'calendar' }">Calendar</button>
                    <button @click="viewMode = 'gantt'" :class="{ active: viewMode === 'gantt' }">Gantt</button>
                </div>
            </div>
            
            <div class="admin-card">
            
            <div v-if="viewMode === 'list'">
            
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
                <div class="date-inputs">
                    <input type="date" v-model="newStartDate" title="Start Date">
                    <input type="date" v-model="newEndDate" title="End Date">
                </div>
                
                <div class="color-select-wrapper" style="position: relative;">
                    <div 
                        class="color-indicator" 
                        :style="{ backgroundColor: newColor || '#transparent', border: newColor ? '1px solid ' + newColor : '1px solid #ccc' }"
                        @click="showColorPicker = !showColorPicker"
                        title="Select Color"
                    ></div>
                    <div v-if="showColorPicker" class="color-picker-popover">
                         <ColorPicker v-model="newColor" :palette="palette" />
                         <div class="picker-footer">
                            <button @click="showColorPicker = false" class="btn-small">Close</button>
                         </div>
                    </div>
                </div>

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
            </div>

            <div v-else-if="viewMode === 'calendar'">
                <CalendarView :todos="todos" />
            </div>

            <div v-else-if="viewMode === 'gantt'">
                <GanttView :todos="todos" />
            </div>
            
            </div>
            
            <!-- Edit modal -->
            <div v-if="editingTodo" class="modal-overlay" @click="cancelEdit">
                <div class="modal-content" @click.stop>
                    <h3>Edit Todo</h3>
                    <div class="form-group">
                        <label>Title:</label>
                        <input v-model="editForm.title" class="todo-input">
                    </div>
                    <div class="form-group">
                        <input v-model="editForm.labels" placeholder="comma-separated" class="labels-input">
                    </div>
                    <div class="form-group">
                        <label>Start Date:</label>
                        <input type="date" v-model="editForm.start_date" class="date-input">
                    </div>
                    <div class="form-group">
                        <label>End Date:</label>
                        <input type="date" v-model="editForm.end_date" class="date-input">
                    </div>
                    <div class="form-group">
                        <label>Color:</label>
                        <div style="position: relative;">
                            <div 
                                class="color-indicator" 
                                :style="{ backgroundColor: editForm.color || '#transparent', border: editForm.color ? '1px solid ' + editForm.color : '1px solid #ccc' }"
                                @click="showEditColorPicker = !showEditColorPicker"
                            ></div>
                            <div v-if="showEditColorPicker" class="color-picker-popover">
                                <ColorPicker v-model="editForm.color" :palette="palette" />
                                <div class="picker-footer">
                                    <button @click="showEditColorPicker = false" class="btn-small">Close</button>
                                </div>
                            </div>
                        </div>
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
        const viewMode = ref('list');
        const todos = ref([]);
        const newTodo = ref('');
        const newLabels = ref('');
        const newStartDate = ref(new Date().toISOString().split('T')[0]);
        const defaultDuration = ref(parseInt(localStorage.getItem('defaultDuration') || '1'));

        const getEndDate = (start, duration) => {
            const d = new Date(start);
            d.setDate(d.getDate() + duration);
            return d.toISOString().split('T')[0];
        };

        const newEndDate = ref(getEndDate(newStartDate.value, defaultDuration.value));
        const newColor = ref('');
        const palette = ref(['#FF6B6B', '#4ECDC4', '#FFE66D', '#1A535C', '#F7FFF7']);
        const showColorPicker = ref(false);
        const showEditColorPicker = ref(false);

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

        const fetchSettings = async () => {
            // Try to get setting from API if not in local storage or to refresh
            try {
                const res = await fetch('/api/settings');
                if (res.ok) {
                    const data = await res.json();
                    if (data.settings && data.settings.default_todo_duration) {
                        defaultDuration.value = parseInt(data.settings.default_todo_duration);
                        localStorage.setItem('defaultDuration', defaultDuration.value);
                        // Update end date if user hasn't touched it? 
                        // Simplified: update it based on current start date
                        newEndDate.value = getEndDate(newStartDate.value, defaultDuration.value);
                    }
                    if (data.settings && data.settings.todo_palette) {
                        try {
                            palette.value = JSON.parse(data.settings.todo_palette);
                            localStorage.setItem('todo_palette', data.settings.todo_palette);
                        } catch (e) { }
                    }
                }
            } catch (e) { }
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
                labels: newLabels.value || null,
                start_date: newStartDate.value || null,
                end_date: newEndDate.value || null,
                color: newColor.value || null
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
                    newStartDate.value = new Date().toISOString().split('T')[0];
                    newEndDate.value = getEndDate(newStartDate.value, defaultDuration.value);
                    newColor.value = '';
                    showColorPicker.value = false;
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
            // if (!confirm('Delete this todo?')) return;

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
                parent_id: todo.parent_id,
                start_date: todo.start_date || '',
                end_date: todo.end_date || '',
                color: todo.color || ''
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

        onMounted(() => {
            fetchTodos();
            fetchSettings();
        });

        onActivated(() => {
            fetchSettings();
        });

        return {
            viewMode,
            todos,
            newTodo,
            newLabels,
            newStartDate,
            newEndDate,
            newParentId,
            selectedLabel,
            editingTodo,
            editForm,
            rootTodos,
            filteredRootTodos,
            allLabels,
            addTodo,
            saveEdit,
            cancelEdit,
            palette,
            newColor,
            showColorPicker,
            showEditColorPicker
        };
    }
};
