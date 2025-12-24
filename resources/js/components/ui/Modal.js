export default {
    props: {
        show: Boolean,
        title: String,
        size: {
            type: String,
            default: 'md' // sm, md, lg
        }
    },
    emits: ['close'],
    template: `
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
                <div :class="['modal-content', 'modal-' + size]">
                    <div class="card-header" style="justify-content: space-between; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin:0">{{ title }}</h3>
                        <button class="btn btn-ghost btn-xs" @click="$emit('close')">
                            <span style="font-size: 1.5rem; line-height: 0.5;">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <slot></slot>
                    </div>
                    <div v-if="$slots.footer" class="card-footer" style="padding-top: var(--space-lg); border-top: 1px solid var(--border-color); margin-top: var(--space-lg); text-align: right;">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </transition>
    `
};
