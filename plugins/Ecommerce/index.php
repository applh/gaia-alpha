<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Ecommerce\EcommerceController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('ecommerce', EcommerceController::class);
});
