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

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/admin/stats', [$this, 'stats']);
    }
}
