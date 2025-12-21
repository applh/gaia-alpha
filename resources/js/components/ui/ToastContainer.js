
import { onMounted } from 'vue';
import { store } from '../../store.js';

export default {
    setup() {
        onMounted(() => {
            if (!document.getElementById('toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    .toast-container {
                        position: fixed;
                        top: 20px;
                        left: 20px;
                        z-index: 2147483647;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        pointer-events: none;
                    }
                    .toast {
                        pointer-events: auto;
                        background: var(--bg-card);
                        color: var(--text-main);
                        border: 1px solid var(--border-color);
                        border-left: 4px solid var(--primary-color);
                        padding: 12px 16px;
                        border-radius: 6px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        display: flex;
                        align-items: flex-start;
                        gap: 12px;
                        min-width: 300px;
                        max-width: 400px;
                        animation: slideIn 0.3s ease;
                    }
                    .toast.success { 
                        background: var(--success-color, #10b981); 
                        color: #fff; 
                        border-left: none; 
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                    .toast.error { 
                        background: var(--error-color, #ef4444); 
                        color: #fff; 
                        border-left: none;
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                    .toast.info { 
                        background: var(--info-color, #3b82f6); 
                        color: #fff; 
                        border-left: none;
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                    
                    /* Adjust close button for colored backgrounds */
                    .toast.success .toast-close,
                    .toast.error .toast-close,
                    .toast.info .toast-close {
                        color: rgba(255,255,255,0.8);
                    }
                    .toast.success .toast-close:hover,
                    .toast.error .toast-close:hover,
                    .toast.info .toast-close:hover {
                        color: #fff;
                    }
                    
                    .toast-message {
                        flex: 1;
                        font-size: 0.95rem;
                        line-height: 1.4;
                    }
                    .toast-close {
                        background: none;
                        border: none;
                        color: var(--text-muted);
                        font-size: 1.2rem;
                        line-height: 1;
                        cursor: pointer;
                        padding: 0;
                        opacity: 0.7;
                    }
                    .toast-close:hover { opacity: 1; }

                    @keyframes slideIn {
                        from { transform: translateX(-100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    .toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
                    .toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-30px); }
                `;
                document.head.appendChild(style);
            }
        });
        return { store };
    },
    template: `
        <div class="toast-container">
            <transition-group name="toast">
                <div v-for="note in store.state.notifications" :key="note.id" class="toast" :class="note.type">
                    <div class="toast-message">{{ note.message }}</div>
                    <button class="toast-close" @click="store.removeNotification(note.id)">×</button>
                </div>
            </transition-group>
        </div>
    `
};
