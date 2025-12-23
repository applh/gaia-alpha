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
- **[Site Packages Guide](docs/core/site_packages.md)**: Evaluation and usage of portable site bundles.
- **Enterprise Ready**: Comes with a full [Enterprise Site Example](docs/user/example_enterprise_site.md) out of the box.

---

## 🛡️ The AI & Machine Center

Everything you need to optimize your collaboration with AI agents like Antigravity.

- **[AI Constitution](docs/ai/constitution.md)**: Core directives and unbreakable rules for agents.
- **[Project Map](docs/ai/project_map.md)**: Functional overview for rapid orientation.
- **[Master Plan](docs/ai/master_plan.md)**: The vision for recreating Gaia Alpha features.
- **[AI Capabilities](docs/ai/ai_capability_evaluation.md)**: Assessment of agent performance relative to codebase complexity.

---

## 📖 Documentation

The Gaia Alpha documentation is organized topically for maximum discoverability.

- **[Documentation Index](docs/index.md)**: The central topical entry point.
- **[Documentation by Role](docs/roles.md)**: Curated guides for Developers, Architects, DevOps, and CTOs.
- **[System Architecture](docs/core/architecture.md)**: Deep dive into the request lifecycle and core logic.
- **[API Reference](docs/core/api_reference.md)**: Full list of endpoints and data structures.
- **[Plugin System](docs/plugins/system_overview.md)**: How to extend the framework.
- **[Roadmap](docs/strategy/roadmap.md)**: Future plans and upcoming features.
- **[Changelog](docs/CHANGELOG.md)**: Version history.

---

## ✨ Key Features

- **Digital Asset Management**: Advanced media library with tagging, search, and organization.
- **Enterprise Site Starter Kit**: Ready-to-use 10-page corporate site structure.
- **Core Plugin Architecture**: 20+ modular plugins including ComponentBuilder, FormBuilder, and ApiBuilder.
- **MCP Server Integration**: 50+ tools for AI-assisted development and management.
- **Declarative Menu System**: JSON-based menu configuration for ease of use.
- **Zero-Config Database**: Auto-migrating SQLite setup with multi-DB support.
- **Reactive UI**: Vue 3 frontend without a build step (ES Modules).
- **CLI Power**: Comprehensive command-line tools for DB, Media, and Files.
- **Video Engine**: Advanced image and video processing on the fly.
- **Real-time Chat**: User-to-user messaging system.

### 📊 By the Numbers

- **125,000+** lines of code
- **~500** files
- **20** active plugins
- **50+** MCP tools for AI integration

---

## 🤝 Community & Contributing

We welcome contributions from everyone! 

- **[Contributing Guide](.github/CONTRIBUTING.md)**: (Coming soon)
- **[CLI Reference](docs/core/cli_commands.md)**: Learn to use the powerful `cli.php` tool.

### Performance Benchmarking
We care about speed. Prove it yourself:
```bash
php cli.php bench:all
```

---

## 📜 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
