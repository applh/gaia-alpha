<?php

namespace Comments;

use GaiaAlpha\Framework;
use GaiaAlpha\Router;
use GaiaAlpha\Hook;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    Framework::registerController('comments', \Comments\CommentsController::class);
});

// Register Frontend Component
\GaiaAlpha\UiManager::registerComponent('comments_section', 'plugins/Comments/resources/js/CommentsSection.js', false);

