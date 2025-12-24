export default {
    props: {
        separator: {
            type: String,
            default: '/'
        }
    },
    provide() {
        return {
            separator: this.separator
        };
    },
    template: `
        <div class="breadcrumb" aria-label="Breadcrumb">
            <slot></slot>
        </div>
    `
};
