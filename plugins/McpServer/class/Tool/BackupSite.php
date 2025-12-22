<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class BackupSite extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'backup_site',
            'description' => 'Create a backup of a site including database and assets',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $site = $arguments['site'] ?? 'default';
        $rootDir = Env::get('root_dir');
        $backupDir = $rootDir . '/my-data/backups';
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir);
        }

        $zipFile = $backupDir . '/' . ($site === 'default' ? 'default' : $site) . '_' . date('Ymd_His') . '.zip';

        $db = \GaiaAlpha\Model\DB::connect();
        $dbDumpFile = $rootDir . '/my-data/database_backup.sql';
        $db->dump($dbDumpFile);

        if ($site === 'default') {
            $cmd = "cd " . escapeshellarg($rootDir . '/my-data') . " && zip -r " . escapeshellarg($zipFile) . " database_backup.sql assets/";
        } else {
            // For now, site-specific backups still assume SQLite or use the main DB.
            // If MultiSite is implemented with separate DBs, this would need more logic.
            $cmd = "cd " . escapeshellarg($rootDir . '/my-data/sites') . " && zip -r " . escapeshellarg($zipFile) . " " . escapeshellarg($site);
        }

        $output = [];
        $return = 0;
        exec($cmd, $output, $return);

        // Cleanup temp dump file
        if (file_exists($dbDumpFile)) {
            unlink($dbDumpFile);
        }

        if ($return !== 0) {
            throw new \Exception("Backup failed: " . implode("\n", $output));
        }

        return $this->resultText("Backup created at: " . str_replace($rootDir . '/', '', $zipFile));
    }
}
