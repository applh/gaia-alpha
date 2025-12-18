<?php

use GaiaAlpha\Hook;
use DatabaseManager\Controller\DatabaseController;

// Register Routes
$dbController = new DatabaseController();
$dbController->registerRoutes();

// Inject Menu

