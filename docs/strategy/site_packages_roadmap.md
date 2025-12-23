# Site Packages: Future Roadmap

This document outlines potential enhancements and future features for the Site Packages system in GaiaAlpha. These ideas aim to make the system more robust, scalable, and developer-friendly.

## 1. Smart Dependency Management
**Goal**: Make imports seamless even if plugins or dependencies are missing.
- **Auto-Install Plugins**: If `site.json` lists plugins not currently installed, the importer could attempt to fetch and install them via a marketplace or repository.
- **Version Checking**: Ensure the target GaiaAlpha version is compatible with the exported package version.

## 2. Content & Media Enhancements
**Goal**: Optimize content transfer and storage.
- **Media Optimization**: Automatically resize or compress images during the export process to reduce package size.
- **Incremental Exports**: Add a `--since` flag to export only content changed after a specific date (useful for backups).
- **External Asset Mapping**: Support mapping assets to CDN URLs instead of local files for high-traffic sites.

## 3. Git Integration
**Goal**: Treat the "Site Package" as a first-class citizen in Version Control.
- **Auto-Commit**: A CLI flag to automatically commit changes to a Git repo after an export.
- **Branch-based Staging**: Import a site package into a specific "staging" database based on a Git branch.

## 4. Advanced Theming
**Goal**: Decouple content from design more effectively.
- **Theme Inheritance**: Allow a `site.json` to declare a "Parent Theme". Only export templates that override the parent.
- **Style Variables**: Export CSS variables or SASS/LESS tokens in a standardized format `theme.json` for easier color swapping.

## 5. Security & Environment
**Goal**: Protect sensitive data during transfer.
- **Secret Stripping**: Ensure no API keys or sensitive env vars are accidentally exported in `site.json` or content.
- **Sanitization Hooks**: Allow developers to register hooks to sanitize content (e.g., removing PII) during export.

## 6. Headless & API
**Goal**: Support modern decoupled architectures.
- **JSON API Export**: Option to export the entire site as a static JSON API structure (`/api/pages/home.json`) for use with Static Site Generators (SSG) like Next.js or Gatsby.

## 7. Migration Tools
**Goal**: Ease the transition from other CMSs.
- **WordPress Importer**: A utility to convert a WordPress XML export into a GaiaAlpha Site Package structure (Post -> Markdown, Media -> Assets).
