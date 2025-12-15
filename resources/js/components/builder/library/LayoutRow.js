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
    styles: `
        .layout-row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -15px;
            margin-right: -15px;
        }
        .align-start { align-items: flex-start; }
        .align-center { align-items: center; }
        .align-end { align-items: flex-end; }
        .align-stretch { align-items: stretch; }
        
        .justify-start { justify-content: flex-start; }
        .justify-center { justify-content: center; }
        .justify-end { justify-content: flex-end; }
        .justify-between { justify-content: space-between; }
        .justify-around { justify-content: space-around; }

        .gutter-none { margin-left: 0; margin-right: 0; }
        .gutter-none > * { padding-left: 0; padding-right: 0; }
        
        .gutter-sm { margin-left: -5px; margin-right: -5px; }
        .gutter-sm > * { padding-left: 5px; padding-right: 5px; }

        .gutter-lg { margin-left: -30px; margin-right: -30px; }
        .gutter-lg > * { padding-left: 30px; padding-right: 30px; }
    `
};
