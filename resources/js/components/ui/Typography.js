export const UITitle = {
    props: {
        level: {
            type: [Number, String],
            default: 1 // 1-6
        }
    },
    template: `
        <component :is="'h' + level" class="ui-title">
            <slot></slot>
        </component>
    `
};

export const UIText = {
    props: {
        type: {
            type: String,
            default: '' // secondary, success, warning, danger
        },
        strong: Boolean,
        italic: Boolean,
        code: Boolean,
        disabled: Boolean
    },
    template: `
        <span 
            class="ui-text" 
            :class="[
                type ? 'ui-text-' + type : '',
                { 'is-strong': strong, 'is-italic': italic, 'is-code': code, 'is-disabled': disabled }
            ]"
        >
            <slot></slot>
        </span>
    `
};

export const UIParagraph = {
    props: {
        direction: {
            type: String,
            default: 'vertical'
        },
        spacing: {
            type: String,
            default: 'md' // sm, md, lg
        }
    },
    template: `
        <p class="ui-paragraph" :class="'spacing-' + spacing">
            <slot></slot>
        </p>
    `
};

export default {
    UITitle,
    UIText,
    UIParagraph
};
