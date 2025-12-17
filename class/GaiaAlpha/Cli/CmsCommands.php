<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class CmsCommands
{
    public static function handleCreatePage(): void
    {
        if (Input::count() < 2) {
            Output::writeln("Usage: cms:create-page <slug> <template> [title]");
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
            Output::info("Page '{$slug}' already exists. Updating template and title...");
            $data = [
                'template_slug' => $template,
                'title' => $title
            ];
            Page::update($existing['id'], $existing['user_id'], $data);
            Output::success("Page '{$slug}' updated.");
        } else {
            Output::info("Creating new page '{$slug}'...");
            $data = [
                'slug' => $slug,
                'template_slug' => $template,
                'title' => $title,
                'content' => '', // Default empty content
                'cat' => 'page'
            ];
            // Assign to admin (ID 1) by default for CLI actions
            Page::create(1, $data);
            Output::success("Page '{$slug}' created.");
        }
    }
}
