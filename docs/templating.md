# Templating System

Gaia Alpha provides a powerful hybrid templating system that supports both **Visual (No-Code)** and **Developer (Code)** modes for creating page templates.

## Overview

Templates are reusable layouts that wrap your page content. They can be stored as:
- **File-based templates**: PHP files in `/templates/` (read-only in UI)
- **Database templates**: Stored in `cms_templates` table (fully editable)

## Template Modes

### Visual Builder (No-Code)

The Visual Builder allows non-developers to create templates using a drag-and-drop interface:

- **Drag components** from the toolbox (Headers, Columns, Images, etc.)
- **Organize layout** into Header, Main, and Footer sections
- **Preview** the structure in real-time
- **Auto-compilation**: JSON structure is compiled to PHP on save

**Example JSON Structure:**
```json
{
  "header": [
    {"type": "h1", "content": "Welcome"}
  ],
  "main": [
    {"type": "columns", "children": [
      {"type": "column", "children": [{"type": "p", "content": "Left column"}]},
      {"type": "column", "children": [{"type": "p", "content": "Right column"}]}
    ]}
  ],
  "footer": [
    {"type": "p", "content": "© 2025"}
  ]
}
```

### Code Editor (Developer)

The Code Editor provides full PHP control with syntax highlighting:

- **Ace Editor** with PHP syntax highlighting
- **File Explorer** sidebar for managing partials
- **Developer Tips** panel with quick reference
- **Auto-aliasing**: Use `Part`, `Page`, `User` without namespaces

**Example PHP Template:**
```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $page['title'] ?></title>
    <link rel="stylesheet" href="/resources/css/site.css">
</head>
<body>
    <?php Part::in('header'); ?>
    
    <main>
        <?= $page['content'] ?>
    </main>
    
    <?php Part::in('footer'); ?>
</body>
</html>
```

## Partials System

Partials are reusable PHP code snippets stored in the `cms_partials` table.

### Creating Partials

1. Open the **Templates** tab in CMS
2. Switch to **Code Editor** mode
3. Click **+** in the Partials sidebar
4. Enter a name (e.g., `header_v2`)
5. Write your PHP code
6. Save

### Using Partials

```php
<?php Part::in('header_v2'); ?>
```

Or with data:
```php
<?php Part::in('navigation', ['active' => 'home']); ?>
```

### Caching

Partials are cached to `/my-data/cache/partials/{name}.php` for performance.

## Available Variables

Templates have access to the `$page` array:

```php
$page['id']           // Page ID
$page['title']        // Page title
$page['slug']         // URL slug
$page['content']      // Main content
$page['image']        // Featured image URL
$page['template_slug'] // Template being used
$page['created_at']   // Creation timestamp
$page['updated_at']   // Last update timestamp
```

## Helper Classes

Thanks to automatic aliasing, you can use these classes without namespaces:

### Part
```php
Part::in('name')           // Include a partial
Part::load('name', $data)  // Include with data
```

### Page
```php
Page::find($id)                    // Find by ID
Page::findBySlug($slug)            // Find by slug
Page::findAllByUserId($userId)     // Get user's pages
```

### User
```php
User::find($id)              // Find user by ID
User::findByUsername($name)  // Find by username
```

## Template Management

### Via CMS Interface

1. Navigate to **Admin → CMS**
2. Click **Templates** tab
3. Choose mode:
   - **Visual Builder**: Drag-and-drop interface
   - **Code Editor**: Full PHP control
4. Create/Edit templates
5. Associate with pages

### Via CLI

```bash
# Export database (includes templates)
php cli.php db:export backup.sql

# Import templates
php cli.php db:import backup.sql
```

## Best Practices

1. **Use Partials** for repeated elements (headers, footers, navigation)
2. **Cache Awareness**: Template changes require cache clearing for DB templates
3. **Security**: Only trusted admins should create PHP templates (RCE risk)
4. **Naming**: Use descriptive names for partials (e.g., `blog_header` not `h1`)
5. **Testing**: Always preview templates before associating with live pages

## Performance

- **File templates**: Fastest (direct `require`)
- **DB templates**: Cached to filesystem on first load
- **Visual templates**: Compiled to PHP once, then cached
- **Partials**: Cached on first use

## Migration from Old System

If you have existing templates, they remain as file-based templates and appear as read-only in the UI. To make them editable:

1. Copy the template content
2. Create a new DB template with the same slug
3. The DB template will override the file template

## Security Considerations

⚠️ **Warning**: PHP templates allow arbitrary code execution. This is a powerful feature but carries inherent risks:

- Only grant template editing to **trusted administrators**
- DB templates are executed via `require` after caching
- Consider code review for production templates
- Monitor `/my-data/cache/templates/` and `/my-data/cache/partials/`

## Troubleshooting

### Template not updating
- Clear cache: `rm -rf my-data/cache/templates/*`
- Check `updated_at` timestamp in database

### Partial not found
- Verify partial exists in `cms_partials` table
- Check spelling in `Part::in('name')`
- Clear partial cache: `rm -rf my-data/cache/partials/*`

### Syntax errors
- Use Developer Tips panel for reference
- Check PHP error logs
- Test partials independently before including

## API Reference

### Template CRUD
```
GET    /@/cms/templates       # List all templates
GET    /@/cms/templates/:id   # Get single template
POST   /@/cms/templates       # Create template
PATCH  /@/cms/templates/:id   # Update template
DELETE /@/cms/templates/:id   # Delete template
```

### Partial CRUD
```
GET    /@/cms/partials        # List all partials
POST   /@/cms/partials        # Create partial
PATCH  /@/cms/partials/:id    # Update partial
DELETE /@/cms/partials/:id    # Delete partial
```
