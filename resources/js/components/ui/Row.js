export default {
    props: {
        gutter: {
            type: Number,
            default: 0
        },
        justify: {
            type: String,
            default: 'start' // start, end, center, space-around, space-between
        },
        align: {
            type: String,
            default: 'top' // top, middle, bottom
        }
    },
    computed: {
        style() {
            const ret = {};
            if (this.gutter) {
                ret.marginLeft = `-${this.gutter / 2}px`;
                ret.marginRight = ret.marginLeft;
            }
            return ret;
        }
    },
    provide() {
        return {
            gutter: this.gutter
        };
    },
    template: `
        <div 
            class="row" 
            :class="[
                'is-justify-' + justify,
                'is-align-' + align
            ]"
            :style="style"
        >
            <slot></slot>
        </div>
    `
};
