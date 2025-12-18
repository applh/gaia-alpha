<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Todo\Controller\TodoController;
use Todo\Model\Todo;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    $controllers = Env::get('controllers');

    $controller = new TodoController();
    if (method_exists($controller, 'init')) {
        $controller->init();
    }

    // Key 'todo' matches the previous key used in Framework::loadControllers for TodoController
    $controllers['todo'] = $controller;

    Env::set('controllers', $controllers);
});

// Inject Stats Card
Hook::add('admin_dashboard_cards', function ($cards) {
    if (class_exists(Todo::class)) {
        $cards[] = [
            'label' => 'Total Todos',
            'value' => Todo::count(),
            'icon' => 'check-square'
        ];
    }
    return $cards;
});

// Inject Menu

