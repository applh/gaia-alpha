
# Working with Antigravity (AI)

To get the best results from your AI Assistant, follow these guidelines. We work best when provided with **Structure**, **Context**, and **Constraint**.

## 1. Prime the Context
Before asking for code, **open the relevant files**.
*   If you want a new Controller, open an existing good Controller *and* the target directory.
*   If you are debugging, open the error log or the file you suspect is broken.

## 2. Invoke the Patterns
Instead of saying "Build a feature", say:
> "Build the 'Tags' feature **following the Plugin Pattern** and the **Controller Pattern**."

This forces the AI to look up `docs/patterns/*` and aligns the output with your standards instantly.

## 3. The "Golden Sample" Strategy
If you want something done in a specific style that isn't documented yet:
1.  Find a file that does it right.
2.  Tell the AI: **"Use `plugins/Map/index.php` as the Golden Sample for this new plugin."**

## 4. Workflows & Iteration
*   **Plan First**: Ask the AI to "Draft a plan" before writing code.
*   **One Step at a Time**: "First create the directory structure, then stop." -> "Now create the controller."
*   **Correction**: If the AI hallucinates a library or pattern, correct it immediately: "We don't use composer for frontend, we use the `min/js/vendor` pattern."

## 5. When I get "Stuck"
If the AI seems to be "re-discovering" the framework:
*   Remind it to read `docs/patterns`.

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

### ❌ "Use standard Namespaces"
*   **The AI thinks:** `App\Controllers`...
*   **The Reality:** `PluginName\Controller` mapped to `plugins/PluginName/class/Controller`.
*   **Fix:** Invoke the **Plugin Pattern**.
