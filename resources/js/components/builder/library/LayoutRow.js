export default {
    name: 'LayoutRow',
    props: {
        gutter: {
            type: String,
            default: 'md' // sm, md, lg, none
        },
        align: {
            type: String,
            default: 'start' // start, center, end, stretch
        },
        justify: {
            type: String,
            default: 'start' // start, center, end, between, around
        }
    },
    template: `
        <div 
            class="layout-row" 
            :class="['gutter-' + gutter, 'align-' + align, 'justify-' + justify]"
        >
            <slot></slot>
        </div>
    `,
};
