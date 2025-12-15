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
```
