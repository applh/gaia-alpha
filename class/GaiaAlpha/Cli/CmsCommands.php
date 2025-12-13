<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Controller\DbController;

class CmsCommands
{
    public static function handleCreatePage(): void
    {
        global $argv;
        if (count($argv) < 4) {
            echo "Usage: cms:create-page <slug> <template> [title]\n";
            exit(1);
        }

        $slug = $argv[2];
        $template = $argv[3];
        $title = $argv[4] ?? ucfirst($slug);

        // Ensure database connection
        DbController::connect();

        // Check if page exists
        $existing = Page::findBySlug($slug);

        if ($existing) {
            echo "Page '{$slug}' already exists. Updating template and title...\n";
            $data = [
                'template_slug' => $template,
                'title' => $title
            ];
            Page::update($existing['id'], $existing['user_id'], $data);
            echo "Page '{$slug}' updated.\n";
        } else {
            echo "Creating new page '{$slug}'...\n";
            $data = [
                'slug' => $slug,
                'template_slug' => $template,
                'title' => $title,
                'content' => '', // Default empty content
                'cat' => 'page'
            ];
            // Assign to admin (ID 1) by default for CLI actions
            Page::create(1, $data);
            echo "Page '{$slug}' created.\n";
        }
    }
}
