# Gaia Alpha

![Gaia Alpha Guardian](docs/assets/cyberpunk_centaur_archer.png)

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777bb4.svg?logo=php)
![Vue Version](https://img.shields.io/badge/vue-3.x-4FC08D.svg?logo=vue.js)
![Status](https://img.shields.io/badge/status-active-success.svg)
> **Gaia Alpha Centauri is built by Antigravity, led by a Senior Software Engineer.**

**Gaia Alpha Centauri** is an advanced, AI-native web application framework designed for the modern era of development. It bridges the gap between rapid prototyping and enterprise scalability by combining a modular **Plugin Architecture**, a reactive **Vue 3** frontend (with zero build steps), and flexible **Multi-Database** persistence (SQLite, MySQL, PostgreSQL). With built-in **MCP Server integration**, it empowers seamless collaboration between human engineers and AI agents.

> "Simplicity is the ultimate sophistication." - Leonardo da Vinci

---

## 🚀 Quick Start

1. **Clone & Enter**
   ```bash
   git clone https://github.com/applh/gaia-alpha.git
   cd gaia-alpha
   ```

2. **Run Server**
   ```bash
   php -S localhost:8000 -t www
   ```

3. **Installation**
   Open [http://localhost:8000](http://localhost:8000). You will be automatically redirected to the **Installation Screen**. 
   
   Here you can:
   - Create your **Administrator Account**.
   - Optionally create a starter **App Dashboard** page (at `/app`).

   *The system invokes a zero-config setup that automatically creates the database and required folders.*

---

## 🤖 The No-Code / Low-Code Revolution

With Gaia Alpha, you don't just write code; you orchestrate it. Our structured **Site Packages** allow you to export, modify, and re-import entire sites using simple AI prompts.

- **[AI Prompts Guide](docs/user/ai_prompts_guide.md)**: Learn how to use LLMs to build features in minutes.
- **Site Packages**: Standardized structure for portable, reusable website definitions.
- **Enterprise Ready**: Comes with a full [Enterprise Site Example](docs/user/example_enterprise_site.md) out of the box.

---

## � The AI Handbook

The **[AI Handbook](docs/ai-handbook/00_master_plan.md)** is your essential guide to building, managing, and evolving Gaia Alpha with AI collaboration.

- **[Master Plan](docs/ai-handbook/00_master_plan.md)**: The roadmap for re-creating current CMS features.
- **[Advanced Prompting](docs/ai-handbook/04_advanced_prompting.md)**: Using ZIPs and Databases for hyper-accurate AI context.
- **[Site Packages Guide](docs/ai-handbook/06_site_packages_guide.md)**: How the AI builds and manages portable site bundles.
- **[Human-AI Synergy](docs/ai-handbook/07_ai_human_collaboration.md)**: New team dynamics for Junior and Senior engineers.
- **[The AI Triad](docs/ai-handbook/10_the_ai_triad_synergy.md)**: How AI, Open Source, and Companies work together.

---

## �📖 Documentation

Everything you need to know about using and extending Gaia Alpha.

- **[Documentation Hub](docs/index.md)**: The central entry point for all docs.
- **[System Architecture](docs/architect/architecture.md)**: Deep dive into the core.
- **[Design System](docs/developer/design_system.md)**: UI architecture, CSS hierarchy, and layout patterns.
- **[API Reference](docs/developer/api.md)**: Endpoints and data structures.
- **[Performance](docs/devops/performance.md)**: Benchmarks and profiling.
- **[Multi-Site](docs/devops/multi_site.md)**: Serving multiple domains from one install.
- **[Plugin System](docs/developer/plugins.md)**: Extending the framework.
- **[Roadmap](docs/cto/roadmap.md)**: Future plans and upcoming features.
- **[Changelog](docs/CHANGELOG.md)**: History of changes and versions.

---

## ✨ Key Features

- **Digital Asset Management**: Advanced media library with tagging, search, and organization.
- **Enterprise Site Starter Kit**: Ready-to-use 10-page corporate site structure.
- **Core Plugin Architecture**: 18+ modular plugins including ComponentBuilder, FormBuilder, and ApiBuilder.
- **MCP Server Integration**: 20+ tools for AI-assisted development and management.
- **Declarative Menu System**: JSON-based menu configuration for ease of use.
- **Zero-Config Database**: Auto-migrating SQLite setup.
- **Reactive UI**: Vue 3 frontend without a build step (ES Modules).
- **CLI Power**: Comprehensive command-line tools for DB, Media, and Files.
- **Video Engine**: Advanced image and video processing on the fly.
- **Role-Based Access**: Built-in authentication and permission system.
- **Real-time Chat**: User-to-user messaging system.
- **Slot-Based Templating**: Powerful visual page builder with reusable layouts.
- **Smart Asset Pipeline**: On-the-fly minification and caching for CSS and JS assets.

### 📊 By the Numbers

- **28,000+** lines of code
- **188** PHP files
- **67** JavaScript files  
- **18** active plugins
- **20+** MCP tools for AI integration

---

## 🤝 Community & Contributing

We welcome contributions from everyone! Here's how you can help:

- **[Contributing Guide](.github/CONTRIBUTING.md)**: How to submit PRs and report bugs.
- **[Code of Conduct](.github/CODE_OF_CONDUCT.md)**: Our pledge for a welcoming community.

### Performance Benchmarking
We care about speed. Proof it yourself:
```bash
php cli.php bench:all
```

---

## 🛠 CLI Overview

Gaia Alpha comes with a powerful CLI tool `cli.php`.

```bash
php cli.php <command> [args]
```

**Common Commands:**
- `table:list users`: View database rows.
- `media:process`: Optimize images.
- `bench:all`: Run performance checks.
- `help`: See all available commands.

For a full list of commands, see the [CLI Documentation](docs/developer/shell_commands.md) or run `php cli.php help`.

---

## 📜 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
