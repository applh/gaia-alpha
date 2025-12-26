import { ref, onMounted, computed, provide, inject } from 'vue';
import { store } from 'store';
import { api } from 'api';
import CalendarView from '/min/js/plugins/Todo/CalendarView.js';
import GanttView from '/min/js/plugins/Todo/GanttView.js';
import ColorPicker from 'ui/ColorPicker.js';
import TreeView from 'ui/TreeView.js';
import Icon from 'ui/Icon.js';
import Tabs from 'ui/Tabs.js';
import TabPane from 'ui/TabPane.js';
import Modal from 'ui/Modal.js';
import Input from 'ui/Input.js';
import UIButton from 'ui/Button.js';
import Tag from 'ui/Tag.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Container from 'ui/Container.js';
import Card from 'ui/Card.js';
import UISelect from 'ui/Select.js';

export default {
    components: {
        TreeView,
        CalendarView,
        GanttView,
        ColorPicker,
        LucideIcon: Icon,
        'ui-tabs': Tabs,
        'ui-tab-pane': TabPane,
        'ui-modal': Modal,
        'ui-input': Input,
        'ui-button': UIButton,
        'ui-tag': Tag,
        'ui-row': Row,
        'ui-col': Col,
        'ui-container': Container,
        'ui-card': Card,
        'ui-select': UISelect
    },
    template: `
        <ui-container class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">Projects</h2>
                <ui-tabs v-model="viewMode">
                    <ui-tab-pane label="List" name="list"></ui-tab-pane>
                    <ui-tab-pane label="Calendar" name="calendar"></ui-tab-pane>
                    <ui-tab-pane label="Gantt" name="gantt"></ui-tab-pane>
                </ui-tabs>
            </div>
            
            <ui-card v-if="viewMode === 'list'" style="margin-top: 24px;">
                <!-- Add new todo -->
                <div class="add-todo-container" style="margin-bottom: 24px; padding: 20px; background: rgba(255, 255, 255, 0.03); border: var(--glass-border); border-radius: var(--radius-lg);">
                    <ui-row :gutter="12" style="margin-bottom: 12px;">
                        <ui-col :span="20">
                            <ui-input 
                                v-model="newTodo" 
                                @keyup.enter="addTodo" 
                                placeholder="Add new todo..."
                            />
                        </ui-col>
                        <ui-col :span="4">
                            <ui-button variant="primary" @click="addTodo" style="width: 100%;">
                                <LucideIcon name="plus" size="18" style="margin-right: 4px;" />
                                Add
                            </ui-button>
                        </ui-col>
                    </ui-row>
                    
                    <ui-row :gutter="12">
                        <ui-col :xs="24" :sm="8">
                            <ui-input v-model="newLabels" placeholder="Labels (comma-separated)">
                                <template #prefix><LucideIcon name="tag" size="16" /></template>
                            </ui-input>
                        </ui-col>
                        <ui-col :xs="24" :sm="8">
                             <div style="display: flex; align-items: center; gap: 8px;">
                                 <LucideIcon name="calendar" size="16" class="text-secondary" />
                                 <input type="date" v-model="newStartDate" class="form-control" style="width: auto;">
                                 <span class="text-secondary">-</span>
                                 <input type="date" v-model="newEndDate" class="form-control" style="width: auto;">
                             </div>
                        </ui-col>
                        <ui-col :xs="24" :sm="8">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div class="color-select-wrapper" style="position: relative;">
                                    <div 
                                        class="color-indicator" 
                                        :style="{ width: '36px', height: '36px', borderRadius: '8px', cursor: 'pointer', backgroundColor: newColor || 'transparent', border: newColor ? '1px solid ' + newColor : '1px solid var(--border-color)' }"
                                        @click="showColorPicker = !showColorPicker"
                                        title="Select Color"
                                    ></div>
                                    <ui-card v-if="showColorPicker" style="position: absolute; top: 100%; left: 0; z-index: 100; margin-top: 8px; width: 200px;">
                                         <ColorPicker v-model="newColor" :palette="palette" />
                                         <div class="picker-footer" style="margin-top: 8px; text-align: right;">
                                                                                        <ui-button size="sm" @click="showColorPicker = false">Close</ui-button>

                                         </div>
                                    </ui-card>
                                </div>
                                <ui-select v-model="newParentId" :options="parentOptions" placeholder="No parent" style="flex: 1;" />
                            </div>
                        </ui-col>
                    </ui-row>
                </div>

                <!-- Filter by label -->
                <div class="todo-filters" v-if="allLabels.length > 0" style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-weight: 600; font-size: 0.9rem;">Filter by label:</span>
                    <ui-tag 
                        @click="selectedLabel = null" 
                        :type="selectedLabel === null ? 'primary' : 'info'"
                        style="cursor: pointer;"
                    >All</ui-tag>
                    <ui-tag 
                        v-for="label in allLabels" 
                        :key="label"
                        @click="selectedLabel = label"
                        :type="selectedLabel === label ? 'primary' : 'info'"
                        style="cursor: pointer;"
                    >{{ label }}</ui-tag>
                </div>
                
                <!-- Todo list with TreeView -->
                <div class="todo-tree-container">
                    <TreeView 
                        :items="treeData" 
                        idKey="id"
                        childrenKey="children"
                        labelKey="title"
                        :draggable="true"
                        :expandedIds="expandedIds"
                        :allowDrop="() => true" 
                        @move="onMove"
                        @toggle="onToggle"
                    >
                         <template #item="{ item }">
                            <div 
                                class="todo-item-card" 
                                :class="{ completed: item.completed }"
                                :style="{ borderLeft: item.color ? '4px solid ' + item.color : '', width: '100%' }"
                                style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.02); border-radius: var(--radius-md); border: var(--glass-border); margin-bottom: 4px;"
                            >
                                <!-- Zone 1: Main Content -->
                                <div class="todo-main" style="display: flex; align-items: center; gap: 8px;">
                                     <div @click.stop="toggleTodo(item)" class="todo-checkbox" style="cursor: pointer;">
                                        <LucideIcon :name="item.completed ? 'check-circle' : 'circle'" size="18" :color="item.completed ? 'var(--success-color)' : 'var(--text-muted)'" />
                                     </div>
                                     <span class="todo-title" :style="{ textDecoration: item.completed ? 'line-through' : 'none', opacity: item.completed ? 0.6 : 1 }">
                                        {{ item.title }}
                                     </span>
                                </div>

                                <!-- Zone 2: Meta Data -->
                                <div class="todo-meta" style="display: flex; gap: 8px; align-items: center;">
                                    <div v-if="item.labels" style="display: flex; gap: 4px;">
                                        <ui-tag v-for="label in parseLabels(item.labels)" :key="label" size="sm" type="info">
                                            <LucideIcon name="tag" size="10" style="margin-right: 2px;" />
                                            {{ label }}
                                        </ui-tag>
                                    </div>
                                    <span v-if="item.start_date || item.end_date" class="todo-dates" style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                                        <LucideIcon name="calendar" size="12" />
                                        <span v-if="item.start_date">{{ item.start_date }}</span>
                                        <span v-if="item.start_date && item.end_date">→</span>
                                        <span v-if="item.end_date">{{ item.end_date }}</span>
                                    </span>
                                
                                    <!-- Zone 3: Tools -->
                                    <div class="todo-tools" style="margin-left: 12px; display: flex; gap: 4px;">
                                        <ui-button size="sm" @click.stop="showEditForm(item)">Edit</ui-button>
                                        <ui-button size="sm" variant="danger" @click.stop="deleteTodo(item.id)">Delete</ui-button>
                                    </div>
                                </div>
                            </div>
                         </template>
                    </TreeView>
                    <div v-if="treeData.length === 0" class="text-center text-muted" style="padding: 40px;">
                        No todos found. Add one above!
                    </div>
                </div>
            </ui-card>

            <div v-else-if="viewMode === 'calendar'" style="margin-top: 24px;">
                <ui-card>
                    <CalendarView :todos="todos" />
                </ui-card>
            </div>

            <div v-else-if="viewMode === 'gantt'" style="margin-top: 24px;">
                <ui-card>
                    <GanttView :todos="todos" />
                </ui-card>
            </div>
            
            <!-- Edit modal -->
            <ui-modal 
                v-model="modalVisible" 
                title="Edit Todo"
                size="medium"
            >
                <div v-if="editingTodo">
                    <ui-row :gutter="12" style="margin-bottom: 16px;">
                        <ui-col :span="24">
                            <ui-input v-model="editForm.title" label="Title" placeholder="What needs to be done?" />
                        </ui-col>
                    </ui-row>
                    
                    <ui-row :gutter="12" style="margin-bottom: 16px;">
                        <ui-col :span="24">
                            <ui-input v-model="editForm.labels" label="Labels" placeholder="comma-separated tags" />
                        </ui-col>
                    </ui-row>

                    <ui-row :gutter="12" style="margin-bottom: 16px;">
                        <ui-col :span="12">
                            <label class="form-label" style="display: block; margin-bottom: 8px;">Start Date</label>
                            <input type="date" v-model="editForm.start_date" class="form-control" style="width: 100%;">
                        </ui-col>
                        <ui-col :span="12">
                            <label class="form-label" style="display: block; margin-bottom: 8px;">End Date</label>
                            <input type="date" v-model="editForm.end_date" class="form-control" style="width: 100%;">
                        </ui-col>
                    </ui-row>

                    <ui-row :gutter="12" style="margin-bottom: 16px;">
                        <ui-col :span="12">
                            <label class="form-label" style="display: block; margin-bottom: 8px;">Color</label>
                            <div style="position: relative;">
                                <div 
                                    class="color-indicator" 
                                    :style="{ width: '100%', height: '36px', borderRadius: '8px', cursor: 'pointer', backgroundColor: editForm.color || '#transparent', border: editForm.color ? '1px solid ' + editForm.color : '1px solid var(--border-color)' }"
                                    @click="showEditColorPicker = !showEditColorPicker"
                                ></div>
                                <ui-card v-if="showEditColorPicker" style="position: absolute; top: 100%; left: 0; z-index: 100; margin-top: 8px; width: 200px;">
                                    <ColorPicker v-model="editForm.color" :palette="palette" />
                                    <div class="picker-footer" style="margin-top: 8px; text-align: right;">
                                        <ui-button size="sm" @click="showEditColorPicker = false">Close</ui-button>
                                    </div>
                                </ui-card>
                            </div>
                        </ui-col>
                        <ui-col :span="12">
                            <ui-select v-model="editForm.parent_id" :options="parentOptionsForEdit" label="Parent Todo" placeholder="No parent" />
                        </ui-col>
                    </ui-row>
                </div>
                <template #footer>
                    <ui-button variant="primary" @click="saveEdit">Save Changes</ui-button>
                    <ui-button @click="cancelEdit">Cancel</ui-button>
                </template>
            </ui-modal>
        </ui-container>
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
        const modalVisible = ref(false);
        const expandedIds = ref([]);


        const parentOptions = computed(() => {
            return [
                { label: 'No parent', value: null },
                ...todos.value.map(t => ({ label: t.title, value: t.id }))
            ];
        });

        const parentOptionsForEdit = computed(() => {
            if (!editingTodo.value) return [{ label: 'No parent', value: null }];
            return [
                { label: 'No parent', value: null },
                ...todos.value
                    .filter(t => t.id !== editingTodo.value.id)
                    .map(t => ({ label: t.title, value: t.id }))
            ];
        });

        const onToggle = (item) => {
            const index = expandedIds.value.indexOf(item.id);
            if (index > -1) {
                expandedIds.value.splice(index, 1);
            } else {
                expandedIds.value.push(item.id);
            }
        };

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

        const treeData = computed(() => {
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
                const data = await api.get('user/settings');
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
            } catch (e) {
                console.error('Failed to fetch settings:', e);
            }
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
            try {
                todos.value = await api.get('todos');
            } catch (e) {
                store.addNotification('Failed to fetch todos: ' + e.message, 'error');
            }
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
                const todo = await api.post('todos', data);
                todos.value.push(todo);
                newTodo.value = '';
                newLabels.value = '';
                newStartDate.value = new Date().toISOString().split('T')[0];
                newEndDate.value = getEndDate(newStartDate.value, defaultDuration.value);
                newColor.value = '';
                showColorPicker.value = false;
                newParentId.value = null;
            } catch (e) {
                store.addNotification('Failed to add todo: ' + (e.message || 'Unknown error'), 'error');
            }
        };

        const toggleTodo = async (todo) => {
            const updated = !todo.completed;
            try {
                await api.patch(`todos/${todo.id}`, { completed: updated });
                const realTodo = todos.value.find(t => t.id === todo.id);
                if (realTodo) realTodo.completed = updated ? 1 : 0;
            } catch (e) {
                store.addNotification('Failed to update todo status: ' + e.message, 'error');
            }
        };

        const deleteTodo = async (id) => {
            if (!(await store.showConfirm('Delete Todo', 'Are you sure you want to delete this todo?'))) return;

            try {
                await api.delete(`todos/${id}`);
                todos.value = todos.value.filter(t => t.id !== id);
                store.addNotification('Todo deleted', 'success');
            } catch (e) {
                store.addNotification('Failed to delete todo: ' + e.message, 'error');
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
            modalVisible.value = true;
        };

        const saveEdit = async () => {
            try {
                await api.put(`todos/${editingTodo.value.id}`, editForm.value);
                const realTodo = todos.value.find(t => t.id === editingTodo.value.id);
                if (realTodo) Object.assign(realTodo, editForm.value);
                cancelEdit();
                store.addNotification('Todo updated', 'success');
            } catch (e) {
                store.addNotification('Failed to update todo: ' + e.message, 'error');
            }
        };

        const cancelEdit = () => {
            editingTodo.value = null;
            editForm.value = {};
            modalVisible.value = false;
        };

        const onMove = async ({ sourceId, target, placement }) => {
            const srcTodo = todos.value.find(t => t.id == sourceId);
            const targetTodo = todos.value.find(t => t.id == target.id);

            if (!srcTodo || !targetTodo) return;

            let newParentId = targetTodo.parent_id;
            const siblings = todos.value.filter(t => t.parent_id == newParentId).sort((a, b) => (a.position - b.position) || (a.id - b.id));

            if (placement === 'inside') {
                newParentId = target.id;
                const childSiblings = todos.value.filter(t => t.parent_id == newParentId);
                const maxPos = childSiblings.reduce((max, t) => Math.max(max, t.position || 0), 0);
                const newPosition = maxPos + 1024;

                srcTodo.parent_id = newParentId;
                srcTodo.position = newPosition;

                if (!expandedIds.value.includes(newParentId)) {
                    expandedIds.value.push(newParentId);
                }

                await updatePosition(srcTodo.id, newParentId, newPosition);
                return;
            }

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
            try {
                await api.post('todos/reorder', {
                    id,
                    parent_id: parentId,
                    position
                });
                await fetchTodos();
            } catch (e) {
                store.addNotification('Failed to move todo: ' + e.message, 'error');
            }
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
            treeData,
            newTodo,
            newLabels,
            newStartDate,
            newEndDate,
            newParentId,
            selectedLabel,
            editingTodo,
            editForm,
            modalVisible,
            allLabels,
            parentOptions,
            parentOptionsForEdit,
            addTodo,
            saveEdit,
            cancelEdit,
            palette,
            newColor,
            showColorPicker,
            showEditColorPicker,
            toggleTodo,
            deleteTodo,
            showEditForm,
            parseLabels,
            onMove,
            onToggle,
            expandedIds
        };
    }
};
;
