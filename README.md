# Gaia Alpha

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777bb4.svg?logo=php)
![Vue Version](https://img.shields.io/badge/vue-3.x-4FC08D.svg?logo=vue.js)
![Status](https://img.shields.io/badge/status-active-success.svg)

**Gaia Alpha** is a lightweight, self-contained web application framework that bridges the gap between simple PHP scripts and complex modern web apps. It features a robust PHP backend, a reactive Vue.js frontend, and zero-configuration SQLite persistence.

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

## 📖 Documentation

Everything you need to know about using and extending Gaia Alpha.

- **[Documentation Hub](docs/index.md)**: The central entry point for all docs.
- **[System Architecture](docs/architect/architecture.md)**: Deep dive into the core.
- **[Design System](docs/developer/design_system.md)**: UI architecture, CSS hierarchy, and layout patterns.
- **[API Reference](docs/developer/api.md)**: Endpoints and data structures.
- **[Performance](docs/devops/performance.md)**: Benchmarks and profiling.
- **[Plugin System](docs/developer/plugins.md)**: Extending the framework.
- **[Roadmap](docs/cto/roadmap.md)**: Future plans and upcoming features.

---

## ✨ Key Features

- **Zero-Config Database**: Auto-migrating SQLite setup.
- **Reactive UI**: Vue 3 frontend without a build step (ES Modules).
- **CLI Power**: Comprehensive command-line tools for DB, Media, and Files.
- **Video Engine**: Advanced image and video processing on the fly.
- **Role-Based Access**: Built-in authentication and permission system.
- **Real-time Chat**: User-to-user messaging system.
- **Slot-Based Templating**: Powerful visual page builder with reusable layouts.
- **Smart Asset Pipeline**: On-the-fly minification and caching for CSS and JS assets.

---

## 🤝 Community & Contributing

We welcome contributions from everyone! Here's how you can help:

- **[Contributing Guide](CONTRIBUTING.md)**: How to submit PRs and report bugs.
- **[Code of Conduct](CODE_OF_CONDUCT.md)**: Our pledge for a welcoming community.

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
