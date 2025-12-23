# Gaia Alpha Roadmap v2.0

This document outlines the future plans and upcoming features for the Gaia Alpha framework. Our goal is to maintain simplicity while providing powerful tools for modern web development.

> [!NOTE]
> This roadmap is living and subject to change based on project priorities and community needs.

## 🔌 Plugin Ecosystem
- [x] **Plugin Discovery**: Standardized `search_plugins` tool via MCP.
- [ ] **UI Management**: An admin panel interface to enable, disable, and configure plugins without touching code.
- [ ] **Dependency Management**: Basic resolution to ensure plugins have required `composer` packages or other plugins.

## 🤖 AI & Autonomous Agents (MCP)
- [x] **MCP Server Core**: Standardized interface for AI agents (Stdio/SSE).
- [x] **Dynamic Tool Discovery**: Automatic discovery of plugin-provided tools.
- [x] **SEO Analysis Tool**: AI-driven content optimization suggestions.
- [x] **AI Image Generation**: Tool to generate and save assets directly to the CMS.
- [ ] **Real-time Log Stream**: SSE-based resource for streaming logs to a developer assistant.
- [ ] **Role-Specific Prompts**: Pre-configured prompts for SEO, Security, and UI/UX roles.

## 🛡️ Security Enhancements
- [ ] **Two-Factor Authentication (2FA)**: Native support for TOTP (Google Authenticator, Authy).
- [ ] **Rate Limiting**: Built-in middleware to prevent brute-force attacks on API and Login endpoints.
- [ ] **Content Security Policy (CSP)**: Rigorous default headers to prevent XSS.
- [ ] **Audit Trail**: Immutable logging of all administrative and API actions.

## 🧩 Admin Builder (Low-Code)
- [x] **Async Component System**: Architecture for loading Vue components from the database.
- [x] **Refine Component Builder**: Nested structure view, custom components, and toolbox styling.
- [x] **Component Library**: Base set of components (Data Table, Stat Card, Container).
- [x] **Form Builder Integration**: Use created forms within the Component Builder.
- [ ] **Component Exchange**: Share and install components from a community repository.

## 💻 Developer Experience
- [x] **Debug Toolbar**: SQL queries, memory usage, and route info for the current page.
- [ ] **Testing Helpers**: Utilities for PHPUnit and Vue component testing.
- [x] **Scaffolding CLI**: Commands like `make:controller` or `make:plugin`.
- [ ] **Real-time Collaboration**: Shared editing sessions via SSE/WebSockets.

## 🎨 Media & Content
- [x] **Advanced Image Formats**: Native support for AVIF and WebP conversion.
- [x] **Bulk Content Importer**: Tools for importing large datasets (JSON/CSV).
- [x] **Content Versioning**: Historical versions for comparison and rollback.
- [x] **Internationalization (i18n)**: Core support for multi-language routes and content.
- [x] **DAM Features**: Better tagging and organization of the media library.

## 🚀 Performance
- [ ] **Caching Drivers**: Support for Redis or Memcached.
- [x] **Asset Minification**: On-the-fly minification of CSS/JS.

## 📈 SEO & Marketing
- [x] **Robots.txt Management**: Admin control over `robots.txt` content.
- [x] **Site Language**: Configurable global `<html lang>` attribute.
- [x] **XML Sitemap**: Auto-generated sitemap at `/sitemap.xml`.
- [x] **Meta Tags**: Dynamic Title, Description, and Keywords.
- [x] **OpenGraph**: Basic OpenGraph Image support for pages.
- [x] **SEO Score & Analysis**: In-editor analysis of content quality (Implemented via [Analytics Plugin](../plugins/analytics.md)).
- [x] **Canonical URLs**: Automatic or manual canonical tag generation.
- [x] **Structured Data**: Schema.org JSON-LD support for Articles, Products, etc.

## 🧪 Experimental Plugin Proposals
- [ ] **[Remote Installs](../plugins/remote_installs.md)**: Centralized management of distributed Gaia instances.
- [ ] **[Cybersecurity Integration](../plugins/cybersecurity.md)**: Advanced threat monitoring and vulnerability scanning.
- [ ] **Apache Spark Integration**: High-performance data analysis for large-scale CMS data.
- [ ] **Social Sync**: Automated cross-platform content distribution via MCP.
