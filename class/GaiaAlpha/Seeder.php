<?php

namespace GaiaAlpha;

use GaiaAlpha\Controller\DbController;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Todo;
use GaiaAlpha\Env;

class Seeder
{
    public static function run(int $userId)
    {
        $pdo = DbController::getPdo();
        $seedDir = Env::get('root_dir') . '/templates/seed';

        // 1. Todos
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

        // 2. CMS Pages
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
                $stmt = $pdo->prepare("INSERT INTO menus (title, location, items, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmt->execute([$menu['title'], $menu['location'], json_encode($menu['items'])]);
            }
        }

        // 4. Forms & Submissions
        $formsFile = $seedDir . '/forms.json';
        if (file_exists($formsFile)) {
            $forms = json_decode(file_get_contents($formsFile), true);
            foreach ($forms as $form) {
                $stmt = $pdo->prepare("INSERT INTO forms (user_id, title, slug, description, schema, submit_label) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $form['title'],
                    $form['slug'],
                    $form['description'],
                    json_encode($form['schema']),
                    $form['submit_label'] ?? 'Submit'
                ]);

                if (!empty($form['submissions'])) {
                    $formId = $pdo->lastInsertId();
                    foreach ($form['submissions'] as $sub) {
                        $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$formId, json_encode($sub), '127.0.0.1', 'Mozilla/5.0 (Demo Agent)']);
                    }
                }
            }
        }

        // 6. Map Markers
        $markersFile = $seedDir . '/markers.json';
        if (file_exists($markersFile)) {
            $markers = json_decode(file_get_contents($markersFile), true);
            $stmt = $pdo->prepare("INSERT INTO map_markers (user_id, label, lat, lng) VALUES (?, ?, ?, ?)");
            foreach ($markers as $m) {
                $stmt->execute([$userId, $m['label'], $m['lat'], $m['lng']]);
            }
        }

        // 7. CMS Templates
        $tplDir = $seedDir . '/templates';
        if (is_dir($tplDir)) {
            $stmt = $pdo->prepare("INSERT INTO cms_templates (user_id, title, slug, content) VALUES (?, ?, ?, ?)");
            foreach (glob($tplDir . '/*.html') as $tplFile) {
                $slug = pathinfo($tplFile, PATHINFO_FILENAME);
                $title = ucwords(str_replace(['_', '-'], ' ', $slug));
                $content = file_get_contents($tplFile);
                $stmt->execute([$userId, $title, $slug, $content]);
            }
        }

        // 8. Data Store (User Preferences)
        $stmt = $pdo->prepare("INSERT INTO data_store (user_id, type, key, value) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, 'user_pref', 'theme', 'dark']);
        $stmt->execute([$userId, 'user_pref', 'language', 'en']);

        // 9. Messages
        $msgsFile = $seedDir . '/messages.json';
        if (file_exists($msgsFile)) {
            $msgs = json_decode(file_get_contents($msgsFile), true);
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, ?, ?)");
            foreach ($msgs as $msg) {
                $stmt->execute([$userId, $userId, $msg['content'], $msg['is_read']]);
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
        $stmt = $pdo->prepare("INSERT INTO map_markers (user_id, label, lat, lng) VALUES (?, ?, ?, ?)");
        for ($i = 1; $i <= 15; $i++) {
            $lat = 48.8566 + (rand(-100, 100) / 1000);
            $lng = 2.3522 + (rand(-100, 100) / 1000);
            $stmt->execute([$userId, "Random Point #$i", $lat, $lng]);
        }

        // Submissions: 20 dummy entries for the Contact form
        if (isset($formId)) {
            $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            for ($i = 1; $i <= 20; $i++) {
                $subData = json_encode([
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'message' => "This is automated message number $i."
                ]);
                $stmt->execute([$formId, $subData, '192.168.1.' . rand(1, 255), 'Mozilla/5.0 (Bot)']);
            }
        }
    }
}
