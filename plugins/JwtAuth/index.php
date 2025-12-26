<?php

use GaiaAlpha\Router;
use JwtAuth\JwtAuthMiddleware;

// Register JWT Middleware as a global middleware
Router::pushMiddleware(JwtAuthMiddleware::class);