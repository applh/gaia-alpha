# Architecture & Patterns to Request

To replicate Gaia Alpha, you need to prompt the AI to use specific architectural patterns that define its flexibility and ease of use.

## 1. The "Service Locator" Pattern (via `Env`)
Unlike strict Dependency Injection, Gaia Alpha uses a pragmatic `Env` class to store global state.
- **Prompt Instruction**: "Create a global `Env` class with static `get($key)` and `set($key, $val)` methods to centrally manage configuration and class instances. No complex DI containers."
- **Why**: This allows any part of the app (including plugins) to easily access the DB (`Env::get('db')`) without wiring up dependencies.

## 2. The "Hook" Event System
The plugin system relies on a simple, string-based event bus.
- **Prompt Instruction**: "Implement a static `Hook` class with `on($event, $callback)` and `run($event, $args)`. It should allow multiple callbacks per event."
- **Why**: This is the glue that lets plugins inject HTML into the `<body>`, add routes, or modify data on save.

## 3. Dynamic Plugin Autoloading
Plugins are not hardcoded. They are discovered.
- **Prompt Instruction**: "Write a custom `spl_autoload_register` function that checks the `plugins/` directory. If a class `MyPlugin\Controller\Foo` is requested, it should look in `plugins/MyPlugin/class/Controller/Foo.php`."
- **Why**: This enables "Drop-in" installation. You just unzip a folder into `plugins/` and it works.

## 4. JSON-Based "Manifests"
Every plugin and component describes itself via JSON.
- **Prompt Instruction**: "Each plugin must have a `plugin.json` defining its name, version, and **menu structure**. The system should parse these to build the Admin sidebar dynamically."
- **Why**: It separates metadata from code, allowing the Admin UI to know about plugins without loading their PHP classes.

## 5. File-Based "Resources"
Data isn't just in the DB; it's often files.
- **Prompt Instruction**: "Store user uploads and generated assets in a `my-data` directory, separate from the code. Ensure the application can serve these assets with correct MIME types."
- **Why**: Easy backup and migration (Import/Export).

## 6. The "Super-Controller" Pattern
Routes map to `Controller::method`.
- **Prompt Instruction**: "Routes should define a class and method string (e.g., `User::login`). The Router should find the class, instantiate it, and call the method."

## 7. Frontend: "No-Build" Tailwind
- **Prompt Instruction**: "Use the Tailwind CSS script for rapid prototyping/development so we don't need a Node.js build step initially. Add custom CSS variables for theming."
