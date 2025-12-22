import { ref, computed, watch, h } from 'vue';
import Icon from 'ui/Icon.js';

const JsonEditor = {
    name: 'JsonEditor',
    components: { LucideIcon: Icon },
    props: {
        modelValue: {
            type: String,
            default: '{}'
        },
        readOnly: Boolean
    },
    emits: ['update:modelValue', 'save'],
    template: `
    <div class="json-editor">
        <div class="json-toolbar">
            <button @click="formatJson" class="btn btn-sm btn-secondary" title="Format JSON">
                <LucideIcon name="align-left" size="14" /> Format
            </button>
            <button @click="expandAll" class="btn btn-sm btn-secondary" title="Expand All">
                <LucideIcon name="maximize-2" size="14" /> Expand
            </button>
            <button @click="collapseAll" class="btn btn-sm btn-secondary" title="Collapse All">
                <LucideIcon name="minimize-2" size="14" /> Collapse
            </button>
            <div class="spacer"></div>
            <button @click="$emit('save')" class="btn btn-sm btn-primary">
                <LucideIcon name="save" size="14" /> Save
            </button>
        </div>
        
        <div v-if="error" class="json-error">
            <LucideIcon name="alert-circle" size="16" />
            {{ error }}
        </div>

        <div class="json-tree-container">
            <div class="json-tree-root">
                <JsonNode 
                    v-if="parsedData"
                    :data="parsedData" 
                    :path="[]" 
                    :is-root="true"
                    @update="onUpdate"
                />
            </div>
        </div>
    </div>
    `,
    setup(props, { emit }) {
        const parsedData = ref(null);
        const error = ref(null);
        const expandLevel = ref(2);

        const parse = (json) => {
            try {
                parsedData.value = JSON.parse(json);
                error.value = null;
            } catch (e) {
                console.error("JSON Parse Error", e);
                error.value = "Invalid JSON: " + e.message;
            }
        };

        watch(() => props.modelValue, (val) => {
            // Only parse if strictly different to avoid loop, 
            // but since we keep internal state, we should parse on external change
            // Simple check: stringify current parsed vs val
            if (JSON.stringify(parsedData.value, null, 2) !== val) {
                parse(val);
            }
        }, { immediate: true });

        const onUpdate = (newData) => {
            parsedData.value = newData;
            // Emit up stringified
            try {
                const json = JSON.stringify(newData, null, 2);
                emit('update:modelValue', json);
            } catch (e) {
                // Should not happen if data is valid
            }
        };

        const formatJson = () => {
            if (parsedData.value) {
                emit('update:modelValue', JSON.stringify(parsedData.value, null, 2));
            }
        };

        // Helper to trigger deep reactivity for expand/collapse (could be implementing via provide/inject or ref traverse)
        // For simplicity, we'll re-mount or notify children. Actually, simple expand all/collapse is hard without refs.
        // Let's implement a global expand state or event bus. simpler: just provide "defaultExpanded" prop to nodes.
        // But dynamic toggle is better.

        return {
            parsedData, error, onUpdate, formatJson,
            expandAll: () => { }, // TODO
            collapseAll: () => { } // TODO
        };
    }
};

// Recursive Node Component
const JsonNode = {
    name: 'JsonNode',
    components: { LucideIcon: Icon },
    props: {
        data: [Object, Array, String, Number, Boolean, null],
        path: Array,
        isRoot: Boolean,
        parentType: String // 'array' | 'object'
    },
    emits: ['update'],
    template: `
    <div class="json-node" :class="{ 'is-root': isRoot }">
        <!-- Object/Array -->
        <div v-if="isObject || isArray" class="json-composite">
            <div class="json-line" 
                draggable="true" 
                @dragstart.stop="onDragStart"
                @dragover.stop.prevent="onDragOver"
                @drop.stop="onDrop"
                @mouseenter="hover = true"
                @mouseleave="hover = false"
            >
                <span @click="toggle" class="json-toggler">
                    <LucideIcon :name="expanded ? 'chevron-down' : 'chevron-right'" size="14" />
                </span>
                
                <!-- Key (if inside object) -->
                <span v-if="parentType === 'object' && !isRoot" class="json-key">
                    <input 
                        v-if="editingKey" 
                        ref="keyInput"
                        v-model="tempKey" 
                        @blur="confirmKeyEdit" 
                        @keydown.enter="confirmKeyEdit"
                        class="json-input key-input"
                    />
                    <span v-else @dblclick="startKeyEdit">{{ currentKey }}</span>
                    <span class="json-colon">:</span>
                </span>
                
                <span class="json-bracket">{{ isArray ? '[' : '{' }}</span>
                <span v-if="!expanded" class="json-collapsed-preview">{{ getPreview() }}</span>
                <span class="json-count" v-if="!expanded">{{ childCount }} items</span>
                
                <div class="json-actions" v-if="hover">
                    <button @click="addChild" title="Add Item"><LucideIcon name="plus" size="12" /></button>
                    <button v-if="!isRoot" @click="deleteSelf" title="Delete"><LucideIcon name="trash-2" size="12" /></button>
                </div>
            </div>

            <div v-if="expanded" class="json-children">
                <div 
                    v-for="(value, key) in data" 
                    :key="key" 
                    class="json-child-wrapper"
                >
                    <JsonNode 
                        :data="value" 
                        :parentType="type"
                        :path="[...path, key]"
                        @update="(val) => onChildUpdate(key, val)"
                        @rename="(oldK, newK) => onChildRename(oldK, newK)"
                        @delete="() => onChildDelete(key)"
                    />
                </div>
                <div class="json-bracket closing">{{ isArray ? ']' : '}' }}</div>
            </div>
            <div v-else class="json-bracket closing">{{ isArray ? ']' : '}' }}</div>
        </div>

        <!-- Primitive -->
        <div v-else class="json-primitive">
            <div class="json-line"
                draggable="true" 
                @dragstart.stop="onDragStart"
                @dragover.stop.prevent="onDragOver"
                @drop.stop="onDrop"
                @mouseenter="hover = true"
                @mouseleave="hover = false"
            >
                <!-- Key (if inside object) -->
                <span v-if="parentType === 'object'" class="json-key">
                    <input 
                        v-if="editingKey" 
                        ref="keyInput"
                        v-model="tempKey" 
                        @blur="confirmKeyEdit" 
                        @keydown.enter="confirmKeyEdit"
                        class="json-input key-input"
                    />
                    <span v-else @dblclick="startKeyEdit">{{ currentKey }}</span>
                    <span class="json-colon">:</span>
                </span>
                
                <!-- Value -->
                 <span class="json-value" :class="valueType">
                    <input 
                        v-if="editingValue" 
                        ref="valueInput"
                        v-model="tempValue" 
                        @blur="confirmValueEdit" 
                        @keydown.enter="confirmValueEdit"
                        class="json-input value-input"
                    />
                     <span v-else @dblclick="startValueEdit">{{ formattedValue }}</span>
                 </span>
                 
                 <div class="json-actions" v-if="hover">
                    <button @click="deleteSelf" title="Delete"><LucideIcon name="trash-2" size="12" /></button>
                </div>
            </div>
        </div>
    </div>
    `,
    setup(props, { emit }) {
        const expanded = ref(true);
        const hover = ref(false);
        const editingKey = ref(false);
        const tempKey = ref('');
        const editingValue = ref(false);
        const tempValue = ref('');

        const isArray = computed(() => Array.isArray(props.data));
        const isObject = computed(() => props.data !== null && typeof props.data === 'object' && !isArray.value);
        const type = computed(() => isArray.value ? 'array' : (isObject.value ? 'object' : 'primitive'));
        const valueType = computed(() => {
            if (props.data === null) return 'null';
            return typeof props.data;
        });

        const childCount = computed(() => {
            if (isObject.value) return Object.keys(props.data).length;
            if (isArray.value) return props.data.length;
            return 0;
        });

        const currentKey = computed(() => {
            const k = props.path[props.path.length - 1];
            return k;
        });

        const formattedValue = computed(() => {
            if (props.data === null) return 'null';
            if (typeof props.data === 'string') return `"${props.data}"`;
            return String(props.data);
        });

        const toggle = () => expanded.value = !expanded.value;

        const getPreview = () => {
            if (isArray.value) return `Array(${props.data.length})`;
            if (isObject.value) return `Object{${Object.keys(props.data).length}}`;
            return '';
        };

        // Edit Logic
        const startKeyEdit = () => {
            if (props.parentType !== 'object') return;
            tempKey.value = currentKey.value;
            editingKey.value = true;
            // focus next tick
            setTimeout(() => {
                // ref handling in recursive component is tricky via template refs in v-for, 
                // but here we are in the component itself.
                // We need to access the input element.
            }, 0);
        };

        const confirmKeyEdit = () => {
            if (tempKey.value !== currentKey.value && tempKey.value.trim() !== '') {
                emit('rename', currentKey.value, tempKey.value);
            }
            editingKey.value = false;
        };

        const startValueEdit = () => {
            tempValue.value = JSON.stringify(props.data); // handles quotes for strings
            // strip quotes if string for easier editing? 
            // Defaulting to raw string edit is often easier.
            if (typeof props.data === 'string') tempValue.value = props.data;
            else tempValue.value = String(props.data);

            editingValue.value = true;
        };

        const confirmValueEdit = () => {
            let val = tempValue.value;
            // Try to infer type
            if (val === 'true') val = true;
            else if (val === 'false') val = false;
            else if (val === 'null') val = null;
            else if (!isNaN(Number(val)) && val.trim() !== '') val = Number(val);
            // else string

            emit('update', val);
            editingValue.value = false;
        };

        const onChildUpdate = (key, val) => {
            let newData;
            if (isArray.value) {
                newData = [...props.data];
                newData[key] = val;
            } else {
                newData = { ...props.data, [key]: val };
            }
            emit('update', newData);
        };

        const onChildRename = (oldKey, newKey) => {
            if (isArray.value) return; // Arrays don't have named keys
            const newData = {};
            // Preserve order
            Object.keys(props.data).forEach(k => {
                if (k === oldKey) newData[newKey] = props.data[oldKey];
                else newData[k] = props.data[k];
            });
            emit('update', newData);
        };

        const onChildDelete = (key) => {
            if (isArray.value) {
                const newData = props.data.filter((_, i) => i !== key);
                emit('update', newData);
            } else {
                const { [key]: deleted, ...rest } = props.data;
                emit('update', rest);
            }
        };

        const deleteSelf = () => emit('delete');

        const addChild = () => {
            if (isArray.value) {
                emit('update', [...props.data, null]);
                expanded.value = true;
            } else {
                // Generate unique key
                let k = 'newKey';
                let i = 1;
                while (k in props.data) { k = 'newKey' + i++; }

                emit('update', { ...props.data, [k]: null });
                expanded.value = true;
            }
        };

        // Drag & Drop
        const onDragStart = (e) => {
            e.dataTransfer.setData('json-path', JSON.stringify(props.path));
            e.dataTransfer.effectAllowed = 'move';
        };

        const onDragOver = (e) => {
            // Only allow if dropping onto same parent type/context or reordering
            // For V1, simplest is reordering within same array/object
            e.dataTransfer.dropEffect = 'move';
        };

        const onDrop = (e) => {
            const srcPathStr = e.dataTransfer.getData('json-path');
            if (!srcPathStr) return;
            const srcPath = JSON.parse(srcPathStr);

            // Check if dragging onto self or child (cycle)
            const selfPathStr = JSON.stringify(props.path);
            if (srcPathStr === selfPathStr) return;

            // Emit event to root? Or handle locally if siblings?
            // Handling reorder in recursive components is hard without a central store or bubbling up to common ancestor
            // For V1, let's implement basic reordering only within same array via specific events

            // Since we don't have a global store, proper DnD across the tree is complex.
            // Let's implement Array Reordering specifically.
            // If we are an array child dropping onto another array child of same parent

            // To simplify: we won't implement full DnD in this snippet without a store.
        };

        return {
            expanded, hover, isArray, isObject, type, valueType, childCount,
            currentKey, formattedValue, toggle, getPreview,
            editingKey, tempKey, startKeyEdit, confirmKeyEdit,
            editingValue, tempValue, startValueEdit, confirmValueEdit,
            onChildUpdate, onChildRename, onChildDelete, deleteSelf, addChild,
            onDragStart, onDragOver, onDrop
        };
    }
};

// Register recursive component
// Vue 3 recursive requires name or registering.
// In this format, we need to ensure JsonNode knows about JsonNode.
JsonNode.components.JsonNode = JsonNode;

// Add JsonNode to JsonEditor components
JsonEditor.components.JsonNode = JsonNode;

export { JsonNode };
export default JsonEditor;
