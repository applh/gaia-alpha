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

        return { update };
    }
};
