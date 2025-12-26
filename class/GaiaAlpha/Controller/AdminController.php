<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Response;

class AdminController extends BaseController
{
    public function stats()
    {
        if (!$this->requireAdmin())
            return;

        // Base Cards
        $cards = [
            [
                'label' => 'Total Users',
                'value' => User::count(),
                'icon' => 'users'
            ],
            [
                'label' => 'Total Pages',
                'value' => Page::count('page'),
                'icon' => 'file-text'
            ],
            [
                'label' => 'Total Templates',
                'value' => \GaiaAlpha\Model\DB::query("SELECT COUNT(*) FROM cms_templates")->fetchColumn(),
                'icon' => 'layout-template'
            ],
            [
                'label' => 'Total Images',
                'value' => Page::count('image'),
                'icon' => 'image'
            ],
            [
                'label' => 'Datastore',
                'value' => \GaiaAlpha\Model\DB::query("SELECT COUNT(*) FROM data_store")->fetchColumn(),
                'icon' => 'database'
            ]
        ];

        // Allow plugins to inject cards
        // Usage: Hook::add('admin_dashboard_cards', function($cards) { $cards[] = ['label' => '...', 'value' => ..., 'icon' => '...']; return $cards; });
        $cards = \GaiaAlpha\Hook::filter('admin_dashboard_cards', $cards);

        // Load Active Plugins to check for FormBuilder
        $activePlugins = [];
        $activePluginsFile = \GaiaAlpha\Env::get('path_data') . '/active_plugins.json';
        if (file_exists($activePluginsFile)) {
            $activePlugins = json_decode(file_get_contents($activePluginsFile), true) ?: [];
        }

        if (in_array('FormBuilder', $activePlugins)) {
            $cards[] = [
                'label' => 'Total Forms',
                'value' => \GaiaAlpha\Model\DB::query("SELECT COUNT(*) FROM forms")->fetchColumn(),
                'icon' => 'clipboard-list'
            ];
            $cards[] = [
                'label' => 'Form Submissions',
                'value' => \GaiaAlpha\Model\DB::query("SELECT COUNT(*) FROM form_submissions")->fetchColumn(),
                'icon' => 'inbox'
            ];
        }

        Response::json(['cards' => $cards]);
    }

    public function dashboard()
    {
        // Reuse ViewController's logic for app.php or similar
        // Since we don't have a shared render, we'll do a simple include for now
        // assuming app.php is the SPA entry.

        $rootDir = \GaiaAlpha\Env::get('root_dir');
        $templatePath = $rootDir . '/templates/app.php';

        if (file_exists($templatePath)) {
            // Need to pass variables if app.php expects them? 
            // ViewController passes nothing to app.php usually or empty array.
            // Let's check ViewController::app() again. It calls render('app.php').
            // render extracts vars.

            // Fetch Global Settings
            $globalSettings = \GaiaAlpha\Model\DataStore::getAll(0, 'global_config');

            // Inject Dynamic UI Components
            $globalSettings['ui_components'] = \GaiaAlpha\UiManager::getComponents();
            $globalSettings['ui_styles'] = \GaiaAlpha\UiManager::getStyles();

            // Inject Active Plugins
            $globalSettings['root_dir'] = $rootDir;
            $pathData = \GaiaAlpha\Env::get('path_data');
            if (file_exists($pathData . '/active_plugins.json')) {
                $globalSettings['active_plugins'] = json_decode(file_get_contents($pathData . '/active_plugins.json'), true);
            } else {
                $globalSettings['active_plugins'] = [];
            }

            // Extract for template
            extract(['globalSettings' => $globalSettings]);

            ob_start();

            include $templatePath;
            $content = ob_get_clean();

            // Inject Debug Toolbar
            if (\GaiaAlpha\Session::isAdmin()) {
                if (strpos($content, '</html>') !== false) {
                    $vueUrl = \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js');
                    $timestamp = time();
                    $componentUrl = \GaiaAlpha\Asset::url('/js/components/admin/DebugToolbar.js');
                    // Use specific timestamp to avoid caching issues if needed, or rely on Asset::url logic
                    // Asset::url usually appends v=...

                    // Note: ViewController sends a placeholder. PublicController sends real data.
                    // AdminController likely runs after appBoot, so Debug data is collecting.
                    // If we are just rendering the app shell, the JS app will make requests.
                    // But the initial request also has SQL queries (e.g. stats).
                    // So we should inject current debug data to be useful.
                    $debugData = \GaiaAlpha\Debug::getData();
                    $jsonData = json_encode($debugData);

                    $toolbarScript = <<<HTML
<div id="gaia-debug-root" style="position:fixed;bottom:0;left:0;right:0;z-index:99999;"></div>
<script>
    window.GAIA_DEBUG_DATA = $jsonData;
</script>
<script type="module">
    import { createApp } from '$vueUrl';
    import * as Vue from '$vueUrl';
    import DebugToolbar from '$componentUrl';
    
    window.Vue = Vue;
    
    const app = createApp(DebugToolbar);
    app.mount('#gaia-debug-root');
</script>
HTML;
                    $content = str_replace('</body>', $toolbarScript . '</body>', $content);
                }
            }

            echo $content;
        } else {
            echo "Admin Template not found";
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', \GaiaAlpha\Router::adminPrefix(), [$this, 'dashboard']);
        \GaiaAlpha\Router::add('GET', \GaiaAlpha\Router::adminPrefix() . '/', [$this, 'dashboard']);
        \GaiaAlpha\Router::add('GET', \GaiaAlpha\Router::adminPrefix() . '/stats', [$this, 'stats']);
    }
}
