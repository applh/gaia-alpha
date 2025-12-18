# Site Import & Export

GaiaAlpha provides powerful tools to export your entire website into a portable **Site Package** and import it back relative to any domain. This feature is ideal for backups, migrations, and sharing "Starter Kits" with the community.

## Exporting a Site

To export your current site, use the `export:site` command.

```bash
php cli.php export:site --out=./my-export
```

By default, this exports the currently active site (or the default site). To export a specific site in a multi-site setup:

```bash
php cli.php export:site --out=./my-export --site=example.com
```

The exported folder will contain all your pages, media, forms, templates, custom components, and assets.

## Importing a Site

To import a Site Package, use the `import:site` command.

```bash
php cli.php import:site --in=./my-export
```

**Note**: This will merge content into your current site database. If pages with the same slug exist, they will be updated. New pages will be created.

To import into a specific site:

```bash
php cli.php import:site --in=./my-export --site=new-domain.com
```

## What is included?

- **Pages**: All your content pages (Markdown).
- **Media**: Images referenced in your content.
- **Forms**: Form definitions and schemas.
- **Templates**: Database-stored templates.
- **Components**: Custom JavaScript components (`my-data/components/custom`).
- **Assets**: Global design assets (`www/assets`).
- **Site Manifest**: A `site.json` file describing dependencies.

## Community Sharing

You can zip the exported folder and share it as a "Starter Kit". Other users can unzip and import it to bootstrap their websites instantly with your design and structure.

> [!TIP]
> Check out a complete [Enterprise Website Example](example_enterprise_site.md) to see how to structure a large site package.
