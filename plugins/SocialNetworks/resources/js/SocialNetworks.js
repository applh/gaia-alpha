import SetupWizard from './components/SetupWizard.js';
import Composer from './components/Composer.js';

export default {
    components: { SetupWizard, Composer },
    data() {
        return {
            accounts: [],
            posts: [],
            loading: true,
            showSetup: false,
            activeTab: 'composer'
        };
    },
    async mounted() {
        await this.loadData();
    },
    methods: {
        async loadData() {
            this.loading = true;
            try {
                const [accRes, postRes] = await Promise.all([
                    fetch('/@/social-networks/accounts').then(r => r.json()),
                    fetch('/@/social-networks/posts').then(r => r.json())
                ]);
                this.accounts = accRes;
                this.posts = postRes;
            } catch (e) {
                console.error("Failed to load social networks data", e);
            } finally {
                this.loading = false;
            }
        }
    },
    template: `
        <div class="social-networks-panel p-6">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Social Networks</h1>
                    <p class="text-secondary">Publish content across all your social channels from one place.</p>
                </div>
                <button @click="showSetup = true" class="btn btn-secondary flex items-center gap-2">
                    <lucide-icon name="settings" class="w-4 h-4" />
                    Connect Accounts
                </button>
            </header>

            <div v-if="loading" class="flex justify-center py-20">
                <div class="spinner"></div>
            </div>

            <div v-else>
                <div class="tabs mb-6">
                    <button 
                        @click="activeTab = 'composer'" 
                        :class="['tab-item', { active: activeTab === 'composer' }]">
                        Composer
                    </button>
                    <button 
                        @click="activeTab = 'history'" 
                        :class="['tab-item', { active: activeTab === 'history' }]">
                        Post History
                    </button>
                    <button 
                        @click="activeTab = 'accounts'" 
                        :class="['tab-item', { active: activeTab === 'accounts' }]">
                        Accounts ({{ accounts.length }})
                    </button>
                </div>

                <div v-if="activeTab === 'composer'">
                    <Composer :accounts="accounts" @refresh="loadData" />
                </div>

                <div v-if="activeTab === 'history'">
                    <div class="card overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-surface-alt text-secondary text-sm">
                                <tr>
                                    <th class="p-4">Content</th>
                                    <th class="p-4">Platform</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="post in posts" :key="post.id" class="border-t border-border">
                                    <td class="p-4 max-w-xs truncate">{{ post.content }}</td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2 capitalize">
                                            <lucide-icon :name="post.platform" class="w-4 h-4" />
                                            {{ post.platform }}
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span :class="['status-badge', post.status]">
                                            {{ post.status }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-secondary">{{ post.published_at || post.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="activeTab === 'accounts'">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="acc in accounts" :key="acc.id" class="card p-4 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <lucide-icon :name="acc.platform" class="w-8 h-8 text-primary" />
                                <div>
                                    <div class="font-bold capitalize">{{ acc.platform }}</div>
                                    <div class="text-xs text-secondary">{{ acc.account_name || 'Connected' }}</div>
                                </div>
                            </div>
                            <button @click="deleteAccount(acc.id)" class="text-danger hover:text-danger-light">
                                <lucide-icon name="trash-2" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <SetupWizard :show="showSetup" @close="showSetup = false" @refresh="loadData" />
        </div>
    `
};
