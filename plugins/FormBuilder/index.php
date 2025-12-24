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
// Menu Item is now registered via plugin.json

// Register Dashboard Widget
Hook::add('dashboard_widgets', function ($widgets) {
    // Check if user has permission if needed (usually handled by dashboard rendering, but good practice)
    $widgets[] = [
        'name' => 'FormWidget',
        'path' => 'plugins/FormBuilder/resources/js/FormWidget.js',
        'width' => 'full' // or 'half', 'third'
    ];
    return $widgets;
});
