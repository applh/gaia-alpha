export default {
    name: 'LinkButton',
    props: {
        label: String,
        href: String,
        target: {
            type: String,
            default: '_self' // _self, _blank
        },
        variant: {
            type: String,
            default: 'secondary'
        },
        icon: String
    },
    template: `
        <a 
            :href="href" 
            :target="target"
            class="btn" 
            :class="'btn-' + variant"
        >
            <i v-if="icon" :class="'icon-' + icon"></i>
            {{ label }}
        </a>
    `
};
