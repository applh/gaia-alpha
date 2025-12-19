# Master Plan: Recreating Gaia Alpha

## Objective
The goal is not to invent a new architecture, but to provide a reproducible "recipe" of prompts that any developer can use to recreate the **current** Gaia Alpha CMS (or a better version of it) using an AI assistant. The focus is on preserving the existing features, look-and-feel, and dynamic capabilities while ensuring high code quality.

## Core Philosophy of Gaia Alpha
To reproduce this system, the AI must understand its specific "flavor":
1.  **Zero-Build / Low-Build**: It's a PHP-native system that doesn't require complex build steps.
2.  **Plugin-Driven**: Everything interesting (Console, Map, Todo) happens in plugins.
3.  **Dynamic Discovery**: The system finds plugins and controllers by scanning directories, not by static configuration.
4.  **Static & Global**: It heavily uses handy static methods (`Env::get()`, `Hook::run()`) for ease of development. (Note: You can ask the AI to keep this or clean it up slightly, but this is the current DNA).

## The Re-creation Strategy
We will break the reconstruction into 4 distinct phases for the AI.

### Phase 1: The Skeleton (The Kernel)
**Goal**: A working "Hello World" that proves the routing and plugin autoloader work.
- **Key Prompts**: Ask for a lightweight PHP core that scans `plugins/` and `class/`, uses a simple `Router`, and boots up.
- **Critical Feature**: The custom autoloader that maps `GaiaAlpha\PluginName\` to `plugins/PluginName/class/`.

### Phase 2: The Core Services
**Goal**: Functional Database, Auth, and Session management.
- **Key Prompts**: Ask for a `DatabaseManager` (PDO wrapper), a `Session` helper, and a `User` model.
- **Critical Feature**: The `my-data` vs `my-config` separation for user data vs system config.

### Phase 3: The Plugin Ecosystem
**Goal**: Rebuild the features (Console, Chat, Map) as modular plugins.
- **Key Prompts**: "Create a Plugin system where each plugin has a `plugin.json` for metadata and menu definitions."
- **Critical Feature**: The recursive menu building from `plugin.json` files.

### Phase 4: The Frontend & Admin UI
**Goal**: The "Premium" look and feel.
- **Key Prompts**: Specific instructions on using Tailwind (via CDN or local), customized scrollbars, and glassmorphism.
- **Critical Feature**: The specific design tokens (colors, spacing) that make Gaia Alpha look unique.

## How to Use This Guide
1. Navigate to `docs/prompts/03_execution_prompts.md` and copy/paste the prompts sequentially into your AI chat window.
2. For even better results using Archives (ZIP) or Database files as context, see [Advanced Prompting Tips](file:///Users/lh/Downloads/antig/gaia-alpha/docs/prompts/04_advanced_prompting.md).
3. To understand how the AI perceives the complexity of this repo, see the [AI Capability Evaluation](file:///Users/lh/Downloads/antig/gaia-alpha/docs/prompts/05_ai_evaluation.md).
4. For details on how the AI builds and manages portable site packages, see the [Site Packages Guide](file:///Users/lh/Downloads/antig/gaia-alpha/docs/prompts/06_site_packages_guide.md).
5. For perspectives on working in mixed AI-Human development teams, see [AI & Human Teams](file:///Users/lh/Downloads/antig/gaia-alpha/docs/prompts/07_ai_human_collaboration.md).
6. To see how companies should evolve their team structures and project timelines, see the [AI Organizational Strategy](file:///Users/lh/Downloads/antig/gaia-alpha/docs/prompts/08_ai_organizational_strategy.md).
