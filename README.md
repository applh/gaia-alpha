# Gaia Alpha

![Gaia Alpha Guardian](docs/assets/cyberpunk_centaur_archer.png)

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777bb4.svg?logo=php)
![Vue Version](https://img.shields.io/badge/vue-3.x-4FC08D.svg?logo=vue.js)
![Status](https://img.shields.io/badge/status-active-success.svg)

> **Gaia Alpha Centauri** is an advanced, AI-native web application framework designed for the modern era of development. It bridges the gap between rapid prototyping and enterprise scalability by combining a modular **Plugin Architecture**, a reactive **Vue 3** frontend, and flexible **Multi-Database** persistence.

---

## ✨ The Gaia Alpha Design System

Gaia Alpha now features a comprehensive, state-of-the-art **UI Design System** inspired by glassmorphism and modern design trends. It is built to be "Agent-Friendly," allowing AI to construct complex UIs with standardized primitives.

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

## 🤖 The AI-Native Advantage

Gaia Alpha is built to work *with* AI. Our structured **Site Packages** and **MCP Integration** allow you to orchestrate entire applications using natural language.

- **[AI Prompts Guide](docs/user/ai_prompts_guide.md)**: Build features in minutes using LLMs.
- **[Site Packages Guide](docs/core/site_packages.md)**: Portable site bundles for rapid deployment.
- **[AI Center](docs/ai/constitution.md)**: Core directives and functional overview for AI agents.

---

## 📖 Documentation

- **[Main Index](docs/index.md)**: Topical entry point for all documentation.
- **[System Architecture](docs/core/architecture.md)**: Deep dive into the request lifecycle.
- **[API Reference](docs/core/api_reference.md)**: Comprehensive endpoint documentation.
- **[Plugin System](docs/plugins/system_overview.md)**: Creating and managing extensions.
- **[Front-end Guide](docs/frontend/component_guide.md)**: Mastering the Design System.

---

## ✨ Key Features

- **Standardized UI**: Every core module and plugin now utilizes the unified **Gaia Alpha Design System**.
- **29+ Modular Plugins**: Out-of-the-box support for `MediaLibrary`, `Ecommerce`, `LMS`, `FormBuilder`, and more.
- **Multi-Database persistence**: Automated SQLite setup with support for MySQL and PostgreSQL.
- **MCP Server Integration**: 50+ tools for AI-assisted development (Antigravity).
- **Reactive Dashboard**: Real-time management of assets, code, and databases.
- **Zero-Build Vue 3**: High performance with modern web standards (ES Modules).

### 📊 By the Numbers

- **77,000+** Lines of Code
- **~660** Files
- **29** Active Plugins
- **50+** MCP Tools for AI integration

---

## 📜 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
