# Import/Export Site Packages

Gaia Alpha provides robust tools to export a site into a portable "Site Package" and import it into a new clean installation. This allows for site backups, template creation, and easy migration between environments (e.g., local to production).

## Exporting a Site

The export command compiles your site's content, structure, and assets into a directory.

### Command

```bash
php cli.php export:site --out=<path/to/directory> [--site=<domain>]
```

*   `--out`: The directory where the site package will be saved.
*   `--site`: (Optional) The domain of the site to export. Defaults to the current active site or default site.

### Example

```bash
php cli.php export:site --out=my-data/exports/mysite-backup
```

### What is Exported?

The export process includes:

*   **Pages**: All CMS pages (`my-data/pages/*.md`).
*   **Templates**: All custom templates (`my-data/templates/*.php`).
*   **Forms**: All form definitions (`my-data/forms/*.json`).
*   **Menus**: All navigation menus (`menus.json`).
*   **Custom Components**: React/JS components (`my-data/components/custom/*.js`).
*   **Assets**: Public assets (`www/assets/`).
*   **Media**: Uploaded files referenced in content.
*   **Manifest**: A `site.json` file containing metadata and plugin dependencies.

> [!NOTE]
> **User Accounts** are NOT exported for security reasons. A default admin account is created during import.
> **Global Settings** (like site title, debug mode) are currently not part of the export package.

---

## Importing a Site

The import process creates a new site environment and populates it with the content from a Site Package.

### Command

You typically use the `site:create` command with the `--import` flag.

```bash
php cli.php site:create <new-domain> --import=<path/to/package>
```

### Example

```bash
php cli.php site:create new-client.test --import=docs/examples/enterprise_site
```

### What happens during Import?

1.  **Site Creation**: A new database is created (`my-data/sites/new-client.test.sqlite`).
2.  **Schema Init**: The core database tables are created.
3.  **Admin User**: A default admin user (`admin` / `admin`) is created.
4.  **Content Import**:
    *   Pages are created.
    *   Templates are saved.
    *   Forms and Menus are inserted into the database.
    *   Assets and Components are copied to their respective directories.
    *   Media files are copied to the new site's upload directory.

---

## Site Package Structure

A valid Site Package is simply a directory with the following structure:

```text
site-package/
├── site.json           # Manifest file (metadata, plugins)
├── menus.json          # Navigation menus export
├── pages/              # Content pages (.md files)
├── templates/          # PHP templates (.php files)
├── forms/              # Form schemas (.json)
├── components/         # Custom UI components (.js)
├── assets/             # CSS, JS, Images (public folder)
└── media/              # Uploaded media files
```

### Creating a Package Manually

You can manually create a Site Package to use as a starter template. ensuring you verify the `site.json` structure:

```json
{
    "name": "My Template",
    "version": "1.0.0",
    "plugins": ["CMS", "Forms"],
    "config": {
        "theme": "default"
    }
}
```
