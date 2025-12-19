# Execution Prompts (The "Recipe")

These are the exact prompts to feed an AI to recreate Gaia Alpha from scratch. Use them sequentially.

## 0. Context Setting
*Paste this first to set the persona.*
```text
You are an expert Senior PHP Developer specializing in "No-Framework" architecture. 
We are building a highly modular, plugin-based CMS called "Gaia Alpha".
Rules:
1. No composer dependencies unless absolutely necessary.
2. No complex build tools (Webpack/Vite) for the backend.
3. Use a "Service Locator" pattern via a static `Env` class (e.g. `Env::get('db')`).
4. All plugins live in `plugins/` and are discovered dynamically.
5. Stylize everything with Tailwind CSS (CDN version is fine for dev) and Glassmorphism.
```

## 1. The Kernel
```text
Create the core kernel structure.
1. `index.php`: The entry point.
2. `class/GaiaAlpha/App.php`: The main application class with a `run()` method.
3. `class/GaiaAlpha/Env.php`: A static class to store config/state.
4. `class/GaiaAlpha/Router.php`: A simple Regex router.
5. `class/GaiaAlpha/Hook.php`: A simple event dispatcher (`on`, `run`).
6. Implement a custom autoloader in `App.php` that maps `GaiaAlpha\` to `class/GaiaAlpha/` and `PluginName\` to `plugins/PluginName/class/`.
Goal: I want to visit `index.php` and see "Gaia Alpha Core Active".
```

## 2. The Plugin System
```text
Implement the dynamic plugin loader.
1. In `App.php`, add a `loadPlugins()` method.
2. It should scan the `plugins/` directory.
3. For each folder, look for `plugin.json` (metadata) and load it into `Env`.
4. Create a test plugin `plugins/Demo/plugin.json` and a controller `plugins/Demo/class/Controller.php`.
5. Hook into the router so `plugins/Demo/class/Controller.php` can handle a route like `/demo`.
Goal: I can access `/demo` and see output from the plugin controller.
```

## 3. Database & Auth
```text
Add the Data and Auth layer.
1. Create `class/GaiaAlpha/Database.php` (PDO wrapper).
2. Create `class/GaiaAlpha/Session.php` (start session, set/get flash data).
3. Create `class/GaiaAlpha/Auth.php`.
   - method `login($email, $password)`: verify against `users` table.
   - method `register($email, $password)`: hash password and insert.
   - method `user()`: return current user or null.
4. Middleware: In `Router`, add support for checking `Auth::user()` before allowing access to `/admin`.
```

## 4. The Admin Dashboard (UI)
```text
Create the Admin Interface.
1. Create a layout template `templates/admin_layout.php`.
   - Include Tailwind CSS via CDN.
   - Sidebar: Iterate through all loaded plugins. If a plugin has a "menu" entry in `plugin.json`, render it here.
2. Create `plugins/Dashboard/` as a core dashboard plugin.
   - `Controller.php`: Render the main stats page.
   - `views/index.php`: A glassmorphism card layout showing "System Status".
Goal: I can login and see a Sidebar populated by `plugin.json` entries.
```

## 5. The "Killer" Plugins
*Execute these one by one.*

### A. Console Provider
```text
Create a `plugins/Console` plugin.
1. It should have a `cli.php` entry point in the root.
2. It scans all other plugins for "Commands" (classes implementing a Command interface).
3. Implement `migrate` command that looks for `.sql` files in `plugins/*/sql/`.
```

### B. Component Builder
```text
Create a `plugins/ComponentBuilder` plugin.
1. CRUD interface to save HTML snippets to `my-data/components/`.
2. Add a global Hook: When rendering any view, replace `[[component:name]]` with the content of the saved component.
```

### C. Multi-Site Support
```text
Implement Multi-Site isolation.
1. In `App.php` (bootstrap), check `$_SERVER['HTTP_HOST']`.
2. Set `Env::set('site_id', $domain)`.
3. Change the storage path: `Env::set('path_data', 'my-data/sites/' . $domain)`.
4. Ensure each site has its own SQLite database or separate folder.
```

## 6. Final Polish
```text
Refine the UI.
1. Update `admin_layout.php` to use a dark theme with backdrop-blur-md (Glassmorphism).
2. Add "Toast" notifications for success/error messages using `Session::flash()`.
3. Ensure all forms have CSRF protection.
```
