import { onMounted, reactive, defineAsyncComponent } from 'vue';

const LucideIcon = defineAsyncComponent(() => import('../Icon.js'));

export default {
    name: 'ComponentToolbox',
    components: { LucideIcon },
    template: `
        <div class="component-toolbox">
            
            <div v-for="(group, name) in groups" :key="name" class="toolbox-group">
                <h4 
                    @click="toggleGroup(name)" 
                    style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none;"
                >
                    {{ name }}
                    <LucideIcon 
                        :name="collapsedGroups.has(name) ? 'chevron-right' : 'chevron-down'" 
                        size="16" 
                        style="opacity: 0.6;"
                    />
                </h4>
                <div class="toolbox-grid" v-show="!collapsedGroups.has(name)">
                    <div 
                        v-for="comp in group" 
                        :key="comp.type" 
                        class="toolbox-item"
                        draggable="true"
                        @dragstart="startDrag($event, comp.type)"
                        @click="$emit('add-component', comp.type)"
                    >
                        <LucideIcon :name="comp.icon" size="16" class="toolbox-icon" />
                        <span>{{ comp.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const collapsedGroups = reactive(new Set());

        const groups = reactive({
            'Data Display': [
                { type: 'data-table', label: 'Table', icon: 'table' },
                { type: 'stat-card', label: 'Stat Card', icon: 'info' },
                { type: 'data-list', label: 'List', icon: 'list' }
            ],
            'Input': [
                { type: 'form', label: 'Form Container', icon: 'check-square' },
                { type: 'input', label: 'Text Input', icon: 'type' },
                { type: 'select', label: 'Select', icon: 'list' },
                { type: 'button', label: 'Button', icon: 'mouse-pointer' }
            ],
            'Visualization': [
                { type: 'chart-bar', label: 'Bar Chart', icon: 'bar-chart' },
                { type: 'chart-line', label: 'Line Chart', icon: 'line-chart' }
            ],
            'Actions': [
                { type: 'action-button', label: 'Action Button', icon: 'zap' },
                { type: 'link-button', label: 'Link Button', icon: 'link' }
            ],
            'Layout': [
                { type: 'container', label: 'Container', icon: 'box' },
                { type: 'row', label: 'Row', icon: 'columns' },
                { type: 'col', label: 'Column', icon: 'layout' }
            ],
            'Custom': []
        });

        const fetchCustomComponents = async () => {
            try {
                const res = await fetch('/@/admin/component-builder/list');
                const data = await res.json();
                if (Array.isArray(data)) {
                    groups['Custom'] = data.map(c => ({
                        type: 'custom:' + c.view_name,
                        label: c.title,
                        icon: c.icon || 'puzzle'
                    }));
                }
            } catch (e) {
                console.error('Failed to load custom components', e);
            }
        };

        onMounted(() => {
            fetchCustomComponents();
        });

        const startDrag = (event, type) => {
            event.dataTransfer.dropEffect = 'copy';
            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('component-type', type);
        };

        const toggleGroup = (name) => {
            if (collapsedGroups.has(name)) {
                collapsedGroups.delete(name);
            } else {
                collapsedGroups.add(name);
            }
        };

        return {
            groups,
            collapsedGroups,
            startDrag,
            toggleGroup
        };
    }
};
