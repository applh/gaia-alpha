<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use MultiSite\SiteController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('site', SiteController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('sites', 'plugins/MultiSite/MultiSitePanel.js', true);


