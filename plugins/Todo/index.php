<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Todo\Controller\TodoController;
use Todo\Model\Todo;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('todo', TodoController::class);
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
// ...

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('todos', 'plugins/Todo/TodoList.js', false);

