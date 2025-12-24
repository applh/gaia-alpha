export default {
    props: {
        src: String,
        alt: String,
        size: {
            type: String,
            default: 'md' // sm, md, lg, xl
        },
        shape: {
            type: String,
            default: 'circle' // circle, square
        },
        text: String // Fallback initial
    },
    template: `
        <div 
            class="avatar" 
            :class="['avatar-' + size, 'avatar-' + shape]"
        >
            <img v-if="src" :src="src" :alt="alt" class="avatar-img" />
            <span v-else-if="text" class="avatar-text">{{ text.charAt(0).toUpperCase() }}</span>
            <span v-else class="avatar-icon">👤</span>
        </div>
    `
};
