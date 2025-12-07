<?php
require __DIR__ . '/../class/GaiaAlpha/Database.php';

use GaiaAlpha\Database;

// Mock session/env if needed, but for CLI usually not strictly required if we just access DB
// But Database class might depend on something. Let's check Database.php content previously or just try straight PDO.
// Actually, looking at previous file reads, Database.php is simple.

$dbPath = __DIR__ . '/../my-data/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Connected to database.\n";

$stmt = $pdo->query("SELECT id, title, content, image FROM cms_pages");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;

foreach ($pages as $page) {
    echo "Processing page: {$page['title']} (ID: {$page['id']})\n";

    // If image is already set, skip
    if (!empty($page['image'])) {
        echo " - Image already set: {$page['image']}\n";
        continue;
    }

    // Extract first image
    if (preg_match('/<img[^>]+src=\\\\?"([^\\\\">]+)\\\\?"/', $page['content'], $matches)) {
        $imageUrl = $matches[1];
        // simple unescape if needed, mainly just strips quotes if regex caught them incorrectly but the regex seems okay

        echo " - Found image in content: $imageUrl\n";

        $update = $pdo->prepare("UPDATE cms_pages SET image = ? WHERE id = ?");
        $update->execute([$imageUrl, $page['id']]);
        $updated++;
    } else {
        echo " - No image found in content.\n";
    }
}

echo "Migration complete. Updated $updated pages.\n";
