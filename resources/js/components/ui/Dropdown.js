export default {
    props: {
        trigger: {
            type: String,
            default: 'hover' // hover, click
        },
        placement: {
            type: String,
            default: 'bottom-start'
        },
        disabled: Boolean
    },
    data() {
        return {
            visible: false
        };
    },
    methods: {
        toggle() {
            if (this.disabled) return;
            this.visible = !this.visible;
        },
        show() {
            if (this.disabled || this.trigger !== 'hover') return;
            this.visible = true;
        },
        hide() {
            if (this.trigger !== 'hover') return;
            this.visible = false;
        },
        handleTriggerClick() {
            if (this.trigger === 'click') {
                this.toggle();
            }
        }
    },
    template: `
        <div class="dropdown" @mouseenter="show" @mouseleave="hide">
            <div class="dropdown-trigger" @click="handleTriggerClick">
                <slot name="trigger"></slot>
            </div>
            <transition name="fade">
                <div v-if="visible" class="dropdown-menu" :class="'is-' + placement">
                    <slot></slot>
                </div>
            </transition>
        </div>
    `
};
