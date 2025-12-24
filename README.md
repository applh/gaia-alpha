# Gaia Alpha

![Gaia Alpha Guardian](docs/assets/cyberpunk_centaur_archer.png)

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777bb4.svg?logo=php)
![Vue Version](https://img.shields.io/badge/vue-3.x-4FC08D.svg?logo=vue.js)
![Status](https://img.shields.io/badge/status-active-success.svg)

## Gaia Alpha Centauri

> **Gaia Alpha Centauri** is an advanced, AI-native web application framework designed from the ground up for the era of autonomous development. It serves as a unified ecosystem that bridges the gap between rapid prototyping and enterprise-grade scalability through its four core pillars:
> 
> 1.  **🤖 AI-Native Core**: Deeply integrated with the Model Context Protocol (MCP), Gaia Alpha provides a standardized interface for LLMs to understand, manipulate, and generate code, content, and UIs autonomously.
> 2.  **🔌 Modular Plugin Architecture**: A decoupled system allowing for seamless extension without core bloat. Every plugin is "Agent-Aware," exposing specific tools and schemas to the AI layer.
> 3.  **⚡ Reactive Vue 3 Frontend**: A zero-build, ESM-native design system that prioritizes performance and visual excellence, optimized for both human interaction and AI-driven UI generation.
> 4.  **💾 Polyglot Persistence**: Automated multi-database support (SQLite, MySQL, PostgreSQL) with a low-code schema management layer that allows AI to orchestrate data structures dynamically.

---

## 🤖 The AI-Native Advantage

Gaia Alpha is engineered from the ground up to be **AI-Native**. It doesn't just "support" AI; it provides a structured interface for autonomous agents to understand, interact with, and build upon the codebase.

- **[MCP Server Core](docs/plugins/mcp_server.md)**: Native support for the **Model Context Protocol**. Every Gaia plugin can expose tools directly to an LLM.
- **Doc-Driven Development**: Our documentation is optimized for both humans and LLMs, providing clear context, schemas, and usage patterns for AI agents.
- **Autonomous Agents**: Built-in support for long-running agents that can manage content, perform SEO audits, and refactor code via the [MCP Integration](docs/ai/mcp_tools.md).
- **[AI Center](docs/ai/constitution.md)**: A central "Constitution" that guides AI behavior within the framework.

---

## ✨ The Gaia Alpha Design System

Our comprehensive **UI Design System** is built to be "Agent-Friendly," allowing AI to construct complex, consistent UIs with standardized primitives.

- **25+ Reusable Components**: Standardized controls for everything from Core Forms to Advanced Data Tables.
- **Pure Vue 3 (ESM)**: Reactive components that work directly in the browser with **zero build steps**.
- **Unified Aesthetics**: A cohesive glassmorphism theme applied across all core views and plugins.
- **[Component Usage Guide](docs/frontend/component_guide.md)**: Detailed API and examples for developers.
- **[Design System Comparison](docs/frontend/design_system_comparison.md)**: Benchmarking against Element+ and UIkit.

---

## 🚀 Quick Start

1. **Clone & Enter**
   ```bash
   git clone https://github.com/applh/gaia-alpha.git
   cd gaia-alpha
   ```

2. **Run Server** (No build required!)
   ```bash
   php -S localhost:8000 -t www
   ```

   *To increase upload size limit (e.g. 500MB):*
   ```bash
   php -d upload_max_filesize=500M -d post_max_size=500M -S localhost:8000 -t www
   ```

3. **Installation**
   Open [http://localhost:8000](http://localhost:8000). You will be automatically redirected to the **Installation Screen**. 

---

## ✨ Key Features

- **Standardized UI**: Every core module and plugin utilizes the unified **Gaia Alpha Design System**.
- **29+ Modular Plugins**: Out-of-the-box support for `MediaLibrary`, `Ecommerce`, `LMS`, `FormBuilder`, and more.
- **Multi-Database Persistence**: Automated SQLite setup with support for MySQL and PostgreSQL.
- **50+ MCP Tools**: Native tools for AI-assisted development and content management.
- **Zero-Build Vue 3**: High performance with modern web standards (ES Modules).
- **[Roadmap](../../docs/strategy/roadmap.md)**: Explore our vision and compare us with the WordPress ecosystem.

### 📊 By the Numbers

- **77,000+** Lines of Code
- **~660** Files
- **29** Active Plugins
- **50+** MCP Tools for AI integration

---

## 📖 Documentation

- **[Main Index](docs/index.md)**: Topical entry point for all documentation.
- **[AI Prompts Guide](docs/user/ai_prompts_guide.md)**: Build features in minutes using LLMs.
- **[System Architecture](docs/core/architecture.md)**: Deep dive into the request lifecycle.
- **[Plugin System](docs/plugins/system_overview.md)**: Creating and managing extensions.
- **[Front-end Guide](docs/frontend/component_guide.md)**: Mastering the Design System.

---

## 📜 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
