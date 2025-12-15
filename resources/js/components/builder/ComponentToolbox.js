import { onMounted, reactive } from 'vue';

export default {
    name: 'ComponentToolbox',
    template: `
        <div class="component-toolbox">
            
            <div v-for="(group, name) in groups" :key="name" class="toolbox-group">
                <h4>{{ name }}</h4>
                <div class="toolbox-grid">
                    <div 
                        v-for="comp in group" 
                        :key="comp.type" 
                        class="toolbox-item"
                        draggable="true"
                        @dragstart="startDrag($event, comp.type)"
                        @click="$emit('add-component', comp.type)"
                    >
                        <i :class="'icon-' + comp.icon"></i>
                        <span>{{ comp.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
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

        return {
            groups,
            startDrag
        };
    }
};
