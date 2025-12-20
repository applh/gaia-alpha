<?php

namespace GaiaAlpha\ImportExport;

use GaiaAlpha\File;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Template;
use GaiaAlpha\Model\DB;

class WebsiteExporter
{
    private string $outDir;
    private int $userId;
    private ?string $assetsDir;

    public function __construct(string $outDir, int $userId, ?string $assetsDir = null)
    {
        $this->outDir = rtrim($outDir, '/');
        $this->userId = $userId;
        $this->assetsDir = $assetsDir;
    }

    public function export()
    {
        // 1. Prepare Directories
        $this->prepareDirectories();

        // 2. Export Pages
        $this->exportPages();

        // 3. Export Templates
        $this->exportTemplates();

        // 4. Export Forms
        $this->exportForms();

        // 5. Export Components
        $this->exportComponents();

        // 6. Export Assets
        $this->exportAssets();

        // 7. Export Menus
        $this->exportMenus();

        // 8. Export Global Settings
        $this->exportGlobalSettings();

        // 9. Generate site.json
        $this->generateSiteJson();

        return true;
    }

    private function exportMenus()
    {
        $menus = \GaiaAlpha\Model\Menu::all();
        $exportData = [];

        foreach ($menus as $menu) {
            $exportData[] = [
                'title' => $menu['title'],
                'location' => $menu['location'],
                'items' => json_decode($menu['items'], true) ?? []
            ];
        }

        if (!empty($exportData)) {
            File::write($this->outDir . '/menus.json', json_encode($exportData, JSON_PRETTY_PRINT));
        }
    }

    private function prepareDirectories()
    {
        if (!File::isDirectory($this->outDir)) {
            File::makeDirectory($this->outDir);
        }

        $dirs = ['pages', 'forms', 'templates', 'components', 'media', 'assets'];
        foreach ($dirs as $dir) {
            $path = $this->outDir . '/' . $dir;
            if (!File::isDirectory($path)) {
                File::makeDirectory($path);
            }
        }
    }

    private function exportPages()
    {
        $pages = Page::findAllByUserId($this->userId); // Note: Need to verify if this returns all needed fields
        // Page::findAllByUserId currently uses "SELECT *", so it should be fine.

        foreach ($pages as $page) {
            $slug = $page['slug'] ?: 'home';
            if ($slug === '/')
                $slug = 'home';

            // Clean slug for filename
            $filename = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) . '.md';

            $content = $page['content'] ?? '';

            // Rewrite Media Links & Copy Media
            $content = $this->processContentMedia($content);

            // Construct Front Matter
            $frontMatter = [
                'title' => $page['title'],
                'slug' => $page['slug'],
                'template_slug' => $page['template_slug'],
                'cat' => $page['cat'],
                'meta_description' => $page['meta_description'],
                'meta_keywords' => $page['meta_keywords'],
                'image' => $this->exportMediaItem($page['image'])
            ];

            // Build File Content
            $fileContent = "---\n";
            foreach ($frontMatter as $key => $val) {
                if ($val !== null && $val !== '') {
                    $valClean = str_replace('"', '\"', $val);
                    $fileContent .= "$key: \"$valClean\"\n";
                }
            }
            $fileContent .= "---\n\n";
            $fileContent .= $content;

            File::write($this->outDir . '/pages/' . $filename, $fileContent);
        }
    }

    private function processContentMedia(string $content): string
    {
        // Find all images matching /media/USER_ID/FILENAME
        // Pattern: /media/(\d+)/([\w\-\.]+)/
        // We only care about media belonging to current user or generic? 
        // For now assume standard path /media/{userId}/{filename}

        return preg_replace_callback('#/media/(\d+)/([\w\-\.]+)#', function ($matches) {
            $userId = $matches[1];
            $filename = $matches[2];

            // Only export if file exists in our uploads
            $sourcePath = $_SERVER['DOCUMENT_ROOT'] . '/../my-data/uploads/' . $userId . '/' . $filename;
            // Adjust path cause we are in CLI usually
            if (!file_exists($sourcePath)) {
                // Try relative to project root if $_SERVER is not set correctly in CLI
                $rootDir = dirname(__DIR__, 3); // class/GaiaAlpha/ImportExport -> class/GaiaAlpha -> class -> root
                $sourcePath = $rootDir . '/my-data/uploads/' . $userId . '/' . $filename;
            }

            if (file_exists($sourcePath)) {
                // Copy to export media folder
                copy($sourcePath, $this->outDir . '/media/' . $filename);
                // Return relative link
                return './media/' . $filename;
            }

            return $matches[0]; // Return original if not found
        }, $content);
    }

    private function exportTemplates()
    {
        $templates = Template::findAllByUserId($this->userId);
        foreach ($templates as $tmpl) {
            $filename = preg_replace('/[^a-z0-9-_]/', '', $tmpl['slug']) . '.php';
            File::write($this->outDir . '/templates/' . $filename, $tmpl['content']);
        }
    }

    private function exportForms()
    {
        $forms = DB::fetchAll("SELECT * FROM forms WHERE user_id = ?", [$this->userId]);
        foreach ($forms as $form) {
            $filename = preg_replace('/[^a-z0-9-]/', '', $form['slug']) . '.json';

            $data = [
                'title' => $form['title'],
                'slug' => $form['slug'],
                'description' => $form['description'],
                'submit_label' => $form['submit_label'],
                'schema' => json_decode($form['schema'])
            ];

            File::write($this->outDir . '/forms/' . $filename, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    private function exportComponents()
    {
        $rootDir = dirname(__DIR__, 3);
        $customCompDir = $rootDir . '/my-data/components/custom';

        if (File::isDirectory($customCompDir)) {
            $files = File::glob($customCompDir . '/*.js');
            foreach ($files as $file) {
                $basename = basename($file);
                copy($file, $this->outDir . '/components/' . $basename);
            }
        }
    }

    private function exportAssets()
    {
        if ($this->assetsDir && File::isDirectory($this->assetsDir)) {
            $srcDir = $this->assetsDir;
        } else {
            $rootDir = dirname(__DIR__, 3);
            $srcDir = $rootDir . '/www/assets';
        }

        if (File::isDirectory($srcDir)) {
            // Recursive copy
            $this->copyDirectory($srcDir, $this->outDir . '/assets');
        }
    }

    private function exportGlobalSettings()
    {
        $settings = \GaiaAlpha\Model\DataStore::getAll(0, 'global_config');

        if (!empty($settings)) {
            File::write(
                $this->outDir . '/settings.json',
                json_encode($settings, JSON_PRETTY_PRINT)
            );
        }
    }

    private function generateSiteJson()
    {
        // Get active plugins
        $rootDir = dirname(__DIR__, 3);
        $pluginsFile = $rootDir . '/my-data/active_plugins.json';
        $plugins = [];
        if (File::exists($pluginsFile)) {
            $plugins = json_decode(File::read($pluginsFile), true) ?? [];
        }

        // Check if global settings were exported
        $hasGlobalSettings = File::exists($this->outDir . '/settings.json');

        $manifest = [
            'name' => 'Exported Site',
            'exported_at' => date('c'),
            'generator' => 'GaiaAlpha',
            'version' => '1.0.0',
            'plugins' => $plugins,
            'has_global_settings' => $hasGlobalSettings,
            'config' => [
                'theme' => 'default' // Placeholder for now
            ]
        ];

        File::write($this->outDir . '/site.json', json_encode($manifest, JSON_PRETTY_PRINT));
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

    private function exportMediaItem(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Match local media path: /media/USER_ID/FILENAME
        if (preg_match('#^/media/(\d+)/([\w\-\.]+)$#', $path, $matches)) {
            $userId = $matches[1];
            $filename = $matches[2];

            // Only export if file exists in our uploads
            $sourcePath = $_SERVER['DOCUMENT_ROOT'] . '/../my-data/uploads/' . $userId . '/' . $filename;

            if (!file_exists($sourcePath)) {
                $rootDir = dirname(__DIR__, 3);
                $sourcePath = $rootDir . '/my-data/uploads/' . $userId . '/' . $filename;
            }

            if (file_exists($sourcePath)) {
                // Copy to export media folder
                copy($sourcePath, $this->outDir . '/media/' . $filename);
                // Return relative link
                return './media/' . $filename;
            }
        }

        return $path;
    }
}
