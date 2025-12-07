import { createApp, ref } from 'vue';

const app = createApp({
    setup() {
        const message = ref('Gaia Alpha is running (with Vue!)');
        return {
            message
        };
    }
});

app.mount('#app');
