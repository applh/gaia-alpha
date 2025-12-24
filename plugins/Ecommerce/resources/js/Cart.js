import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Input from 'ui/Input.js';
import Textarea from 'ui/Textarea.js';
import Divider from 'ui/Divider.js';
import { UITitle, UIText } from 'ui/Typography.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-input': Input,
        'ui-textarea': Textarea,
        'ui-divider': Divider,
        'ui-title': UITitle,
        'ui-text': UIText
    },
    data() {
        return {
            cart: { items: [], total: 0 },
            showCheckout: false,
            email: '',
            address: ''
        }
    },
    async mounted() {
        const res = await fetch('/api/ecommerce/cart');
        this.cart = await res.json();
    },
    methods: {
        async checkout() {
            const res = await fetch('/api/ecommerce/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: this.email, address: { line1: this.address } })
            });
            const data = await res.json();

            if (data.success) {
                store.addNotification('Order Paid! ID: ' + data.order.order_id, 'success');
                this.cart = { items: [], total: 0 };
                this.showCheckout = false;
            } else {
                store.addNotification('Error: ' + data.error, 'error');
            }
        }
    },
    template: `
        <ui-container class="p-6" style="max-width: 600px; margin: 0 auto;">
            <ui-card>
                <ui-title :level="2" style="margin-bottom: 24px;">Your Cart</ui-title>
                
                <div v-if="cart.items.length === 0" style="padding: 24px; text-align: center; color: var(--text-muted);">
                    Cart is empty.
                </div>
                
                <div v-else>
                    <div v-for="item in cart.items" :key="item.product.id" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 8px 0;">
                        <ui-text>{{ item.product.title }} (x{{ item.quantity }})</ui-text>
                        <ui-text weight="bold">\${{ item.subtotal }}</ui-text>
                    </div>
                    
                    <ui-divider />
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; margin-bottom: 24px;">
                        <ui-text size="large" weight="bold">Total</ui-text>
                        <ui-text size="large" weight="bold" style="color: var(--accent-color);">\${{ cart.total }}</ui-text>
                    </div>
                    
                    <ui-button v-if="!showCheckout" type="primary" size="large" @click="showCheckout = true" style="width: 100%;">
                        Proceed to Checkout
                    </ui-button>
                    
                    <div v-if="showCheckout" style="margin-top: 32px; padding-top: 32px; border-top: 1px dashed var(--border-color);">
                        <ui-title :level="3" style="margin-bottom: 20px;">Checkout</ui-title>
                        <ui-input v-model="email" label="Email Address" placeholder="you@example.com" style="margin-bottom: 16px;" />
                        <ui-textarea v-model="address" label="Shipping Address" placeholder="Street, City, State, Zip" style="margin-bottom: 24px;" />
                        <ui-button type="primary" size="large" @click="checkout" style="width: 100%;">
                            <LucideIcon name="credit-card" size="18" style="margin-right: 8px;" />
                            Pay Now
                        </ui-button>
                    </div>
                </div>
            </ui-card>
        </ui-container>
    `
}

