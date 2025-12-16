import { ref, reactive, onMounted, computed } from 'vue';
import { useCrud } from 'composables/useCrud.js';
import Modal from 'ui/Modal.js';
import { store } from 'store';

export default {
    components: { Modal },
    template: `
        <div class="menu-builder-container">
            <!-- List View -->
            <div v-if="!editMode">
                <table v-if="menus.length">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="menu in menus" :key="menu.id">
                            <td>{{ menu.title }}</td>
                            <td>{{ menu.location || '-' }}</td>
                            <td>{{ getItemsCount(menu.items) }} items</td>
                            <td class="actions">
                                <button @click="openEdit(menu)">Edit</button>
                                <button @click="deleteMenu(menu.id)" class="btn-small btn-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else>No menus found.</p>
            </div>

            <!-- Editor View -->
            <div v-else class="menu-editor">
                <div class="editor-header">
                    <button @click="closeEditor" class="btn-xs">&larr; Back</button>
                    <h3>{{ form.id ? 'Edit Menu' : 'New Menu' }}</h3>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Menu Title</label>
                        <input v-model="form.title" placeholder="Main Menu">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <select v-model="form.location">
                            <option value="">-- None --</option>
                            <option value="header">Header</option>
                            <option value="footer">Footer</option>
                            <option value="sidebar">Sidebar</option>
                        </select>
                    </div>
                </div>

                <div class="items-editor">
                    <h4>Menu Items</h4>
                    <div class="items-list">
                        <div v-for="(item, idx) in formItems" :key="idx" class="menu-item-row">
                            <span class="drag-handle">☰</span>
                            <input v-model="item.label" placeholder="Label" class="item-label">
                            <select v-model="item.type" class="item-type">
                                <option value="url">URL</option>
                                <option value="page">Page</option>
                            </select>
                            <input v-model="item.value" :placeholder="item.type === 'page' ? 'Page Slug' : 'https://...'" class="item-value">
                            <button @click="removeItem(idx)" class="btn-xs btn-danger">×</button>
                        </div>
                    </div>
                    <button @click="addItem" class="btn-secondary btn-small">+ Add Item</button>
                </div>

                <div class="form-actions">
                    <button @click="saveMenu" class="btn-primary">Save Menu</button>
                </div>
            </div>
        </div>
    `,
    setup() {
        const { items: menus, fetchItems, createItem, updateItem, deleteItem } = useCrud('/@/menus');

        const editMode = ref(false);
        const form = reactive({
            id: null,
            title: '',
            location: '',
            items: ''
        });
        const formItems = ref([]);

        const getItemsCount = (json) => {
            try {
                const arr = JSON.parse(json);
                return Array.isArray(arr) ? arr.length : 0;
            } catch { return 0; }
        };

        const openCreate = () => {
            form.id = null;
            form.title = '';
            form.location = '';
            formItems.value = [];
            editMode.value = true;
        };

        const openEdit = (menu) => {
            form.id = menu.id;
            form.title = menu.title;
            form.location = menu.location;
            try {
                const parsed = JSON.parse(menu.items);
                formItems.value = Array.isArray(parsed) ? parsed : [];
            } catch {
                formItems.value = [];
            }
            editMode.value = true;
        };

        const closeEditor = () => {
            editMode.value = false;
        };

        const addItem = () => {
            formItems.value.push({ label: 'New Link', type: 'url', value: '' });
        };

        const removeItem = (idx) => {
            formItems.value.splice(idx, 1);
        };

        const deleteMenu = async (id) => {
            await deleteItem(id);
        };

        const saveMenu = async () => {
            console.log('Saving menu items:', formItems.value);
            const payload = {
                title: form.title,
                location: form.location,
                items: JSON.stringify(formItems.value) // Encode manual array to JSON string
            };

            try {
                if (form.id) {
                    await updateItem(form.id, payload);
                } else {
                    await createItem(payload);
                }
                closeEditor();
            } catch (e) {
                alert('Failed to save menu: ' + e.message);
            }
        };

        onMounted(fetchItems);

        return {
            menus, editMode, form, formItems,
            openCreate, openEdit, closeEditor,
            addItem, removeItem, deleteMenu, saveMenu, getItemsCount
        };
    }
};
