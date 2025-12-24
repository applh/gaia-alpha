export default {
    props: {
        to: String, // URL or path
        replace: Boolean
    },
    inject: ['separator'],
    template: `
        <span class="breadcrumb-item">
            <span class="breadcrumb-text">
                <a v-if="to" :href="to" :class="{ 'is-link': !!to }">
                    <slot></slot>
                </a>
                <span v-else class="is-text">
                    <slot></slot>
                </span>
            </span>
            <span class="breadcrumb-separator">{{ separator }}</span>
        </span>
    `
};
