<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Chat\Controller\ChatController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('chat', ChatController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('chat', 'plugins/Chat/ChatPanel.js', false);

// Inject Menu

