<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;
use GaiaAlpha\Database;
use GaiaAlpha\Model\Page;

class CreateSite extends BaseTool
{
    public function execute(array $arguments): array
    {
        $domain = $arguments['domain'] ?? null;
        if (!$domain) {
            throw new \Exception("Domain name is required.");
        }

        $rootDir = Env::get('root_dir');
        $sitePath = $rootDir . '/my-data/sites/' . $domain;
        if (File::isDirectory($sitePath)) {
            throw new \Exception("Site directory '$domain' already exists.");
        }
        File::makeDirectory($sitePath);
        File::makeDirectory($sitePath . '/assets');
        $dbPath = $sitePath . '/database.sqlite';
        $dsn = 'sqlite:' . $dbPath;
        $db = new Database($dsn);
        $db->ensureSchema();

        // Note: switchSite handling remains in Server.php to maintain connection state
        // but for bootstrap we need to manually inject connection or let Server.php handle it.
        // For simplicity during transition, we assume Server::switchSite was called if needed,
        // but create_site actually changes the active connection.

        // This tool is slightly special because it changes state.
        // We'll return technical info and let Server.php handle any secondary state changes.

        \GaiaAlpha\Model\DB::setConnection($db);
        $userId = \GaiaAlpha\Model\User::create('admin', 'admin', 100);
        Page::create($userId, [
            'title' => 'Home',
            'slug' => 'home',
            'content' => '<h1>Welcome to ' . $domain . '</h1>',
            'cat' => 'page'
        ]);

        return $this->resultText("Site '$domain' created successfully with admin/admin credentials.");
    }
}
