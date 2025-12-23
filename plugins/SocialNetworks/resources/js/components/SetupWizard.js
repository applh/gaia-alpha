export default {
    props: {
        show: Boolean
    },
    emits: ['close', 'refresh'],
    data() {
        return {
            selectedPlatform: null,
            platforms: [
                { id: 'x', name: 'X (Twitter)', icon: 'twitter', portal: 'https://developer.x.com/' },
                { id: 'linkedin', name: 'LinkedIn', icon: 'linkedin', portal: 'https://www.linkedin.com/developers/' },
                { id: 'youtube', name: 'YouTube', icon: 'youtube', portal: 'https://console.cloud.google.com/' },
                { id: 'tiktok', name: 'TikTok', icon: 'music', portal: 'https://developers.tiktok.com/' }
            ],
            apiKey: '',
            clientSecret: ''
        };
    },
    methods: {
        async connect() {
            // Simulation of connecting an account
            alert(`Connecting to ${this.selectedPlatform.name}...`);
            this.$emit('refresh');
            this.$emit('close');
        }
    },
    template: `
        <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
            <div class="modal max-w-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gradient">Connect Social Network</h2>
                    <button @click="$emit('close')" class="text-secondary hover:text-primary">
                        <lucide-icon name="x" class="w-6 h-6" />
                    </button>
                </div>

                <div v-if="!selectedPlatform" class="grid grid-cols-2 gap-4">
                    <button 
                        v-for="p in platforms" 
                        :key="p.id" 
                        @click="selectedPlatform = p"
                        class="p-6 card hover:border-primary transition-all flex flex-col items-center gap-4 text-center group">
                        <lucide-icon :name="p.icon" class="w-12 h-12 group-hover:scale-110 transition-transform" />
                        <span class="font-bold text-lg">{{ p.name }}</span>
                    </button>
                </div>

                <div v-else class="animate-in slide-in-from-right">
                    <button @click="selectedPlatform = null" class="mb-4 text-sm text-secondary flex items-center gap-1">
                        <lucide-icon name="arrow-left" class="w-3 h-3" />
                        Back to platforms
                    </button>
                    
                    <div class="flex items-center gap-4 mb-6">
                        <lucide-icon :name="selectedPlatform.icon" class="w-10 h-10 text-primary" />
                        <h3 class="text-xl font-bold">{{ selectedPlatform.name }} Setup</h3>
                    </div>

                    <div class="bg-surface-alt p-4 rounded-lg mb-6 text-sm">
                        <p class="mb-2 font-bold">How to get your keys:</p>
                        <ol class="list-decimal list-inside space-y-2 opacity-80">
                            <li>Visit the <a :href="selectedPlatform.portal" target="_blank" class="text-primary hover:underline">{{ selectedPlatform.name }} Developer Portal</a>.</li>
                            <li>Create a new application or project.</li>
                            <li>Enable the relevant APIs (Posting, Media Upload).</li>
                            <li>Copy the Client ID and Secret to the fields below.</li>
                        </ol>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="form-field">
                            <label>Client ID / API Key</label>
                            <input v-model="apiKey" type="text" class="input w-full" placeholder="Enter your key here...">
                        </div>
                        <div class="form-field">
                            <label>Client Secret</label>
                            <input v-model="clientSecret" type="password" class="input w-full" placeholder="Leave blank if not required...">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button @click="selectedPlatform = null" class="btn btn-ghost">Cancel</button>
                        <button @click="connect" class="btn btn-primary px-8">Connect Account</button>
                    </div>
                </div>
            </div>
        </div>
    `
};
