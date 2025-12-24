export default {
    props: {
        fluid: Boolean,
        maxWidth: String // optional max-width value
    },
    template: `
        <div 
            class="container" 
            :class="{ 'is-fluid': fluid }"
            :style="maxWidth ? { maxWidth: maxWidth } : {}"
        >
            <slot></slot>
        </div>
    `
};
