# Architecture Plan: Migration to Core Plugins

## 1. Objective
To refactor the Gaia Alpha monolithic architecture by moving distinct functional features into "Core Plugins". This aims to:
-   **Improve Modularity**: Isolate feature logic, reducing coupling.
-   **Enhance Maintainability**: Smaller, focused codebases for each feature.
-   **Enable Optionality**: Allow users/developers to enable/disable core features (e.g., disable CMS if acting as a pure API server).
-   **Validate Plugin System**: "Dogfood" the plugin API to ensure it is robust enough for high-complexity features.

## 2. Methodology
A "Core Plugin" is functionally identical to a standard user-land plugin but may fulfill essential system roles. They will be distributed with the core system but architecturally decoupled.

### 2.1 Storage
-   **Standard Plugins**: `plugins/`
-   **Core Plugins**: `plugins/`. Distinguished by a `"type": "core"` property in `plugin.json`. This flag will be used to prevent accidental deletion and potentially handle load order.

### 2.2 Capabilities Required
To support these features as plugins, the Plugin System must strictly support:
-   **Admin Route Registration**: Injecting pages into the Admin Panel.
-   **API Route Registration**: defining `/@/v1/...` endpoints.
-   **UI Injection**: Adding menu items to the Admin Sidebar and Dashboard widgets.
-   **Asset Serving**: Serving plugin-specific JS/CSS (already partially supported but needs hardening).

## 3. Candidates for Migration

Based on the current codebase analysis, the following features are prime candidates:

| Feature | Backend Controllers | Frontend Components | Complexity |
| :--- | :--- | :--- | :--- |
| **Console** | `ConsoleController` | `ConsolePanel.js` | Low |
| **Chat** | `ChatController` | `ChatPanel.js` | Low | **Done** |
| **Media Manager** | `MediaController` | (Media UI components) | Medium |
| **Multi-Site** | `SiteController` | `MultiSitePanel.js` | Medium | **Done** |
| **Database Manager** | `DbController` | `DatabaseManager.js` | High | **Done** |
| **API Builder** | `ApiBuilderController`, `DynamicApiController` | `ApiManager.js` | High |
| **Component Builder** | `AdminComponentBuilderController` | `builders/*` | High |
| **CMS/Pages** | `CmsController`, `ViewController` | (Page Editors) | Very High |

## 4. Migration Strategy

### Phase 1: Proof of Concept (Low Risk)
**Target: Console & Chat (Completed)**
-   **Goal**: Validate that simple features can be fully externalized.
-   **Status**: **Useable/Done**
-   **Tasks**:
    1. Update Autoloader/PluginManager to identify these new plugins.
    2. Move `ConsoleController` logic to `plugins/Console/class/`.
    3. Move `ConsolePanel.js` to `plugins/Console/resources/js/`.
    4. Register Admin Routes via `plugin.json` or `Plugin.php` boot.

### Phase 2: Tooling (Medium/High Risk)
**Target: API Builder & Database Manager (Partial)**
-   **Goal**: Move complex admin tools.
-   **Challenge**: These tools often rely on deep system access.
-   **Tasks**:
    1. Ensure `DynamicApiController` can route requests even when inside a plugin.
    2. Ensure `AssetController` can serve the Vue components from the plugin directory efficiently.

### Phase 3: Core Logic (High Risk)
**Target: CMS (Pages & Views)**
-   **Goal**: Make the "Website" part of Gaia Alpha optional.
-   **Challenge**: The root route handling logic (`/`) often assumes a CMS structure.
-   **Tasks**:
    1. Refactor `PublicController` to delegate "root" handling to the CMS plugin if active.
    2. If CMS is disabled, default to a generic "App Running" or 404 page.

## 5. Directory Structure Example

```text
gaia-alpha/
├── class/GaiaAlpha/   (Kernel Only: App, Router, PluginManager, AssetController)
├── plugins/
│   ├── Console/
│   │   ├── plugin.json
│   │   ├── class/
│   │   │   └── ConsoleController.php
│   │   └── resources/
│   │       └── js/
│   │           └── ConsolePanel.js
│   ├── ApiBuilder/
│   │   ├── ...
```

## 6. Implementation Steps for Evaluation

1.  **Refactor Plugin Manager**: 
    -   Update `PluginManager` to read the `type` field from `plugin.json`.
    -   Implement protection against deleting plugins with `type: "core"`.
    -   Ensure Core plugins are loaded before User plugins if necessary.
2.  **Asset Pipeline Update**: Verify `AssetController` can serve `plugins/{PluginName}/resources/js/...` as module imports easily (e.g. `import Console from 'plugins/Console/js/Console.js'`).
3.  **Route Hooking**: Standardize how plugins inject Admin Routes (`/@/admin/...`).

## 7. Recommendation
Start with **Phase 1 (Console)** immediately to validate the approach without risking system stability.
