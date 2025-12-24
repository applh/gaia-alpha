export default {
    props: {
        modelValue: Boolean,
        title: String,
        direction: {
            type: String,
            default: 'right' // left, right, top, bottom
        },
        size: {
            type: String,
            default: '30%'
        },
        closable: {
            type: Boolean,
            default: true
        }
    },
    emits: ['update:modelValue', 'close'],
    methods: {
        close() {
            this.$emit('update:modelValue', false);
            this.$emit('close');
        }
    },
    template: `
        <transition name="drawer-fade">
            <div v-if="modelValue" class="drawer-wrapper" @click.self="close">
                <div 
                    class="drawer" 
                    :class="'drawer-' + direction"
                    :style="{ [direction === 'left' || direction === 'right' ? 'width' : 'height']: size }"
                >
                    <div class="drawer-header" v-if="title || closable">
                        <span class="drawer-title" v-if="title">{{ title }}</span>
                        <button v-if="closable" class="drawer-close" @click="close">&times;</button>
                    </div>
                    <div class="drawer-body">
                        <slot></slot>
                    </div>
                </div>
            </div>
        </transition>
    `
};
