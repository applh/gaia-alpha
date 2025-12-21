
import { computed } from 'vue';
import { store } from '../store.js';

export function useMenu() {
    const baseMenuItems = [
        { label: 'Dashboard', view: 'dashboard', icon: 'layout-dashboard', adminOnly: true },
        {
            label: 'Projects', icon: 'check-square', id: 'grp-projects', children: [
                // { label: 'Tasks', view: 'todos', icon: 'list-todo' }, // Moved to Plugin
            ]
        },
        {
            label: 'Content', icon: 'folder', id: 'grp-content', children: [
                { label: 'CMS', view: 'cms', icon: 'file-text' },
                { label: 'Templates', view: 'cms-templates', icon: 'layout-template', adminOnly: true },
                // { label: 'Components', view: 'cms-components', icon: 'puzzle', adminOnly: true }, // Injected by Plugin
                { label: 'Forms', view: 'forms', icon: 'clipboard-list' }
            ]
        },
        {
            label: 'System', icon: 'settings-2', id: 'grp-system', adminOnly: true, children: [
                { label: 'Users', view: 'users', icon: 'users' },

                // APIs injected via plugin
                // Console injected via plugin
                // Sites injected via plugin
                { label: 'Site Settings', view: 'site-settings', icon: 'globe' },
                { label: 'Plugins', view: 'plugins', icon: 'plug' },
            ]
        }
    ];

    const menuTree = computed(() => {
        const isAdmin = store.getters.isAdmin.value; // Access value property for computed/ref
        const dynamicItems = (store.state.user && store.state.user.menu_items) ? store.state.user.menu_items : [];

        // Validation helper
        const validateMenuItem = (item) => {
            if (!item.label && !item.id) {
                console.warn('Invalid menu item (missing label or id):', item);
                return false;
            }
            if (!item.id && !item.view && (!item.children || item.children.length === 0)) {
                console.warn('Invalid menu item (missing view, id, or children):', item);
                return false;
            }
            return true;
        };

        // Deep clone base items to avoid mutation issues
        let items = JSON.parse(JSON.stringify(baseMenuItems));

        // Merge dynamic items
        dynamicItems.forEach(dItem => {
            if (!validateMenuItem(dItem)) return;

            const existingIndex = items.findIndex(i => i.id === dItem.id || (i.label && i.label === dItem.label));
            if (existingIndex > -1) {
                // Merge children
                if (dItem.children) {
                    if (!items[existingIndex].children) items[existingIndex].children = [];
                    items[existingIndex].children.push(...dItem.children.filter(validateMenuItem));
                }
            } else {
                // Add new item
                items.push(dItem);
            }
        });

        return items.map(item => {
            if (item.adminOnly && !isAdmin) return null;

            // Visibility Check: Ensure component exists for the view
            const isViewAvailable = (view) => {
                if (!view) return true; // Group headers might not have view

                // Allow Core Views (hardcoded list matching static map in site.js)
                const coreViews = [
                    'dashboard', 'users', 'cms', 'cms-templates', 'cms-components',
                    'forms', 'settings', 'site-settings', 'plugins', 'todos', 'login'
                ];
                if (coreViews.includes(view)) return true;

                // Check Dynamic Components
                if (window.siteConfig && window.siteConfig.ui_components && window.siteConfig.ui_components[view]) {
                    return true;
                }

                return false;
            };

            // Filter Children
            if (item.children) {
                const visibleChildren = item.children.filter(child => {
                    const hasPerms = !child.adminOnly || isAdmin;
                    const exists = isViewAvailable(child.view);
                    return hasPerms && exists;
                });
                if (visibleChildren.length === 0) return null;
                return { ...item, children: visibleChildren };
            }

            // Check Single Item
            if (!isViewAvailable(item.view)) return null;

            return item;
        }).filter(Boolean);
    });

    return {
        baseMenuItems,
        menuTree
    };
}
