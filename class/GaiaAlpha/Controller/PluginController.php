<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Filesystem;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Env;

class PluginController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();

        $pathData = Env::get('path_data');
        $rootDir = Env::get('root_dir');

        $pluginDirs = [
            $pathData . '/plugins',
            $rootDir . '/plugins'
        ];

        $activePluginsFile = $pathData . '/active_plugins.json';

        $activePlugins = [];
        if (Filesystem::exists($activePluginsFile)) {
            $activePlugins = json_decode(Filesystem::read($activePluginsFile), true);
        } else {
            // If file doesn't exist, all found plugins are implicitly active
            $allActive = true;
        }

        $plugins = [];
        foreach ($pluginDirs as $pluginsDir) {
            if (Filesystem::isDirectory($pluginsDir)) {
                foreach (Filesystem::glob($pluginsDir . '/*', GLOB_ONLYDIR) as $dir) {
                    $name = basename($dir);
                    if (Filesystem::exists($dir . '/index.php')) {
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
        $this->requireAdmin();
        $data = Request::input();

        $name = $data['name'] ?? null;
        if (!$name) {
            Response::json(['error' => 'Plugin name required'], 400);
            return;
        }

        $pathData = Env::get('path_data');
        $rootDir = Env::get('root_dir');

        $exists = Filesystem::isDirectory($pathData . '/plugins/' . $name) || Filesystem::isDirectory($rootDir . '/plugins/' . $name);

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
        $this->requireAdmin();
        $data = Request::input();
        $activePlugins = $data['active_plugins'] ?? [];

        if (!is_array($activePlugins)) {
            Response::json(['error' => 'Invalid input'], 400);
            return;
        }

        $pathData = Env::get('path_data');
        $activePluginsFile = $pathData . '/active_plugins.json';

        Filesystem::write($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT));
        Response::json(['success' => true]);
    }

    public function install()
    {
        $this->requireAdmin();
        $input = Request::input();
        $url = $input['url'] ?? '';
        $isRaw = $input['is_raw'] ?? false;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Response::json(['error' => 'Invalid URL'], 400);
            return;
        }

        $pathData = Env::get('path_data');
        $tmpDir = $pathData . '/cache/tmp';
        if (!Filesystem::isDirectory($tmpDir)) {
            Filesystem::makeDirectory($tmpDir, 0777, true);
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
        Filesystem::write($tmpFile, $content);

        $zip = new \ZipArchive;
        if ($zip->open($tmpFile) === TRUE) {
            $extractPath = $tmpDir . '/extract_' . uniqid();
            Filesystem::makeDirectory($extractPath);
            $zip->extractTo($extractPath);
            $zip->close();

            $files = scandir($extractPath);
            $pluginRoot = $extractPath;
            $items = array_diff($files, ['.', '..']);

            if (count($items) === 1 && Filesystem::isDirectory($extractPath . '/' . reset($items))) {
                $pluginRoot = $extractPath . '/' . reset($items);
            }

            if (!Filesystem::exists($pluginRoot . '/index.php')) {
                Filesystem::deleteDirectory($extractPath);
                Filesystem::delete($tmpFile);
                Response::json(['error' => 'Invalid Plugin: index.php not found in root.'], 400);
                return;
            }

            $repoName = 'installed_plugin_' . uniqid();
            if (preg_match('#github\.com/[^/]+/([^/]+)#', $url, $matches)) {
                $repoName = $matches[1];
            }

            $targetDir = $pathData . '/plugins/' . $repoName;
            if (Filesystem::isDirectory($targetDir)) {
                $targetDir .= '_' . uniqid();
            }

            Filesystem::move($pluginRoot, $targetDir);

            Filesystem::deleteDirectory($extractPath);
            if (Filesystem::isDirectory($extractPath)) {
                Filesystem::deleteDirectory($extractPath);
            }
            Filesystem::delete($tmpFile);

            Response::json(['success' => true, 'dir' => basename($targetDir)]);
        } else {
            Filesystem::delete($tmpFile);
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
