import { store } from 'store';

const MailPanel = {
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <div class="page-title">
                    <i data-lucide="mail"></i>
                    Mail System
                    <span class="text-muted text-small" style="margin-left: 10px; font-weight: normal;">(Fake/Dev Inbox)</span>
                </div>
                <div class="button-group">
                    <button @click="fetchInbox" class="btn">
                        <i data-lucide="refresh-cw"></i> Refresh
                    </button>
                    <button @click="sendTestEmail" class="btn" style="background: var(--info-color); color: white;">
                        <i data-lucide="send"></i> Send Test
                    </button>
                    
                    <!-- Clear Button with Confirmation State -->
                    <button v-if="!confirmClearState" @click="confirmClearState = true" class="btn btn-danger">
                        <i data-lucide="trash-2"></i> Clear
                    </button>
                    <div v-else class="button-group" style="gap: 5px;">
                        <span style="font-size: 0.9rem; color: var(--danger-color); align-self: center;">Are you sure?</span>
                        <button @click="clearInbox" class="btn btn-danger btn-small">Yes</button>
                        <button @click="confirmClearState = false" class="btn btn-secondary btn-small">No</button>
                    </div>
                </div>
            </div>

            <div class="admin-card" style="padding: 0; overflow: hidden; display: flex; height: calc(100vh - 180px);">
                <!-- Email List (Left Sidebar) -->
                <div style="width: 320px; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background: rgba(0,0,0,0.1);">
                    <div style="padding: var(--space-md); border-bottom: 1px solid var(--border-color); font-weight: 600;">
                        Inbox ({{ emails.length }})
                    </div>
                    
                    <div style="flex: 1; overflow-y: auto;">
                        <div v-if="emails.length === 0" style="padding: var(--space-xl); text-align: center; color: var(--text-secondary);">
                            <i data-lucide="inbox" style="width: 32px; height: 32px; opacity: 0.5; margin-bottom: 10px;"></i>
                            <p>No emails found</p>
                        </div>
                        
                        <div v-else>
                            <div v-for="email in emails" 
                                :key="email.id"
                                @click="selectEmail(email)"
                                :style="{ 
                                    padding: 'var(--space-md)', 
                                    borderBottom: '1px solid var(--border-color)', 
                                    cursor: 'pointer',
                                    background: selectedEmail?.id === email.id ? 'rgba(255,255,255,0.05)' : 'transparent',
                                    borderLeft: selectedEmail?.id === email.id ? '3px solid var(--accent-color)' : '3px solid transparent'
                                }"
                            >
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;">{{ email.subject }}</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">{{ formatTime(email.timestamp) }}</span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    To: {{ email.to }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Detail (Right Content) -->
                <div style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                    <div v-if="!selectedEmail" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-secondary);">
                        <i data-lucide="mail-open" style="width: 48px; height: 48px; opacity: 0.2; margin-bottom: var(--space-md);"></i>
                        <p>Select an email to read</p>
                    </div>
                    
                    <div v-else style="display: flex; flex-direction: column; height: 100%;">
                        <div style="padding: var(--space-lg); border-bottom: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-md);">
                                <h2 style="margin: 0; font-size: 1.4rem;">{{ selectedEmail.subject }}</h2>
                                <span class="label-tag" style="background: var(--bg-color); color: var(--text-secondary); border: 1px solid var(--border-color);">{{ selectedEmail.timestamp }}</span>
                            </div>
                            
                            <div style="display: flex; gap: var(--space-md); font-size: 0.9rem;">
                                <div>
                                    <span style="color: var(--text-secondary);">To:</span> 
                                    <span style="color: var(--text-primary); font-weight: 500;">{{ selectedEmail.to }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="flex: 1; overflow-y: auto; padding: var(--space-lg); background: var(--bg-color);">
                            <div style="background: white; color: black; padding: var(--space-lg); border-radius: var(--radius-sm); min-height: 200px;" v-html="selectedEmail.body"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    data() {
        return {
            emails: [],
            selectedEmail: null,
            confirmClearState: false,
            loading: false
        }
    },
    mounted() {
        this.fetchInbox();
        this.pollTimer = setInterval(this.fetchInbox, 10000);
        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
    },
    beforeUnmount() {
        clearInterval(this.pollTimer);
    },
    updated() {
        if (window.lucide) window.lucide.createIcons();
    },
    methods: {
        async fetchInbox() {
            try {
                const response = await fetch('/@/admin/mail/inbox');
                const data = await response.json();
                this.emails = data.emails || [];

                if (this.selectedEmail) {
                    const found = this.emails.find(e => e.id === this.selectedEmail.id);
                    if (!found) this.selectedEmail = null;
                }
            } catch (error) {
                console.error('Failed to fetch inbox:', error);
                store.addNotification('Failed to fetch inbox', 'error');
            }
        },
        selectEmail(email) {
            this.selectedEmail = email;
        },
        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        async sendTestEmail() {
            try {
                const response = await fetch('/@/admin/mail/send-test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.status === 'success') {
                    store.addNotification('Test email sent successfully!', 'success');
                    this.fetchInbox();
                } else {
                    store.addNotification('Error: ' + result.message, 'error');
                }
            } catch (error) {
                store.addNotification('Failed to send test email', 'error');
            }
        },
        async clearInbox() {
            try {
                await fetch('/@/admin/mail/clear', { method: 'POST' });
                this.emails = [];
                this.selectedEmail = null;
                this.confirmClearState = false;
                store.addNotification('Inbox cleared', 'success');
            } catch (error) {
                console.error('Failed to clear inbox:', error);
                store.addNotification('Failed to clear inbox', 'error');
            }
        }
    }
};

export default MailPanel;
