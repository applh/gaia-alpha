<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use ComponentBuilder\Controller\ComponentBuilderController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('component_builder', ComponentBuilderController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('component-builder', 'plugins/ComponentBuilder/ComponentBuilder.js', true);



