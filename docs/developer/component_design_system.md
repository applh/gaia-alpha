# Component Design System

Gaia Alpha uses a structured, modular component system built with Vue.js 3 and Native ES Modules (Import Maps). This design system ensures consistency, maintainability, and ease of use across the application, particularly in the Admin Panel and CMS.

## Directory Structure

Components are organized in `resources/js/components/` with the following subdirectories:

*   **`ui/`**: Low-level, reusable UI primitives. (e.g., `Icon.js`, `Modal.js`, `SortTh.js`, `PasswordInput.js`, `ImageSelector.js`).
*   **`admin/`**: Components specific to the Admin Panel logic (e.g., `Login.js`, `DebugToolbar.js`, `ConsolePanel.js`).
*   **`cms/`**: Components related to Content Management (e.g., `CMS.js`, `FormsAdmin.js`, `FormSubmissions.js`).
*   **`builders/`**: Complex builder interfaces (e.g., `ComponentBuilder.js`, `MenuBuilder.js`, `TemplateBuilder.js`) and their sub-components (in `builders/builder/`).
*   **`views/`**: Full-page views or complex panel components (e.g., `TodoList.js`, `MapPanel.js`, `ChatPanel.js`).

## Import Map Aliases

To simplify imports and avoid relative path hell, we use Native Import Maps defined in `templates/app.php`.

| Alias | Path | Description |
| :--- | :--- | :--- |
| `vue` | `/js/vendor/vue.esm-browser.js` | Vue 3 ESM build |
| `@/` | `/min/js/` | Javascript Root |
| `components/` | `/min/js/components/` | Components Root |
| `ui/` | `/min/js/components/ui/` | UI Primitives |
| `admin/` | `/min/js/components/admin/` | Admin Components |
| `cms/` | `/min/js/components/cms/` | CMS Components |
| `builders/` | `/min/js/components/builders/` | Builder Components |
| `views/` | `/min/js/components/views/` | View Components |
| `composables/` | `/min/js/composables/` | Vue Composables |
| `store` | `/min/js/store.js` | Global State Store |

**Best Practice:** Always use aliases (e.g., `import Icon from 'ui/Icon.js'`) instead of relative paths (`import Icon from '../../ui/Icon.js'`).

## Standard Components

### 1. Icon (`ui/Icon.js`)
A wrapper around Lucide Icons.
```javascript
import Icon from 'ui/Icon.js';
// Usage: <Icon name="user" size="16" />
```

### 2. Modal (`ui/Modal.js`)
Standard modal dialog.
```javascript
import Modal from 'ui/Modal.js';
// Usage:
// <Modal :show="isOpen" title="My Modal" @close="isOpen = false">
//    <p>Content</p>
// </Modal>
```

### 3. SortTh (`ui/SortTh.js`)
Sortable table header.
```javascript
import SortTh from 'ui/SortTh.js';
// Usage: <SortTh name="username" label="Username" :currentSort="sortColumn" :sortDir="sortDirection" @sort="sortBy" />
```

### 4. PasswordInput (`ui/PasswordInput.js`)
Password field with toggle visibility.
```javascript
import defineAsyncComponent from 'vue';
const PasswordInput = defineAsyncComponent(() => import('ui/PasswordInput.js'));
// Usage: <password-input v-model="password" />
```

### 5. ImageSelector (`ui/ImageSelector.js`)
Media library text/upload interface.

### 6. CodeEditor (`ui/CodeEditor.js`)
Simple code editor component (often wraps a textarea or lightweight editor).

## CSS Architecture
Styles are located in `resources/css/`.
*   `index.css`: Main entry point.
*   `admin.css`: Admin panel specific styles.
*   `vars.css`: CSS Variables (Colors, Spacing).

**Key Variables:**
*   `--primary-color`: Main brand color.
*   `--bg-color`: Application background.
*   `--text-primary`: Main text color.
*   `--border-color`: Standard border color.

## Usage Patterns

1.  **Async Components:** For heavier components, use `defineAsyncComponent`.
    ```javascript
    const MyComp = defineAsyncComponent(() => import('path/to/comp.js'));
    ```
2.  **Composables:** Logic reuse via `composables/` (e.g., `useCrud`, `useSorting`).
3.  **Global Store:** Simple reactive state via `store.js`.

## Developing New Components
1.  **Identify Category:** Decide if it is `ui` (generic), `admin` (logic), or `view` (page).
2.  **Create File:** Create `.js` file in appropriate directory.
3.  **Use Template String:** Vue components are primarily Single File Components (SFC) style but written in JS strings for native browser support without build step (though we may add one later).
4.  **Export:** `export default { ... }`.
5.  **Import:** Use the appropriate alias in consumer files.
