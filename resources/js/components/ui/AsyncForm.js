import { ref, defineComponent } from 'vue';
import Icon from 'ui/Icon.js';

export default defineComponent({
    name: 'AsyncForm',
    components: { LucideIcon: Icon },
    props: {
        action: {
            type: Function,
            required: true
        },
        submitLabel: {
            type: String,
            default: 'Save Changes'
        },
        successMessage: {
            type: String,
            default: 'Saved!'
        },
        errorMessage: {
            type: String,
            default: 'Error occurred'
        }
    },
    setup(props) {
        const status = ref('idle'); // idle, loading, success, error
        const error = ref(null);

        const submit = async () => {
            if (status.value === 'loading') return;

            status.value = 'loading';
            error.value = null;

            try {
                await props.action();
                status.value = 'success';
                setTimeout(() => {
                    if (status.value === 'success') {
                        status.value = 'idle';
                    }
                }, 2000);
            } catch (e) {
                console.error("AsyncForm error:", e);
                error.value = e.message || props.errorMessage;
                status.value = 'error';
                setTimeout(() => {
                    if (status.value === 'error') {
                        status.value = 'idle';
                    }
                }, 3000);
            }
        };

        return {
            status,
            error,
            submit
        };
    },
    template: `
        <form @submit.prevent="submit" class="async-form">
            <!-- Main Content -->
            <slot></slot>
            
            <!-- Global Error Message -->
            <div v-if="error" class="form-error-banner" style="color: var(--danger-color); margin-top: 10px; font-size: 0.9em;">
                <LucideIcon name="alert-circle" size="14" style="vertical-align: middle; margin-right: 4px;" />
                {{ error }}
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <slot name="actions" :loading="status === 'loading'" :status="status" :submit="submit">
                    <button type="submit" class="btn-primary" :disabled="status === 'loading'" style="min-width: 140px;">
                        <LucideIcon v-if="status === 'loading'" name="loader" class="spin" size="18" />
                        <LucideIcon v-else-if="status === 'success'" name="check" size="18" />
                        <LucideIcon v-else-if="status === 'error'" name="alert-circle" size="18" />
                        
                        <span v-else>{{ submitLabel }}</span>
                        
                        <span v-if="status === 'success'" style="margin-left: 8px;">{{ successMessage }}</span>
                        <span v-if="status === 'error'" style="margin-left: 8px;">Error</span>
                    </button>
                    
                    <slot name="extra-buttons"></slot>
                </slot>
            </div>
        </form>
    `
});
