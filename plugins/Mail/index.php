<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Mail\Controller\MailController;
use Mail\Controller\NewsletterController;

// Register Controllers
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('mail', MailController::class);
    \GaiaAlpha\Framework::registerController('mail_newsletters', NewsletterController::class);
});

// Register UI Components
\GaiaAlpha\UiManager::registerComponent('mail/inbox', 'plugins/Mail/MailPanel.js', true);
\GaiaAlpha\UiManager::registerComponent('mail/newsletters', 'plugins/Mail/resources/js/NewsletterManager.js', true);
\GaiaAlpha\UiManager::registerComponent('mail/newsletter-editor', 'plugins/Mail/resources/js/NewsletterBuilder.js', true);

// Add Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-mail',
            'label' => 'Mail & Newsletters',
            'icon' => 'mail',
            'children' => [
                [
                    'label' => 'Inbox',
                    'view' => 'mail/inbox',
                    'icon' => 'inbox'
                ],
                [
                    'label' => 'Newsletters',
                    'view' => 'mail/newsletters',
                    'icon' => 'send'
                ]
            ]
        ];
    }
    return $data;
});
