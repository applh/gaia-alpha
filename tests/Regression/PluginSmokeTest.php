<?php

namespace GaiaAlpha\Tests\Regression;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;
use GaiaAlpha\Env;

class PluginSmokeTest extends TestCase
{
    /**
     * Iterate through all directory plugins and verify they have a valid plugin.json
     */
    public function testAllPluginsHaveValidManifest()
    {
        $rootDir = Env::get('root_dir');
        $pluginsDir = $rootDir . '/plugins';

        // Get all subdirectories in plugins/
        $dirs = glob($pluginsDir . '/*', GLOB_ONLYDIR);

        Assert::assertTrue(count($dirs) > 0, "No plugins found in $pluginsDir");

        foreach ($dirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            $manifestPath = $pluginDir . '/plugin.json';

            // 1. Verify plugin.json exists
            Assert::assertTrue(
                file_exists($manifestPath),
                "Plugin '$pluginName' is missing plugin.json"
            );

            // 2. Verify JSON is valid
            $content = file_get_contents($manifestPath);
            $json = json_decode($content, true);

            Assert::assertTrue(
                json_last_error() === JSON_ERROR_NONE,
                "Plugin '$pluginName' has invalid JSON in plugin.json: " . json_last_error_msg()
            );

            // 3. Verify basic structure
            Assert::assertTrue(
                isset($json['name']),
                "Plugin '$pluginName' manifest is missing 'name' field"
            );
        }
    }
}
