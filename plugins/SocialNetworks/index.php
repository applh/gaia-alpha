<?php

use GaiaAlpha\Hook;
use SocialNetworks\Controller\SocialNetworksController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('social-networks', SocialNetworksController::class);
});

// 2. Register UI Component
\GaiaAlpha\UiManager::registerComponent('social-networks', 'plugins/SocialNetworks/resources/js/SocialNetworks.js', true);

// 3. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-social',
            'label' => 'Social Networks',
            'icon' => 'share-2',
            'children' => [
                [
                    'label' => 'Dashboard',
                    'view' => 'social-networks',
                    'icon' => 'layout'
                ]
            ]
        ];
    }
    return $data;
});
