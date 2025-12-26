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

            include $templatePath;
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
