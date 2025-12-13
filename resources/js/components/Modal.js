export default {
    props: {
        show: Boolean,
        title: String
    },
    emits: ['close'],
    template: `
        <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
            <div class="modal">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">{{ title }}</h3>
                    <button @click="$emit('close')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-primary);">&times;</button>
                </div>
                <slot></slot>
            </div>
        </div>
    `
};
