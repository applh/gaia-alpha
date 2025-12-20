<?php

namespace JwtAuth\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Model\DataStore;
use GaiaAlpha\Router;

class JwtSettingsController extends BaseController
{
    /**
     * Get current JWT settings
     */
    public function getSettings()
    {
        $this->requireAdmin();

        $settings = DataStore::getAll(0, 'jwt_settings');

        // Ensure defaults
        if (!isset($settings['ttl'])) {
            $settings['ttl'] = 3600;
        }

        Response::json($settings);
    }

    /**
     * Save JWT settings
     */
    public function saveSettings()
    {
        $this->requireAdmin();

        $ttl = Request::input('ttl');
        if ($ttl !== null) {
            DataStore::set(0, 'jwt_settings', 'ttl', (string) $ttl);
        }

        Response::json(['success' => true]);
    }

    /**
     * Refresh the JWT signing secret
     */
    public function refreshSecret()
    {
        $this->requireAdmin();

        $newSecret = bin2hex(random_bytes(32));
        DataStore::set(0, 'jwt_settings', 'secret', $newSecret);

        Response::json(['success' => true, 'secret' => $newSecret]);
    }

    /**
     * Register routes for settings API
     */
    public function registerRoutes()
    {
        Router::add('GET', '/@/admin/jwt/settings', [$this, 'getSettings']);
        Router::add('POST', '/@/admin/jwt/settings', [$this, 'saveSettings']);
        Router::add('POST', '/@/admin/jwt/refresh-secret', [$this, 'refreshSecret']);
    }
}
