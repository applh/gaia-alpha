# Gaia Alpha Roadmap v2.1

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
- [x] **Audit Trail**: Immutable logging of all administrative and API actions. (Implemented via [AuditTrail Plugin](../../plugins/AuditTrail/docs/index.md))

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

## 🔄 WordPress Comparative Matrix (Top 100 Plugins)

Gaia Alpha is designed to provide "out-of-the-box" high-performance equivalents to the most common WordPress plugin categories, reducing maintenance overhead and technical debt.

| WordPress Category | Top 100 WP Plugins | Gaia Alpha Equivalent | Status |
| :----------------- | :----------------- | :-------------------- | :----- |
| **SEO** | Yoast, Rank Math, AIOSEO | `Analytics`, `SEO Tools` | ✅ Core/Active |
| **Page Building** | Elementor, Divi, Beaver Builder | `ComponentBuilder`, `NodeEditor` | ✅ Active |
| **E-commerce** | WooCommerce, EDD | `Ecommerce` Plugin | ✅ Active |
| **LMS** | LearnDash, LifterLMS | `Lms` Plugin | ✅ Active |
| **Security/Audit** | Wordfence, Sucuri, Audit Log | `AuditTrail`, `JwtAuth` | ✅ Active |
| **Forms** | WPForms, Gravity Forms, CF7 | `FormBuilder` Plugin | ✅ Active |
| **Performance** | WP Rocket, LiteSpeed Cache | Minification, Asset Pipeline | ✅ Active |
| **Media Library** | HappyFiles, FileBird | `MediaLibrary`, `FileExplorer` | ✅ Active |
| **Database/Fields** | ACF, Meta Box | Managed Schemas, Low-code Props | ✅ Active |
| **Communication** | WP Mail SMTP, Mailchimp | `Mail` Plugin | ✅ Active |
| **Analytics** | MonsterInsights, Site Kit | `Analytics`, `ApiAnalytics` | ✅ Active |
| **Member/Social** | BuddyPress, MemberPress | `SocialNetworks`, `Comments` | ✅ Active |
| **Backup/Remote** | UpdraftPlus, MainWP | `RemoteInstalls` (Experimental) | 🚧 Planned |
| **Performance/Caching** | Redis Object Cache | Caching Drivers (Redis/Memcached) | 🚧 Planned |

## 🎯 Strategic Advantage over WordPress
- **Lower Boilerplate**: No need to install 30+ plugins to get a functional site.
- **AI-Native**: Deep integration with MCP for autonomous content and code management.
- **Developer First**: Clean PHP/Vue architecture without the legacy "hooks soup".
- **Performance**: Sub-100ms response times by avoiding heavy plugin overhead.

