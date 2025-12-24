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
            products: []
        }
    },
    async mounted() {
        const res = await fetch('/api/ecommerce/products');
        this.products = await res.json();
    },
    methods: {
        async addToCart(product) {
            await fetch('/api/ecommerce/cart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: product.id, quantity: 1 })
            });
            store.addNotification('Added to cart!', 'success');
        }
    },
    template: `
        <ui-container class="p-6">
            <ui-row :gutter="24">
                <ui-col v-for="product in products" :key="product.id" :xs="24" :sm="12" :md="6" style="margin-bottom: 24px;">
                    <ui-card style="height: 100%; display: flex; flex-direction: column;">
                        <div class="bg-gray-200 h-32 mb-4 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);"></div>
                        <ui-title :level="3" style="flex: 1; margin: 0 0 8px 0;">{{ product.title }}</ui-title>
                        <ui-text size="large" weight="bold" style="color: var(--accent-color); margin-bottom: 16px;">
                            \${{ product.price }}
                        </ui-text>
                        <ui-button type="primary" @click="addToCart(product)" style="width: 100%;">
                            <LucideIcon name="shopping-cart" size="18" style="margin-right: 8px;" />
                            Add to Cart
                        </ui-button>
                    </ui-card>
                </ui-col>
            </ui-row>
        </ui-container>
    `
}

