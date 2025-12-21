<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\File;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Env;

class PluginController extends BaseController
{
    public function index()
    {
        if (!$this->requireAdmin())
            return;

        $pathData = Env::get('path_data');
        $rootDir = Env::get('root_dir');

        $pluginDirs = [
            $pathData . '/plugins',
            $rootDir . '/plugins'
        ];

        $activePluginsFile = $pathData . '/active_plugins.json';

        $activePlugins = [];
        if (File::exists($activePluginsFile)) {
            $activePlugins = json_decode(File::read($activePluginsFile), true);
        } else {
            // If file doesn't exist, all found plugins are implicitly active
            $allActive = true;
        }

        $plugins = [];
        foreach ($pluginDirs as $pluginsDir) {
            if (File::isDirectory($pluginsDir)) {
                foreach (File::glob($pluginsDir . '/*', GLOB_ONLYDIR) as $dir) {
                    $name = basename($dir);
                    if (File::exists($dir . '/index.php')) {
                        $plugins[] = [
                            'name' => $name,
                            'active' => isset($allActive) ? true : in_array($name, $activePlugins),
                            'is_core' => strpos($dir, $rootDir) === 0
                        ];
                    }
                }
            }
        }

        Response::json($plugins);
    }

    public function togglePlugin()
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();

        $name = $data['name'] ?? null;
        if (!$name) {
            Response::json(['error' => 'Plugin name required'], 400);
            return;
        }

        $pathData = Env::get('path_data');
        $rootDir = Env::get('root_dir');

        $exists = File::isDirectory($pathData . '/plugins/' . $name) || File::isDirectory($rootDir . '/plugins/' . $name);

        if (!$exists) {
            Response::json(['error' => 'Plugin does not exist'], 404);
            return;
        }

        // Note: Full implementation of toggle logic was omitted in AdminController snippet, 
        // but the savePlugins method is the preferred way now.
        Response::json(['error' => 'Use savePlugins for batch updates'], 400);
    }

    public function savePlugins()
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();
        $activePlugins = $data['active_plugins'] ?? [];

        if (!is_array($activePlugins)) {
            Response::json(['error' => 'Invalid input'], 400);
            return;
        }

        $pathData = Env::get('path_data');
        $activePluginsFile = $pathData . '/active_plugins.json';

        File::write($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT));
        Response::json(['success' => true]);
    }

    public function install()
    {
        if (!$this->requireAdmin())
            return;
        $input = Request::input();
        $url = $input['url'] ?? '';
        $isRaw = $input['is_raw'] ?? false;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Response::json(['error' => 'Invalid URL'], 400);
            return;
        }

        $pathData = Env::get('path_data');
        $tmpDir = $pathData . '/cache/tmp';
        if (!File::isDirectory($tmpDir)) {
            File::makeDirectory($tmpDir, 0777, true);
        }

        $tmpFile = $tmpDir . '/plugin_install_' . uniqid() . '.zip';

        if (!$isRaw && strpos($url, 'github.com') !== false && substr($url, -4) !== '.zip') {
            $url = rtrim($url, '/') . '/archive/HEAD.zip';
        }

        $content = @file_get_contents($url);
        if ($content === false) {
            Response::json(['error' => 'Failed to download file from URL.'], 400);
            return;
        }
        File::write($tmpFile, $content);

        $zip = new \ZipArchive;
        if ($zip->open($tmpFile) === TRUE) {
            $extractPath = $tmpDir . '/extract_' . uniqid();
            File::makeDirectory($extractPath);
            $zip->extractTo($extractPath);
            $zip->close();

            $files = scandir($extractPath);
            $pluginRoot = $extractPath;
            $items = array_diff($files, ['.', '..']);

            if (count($items) === 1 && File::isDirectory($extractPath . '/' . reset($items))) {
                $pluginRoot = $extractPath . '/' . reset($items);
            }

            if (!File::exists($pluginRoot . '/index.php')) {
                File::deleteDirectory($extractPath);
                File::delete($tmpFile);
                Response::json(['error' => 'Invalid Plugin: index.php not found in root.'], 400);
                return;
            }

            $repoName = 'installed_plugin_' . uniqid();
            if (preg_match('#github\.com/[^/]+/([^/]+)#', $url, $matches)) {
                $repoName = $matches[1];
            }

            $targetDir = $pathData . '/plugins/' . $repoName;
            if (File::isDirectory($targetDir)) {
                $targetDir .= '_' . uniqid();
            }

            File::move($pluginRoot, $targetDir);

            File::deleteDirectory($extractPath);
            if (File::isDirectory($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            File::delete($tmpFile);

            Response::json(['success' => true, 'dir' => basename($targetDir)]);
        } else {
            File::delete($tmpFile);
            Response::json(['error' => 'Failed to unzip file.'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/admin/plugins', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/admin/plugins/install', [$this, 'install']);
        \GaiaAlpha\Router::add('POST', '/@/admin/plugins/toggle', [$this, 'togglePlugin']);
        \GaiaAlpha\Router::add('POST', '/@/admin/plugins/save', [$this, 'savePlugins']);
    }
}
