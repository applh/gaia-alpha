import SetupWizard from './components/SetupWizard.js';
import Composer from './components/Composer.js';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Tabs from 'ui/Tabs.js';
import TabPane from 'ui/TabPane.js';
import DataTable from 'ui/DataTable.js';
import Spinner from 'ui/Spinner.js';
import { UITitle, UIText } from 'ui/Typography.js';
import { store } from 'store';

export default {
    components: {
        SetupWizard,
        Composer,
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-tabs': Tabs,
        'ui-tab-pane': TabPane,
        'ui-data-table': DataTable,
        'ui-spinner': Spinner,
        'ui-title': UITitle,
        'ui-text': UIText
    },
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
        },
        async deleteAccount(accountId) {
            if (!confirm('Are you sure you want to disconnect this account?')) return;

            try {
                const res = await fetch(`/@/social-networks/accounts/${accountId}`, { method: 'DELETE' });
                if (res.ok) {
                    store.addNotification('Account disconnected successfully', 'success');
                    await this.loadData();
                }
            } catch (e) {
                store.addNotification('Failed to disconnect account', 'error');
            }
        }
    },
    template: `
        <ui-container class="social-networks-panel">
            <div class="admin-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px;">
                <div>
                    <ui-title :level="1">Social Networks</ui-title>
                    <ui-text class="text-secondary">Publish content across all your social channels from one place.</ui-text>
                </div>
                <ui-button @click="showSetup = true">
                    <LucideIcon name="settings" size="18" style="margin-right: 8px;" />
                    Connect Accounts
                </ui-button>
            </div>

            <div v-if="loading" style="display: flex; justify-content: center; padding: 100px;">
                <ui-spinner size="lg" />
            </div>

            <div v-else>
                <ui-tabs v-model="activeTab">
                    <ui-tab-pane name="composer" label="Composer">
                        <div style="padding-top: 24px;">
                            <Composer :accounts="accounts" @refresh="loadData" />
                        </div>
                    </ui-tab-pane>
                    
                    <ui-tab-pane name="history" label="Post History">
                        <div style="padding-top: 24px;">
                            <ui-card style="padding: 0;">
                                <ui-data-table 
                                    :data="posts"
                                    :columns="[
                                        { label: 'Content', prop: 'content', render: (row) => h('div', { class: 'truncate', style: 'max-width: 300px' }, row.content) },
                                        { label: 'Platform', render: (row) => h('div', { style: 'display: flex; align-items: center; gap: 8px; text-transform: capitalize' }, [h(Icon, { name: row.platform, size: 16 }), row.platform]) },
                                        { label: 'Status', render: (row) => h('span', { class: ['status-badge', row.status] }, row.status) },
                                        { label: 'Date', render: (row) => row.published_at || row.created_at }
                                    ]"
                                />
                            </ui-card>
                        </div>
                    </ui-tab-pane>
                    
                    <ui-tab-pane name="accounts" :label="'Accounts (' + accounts.length + ')'">
                        <div style="padding-top: 24px;">
                            <ui-row :gutter="20">
                                <ui-col v-for="acc in accounts" :key="acc.id" :xs="24" :sm="12" :md="8">
                                    <ui-card style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                        <div style="display: flex; align-items: center; gap: 16px;">
                                            <LucideIcon :name="acc.platform" size="32" style="color: var(--accent-color);" />
                                            <div>
                                                <ui-text weight="bold" style="display: block; text-transform: capitalize;">{{ acc.platform }}</ui-text>
                                                <ui-text size="extra-small" class="text-muted">{{ acc.account_name || 'Connected' }}</ui-text>
                                            </div>
                                        </div>
                                        <ui-button type="danger" size="small" @click="deleteAccount(acc.id)">
                                            <LucideIcon name="trash-2" size="14" />
                                        </ui-button>
                                    </ui-card>
                                </ui-col>
                            </ui-row>
                        </div>
                    </ui-tab-pane>
                </ui-tabs>
            </div>

            <SetupWizard :show="showSetup" @close="showSetup = false" @refresh="loadData" />
        </ui-container>
    `
};

