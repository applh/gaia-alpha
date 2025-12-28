<?php

require_once __DIR__ . '/../../class/GaiaAlpha/Env.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Debug.php';
require_once __DIR__ . '/../../class/GaiaAlpha/File.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Request.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Hook.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Session.php';
require_once __DIR__ . '/../../class/GaiaAlpha/UiManager.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Framework.php';

use GaiaAlpha\Framework;
use GaiaAlpha\Env;
use GaiaAlpha\Hook;
use GaiaAlpha\File;

Env::set('root_dir', dirname(__DIR__, 2));
// Use a temp data dir for testing to avoid messaging with real data, OR just use real data carefully.
// Let's use real data path but different cache file? No, framework hardcodes filename.
// Used real path so we can verify against real plugins.
Env::set('path_data', dirname(__DIR__, 2) . '/my-data');

// Mock request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/@/admin/dashboard';
// Set request context related things if needed (admin prefix logic)
Env::set('admin_prefixes', ['/@/admin']);

// Ensure we start fresh
$manifestFile = 'my-data/cache/plugins_manifest.json';
if (file_exists($manifestFile)) {
    unlink($manifestFile);
}

// 1. First Load (Generation)
echo "1. Generating Manifest...\n";
Framework::loadPlugins();

$manifest = json_decode(file_get_contents($manifestFile), true);
if (!$manifest) {
    echo "ERROR: Manifest not generated.\n";
    exit(1);
}

// Check if 'menu' key exists in manifest for McpServer plugin
$foundMcp = false;
$hasMenu = false;
foreach ($manifest as $plugin) {
    if (strpos($plugin['path'], 'McpServer') !== false) {
        $foundMcp = true;
        $hasMenu = isset($plugin['menu']) && !empty($plugin['menu']['items']);
        break;
    }
}

if (!$foundMcp) {
    echo "ERROR: McpServer plugin not found in manifest.\n";
    exit(1);
}

if (!$hasMenu) {
    echo "ERROR: Menu config NOT saved in manifest for McpServer plugin.\n";
    exit(1);
}
echo "SUCCESS: Menu config saved in manifest for McpServer.\n";

// 2. Clear hooks to simulate new request
// Hook::reset(); // Assuming we had a reset method, but we don't.
// However, Framework::loadPlugins doesn't double-load files if we don't clear default hook array.
// But we can check if the hook count strictly increases or if we can inspect hooks.
// Since we can't easily reset static properties of Hook class without a method, 
// we'll rely on inspecting the manifest content which we did above.
// To verify the loading part, we can't easily do it in the same process unless we clear included files (impossible) 
// or reset Hook state.

// Actually, we can check if the hook IS registered now (since we just ran loadPlugins generation path).
// But the real test is the cached path.
// Let's rely on the manifest inspection for now, and manual verification for the actual effect.
// Or we can try to "re-run" loadPlugins?
// Since `require_once` prevents re-inclusion, re-running loadPlugins will skip inclusion.
// But it SHOULD re-run menu registration if our logic is correct (wait, no).
// In cached path:
/*
    if (file_exists($pluginPath)) {
        include_once $pluginPath;
    }
*/
// Menu registration is separate.
// If we re-run `loadPlugins`, and we are in cached mode (manifest exists).
// It iterates manifest.
// It calls `registerPluginMenuItems`.
// It calls `include_once`.
// So hooks should duplicate?
// If hooks duplicate, it proves it ran.

// Helper to get hooks via reflection
function getHooks()
{
    $ref = new ReflectionClass('GaiaAlpha\Hook');
    $prop = $ref->getProperty('hooks');
    $prop->setAccessible(true);
    return $prop->getValue();
}

$hooks = getHooks();
$startHooks = count($hooks['auth_session_data'] ?? []);
echo "Hooks before cached load: $startHooks\n";

Framework::loadPlugins();

$hooks = getHooks();
$endHooks = count($hooks['auth_session_data'] ?? []);
echo "Hooks after cached load: $endHooks\n";

if ($endHooks > $startHooks) {
    echo "SUCCESS: Plugin menus registered during cached load (hooks increased).\n";
} else {
    echo "ERROR: Plugin menus NOT registered during cached load (hooks did not increase).\n";
    // This might fail if the hook system prevents duplicates or if my assumption about duplication is wrong.
    // Hook::add just appends.
}
