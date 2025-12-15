import { computed } from 'vue';

export default {
    name: 'ComponentProperties',
    props: {
        component: Object
    },
    template: `
        <div class="component-properties">
            <h3>Properties</h3>
            <div v-if="!component" class="empty-state">
                Select a component to edit properties
            </div>
            <div v-else>
                <div class="form-group">
                    <label>ID</label>
                    <input type="text" :value="component.id" disabled>
                </div>
                <div class="form-group">
                    <label>Label</label>
                    <input 
                        type="text" 
                        :value="component.label" 
                        @input="update('label', $event.target.value)"
                    >
                </div>
                
                <hr>
                
                <!-- Dynamic props based on type -->
                <div v-if="component.type === 'stat-card'">
                    <div class="form-group">
                        <label>Value</label>
                        <input 
                            type="text" 
                            :value="component.props.value"
                            @input="update('props.value', $event.target.value)"
                        >
                    </div>
                </div>
                
                <div v-if="component.type === 'data-table'">
                     <div class="form-group">
                        <label>Data Endpoint</label>
                        <input 
                            type="text" 
                             :value="component.props.endpoint"
                             @input="update('props.endpoint', $event.target.value)"
                        >
                    </div>
                </div>

                <!-- Form Props -->
                <div v-if="component.type === 'form'">
                     <div class="form-group">
                        <label>Action URL</label>
                        <input 
                            type="text" 
                             :value="component.props.action"
                             @input="update('props.action', $event.target.value)"
                        >
                    </div>
                    <div class="form-group">
                        <label>Method</label>
                        <select 
                            :value="component.props.method || 'POST'"
                            @change="update('props.method', $event.target.value)"
                        >
                            <option value="POST">POST</option>
                            <option value="GET">GET</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                </div>

                <!-- Input Props -->
                <div v-if="component.type === 'input'">
                     <div class="form-group">
                        <label>Name (Field Key)</label>
                        <input 
                            type="text" 
                             :value="component.props.name"
                             @input="update('props.name', $event.target.value)"
                        >
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select 
                            :value="component.props.type || 'text'"
                            @change="update('props.type', $event.target.value)"
                        >
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="email">Email</option>
                            <option value="password">Password</option>
                            <option value="date">Date</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Placeholder</label>
                        <input 
                            type="text" 
                             :value="component.props.placeholder"
                             @input="update('props.placeholder', $event.target.value)"
                        >
                    </div>
                </div>

                <!-- Select Props -->
                <div v-if="component.type === 'select'">
                    <div class="form-group">
                        <label>Name (Field Key)</label>
                        <input 
                            type="text" 
                             :value="component.props.name"
                             @input="update('props.name', $event.target.value)"
                        >
                    </div>
                    <div class="form-group">
                        <label>Options (JSON)</label>
                        <textarea 
                             :value="component.props.options ? JSON.stringify(component.props.options) : '[]'"
                             @change="tryUpdateJson('props.options', $event.target.value)"
                             rows="4"
                        ></textarea>
                        <small class="text-muted">Array of objects: [{"value":"1", "label":"One"}]</small>
                    </div>
                </div>

                <!-- Button Props -->
                <div v-if="component.type === 'button'">
                     <div class="form-group">
                        <label>Type</label>
                         <select 
                            :value="component.props.type || 'button'"
                            @change="update('props.type', $event.target.value)"
                        >
                            <option value="button">Button</option>
                            <option value="submit">Submit</option>
                            <option value="reset">Reset</option>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>Variant</label>
                         <select 
                            :value="component.props.variant || 'primary'"
                            @change="update('props.variant', $event.target.value)"
                        >
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>
                </div>

                <!-- Action Button Props -->
                <div v-if="component.type === 'action-button'">
                     <div class="form-group">
                        <label>Action Name (Event)</label>
                        <input 
                            type="text" 
                             :value="component.props.action"
                             @input="update('props.action', $event.target.value)"
                             placeholder="e.g. refresh"
                        >
                    </div>
                     <div class="form-group">
                        <label>Variant</label>
                         <select 
                            :value="component.props.variant || 'primary'"
                            @change="update('props.variant', $event.target.value)"
                        >
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="danger">Danger</option>
                            <option value="success">Success</option>
                        </select>
                    </div>
                </div>

                <!-- Link Button Props -->
                <div v-if="component.type === 'link-button'">
                     <div class="form-group">
                        <label>URL</label>
                        <input 
                            type="text" 
                             :value="component.props.href"
                             @input="update('props.href', $event.target.value)"
                        >
                    </div>
                     <div class="form-group">
                        <label>Target</label>
                         <select 
                            :value="component.props.target || '_self'"
                            @change="update('props.target', $event.target.value)"
                        >
                            <option value="_self">Same Tab</option>
                            <option value="_blank">New Tab</option>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>Variant</label>
                         <select 
                            :value="component.props.variant || 'secondary'"
                            @change="update('props.variant', $event.target.value)"
                        >
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                        </select>
                    </div>
                </div>

                <!-- Chart Props -->
                <div v-if="component.type && component.type.startsWith('chart-')">
                     <div class="form-group">
                        <label>Endpoint</label>
                        <input 
                            type="text" 
                             :value="component.props.endpoint"
                             @input="update('props.endpoint', $event.target.value)"
                        >
                    </div>
                     <div class="form-group">
                        <label>Title</label>
                        <input 
                            type="text" 
                             :value="component.props.title"
                             @input="update('props.title', $event.target.value)"
                        >
                    </div>
                </div>
                
                <!-- Layout Container Props -->
                <div v-if="component.type === 'container'">
                    <div class="form-group">
                        <label>Fluid Width</label>
                         <select 
                            :value="component.props.fluid || false"
                            @change="update('props.fluid', $event.target.value === 'true')"
                        >
                            <option :value="false">Fixed (Centered)</option>
                            <option :value="true">Fluid (100%)</option>
                        </select>
                    </div>
                </div>

                <!-- Layout Row Props -->
                <div v-if="component.type === 'row'">
                    <div class="form-group">
                        <label>Gutter</label>
                         <select 
                            :value="component.props.gutter || 'md'"
                            @change="update('props.gutter', $event.target.value)"
                        >
                            <option value="none">None</option>
                            <option value="sm">Small</option>
                            <option value="md">Medium</option>
                            <option value="lg">Large</option>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>Align Items</label>
                         <select 
                            :value="component.props.align || 'start'"
                            @change="update('props.align', $event.target.value)"
                        >
                            <option value="start">Start</option>
                            <option value="center">Center</option>
                            <option value="end">End</option>
                            <option value="stretch">Stretch</option>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>Justify Content</label>
                         <select 
                            :value="component.props.justify || 'start'"
                            @change="update('props.justify', $event.target.value)"
                        >
                            <option value="start">Start</option>
                            <option value="center">Center</option>
                            <option value="end">End</option>
                            <option value="between">Space Between</option>
                            <option value="around">Space Around</option>
                        </select>
                    </div>
                </div>
                
                <!-- Layout Column Props -->
                <div v-if="component.type === 'col'">
                     <div class="form-group">
                        <label>Width (1-12)</label>
                        <input 
                            type="number" 
                            min="1"
                            max="12"
                             :value="component.props.width || 12"
                             @input="update('props.width', $event.target.value)"
                        >
                    </div>
                </div>

            </div>
        </div>
    `,
    setup(props, { emit }) {
        const update = (key, value) => {
            emit('update', {
                id: props.component.id,
                key,
                value
            });
        };

        const tryUpdateJson = (key, value) => {
            try {
                const parsed = JSON.parse(value);
                update(key, parsed);
            } catch (e) {
                console.error("Invalid JSON", e);
                // Maybe show toast error
            }
        };

        return { update, tryUpdateJson };
    },
    styles: `
        .component-properties input,
        .component-properties select,
        .component-properties textarea {
            width: 100%;
            padding: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 4px;
            margin-top: 4px;
        }
        .text-muted {
            font-size: 0.8rem;
            color: #aaa;
            display: block;
            margin-top: 4px;
        }
    `
};
