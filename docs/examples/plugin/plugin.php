<?php

use GaiaAlpha\Hook;

/**
 * Example Plugin
 * 
 * This is a sample plugin demonstrating the use of hooks.
 * It adds a custom meta tag to the head and a footer message.
 */

// 1. Hook into the <head> of public pages
Hook::add('public_page_render_head', function ($page) {
    echo '<meta name="generator" content="Gaia Alpha Example Plugin">';
});

// 2. Hook into the <footer> of public pages
Hook::add('public_page_render_footer', function ($page) {
    echo '<div style="text-align:center; padding: 20px; color: #888; border-top: 1px solid #eee; margin-top: 20px;">';
    echo 'Powered by <strong>Gaia Alpha</strong> with Simple Plugin';
    echo '</div>';
});

// 3. Hook into application boot (logging example)
Hook::add('app_boot', function () {
    // This runs on every request (Web & CLI)
    // You could initialize services here.

    // Example Logging
    if (class_exists('GaiaAlpha\Debug')) {
        \GaiaAlpha\Debug::logPlugin('simple_demo', 'Plugin booted successfully', ['sapi' => php_sapi_name()]);
    }

    if (php_sapi_name() === 'cli') {
        // echo "Example Plugin Booted in CLI\n";
    }
});

// 4. Hook into Admin Menu (New!)
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        // Add a new item to the "System" group
        $data['user']['menu_items'][] = [
            'id' => 'grp-system',
            'children' => [
                [
                    'label' => 'Example Plugin', 
                    'view' => 'example-plugin-view', 
                    'icon' => 'star'
                ]
            ]
        ];
    }
    return $data;
});
