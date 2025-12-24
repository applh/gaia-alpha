<?php

namespace Comments;

use GaiaAlpha\Framework;
use GaiaAlpha\Router;

// Register API Routes
Router::add('GET', '/api/comments', 'Comments\CommentsController::index');
Router::add('POST', '/api/comments', 'Comments\CommentsController::store');
Router::add('PUT', '/api/comments/{id}', 'Comments\CommentsController::update');
Router::add('DELETE', '/api/comments/{id}', 'Comments\CommentsController::delete');

// Register Frontend Component
\GaiaAlpha\UiManager::registerComponent('comments_section', 'plugins/Comments/resources/js/CommentsSection.js', false);

