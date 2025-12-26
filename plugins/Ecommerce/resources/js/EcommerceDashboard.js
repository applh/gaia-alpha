import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Container from 'ui/Container.js';
import { UITitle, UIText } from 'ui/Typography.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-row': Row,
        'ui-col': Col,
        'ui-container': Container,
        'ui-title': UITitle,
        'ui-text': UIText
    },
    data() {
        return {
            stats: {
                total_sales: 0,
                order_count: 0,
                product_count: 0
            },
            products: [],
            loading: true
        }
    },
    async mounted() {
        try {
            const [statsRes, productsRes] = await Promise.all([
                fetch('/api/ecommerce/stats'),
                fetch('/api/ecommerce/products')
            ]);

            this.stats = await statsRes.json();
            this.products = await productsRes.json();
        } catch (error) {
            console.error('Failed to fetch dashboard data:', error);
        } finally {
            this.loading = false;
        }
    },
    template: `
        <ui-container class="p-6">
            <div class="admin-header">
                <div>
                    <ui-title :level="1">E-commerce Dashboard</ui-title>
                    <ui-text color="secondary">Overview of your store performance and products.</ui-text>
                </div>
            </div>

            <!-- Stats Overview -->
            <ui-row :gutter="24" class="mb-8">
                <ui-col :xs="24" :sm="8">
                    <ui-card class="d-flex align-items-center p-4">
                        <div class="stat-icon bg-success-soft mr-4">
                            <LucideIcon name="dollar-sign" size="24" />
                        </div>
                        <div>
                            <ui-text size="small" weight="bold" color="secondary" class="text-uppercase">Total Sales</ui-text>
                            <ui-title :level="2" class="m-0">\${{ stats.total_sales }}</ui-title>
                        </div>
                    </ui-card>
                </ui-col>
                <ui-col :xs="24" :sm="8">
                    <ui-card class="d-flex align-items-center p-4">
                        <div class="stat-icon bg-primary-soft mr-4">
                            <LucideIcon name="shopping-bag" size="24" />
                        </div>
                        <div>
                            <ui-text size="small" weight="bold" color="secondary" class="text-uppercase">Orders</ui-text>
                            <ui-title :level="2" class="m-0">{{ stats.order_count }}</ui-title>
                        </div>
                    </ui-card>
                </ui-col>
                <ui-col :xs="24" :sm="8">
                    <ui-card class="d-flex align-items-center p-4">
                        <div class="stat-icon bg-info-soft mr-4">
                            <LucideIcon name="package" size="24" />
                        </div>
                        <div>
                            <ui-text size="small" weight="bold" color="secondary" class="text-uppercase">Products</ui-text>
                            <ui-title :level="2" class="m-0">{{ stats.product_count }}</ui-title>
                        </div>
                    </ui-card>
                </ui-col>
            </ui-row>

            <!-- Products List -->
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <ui-title :level="3">Recent Products</ui-title>
                <ui-button variant="primary" size="small">
                    <LucideIcon name="plus" size="16" class="mr-2" />
                    Add Product
                </ui-button>
            </div>

            <ui-row :gutter="24">
                <ui-col v-for="product in products" :key="product.id" :xs="24" :sm="12" :md="8" lg="6" class="mb-4">
                    <ui-card class="h-100 p-0 overflow-hidden">
                        <div class="product-image-placeholder bg-gray-dark d-flex align-items-center justify-content-center" style="height: 160px; background: rgba(255,255,255,0.03);">
                            <LucideIcon name="image" size="32" color="muted" />
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <ui-title :level="4" class="m-0">{{ product.title }}</ui-title>
                                <ui-text weight="bold" color="accent">\${{ product.price }}</ui-text>
                            </div>
                            <ui-text size="small" color="secondary" class="mb-4 text-truncate-2">{{ product.description }}</ui-text>
                            <div class="d-flex gap-2">
                                <ui-button variant="outline" size="small" class="flex-grow-1">Edit</ui-button>
                                <ui-button variant="ghost" size="small" color="danger">
                                    <LucideIcon name="trash-2" size="14" />
                                </ui-button>
                            </div>
                        </div>
                    </ui-card>
                </ui-col>
            </ui-row>

            <div v-if="products.length === 0 && !loading" class="text-center p-12 bg-gray-dark rounded">
                <LucideIcon name="package-open" size="48" class="mb-4 text-muted" />
                <ui-title :level="3">No products yet</ui-title>
                <ui-text color="secondary">Start by adding your first product to the store.</ui-text>
            </div>
        </ui-container>
    `,
    style: `
        .bg-success-soft { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .bg-primary-soft { background: rgba(0, 123, 255, 0.1); color: #007bff; }
        .bg-info-soft { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .gap-2 { gap: 0.5rem; }
    `
}
