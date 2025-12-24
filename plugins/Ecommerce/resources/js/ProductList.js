export default {
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
            alert('Added to cart!');
        }
    },
    template: `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
            <div v-for="product in products" :key="product.id" class="border rounded-lg p-4 flex flex-col">
                <div class="bg-gray-200 h-32 mb-4 rounded"></div>
                <h3 class="font-bold flex-1">{{ product.title }}</h3>
                <p class="text-lg font-mono my-2">\${{ product.price }}</p>
                <button @click="addToCart(product)" class="bg-black text-white py-2 rounded mt-2 hover:bg-gray-800">
                    Add to Cart
                </button>
            </div>
        </div>
    `
}
