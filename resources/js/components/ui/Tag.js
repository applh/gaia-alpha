export default {
    props: {
        type: {
            type: String, // primary, success, danger, warning, info
            default: 'primary'
        },
        closable: Boolean,
        round: Boolean,
        size: {
            type: String,
            default: 'md' // sm, md, lg
        }
    },
    emits: ['close', 'click'],
    template: `
        <span
            class="tag"
            :class="['tag-' + type, 'tag-' + size, { 'is-round': round }]"
            @click="$emit('click', $event)"
        >
            <slot></slot>
            <i v-if="closable" class="tag-close-icon" @click.stop="$emit('close', $event)">&times;</i>
        </span>
    `
};
