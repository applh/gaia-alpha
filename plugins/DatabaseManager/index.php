<?php

use GaiaAlpha\Hook;
use DatabaseManager\Controller\DatabaseController;

// Register Routes
$dbController = new DatabaseController();
$dbController->registerRoutes();

$dbController->registerRoutes();

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('database', 'plugins/DatabaseManager/DatabaseManager.js', true);

// Inject Menu

