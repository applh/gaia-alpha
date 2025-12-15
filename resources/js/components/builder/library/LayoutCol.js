export default {
    name: 'LayoutCol',
    props: {
        width: {
            type: [Number, String],
            default: 12
        }
    },
    template: `
        <div class="layout-col" :style="style">
            <slot></slot>
        </div>
    `,
    computed: {
        style() {
            const width = parseInt(this.width);
            const percentage = (width / 12) * 100;
            return {
                flex: `${percentage}%`,
                maxWidth: `${percentage}%`,
                paddingLeft: '15px',
                paddingRight: '15px'
            };
        }
    }
};
