<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Cli\Input;

class CmsCommands
{
    public static function handleCreatePage(): void
    {
        if (Input::count() < 2) {
            echo "Usage: cms:create-page <slug> <template> [title]\n";
            exit(1);
        }

        $slug = Input::get(0);
        $template = Input::get(1);
        $title = Input::get(2, ucfirst($slug));

        // Ensure database connection
        \GaiaAlpha\Model\DB::connect();

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
