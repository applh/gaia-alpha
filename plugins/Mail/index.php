<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Mail\Controller\MailController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('mail', MailController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('mail/inbox', 'plugins/Mail/MailPanel.js', true);


