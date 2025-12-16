<?php

namespace GaiaAlpha;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Todo;
use GaiaAlpha\Model\DB;
use GaiaAlpha\Env;

class Seeder
{
    public static function run(int $userId)
    {
        // echo "Starting seeder for user $userId...\n";
        $seedDir = Env::get('root_dir') . '/templates/seed';

        // echo "1. Seeding Todos...\n";
        $todosFile = $seedDir . '/todos.json';
        if (file_exists($todosFile)) {
            $todos = json_decode(file_get_contents($todosFile), true);
            $getDate = fn($d) => $d ? date('Y-m-d', strtotime($d)) : null;

            foreach ($todos as $todo) {
                $start = $getDate($todo['start_date'] ?? null);
                $end = $getDate($todo['end_date'] ?? null);

                // If it has children vs flat
                if (isset($todo['children'])) {
                    $parentId = Todo::create($userId, $todo['title'], null, $todo['labels'] ?? null, $start, $end, $todo['color'] ?? null);
                    foreach ($todo['children'] as $child) {
                        $cStart = $getDate($child['start_date'] ?? null);
                        $cEnd = $getDate($child['end_date'] ?? null);
                        Todo::create($userId, $child['title'], $parentId, $child['labels'] ?? null, $cStart, $cEnd, $child['color'] ?? null);
                    }
                } else {
                    Todo::create($userId, $todo['title'], null, $todo['labels'] ?? null, $start, $end, $todo['color'] ?? null);
                }
            }
        }

        // echo "2. Seeding Partials...\n";
        // Copy actual header/footer files as partials
        $rootDir = Env::get('root_dir');
        $headerContent = file_get_contents($rootDir . '/templates/layout/header.php');
        $footerContent = file_get_contents($rootDir . '/templates/layout/footer.php');

        $partialsData = [
            [
                'name' => 'site_header',
                'content' => $headerContent
            ],
            [
                'name' => 'site_footer',
                'content' => $footerContent
            ]
        ];

        // Clear existing partials for this user
        DB::execute("DELETE FROM cms_partials WHERE user_id = ?", [$userId]);

        $sql = "INSERT INTO cms_partials (user_id, name, content) VALUES (?, ?, ?)";
        foreach ($partialsData as $partial) {
            DB::execute($sql, [$userId, $partial['name'], $partial['content']]);
        }

        // echo "3. Seeding Pages...\n";
        $pagesDir = $seedDir . '/pages';
        if (is_dir($pagesDir)) {
            foreach (glob($pagesDir . '/*.json') as $pageFile) {
                $pageData = json_decode(file_get_contents($pageFile), true);
                Page::create($userId, $pageData);
            }
        }

        // 3. Menus
        $menusFile = $seedDir . '/menus.json';
        if (file_exists($menusFile)) {
            $menus = json_decode(file_get_contents($menusFile), true);
            foreach ($menus as $menu) {
                DB::execute(
                    "INSERT INTO menus (title, location, items, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$menu['title'], $menu['location'], json_encode($menu['items'])]
                );
            }
        }

        // 4. Forms & Submissions
        $formsFile = $seedDir . '/forms.json';
        if (file_exists($formsFile)) {
            $forms = json_decode(file_get_contents($formsFile), true);
            foreach ($forms as $form) {
                $sql = "INSERT INTO forms (user_id, title, slug, description, schema, submit_label) VALUES (?, ?, ?, ?, ?, ?)";
                DB::execute($sql, [
                    $userId,
                    $form['title'],
                    $form['slug'],
                    $form['description'],
                    json_encode($form['schema']),
                    $form['submit_label'] ?? 'Submit'
                ]);

                if (!empty($form['submissions'])) {
                    $formId = DB::lastInsertId();
                    foreach ($form['submissions'] as $sub) {
                        DB::execute(
                            "INSERT INTO form_submissions (form_id, data, ip_address, user_agent) VALUES (?, ?, ?, ?)",
                            [$formId, json_encode($sub), '127.0.0.1', 'Mozilla/5.0 (Demo Agent)']
                        );
                    }
                }
            }
        }

        // 6. Map Markers
        $markersFile = $seedDir . '/markers.json';
        if (file_exists($markersFile)) {
            $markers = json_decode(file_get_contents($markersFile), true);
            $sql = "INSERT INTO map_markers (user_id, label, lat, lng) VALUES (?, ?, ?, ?)";
            foreach ($markers as $m) {
                DB::execute($sql, [$userId, $m['label'], $m['lat'], $m['lng']]);
            }
        }

        // echo "7. Seeding Templates...\n";
        // Copy the home_template content (between header and footer)
        $homeTemplateContent = file_get_contents($seedDir . '/default_template_fallback.php');

        $defaultTemplate = [
            'title' => 'Default Site Template',
            'slug' => 'default_site',
            'content' => $homeTemplateContent
        ];

        $sql = "INSERT INTO cms_templates (user_id, title, slug, content) VALUES (?, ?, ?, ?)";
        DB::execute($sql, [$userId, $defaultTemplate['title'], $defaultTemplate['slug'], $defaultTemplate['content']]);

        // Update existing pages to use the new template ONLY if they don't have one
        DB::execute("UPDATE cms_pages SET template_slug = 'default_site' WHERE user_id = ? AND (template_slug IS NULL OR template_slug = '')", [$userId]);

        // Also seed from files if they exist
        $tplDir = $seedDir . '/templates';
        if (is_dir($tplDir)) {
            $sql = "INSERT INTO cms_templates (user_id, title, slug, content) VALUES (?, ?, ?, ?)";
            foreach (glob($tplDir . '/*.html') as $tplFile) {
                $slug = pathinfo($tplFile, PATHINFO_FILENAME);
                $title = ucwords(str_replace(['_', '-'], ' ', $slug));
                $content = file_get_contents($tplFile);
                DB::execute($sql, [$userId, $title, $slug, $content]);
            }
        }

        // 8. Data Store (User Preferences)
        $sql = "INSERT INTO data_store (user_id, type, key, value) VALUES (?, ?, ?, ?)";
        DB::execute($sql, [$userId, 'user_pref', 'theme', 'dark']);
        DB::execute($sql, [$userId, 'user_pref', 'language', 'en']);

        // 9. Messages
        $msgsFile = $seedDir . '/messages.json';
        if (file_exists($msgsFile)) {
            $msgs = json_decode(file_get_contents($msgsFile), true);
            $sql = "INSERT INTO messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, ?, ?)";
            foreach ($msgs as $msg) {
                DB::execute($sql, [$userId, $userId, $msg['content'], $msg['is_read']]);
            }
        }

        // 10. Bulk Generation (Show off power)
        // Todos: 25 extra items with random dates
        for ($i = 1; $i <= 25; $i++) {
            $hasDate = $i % 3 !== 0; // 2/3 items have dates
            $start = null;
            $end = null;

            if ($hasDate) {
                $offset = rand(-5, 15);
                $start = date('Y-m-d', strtotime((($offset >= 0) ? "+$offset" : "$offset") . " days"));

                // 50% chance of range vs single date
                if (rand(0, 1)) {
                    $duration = rand(1, 5);
                    $end = date('Y-m-d', strtotime("$start +$duration days"));
                }
            }

            Todo::create($userId, "Bulk Task #$i - " . bin2hex(random_bytes(4)), null, "generated", $start, $end, $i % 2 == 0 ? '#64748b' : null);
        }

        // Markers: 15 random locations around Paris
        $sql = "INSERT INTO map_markers (user_id, label, lat, lng) VALUES (?, ?, ?, ?)";
        for ($i = 1; $i <= 15; $i++) {
            $lat = 48.8566 + (rand(-100, 100) / 1000);
            $lng = 2.3522 + (rand(-100, 100) / 1000);
            DB::execute($sql, [$userId, "Random Point #$i", $lat, $lng]);
        }

        // Submissions: 20 dummy entries for the Contact form
        if (isset($formId)) {
            $sql = "INSERT INTO form_submissions (form_id, data, ip_address, user_agent) VALUES (?, ?, ?, ?)";
            for ($i = 1; $i <= 20; $i++) {
                $subData = json_encode([
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'message' => "This is automated message number $i."
                ]);
                DB::execute($sql, [$formId, $subData, '192.168.1.' . rand(1, 255), 'Mozilla/5.0 (Bot)']);
            }
        }
    }
}
