<?php

namespace GaiaAlpha\ImportExport;

use GaiaAlpha\File;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Template;
use GaiaAlpha\Model\DB;

class WebsiteImporter
{
    private string $inDir;
    private int $userId;

    public function __construct(string $inDir, int $userId)
    {
        $this->inDir = rtrim($inDir, '/');
        $this->userId = $userId;
    }

    public function import()
    {
        if (!File::isDirectory($this->inDir)) {
            throw new \Exception("Import directory not found: " . $this->inDir);
        }

        // 1. Check Manifest
        $this->checkManifest();

        // 2. Import Assets (First to ensure available)
        $this->importAssets();

        // 3. Import Components
        $this->importComponents();

        // 4. Import Templates
        $this->importTemplates();

        // 5. Import Forms
        $this->importForms();

        // 6. Import Pages & Media
        $this->importPages();

        return true;
    }

    private function checkManifest()
    {
        $path = $this->inDir . '/site.json';
        if (File::exists($path)) {
            $manifest = json_decode(File::read($path), true);
            // Verify required plugins
            $requiredPlugins = $manifest['plugins'] ?? [];

            // Check active plugins
            $rootDir = dirname(__DIR__, 3);
            $pluginsFile = $rootDir . '/my-data/active_plugins.json';
            $activePlugins = [];
            if (File::exists($pluginsFile)) {
                $activePlugins = json_decode(File::read($pluginsFile), true) ?? [];
            }

            $missing = array_diff($requiredPlugins, $activePlugins);
            if (!empty($missing)) {
                echo "WARNING: The following plugins are required but not active: " . implode(', ', $missing) . "\n";
            }
        }
    }

    private function importAssets()
    {
        $srcDir = $this->inDir . '/assets';
        if (!File::isDirectory($srcDir)) {
            return;
        }

        $rootDir = dirname(__DIR__, 3);
        $dstDir = $rootDir . '/www/assets';

        $this->copyDirectory($srcDir, $dstDir);
    }

    private function importComponents()
    {
        $srcDir = $this->inDir . '/components';
        if (!File::isDirectory($srcDir)) {
            return;
        }

        $rootDir = dirname(__DIR__, 3);
        $dstDir = $rootDir . '/my-data/components/custom';

        if (!File::isDirectory($dstDir)) {
            File::makeDirectory($dstDir);
        }

        $files = File::glob($srcDir . '/*.js');
        foreach ($files as $file) {
            $basename = basename($file);
            copy($file, $dstDir . '/' . $basename);
        }
    }

    private function importTemplates()
    {
        $srcDir = $this->inDir . '/templates';
        if (!File::isDirectory($srcDir)) {
            return;
        }

        $files = File::glob($srcDir . '/*.php');
        foreach ($files as $file) {
            $slug = basename($file, '.php');
            $content = File::read($file);

            // Upsert Template
            $existing = Template::findBySlug($slug);
            if ($existing) {
                // Update
                Template::update($existing['id'], $this->userId, ['title' => $slug, 'content' => $content]);
            } else {
                // Create
                Template::create($this->userId, ['title' => $slug, 'slug' => $slug, 'content' => $content]);
            }
        }
    }

    private function importForms()
    {
        $srcDir = $this->inDir . '/forms';
        if (!File::isDirectory($srcDir)) {
            return;
        }

        $files = File::glob($srcDir . '/*.json');
        foreach ($files as $file) {
            $data = json_decode(File::read($file), true);
            if (!$data)
                continue;

            $slug = $data['slug'];

            // Check existing
            $existing = DB::fetch("SELECT id FROM forms WHERE slug = ? AND user_id = ?", [$slug, $this->userId]);

            $fields = [
                'user_id' => $this->userId,
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'submit_label' => $data['submit_label'],
                'schema' => json_encode($data['schema'])
            ];

            if ($existing) {
                $sql = "UPDATE forms SET title=?, description=?, submit_label=?, schema=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";
                DB::execute($sql, [$fields['title'], $fields['description'], $fields['submit_label'], $fields['schema'], $existing['id']]);
            } else {
                $sql = "INSERT INTO forms (user_id, title, slug, description, submit_label, schema, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                DB::execute($sql, [$this->userId, $fields['title'], $fields['slug'], $fields['description'], $fields['submit_label'], $fields['schema']]);
            }
        }
    }

    private function importPages()
    {
        $srcDir = $this->inDir . '/pages';
        if (!File::isDirectory($srcDir)) {
            return;
        }

        $files = File::glob($srcDir . '/*.md');
        foreach ($files as $file) {
            $rawContent = File::read($file);

            // Parse Front Matter
            $parts = preg_split('/^---$/m', $rawContent, 3);
            if (count($parts) < 3) {
                // No front matter? Skip or treat as basic content
                continue;
            }

            $frontMatterRaw = trim($parts[1]);
            $body = trim($parts[2]);

            // Simple YAML parser (since we don't have yaml extension guaranteed)
            $meta = [];
            $lines = explode("\n", $frontMatterRaw);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line || strpos($line, ':') === false)
                    continue;
                list($key, $val) = explode(':', $line, 2);
                $key = trim($key);
                $val = trim($val, " \"'");
                $meta[$key] = $val;
            }

            $slug = $meta['slug'] ?? basename($file, '.md');
            if ($slug === 'home')
                $slug = '/';

            // Process Links & Media
            $body = $this->processContentMedia($body);

            // Upsert Page
            $data = [
                'title' => $meta['title'] ?? ucfirst($slug),
                'slug' => $slug,
                'content' => $body,
                // 'image' => ... // TODO: handle cover image import if refererenced
                'cat' => $meta['cat'] ?? 'page',
                'template_slug' => $meta['template_slug'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'meta_keywords' => $meta['meta_keywords'] ?? null
            ];

            $existing = Page::findBySlug($slug);
            if ($existing) {
                Page::update($existing['id'], $this->userId, $data);
            } else {
                Page::create($this->userId, $data);
            }
        }
    }

    private function processContentMedia(string $content): string
    {
        // Replace ./media/filename with /media/USER_ID/filename
        // and copy file from import/media to uploads/USER_ID

        return preg_replace_callback('#\./media/([\w\-\.]+)#', function ($matches) {
            $filename = $matches[1];

            // Source file in import package
            $srcPath = $this->inDir . '/media/' . $filename;

            if (File::exists($srcPath)) {
                // Target directory
                $rootDir = dirname(__DIR__, 3);
                $uploadDir = $rootDir . '/my-data/uploads/' . $this->userId;

                if (!File::isDirectory($uploadDir)) {
                    File::makeDirectory($uploadDir);
                }

                copy($srcPath, $uploadDir . '/' . $filename);

                return '/media/' . $this->userId . '/' . $filename;
            }

            return $matches[0];
        }, $content);
    }

    private function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        if (!File::isDirectory($dst)) {
            File::makeDirectory($dst);
        }

        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
