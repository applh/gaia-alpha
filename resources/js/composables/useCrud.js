import { ref } from 'vue';

export function useCrud(baseUrl, idField = 'id') {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const fetchItems = async (queryParams = '') => {
        loading.value = true;
        error.value = null;
        try {
            const res = await fetch(`${baseUrl}${queryParams}`);
            if (res.ok) {
                const data = await res.json();
                // Handle wrapped responses (e.g. { users: [...] } or just [...])
                // For now assuming array or object with data property if strictly conventional, 
                // but our API seems to return arrays directly mostly, except for table data.
                // We'll assume array if array, otherwise look for common keys.
                if (Array.isArray(data)) {
                    items.value = data;
                } else if (data.data && Array.isArray(data.data)) {
                    items.value = data.data; // Pagination structure
                } else if (Object.keys(data).length === 1 && Array.isArray(Object.values(data)[0])) {
                    items.value = Object.values(data)[0]; // { users: [...] }
                } else {
                    items.value = data; // Fallback
                }
            } else {
                throw new Error('Failed to fetch items');
            }
        } catch (e) {
            error.value = e.message;
            console.error(e);
        } finally {
            loading.value = false;
        }
    };

    const createItem = async (payload) => {
        error.value = null;
        try {
            const res = await fetch(baseUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                await fetchItems();
                return data;
            } else {
                throw new Error(data.error || 'Creation failed');
            }
        } catch (e) {
            error.value = e.message;
            throw e;
        }
    };

    const updateItem = async (id, payload) => {
        error.value = null;
        try {
            const res = await fetch(`${baseUrl}/${id}`, {
                method: 'PATCH', // or PUT
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                await fetchItems();
                return data;
            } else {
                throw new Error(data.error || 'Update failed');
            }
        } catch (e) {
            error.value = e.message;
            throw e;
        }
    };

    const deleteItem = async (id) => {
        // if (!confirm('Are you sure you want to delete this item?')) return;
        error.value = null;
        try {
            const res = await fetch(`${baseUrl}/${id}`, { method: 'DELETE' });
            if (res.ok) {
                await fetchItems();
            } else {
                const data = await res.json();
                throw new Error(data.error || 'Deletion failed');
            }
        } catch (e) {
            error.value = e.message;
            alert(e.message);
        }
    };

    return {
        items,
        loading,
        error,
        fetchItems,
        createItem,
        updateItem,
        deleteItem
    };
}
