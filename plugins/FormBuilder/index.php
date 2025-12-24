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
