export const DropdownItem = {
    props: {
        command: [String, Number, Object],
        disabled: Boolean,
        divided: Boolean
    },
    inject: ['handleItemClick'],
    template: `
        <div 
            class="dropdown-item" 
            :class="{ 'is-disabled': disabled, 'is-divided': divided }"
            @click="handleItemClick(this)"
        >
            <slot></slot>
        </div>
    `
};

export default {
    components: { DropdownItem },
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
    emits: ['command'],
    data() {
        return {
            visible: false
        };
    },
    provide() {
        return {
            handleItemClick: this.handleItemClick
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
        },
        handleItemClick(item) {
            if (item.disabled) return;
            this.$emit('command', item.command);
            this.visible = false;
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

