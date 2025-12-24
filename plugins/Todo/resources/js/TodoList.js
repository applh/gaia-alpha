import { ref, onMounted, onActivated, computed, provide, inject } from 'vue';
import CalendarView from '/min/js/plugins/Todo/CalendarView.js';
import GanttView from '/min/js/plugins/Todo/GanttView.js';
import ColorPicker from 'ui/ColorPicker.js';
import TreeView from 'ui/TreeView.js';
import Icon from 'ui/Icon.js';

export default {
    components: { TreeView, CalendarView, GanttView, ColorPicker, LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">Projects</h2>
                <div class="nav-tabs">
                    <button @click="viewMode = 'list'" :class="{ active: viewMode === 'list' }">List</button>
                    <button @click="viewMode = 'calendar'" :class="{ active: viewMode === 'calendar' }">Calendar</button>
                    <button @click="viewMode = 'gantt'" :class="{ active: viewMode === 'gantt' }">Gantt</button>
                </div>
            </div>
            
            <div class="admin-card">
            
            <div v-if="viewMode === 'list'">
            
            <!-- Add new todo -->
            <div class="add-todo-container" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; padding: 20px; background: var(--glass-bg); border: var(--glass-border); border-radius: var(--radius-lg);">
                <div class="add-todo-row" style="display: flex; gap: 12px;">
                    <input 
                        v-model="newTodo" 
                        @keyup.enter="addTodo" 
                        placeholder="Add new todo..."
                        style="flex: 1;"
                    >
                    <button @click="addTodo" class="btn btn-primary">
                        <LucideIcon name="plus" size="18" style="margin-right: 4px; vertical-align: middle;" />
                        Add
                    </button>
                </div>
                
                <div class="add-todo-controls" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <div class="control-group" style="flex: 1; min-width: 200px; display: flex; align-items: center; gap: 8px;">
                        <LucideIcon name="tag" size="16" class="text-secondary" />
                        <input 
                            v-model="newLabels" 
                            placeholder="Labels (comma-separated)"
                        >
                    </div>

                    <div class="control-group" style="display: flex; align-items: center; gap: 8px;">
                         <LucideIcon name="calendar" size="16" class="text-secondary" />
                         <input type="date" v-model="newStartDate" title="Start Date" style="width: auto;">
                         <span class="text-secondary">-</span>
                         <input type="date" v-model="newEndDate" title="End Date" style="width: auto;">
                    </div>

                    <div class="control-group" style="display: flex; align-items: center; gap: 8px;">
                         <div class="color-select-wrapper" style="position: relative;">
                            <div 
                                class="color-indicator" 
                                :style="{ width: '36px', height: '36px', borderRadius: '8px', cursor: 'pointer', backgroundColor: newColor || 'transparent', border: newColor ? '1px solid ' + newColor : '1px solid var(--border-color)' }"
                                @click="showColorPicker = !showColorPicker"
                                title="Select Color"
                            ></div>
                            <div v-if="showColorPicker" class="color-picker-popover" style="position: absolute; top: 100%; left: 0; z-index: 100; margin-top: 8px;">
                                 <ColorPicker v-model="newColor" :palette="palette" />
                                 <div class="picker-footer" style="margin-top: 8px; text-align: right;">
                                    <button @click="showColorPicker = false" class="btn btn-sm btn-secondary">Close</button>
                                 </div>
                            </div>
                        </div>
                    </div>

                    <div class="control-group" style="flex: 1; min-width: 200px; display: flex; align-items: center; gap: 8px;">
                         <LucideIcon name="git-merge" size="16" class="text-secondary" />
                        <select v-model="newParentId">
                            <option :value="null">No parent</option>
                            <option v-for="todo in todos" :key="todo.id" :value="todo.id">
                                {{ todo.title }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

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
            
            <!-- Todo list with TreeView -->
            <div class="todo-tree-container">
                <TreeView 
                    :items="treeData" 
                    idKey="id"
                    childrenKey="children"
                    labelKey="title"
                    :draggable="true"
                    :allowDrop="() => true" 
                    @move="onMove"
                    @toggle="onToggle"
                >
                     <template #item="{ item }">
                        <div 
                            class="todo-item-card" 
                            :class="{ completed: item.completed }"
                            :style="{ borderLeft: item.color ? '4px solid ' + item.color : '', width: '100%' }"
                            style="display: flex; justify-content: space-between; align-items: center;"
                        >
                            <!-- Zone 1: Main Content -->
                            <div class="todo-main" style="display: flex; align-items: center; gap: 8px;">
                                 <div @click.stop="toggleTodo(item)" class="todo-checkbox" :class="{ checked: item.completed }">
                                    <LucideIcon :name="item.completed ? 'check-circle' : 'circle'" size="18" :color="item.completed ? 'var(--success-color)' : 'var(--text-muted)'" />
                                 </div>
                                 <span class="todo-title">
                                    {{ item.title }}
                                 </span>
                            </div>

                            <!-- Zone 2: Meta Data -->
                            <div class="todo-meta" style="display: flex; gap: 8px; align-items: center;">
                                <span v-if="item.labels" class="todo-labels">
                                    <span v-for="label in parseLabels(item.labels)" :key="label" class="label-tag">
                                        <LucideIcon name="tag" size="10" />
                                        {{ label }}
                                    </span>
                                </span>
                                <span v-if="item.start_date || item.end_date" class="todo-dates">
                                    <LucideIcon name="calendar" size="12" />
                                    <span v-if="item.start_date">{{ item.start_date }}</span>
                                    <span v-if="item.start_date && item.end_date">→</span>
                                    <span v-if="item.end_date">{{ item.end_date }}</span>
                                </span>
                            
                                <!-- Zone 3: Tools -->
                                <div class="todo-tools" style="margin-left: 12px;">
                                    <button @click.stop="showEditForm(item)" class="btn-small" title="Edit">Edit</button>
                                    <button @click.stop="deleteTodo(item.id)" class="btn-small btn-danger" title="Delete">Delete</button>
                                </div>
                            </div>
                        </div>
                     </template>
                </TreeView>
                <div v-if="treeData.length === 0" class="text-center text-muted" style="padding: 20px;">
                    No todos found. Add one above!
                </div>
            </div>

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

        const expandedTodos = ref([]); // Track expanded state for tree if needed, though TreeView handles it internally if we don't bind strict

        const onToggle = (item) => {
            item.expanded = !item.expanded;
        };

        const rootTodos = computed(() => {
            return todos.value.filter(t => !t.parent_id);
        });

        const filteredRootTodos = computed(() => {
            // If filtered, we still might want to show children.
            // But if filtering by label, we probably only want to show matches?
            // Logic in TodoItem was: Root is filtered. Children are ALWAYS shown.
            // We will maintain that logic.
            if (!selectedLabel.value) {
                return rootTodos.value;
            }
            return rootTodos.value.filter(t =>
                t.labels && t.labels.includes(selectedLabel.value)
            );
        });

        const treeData = computed(() => {
            // Transform flat list to hierarchy
            const build = (parentId) => {
                return todos.value
                    .filter(t => t.parent_id == parentId)
                    .sort((a, b) => (a.position - b.position) || (a.id - b.id))
                    .map(t => ({
                        ...t,
                        children: build(t.id)
                    }));
            };

            return filteredRootTodos.value.map(t => ({
                ...t,
                children: build(t.id)
            }));
        });


        const parseLabels = (labelsString) => {
            if (!labelsString) return [];
            return labelsString.split(',').map(l => l.trim()).filter(l => l);
        };

        const fetchSettings = async () => {
            try {
                const res = await fetch('/@/user/settings');
                if (res.ok) {
                    const data = await res.json();
                    if (data.settings && data.settings.default_todo_duration) {
                        defaultDuration.value = parseInt(data.settings.default_todo_duration);
                        localStorage.setItem('defaultDuration', defaultDuration.value);
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
            const res = await fetch('/@/todos');
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
                const res = await fetch('/@/todos', {
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
                    store.addNotification('Failed to add todo: ' + (error.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                store.addNotification('Connection error: ' + e.message, 'error');
            }
        };

        const toggleTodo = async (todo) => {
            const updated = !todo.completed;
            const res = await fetch(`/@/todos/${todo.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ completed: updated })
            });
            if (res.ok) {
                // We need to update the original item in todos.value, NOT just the clone in treeData
                const realTodo = todos.value.find(t => t.id === todo.id);
                if (realTodo) realTodo.completed = updated ? 1 : 0;
            }
        };

        const deleteTodo = async (id) => {
            if (!(await store.showConfirm('Delete Todo', 'Are you sure you want to delete this todo?'))) return;

            const res = await fetch(`/@/todos/${id}`, { method: 'DELETE' });
            if (res.ok) {
                todos.value = todos.value.filter(t => t.id !== id);
                store.addNotification('Todo deleted', 'success');
            } else {
                store.addNotification('Failed to delete todo', 'error');
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
            const res = await fetch(`/@/todos/${editingTodo.value.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(editForm.value)
            });

            if (res.ok) {
                const realTodo = todos.value.find(t => t.id === editingTodo.value.id);
                if (realTodo) Object.assign(realTodo, editForm.value);
                cancelEdit();
            }
        };

        const cancelEdit = () => {
            editingTodo.value = null;
            editForm.value = {};
        };

        // Handler for TreeView move event
        const onMove = async ({ sourceId, target, placement }) => {
            // sourceId: string/number
            // target: object (node)
            // placement: 'before', 'after', 'inside'

            const srcTodo = todos.value.find(t => t.id == sourceId);
            const targetTodo = todos.value.find(t => t.id == target.id);

            if (!srcTodo || !targetTodo) return;

            let newParentId = targetTodo.parent_id;
            // Calculations for new position
            const siblings = todos.value.filter(t => t.parent_id == newParentId).sort((a, b) => (a.position - b.position) || (a.id - b.id));

            // ... [Logic similar to previous onDrop, adapted] ...

            if (placement === 'inside') {
                newParentId = target.id;
                const childSiblings = todos.value.filter(t => t.parent_id == newParentId);
                const maxPos = childSiblings.reduce((max, t) => Math.max(max, t.position || 0), 0);
                const newPosition = maxPos + 1024;

                // Update state
                srcTodo.parent_id = newParentId;
                srcTodo.position = newPosition;

                // API call
                await updatePosition(srcTodo.id, newParentId, newPosition);
                return;
            }

            // Before/After logic
            const targetIdx = siblings.findIndex(t => t.id === target.id);
            let prevPos = -1024;
            let nextPos = 1000000;
            let newPosition = 0;

            if (placement === 'before') {
                if (targetIdx > 0) prevPos = siblings[targetIdx - 1].position;
                nextPos = targetTodo.position;
            } else { // after
                prevPos = targetTodo.position;
                if (targetIdx < siblings.length - 1) nextPos = siblings[targetIdx + 1].position;
            }
            newPosition = (prevPos + nextPos) / 2;

            srcTodo.parent_id = newParentId;
            srcTodo.position = newPosition;

            await updatePosition(srcTodo.id, newParentId, newPosition);
        };

        const updatePosition = async (id, parentId, position) => {
            await fetch('/@/todos/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id,
                    parent_id: parentId,
                    position
                })
            });
            // Force resort not needed as we updated state, and computed treeData resorts.
            // But we might need to trigger array update.
            todos.value = [...todos.value];
        }

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
            treeData, // New for TreeView
            newTodo,
            newLabels,
            newStartDate,
            newEndDate,
            newParentId,
            selectedLabel,
            editingTodo,
            editForm,
            allLabels,
            addTodo,
            saveEdit,
            cancelEdit,
            palette,
            newColor,
            showColorPicker,
            showEditColorPicker,
            // Actions
            toggleTodo,
            deleteTodo,
            showEditForm,
            parseLabels,
            onMove,
            onToggle
        };
    }
};
