export default {
    name: 'LayoutContainer',
    props: {
        fluid: {
            type: Boolean,
            default: false
        }
    },
    template: `
        <div class="layout-container" :class="{ 'fluid': fluid }">
            <slot></slot>
        </div>
    `,
    styles: `
        .layout-container {
            width: 100%;
            margin-right: auto;
            margin-left: auto;
            padding-right: 15px;
            padding-left: 15px;
        }
        .layout-container:not(.fluid) {
            max-width: 1200px;
        }
    `
};
