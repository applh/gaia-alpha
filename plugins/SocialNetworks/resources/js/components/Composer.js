import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    props: {
        accounts: Array
    },
    emits: ['refresh'],
    data() {
        return {
            content: '',
            selectedAccounts: [],
            mediaUrls: [],
            isPublishing: false
        };
    },
    methods: {
        async publish() {
            if (this.selectedAccounts.length === 0) return alert("Select at least one account");
            if (!this.content) return alert("Enter some content");

            this.isPublishing = true;
            try {
                const res = await fetch('/@/social-networks/publish', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        content: this.content,
                        account_ids: this.selectedAccounts,
                        media_urls: this.mediaUrls
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.content = '';
                    this.selectedAccounts = [];
                    this.$emit('refresh');
                    alert("Content published successfully!");
                }
            } catch (e) {
                console.error("Publish failed", e);
            } finally {
                this.isPublishing = false;
            }
        }
    },
    template: `
        <div class="composer-container card p-6 max-w-4xl mx-auto">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                <lucide-icon name="pen-tool" class="w-5 h-5 text-primary" />
                Compose Post
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Select Platforms -->
                <div class="md:col-span-1 border-r border-border pr-6">
                    <label class="block text-sm font-bold mb-4 text-secondary uppercase tracking-wider">Select Platforms</label>
                    <div v-if="accounts.length === 0" class="text-xs text-secondary opacity-60 italic">
                        No accounts connected. Use the settings to connect platforms.
                    </div>
                    <div class="space-y-3">
                        <label v-for="acc in accounts" :key="acc.id" class="flex items-center gap-3 cursor-pointer p-2 rounded hover:bg-surface-alt transition-colors">
                            <input type="checkbox" v-model="selectedAccounts" :value="acc.id" class="checkbox">
                            <lucide-icon :name="acc.platform" class="w-5 h-5" />
                            <div class="flex flex-col">
                                <span class="text-sm font-medium capitalize">{{ acc.platform }}</span>
                                <span class="text-[10px] text-secondary">{{ acc.account_name || 'Personal' }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="md:col-span-2">
                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2 text-secondary uppercase tracking-wider">Caption</label>
                        <textarea 
                            v-model="content"
                            class="input w-full min-h-[150px] text-lg resize-none p-4" 
                            placeholder="What's on your mind?"></textarea>
                    </div>

                    <div class="mb-8 p-4 bg-surface-alt rounded-lg border border-dashed border-border flex flex-col items-center justify-center gap-2 group cursor-pointer hover:border-primary transition-colors">
                        <lucide-icon name="image" class="w-8 h-8 text-secondary group-hover:text-primary transition-colors" />
                        <span class="text-xs text-secondary">Click to attach images, videos or PDFs from Library</span>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-border">
                        <button 
                            @click="publish" 
                            :disabled="isPublishing || selectedAccounts.length === 0"
                            class="btn btn-primary px-10 py-3 text-lg relative overflow-hidden group">
                            <span v-if="isPublishing" class="flex items-center gap-2">
                                <div class="spinner-sm inline-block"></div>
                                Publishing...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <lucide-icon name="send" class="w-5 h-5 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" />
                                Publish Now
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
};
