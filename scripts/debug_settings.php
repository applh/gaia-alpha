<?php
require __DIR__ . '/../class/autoload.php';
require __DIR__ . '/../my-config.php';

use GaiaAlpha\Database;
use GaiaAlpha\Model\DataStore;

// Mock Session
$_SESSION['user_id'] = 1;

try {
    $db = new Database(GAIA_DB_DSN);
    $model = new DataStore($db);

    echo "Testing getAll...\n";
    $settings = $model->getAll(1, 'user_pref');
    echo "Result: " . json_encode($settings) . "\n";

    echo "Testing set...\n";
    $success = $model->set(1, 'user_pref', 'theme', 'light');
    echo "Set Result: " . ($success ? 'true' : 'false') . "\n";

    echo "Testing get...\n";
    $val = $model->get(1, 'user_pref', 'theme');
    echo "Get Result: " . $val . "\n";

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
