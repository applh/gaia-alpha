export default {
    props: {
        type: {
            type: String,
            default: 'primary' // primary, success, warning, danger, info
        },
        underline: {
            type: String,
            default: 'hover' // always, hover, none
        },
        disabled: Boolean,
        href: String,
        icon: String
    },
    emits: ['click'],
    template: `
        <a 
            :href="disabled ? null : href"
            class="ui-link"
            :class="[
                'ui-link-' + type,
                'underline-' + underline,
                { 'is-disabled': disabled }
            ]"
            @click="$emit('click', $event)"
        >
            <i v-if="icon" :class="icon" class="ui-link-icon"></i>
            <span class="ui-link-inner">
                <slot></slot>
            </span>
        </a>
    `
};
