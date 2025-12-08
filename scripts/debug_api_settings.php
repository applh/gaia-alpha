<?php
// Mock Session before loading App
session_start();
$_SESSION['user_id'] = 1;

require __DIR__ . '/../class/autoload.php';
require __DIR__ . '/../my-config.php';

use GaiaAlpha\App;
use GaiaAlpha\Controller\SettingsController;
use GaiaAlpha\Database;

echo "DSN: " . GAIA_DB_DSN . "\n";

// Buffering to catch output
ob_start();

try {
    $db = new Database(GAIA_DB_DSN);
    $controller = new SettingsController($db);

    // Simulate Index call
    $controller->index();

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
}

$output = ob_get_clean();

echo "RAW OUTPUT START:\n";
echo $output;
echo "\nRAW OUTPUT END\n";

// Validation
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Valid JSON.\n";
    print_r($json);
} else {
    echo "INVALID JSON: " . json_last_error_msg() . "\n";
}
