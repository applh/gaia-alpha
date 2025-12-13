# Gaia Alpha Roadmap

This document outlines the future plans and upcoming features for the Gaia Alpha framework. Our goal is to maintain simplicity while providing powerful tools for modern web development.

> [!NOTE]
> This roadmap is living and subject to change based on community feedback and project priorities.

## 🔌 Plugin Ecosystem
- **Plugin Marketplace**: A simple directory or JSON-based registry to discover community plugins.
- **UI Management**: An admin panel interface to enable, disable, and configure plugins without touching code.
- **Dependency Management**: Basic resolution to ensure plugins have required `composer` packages or other plugins.

## 🛡️ Security Enhancements
- **Two-Factor Authentication (2FA)**: Native support for TOTP (Google Authenticator, Authy).
- **Rate Limiting**: Built-in middleware to prevent brute-force attacks on API and Login endpoints.
- **Content Security Policy (CSP)**: rigorous default headers to prevent XSS.

## 💻 Developer Experience
- **Debug Toolbar**: A frontend overlay showing SQL queries, memory usage, and route info for the current page.
- **Testing Helpers**: Utilities to make writing PHPUnit and Vue component tests easier.
- **Scaffolding CLI**: Commands like `make:controller` or `make:plugin` to speed up development.

## 🎨 Media & Content
- **Advanced Image Formats**: Native support for AVIF and WebP conversion on upload.
- **Internationalization (i18n)**: Core support for multi-language routes and content.
- **DAM Features**: Better tagging, searching, and organizing of the media library.

## 🚀 Performance
- **Caching Drivers**: Support for Redis or Memcached in addition to the file-based cache.
- [x] **Asset Minification**: On-the-fly minification of CSS/JS for production environments.
