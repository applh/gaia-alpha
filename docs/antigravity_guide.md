
# Working with Antigravity (AI)

To get the best results from your AI Assistant, follow these guidelines. We work best when provided with **Structure**, **Context**, and **Constraint**.

## 1. Prime the Context
Before asking for code, **open the relevant files**.
*   If you want a new Controller, open an existing good Controller *and* the target directory.
*   If you are debugging, open the error log or the file you suspect is broken.

## 2. Invoke the Patterns
Instead of saying "Build a feature", say:
> "Build the 'Tags' feature **following the Plugin Pattern** and the **Declarative Menu Pattern**."

This forces the AI to look up `docs/developer/*` and aligns the output with your standards instantly.

## 3. The "Golden Sample" Strategy
If you want something done in a specific style that isn't documented yet:
1.  Find a file that does it right.
2.  Tell the AI: **"Use `plugins/Todo/plugin.json` as the Golden Sample for declarative menu config."**

## 4. Workflows & Iteration
*   **Plan First**: Ask the AI to "Draft a plan" before writing code.
*   **One Step at a Time**: "First create the directory structure, then stop." -> "Now create the controller."
*   **Correction**: If the AI hallucinates a library or pattern, correct it immediately: "We don't use composer for frontend, we use the `min/js/vendor` pattern."

## 5. When I get "Stuck"
If the AI seems to be "re-discovering" the framework:
*   Remind it to read `docs/developer/*.md`.

## 6. Common Pitfalls & Anti-Patterns

Here are things the AI frequently gets wrong in this specific codebase. **Correcting these early saves time.**

### ❌ "Use Composer / NPM"
*   **The AI thinks:** "I need to install a library, check `package.json`."
*   **The Reality:** This project uses **manual vendor management**. JS libs go in `min/js/vendor/`. PHP libs go in `lib/`.
*   **Fix:** Remind it: *"This is a no-build project. No npm, no composer."*

### ❌ "Create a .vue file"
*   **The AI thinks:** "Vue 3 means Single File Components (.vue)."
*   **The Reality:** We use **ES Modules (.js)** with `template` strings.
*   **Fix:** Remind it: *"Use the UI Component Pattern (.js files)."*

### ❌ "Routes are auto-discovered"
*   **The AI thinks:** "If I create a controller file, it works."
*   **The Reality:** You MUST manually register the controller in the plugin's `index.php` hook.
*   **Fix:** Check `index.php` whenever a 404 appears.

### ❌ "Use Manual Menu Hooks"
*   **The AI thinks:** "I need to write PHP code to add a menu item."
*   **The Reality:** We now use **Declarative Config** in `plugin.json` for menu items.
*   **Fix:** Check `plugin.json` and use the `menu` key.

### ❌ "Use standard Namespaces"
*   **The AI thinks:** `App\Controllers`...
*   **The Reality:** `PluginName\Controller` mapped to `plugins/PluginName/class/Controller`.
*   **Fix:** Invoke the **Plugin Pattern**.

## 7. Context Management & New Tasks
When starting a new objective, **start a new conversation**.

*   **Fresh Context**: Clears noise and old code snippets, allowing the AI to focus entirely on the new goal.
*   **Performance**: Smaller context windows result in faster and more accurate reasoning.
*   **Knowledge Persistence**: Improvements to docs (like this file) are persistent. The new agent effectively "learns" what the previous one documented.

**When to stay:** Only keep the conversation for direct follow-ups or immediate iteration on the specific task.

## 8. Plugin Architecture Best Practices
To keep the codebase maintainable, follow these strict rules for Plugins:

### Glue vs Logic
*   **`index.php` is Glue**: It should ONLY contain `Hook::add` calls and basic setup.
*   **Logic belongs in Classes**: Move all business logic, HTML generation, and complex data handling into `class/Controller/` or `class/Model/`.

### Directory Structure
*   `plugins/MyPlugin/`
    *   `plugin.json` (Manifest & Menu Config)
    *   `index.php` (Hooks & Registration)
    *   `class/` (PHP Classes, mapped to `MyPlugin\` namespace automatically)
    *   `resources/` (JS, CSS, Vue components)

### Registration Order
*   Always register controllers in the `framework_load_controllers_after` hook.
*   Always register API routes inside your Controller's `registerRoutes` method.
*   **Never** use a custom autoloader if your classes follow the standard `MyPlugin\Namespace` -> `plugins/MyPlugin/class/Namespace.php` map.
