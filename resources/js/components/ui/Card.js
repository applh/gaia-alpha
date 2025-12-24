export default {
    props: {
        title: String,
        loading: Boolean,
        noPadding: Boolean
    },
    template: `
        <div class="admin-card">
            <div v-if="title || $slots.header" class="card-header">
                <slot name="header">
                    <h3>{{ title }}</h3>
                </slot>
                <div v-if="$slots.actions" class="card-actions">
                    <slot name="actions"></slot>
                </div>
            </div>
            
            <div v-if="loading" class="card-loading">
                Loading...
            </div>
            
            <div v-else :class="{ 'card-body': !noPadding }">
                <slot></slot>
            </div>
            
            <div v-if="$slots.footer" class="card-footer">
                <slot name="footer"></slot>
            </div>
        </div>
    `
};
