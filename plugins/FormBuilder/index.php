<?php

use GaiaAlpha\Framework;
use GaiaAlpha\UiManager;
use GaiaAlpha\Hook;
use FormBuilder\Controller\FormController;

// Register Controller
Framework::registerController('form_builder', FormController::class);

// Register UI Component
// This maps the 'forms' view to the main admin component of the plugin, effectively replacing the core one
UiManager::registerComponent('forms', 'plugins/FormBuilder/resources/js/FormsAdmin.js', true);

// Add Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        // Add to main menu. Note: If core has a 'forms' menu item, this might duplicate it unless we remove it.
        // But reusing the 'forms' view key is the important part for overwriting the component location.
        $data['user']['menu_items'][] = [
            'label' => 'Form Builder',
            'view' => 'forms', // Matches the registered component
            'icon' => 'layout-list' // Lucide icon
        ];
    }
    return $data;
});
