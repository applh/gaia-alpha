<?php
require_once __DIR__ . '/../../class/GaiaAlpha/App.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Env.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Hook.php';

\GaiaAlpha\Env::set('autoloaders', [
    [\GaiaAlpha\App::class, 'autoloadFramework'],
    [\GaiaAlpha\App::class, 'autoloadPlugins'],
    [\GaiaAlpha\App::class, 'autoloadAliases']
]);
\GaiaAlpha\App::registerAutoloaders();
\GaiaAlpha\App::web_setup(__DIR__ . '/../..');

use GaiaAlpha\Model\DB;

try {
    echo "Starting Multi-DB Backup/Restore Verification...\n";

    // 1. Setup Test Table
    echo "Step 1: Creating test table...\n";
    DB::execute("DROP TABLE IF EXISTS test_backup");
    DB::execute("CREATE TABLE test_backup (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        val INTEGER,
        created_at DATETIME
    )");

    DB::execute(
        "INSERT INTO test_backup (name, val, created_at) VALUES (?, ?, ?)",
        ['Test Record 1', 100, date('Y-m-d H:i:s')]
    );
    DB::execute(
        "INSERT INTO test_backup (name, val, created_at) VALUES (?, ?, ?)",
        ['Test Record 2', 200, date('Y-m-d H:i:s')]
    );

    $countBefore = DB::fetchColumn("SELECT COUNT(*) FROM test_backup");
    echo "Count before backup: $countBefore\n";

    // 2. Perform Backup
    echo "Step 2: Performing backup...\n";
    $backupFile = __DIR__ . '/test_db_backup.sql';
    $db = DB::connect();
    $db->dump($backupFile);

    if (file_exists($backupFile)) {
        echo "SUCCESS: Backup file created at $backupFile\n";
    } else {
        throw new Exception("FAILURE: Backup file not created.");
    }

    // 3. Destructive Action
    echo "Step 3: Dropping test table...\n";
    DB::execute("DROP TABLE test_backup");

    // 4. Restore
    echo "Step 4: Restoring from backup...\n";
    $db->import($backupFile);

    // 5. Verify
    echo "Step 5: Verifying restored data...\n";
    $countAfter = DB::fetchColumn("SELECT COUNT(*) FROM test_backup");
    echo "Count after restore: $countAfter\n";

    if ($countBefore === $countAfter) {
        $record = DB::fetch("SELECT * FROM test_backup WHERE name = ?", ['Test Record 1']);
        if ($record && $record['val'] == 100) {
            echo "SUCCESS: Data verified successfully.\n";
        } else {
            throw new Exception("FAILURE: Data content mismatch.");
        }
    } else {
        throw new Exception("FAILURE: Record count mismatch.");
    }

    // Cleanup
    echo "Cleaning up...\n";
    DB::execute("DROP TABLE test_backup");
    unlink($backupFile);

    echo "ALL TESTS PASSED!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
