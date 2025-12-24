export default {
    props: {
        label: String,
        name: {
            type: String,
            required: true
        }
    },
    inject: ['registerTab', 'unregisterTab', 'activeTabName'],
    data() {
        return {
            isActive: false
        };
    },
    computed: {
        active() {
            return this.activeTabName.value === this.name;
        }
    },
    watch: {
        activeTabName: {
            handler(val) {
                this.isActive = (val && val.value === this.name) || val === this.name;
            },
            immediate: true
        }
    },
    mounted() {
        this.registerTab(this);
    },
    beforeUnmount() {
        this.unregisterTab(this);
    },
    template: `
        <div v-show="active" class="tab-pane">
            <slot></slot>
        </div>
    `
};
