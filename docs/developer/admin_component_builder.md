# Admin Component Builder - Developer Guide

## Overview
The component builder generates Vue 3 async components stored as `.js` files in `resources/js/components/custom/`. It uses a metadata-driven approach where the definition is stored in the database (`admin_components`) and the code is generated on-the-fly or upon save.

## Architecture

### Backend
- **Controller**: `AdminComponentBuilderController.php` - Handles CRUD and generation requests.
- **Service**: `ComponentCodeGenerator.php` - Translates JSON definition to Vue template strings.
- **Storage**: `AdminComponentManager.php` - Manages DB records and file writing.

### Frontend
- **Builder**: `resources/js/components/builder/` - Contains the UI logic.
- **Library**: `resources/js/components/builder/library/` - The source components used in previews and generated code.
  - These are **Async Components** loaded via `defineAsyncComponent`.

## Adding a New Component

To add a new component (e.g., `VideoPlayer`) to the builder:

1. **Create Library Component**:
   Create `resources/js/components/builder/library/VideoPlayer.js`:
   ```javascript
   export default {
       props: { src: String },
       template: '<video :src="src" controls></video>'
   };
   ```

2. **Register in Toolbox**:
   Edit `resources/js/components/builder/ComponentToolbox.js`:
   ```javascript
   { type: 'video-player', label: 'Video', icon: 'play' }
   ```

3. **Add Properties**:
   Edit `resources/js/components/builder/ComponentProperties.js`:
   ```javascript
   <div v-if="component.type === 'video-player'">
       <input @input="update('props.src', $val)" ... />
   </div>
   ```

4. **Update Code Generator**:
   Edit `class/GaiaAlpha/Service/ComponentCodeGenerator.php`:
   - Import the file in the top `defineAsyncComponent` block.
   - Add a `case` in `generateComponent()`:
     ```php
     case 'video-player':
         return "<VideoPlayer src=\"{$props['src']}\" />";
     ```

## Code Generation Logic
The generator produces a Vue component string with:
- `<template>`: Recursive render of the layout tree.
- `<script>`: Imports, `setup()` with reactive state (`data`, `formData`), and helper methods (`submitForm`, `handleAction`).
- `<style>`: Scoped styles.

## Debugging
- **Generated Files**: Check `resources/js/components/custom/` to see the actual output code.
- **Preview**: The "Preview" feature in the builder loads the generated code in an iframe/modal (implementation pending).
