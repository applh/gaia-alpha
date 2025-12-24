export default {
    props: {
        span: {
            type: Number,
            default: 24
        },
        offset: {
            type: Number,
            default: 0
        },
        push: {
            type: Number,
            default: 0
        },
        pull: {
            type: Number,
            default: 0
        },
        xs: [Number, Object],
        sm: [Number, Object],
        md: [Number, Object],
        lg: [Number, Object],
        xl: [Number, Object]
    },
    inject: ['gutter'],
    computed: {
        style() {
            const ret = {};
            if (this.gutter) {
                ret.paddingLeft = `${this.gutter / 2}px`;
                ret.paddingRight = ret.paddingLeft;
            }
            return ret;
        },
        classList() {
            const list = ['col'];

            ['span', 'offset', 'pull', 'push'].forEach(prop => {
                if (this[prop] !== undefined && this[prop] !== 0) {
                    list.push(prop !== 'span' ? `col-${prop}-${this[prop]}` : `col-${this[prop]}`);
                }
            });

            ['xs', 'sm', 'md', 'lg', 'xl'].forEach(size => {
                if (typeof this[size] === 'number') {
                    list.push(`col-${size}-${this[size]}`);
                } else if (typeof this[size] === 'object') {
                    const props = this[size];
                    Object.keys(props).forEach(prop => {
                        list.push(
                            prop !== 'span'
                                ? `col-${size}-${prop}-${props[prop]}`
                                : `col-${size}-${props[prop]}`
                        );
                    });
                }
            });

            return list;
        }
    },
    template: `
        <div 
            :class="classList"
            :style="style"
        >
            <slot></slot>
        </div>
    `
};
