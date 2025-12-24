export default {
    props: {
        show: Boolean,
        title: String,
        message: String,
        confirmText: String,
        cancelText: String
    },
    emits: ['confirm', 'cancel'],
    template: `
        <div v-if="show" class="modal-overlay" style="z-index: 10000;">
            <div class="modal-content modal-sm">
                <div class="card-header">
                    <h3 style="margin:0">{{ title || 'Confirm' }}</h3>
                </div>
                <div class="modal-body" style="padding: var(--space-lg) 0;">
                    <p>{{ message }}</p>
                </div>
                <div class="card-footer" style="display: flex; justify-content: flex-end; gap: var(--space-md);">
                    <button class="btn btn-ghost" @click="$emit('cancel')">
                        {{ cancelText || 'Cancel' }}
                    </button>
                    <button class="btn btn-primary" @click="$emit('confirm')">
                        {{ confirmText || 'Confirm' }}
                    </button>
                </div>
            </div>
        </div>
    `
};
