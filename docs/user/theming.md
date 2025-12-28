# Theming and Templates

Gaia Alpha uses a simple PHP-based templating system. The templates are located in the `templates/` directory.

## Directory Structure

```
templates/
├── header.php          # Global header included in all public pages
├── footer.php          # Global footer included in all public pages
├── public_home.php     # Template for the homepage
├── single_page.php     # Template for standard content pages
├── home_template.php   # An alternative homepage template (example)
└── app.php             # The main application shell (SPA)
```

## Creating a New Template

To create a new template, simply add a `.php` file to the `templates/` directory. You can use the globally available `$page` variable to access page content and settings.

### Include Header and Footer

We recommend including the global header and footer to maintain consistency:

```php
<?php
$title = $page['title'] ?? 'My Page';
require __DIR__ . '/header.php';
?>

<div class="my-custom-content">
    <h1><?= htmlspecialchars($page['title']) ?></h1>
    <?= $page['content'] ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
```

## Header and Footer

The `header.php` and `footer.php` files contain the global layout elements.

- **header.php**: Handles the HTML `<head>`, navigation menu, and opening `<body>` tags.
- **footer.php**: Handles the footer area and closing `</body>` tags.

You can modify these files to change the global look and feel of your site.

## Database Templates

Templates stored in the database (CMS Templates) should also include the header and footer using the `GaiaAlpha\Env` helper to resolve the path, as they are evaluated in a different context.

```php
<?php require \GaiaAlpha\Env::get('path_root') . '/templates/header.php'; ?>

<!-- Content -->

<?php require \GaiaAlpha\Env::get('path_root') . '/templates/footer.php'; ?>
```
