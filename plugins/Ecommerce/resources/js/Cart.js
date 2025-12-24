export default {
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
                alert('Order Paid! ID: ' + data.order.order_id);
                this.cart = { items: [], total: 0 };
                this.showCheckout = false;
            } else {
                alert('Error: ' + data.error);
            }
        }
    },
    template: `
        <div class="p-6 max-w-md mx-auto">
            <h1 class="text-2xl font-bold mb-4">Your Cart</h1>
            
            <div v-if="cart.items.length === 0" class="text-gray-500">Cart is empty.</div>
            
            <div v-else>
                <div v-for="item in cart.items" :key="item.product.id" class="flex justify-between mb-2">
                    <span>{{ item.product.title }} (x{{ item.quantity }})</span>
                    <span>\${{ item.subtotal }}</span>
                </div>
                <div class="border-t mt-4 pt-2 font-bold flex justify-between">
                    <span>Total</span>
                    <span>\${{ cart.total }}</span>
                </div>
                
                <button v-if="!showCheckout" @click="showCheckout = true" class="w-full bg-green-500 text-white py-2 rounded mt-4">
                    Proceed to Checkout
                </button>
                
                <div v-if="showCheckout" class="mt-6 border p-4 rounded bg-gray-50">
                    <h3 class="font-bold mb-2">Checkout</h3>
                    <input v-model="email" placeholder="Email" class="w-full border p-2 mb-2 rounded">
                    <textarea v-model="address" placeholder="Address" class="w-full border p-2 mb-2 rounded"></textarea>
                    <button @click="checkout" class="w-full bg-black text-white py-2 rounded">
                        Pay Now
                    </button>
                </div>
            </div>
        </div>
    `
}
