<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use MlPca\Controller\PcaController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('ml_pca', PcaController::class);
});

// 2. Register UI Component dynamically
\GaiaAlpha\UiManager::registerComponent('ml-pca', 'plugins/MlPca/MlPca.js', true);


// 2. Register Menu Item (Using hook for full control over Group Label)
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-tools',
            'label' => 'Tools',
            'icon' => 'pen-tool',
            'children' => [
                [
                    'label' => 'PCA Analysis',
                    'view' => 'ml-pca',
                    'icon' => 'scatter-chart'
                ]
            ]
        ];
    }
    return $data;
});
