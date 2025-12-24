export default {
    props: {
        percentage: {
            type: Number,
            default: 0,
            validator: (val) => val >= 0 && val <= 100
        },
        type: {
            type: String,
            default: 'primary' // primary, success, warning, danger
        },
        strokeWidth: {
            type: Number,
            default: 6
        },
        showText: {
            type: Boolean,
            default: true
        }
    },
    template: `
        <div class="progress-bar-container">
            <div 
                class="progress-bar-outer" 
                :style="{ height: strokeWidth + 'px' }"
            >
                <div 
                    class="progress-bar-inner" 
                    :class="'bg-' + type"
                    :style="{ width: percentage + '%' }"
                ></div>
            </div>
            <div v-if="showText" class="progress-bar-text">
                {{ percentage }}%
            </div>
        </div>
    `
};
