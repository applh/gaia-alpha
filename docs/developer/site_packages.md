# Site Packages Technical Spec

A **Site Package** is a standard directory structure used by GaiaAlpha to transport website content and configuration.

## Directory Structure

```
/
  assets/             # Public assets (css, js, images) -> mapped to www/assets
  components/         # Custom JS components -> mapped to my-data/components/custom
  forms/              # JSON definitions of forms
  media/              # Media files referenced by content
  pages/              # Markdown content files
  templates/          # PHP template files (stored in DB)
  site.json           # Manifest file
```

## `site.json` Format

The `site.json` file contains metadata and plugin dependencies.

```json
{
    "name": "Exported Site",
    "exported_at": "2024-12-18T23:00:00+00:00",
    "generator": "GaiaAlpha",
    "version": "1.0.0",
    "plugins": [
        "contact-form",
        "seo-pack"
    ],
    "config": {
        "theme": "default"
    }
}
```

The importer checks the `plugins` list against the active plugins in `my-data/active_plugins.json` and issues a warning if requirements are missing.

## Content Format

### Pages (Markdown)
Pages are stored as Markdown files with YAML Front Matter.
Links to media files are rewritten to relative paths (`./media/filename.jpg`) to ensure portability.

**SEO Support**
GaiaAlpha automatically maps standard Front Matter fields to database columns for strong SEO.

```markdown
---
title: "About Us"
slug: "about"
template_slug: "page"
meta_description: "Learn about our company history and mission."
meta_keywords: "about, company, history, mission"
image: "./media/team-photo.jpg"
---

Hello world! ![Image](./media/photo.jpg)
```

**Template Integration**
Your PHP templates should render these fields as meta tags:

```php
<title><?php echo $page['title']; ?></title>
<meta name="description" content="<?php echo $page['meta_description']; ?>">
<meta property="og:image" content="<?php echo $page['image']; ?>">
```

### Forms (JSON)
Forms are exported as JSON files containing the title, slug, properties, and the Form.io compatible schema.

## Extension

To extend the export/import logic, developers can modify `class/GaiaAlpha/ImportExport/WebsiteExporter.php` and `WebsiteImporter.php`.
