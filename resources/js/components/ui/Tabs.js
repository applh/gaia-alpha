import { computed } from 'vue';

export default {
    props: {
        modelValue: String, // name of active tab
    },
    emits: ['update:modelValue', 'tab-click'],
    data() {
        return {
            tabs: []
        };
    },
    methods: {
        registerTab(tab) {
            this.tabs.push(tab);
        },
        unregisterTab(tab) {
            const index = this.tabs.indexOf(tab);
            if (index > -1) {
                this.tabs.splice(index, 1);
            }
        },
        handleTabClick(tab) {
            this.$emit('update:modelValue', tab.name);
            this.$emit('tab-click', tab);
        }
    },
    provide() {
        return {
            registerTab: this.registerTab,
            unregisterTab: this.unregisterTab,
            activeTabName: computed(() => this.modelValue)
        };
    },
    template: `
        <div class="tabs-container">
            <div class="nav-tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.name"
                    class="btn"
                    :class="{ active: modelValue === tab.name }"
                    @click="handleTabClick(tab)"
                >
                    {{ tab.label }}
                </button>
            </div>
            <div class="tab-content">
                <slot></slot>
            </div>
        </div>
    `
};
