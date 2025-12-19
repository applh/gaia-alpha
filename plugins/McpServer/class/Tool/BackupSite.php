<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class BackupSite extends BaseTool
{
    public function execute(array $arguments): array
    {
        $site = $arguments['site'] ?? 'default';
        $rootDir = Env::get('root_dir');
        $backupDir = $rootDir . '/my-data/backups';
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir);
        }

        $zipFile = $backupDir . '/' . ($site === 'default' ? 'default' : $site) . '_' . date('Ymd_His') . '.zip';

        if ($site === 'default') {
            $cmd = "cd " . escapeshellarg($rootDir . '/my-data') . " && zip -r " . escapeshellarg($zipFile) . " database.sqlite assets/";
        } else {
            $cmd = "cd " . escapeshellarg($rootDir . '/my-data/sites') . " && zip -r " . escapeshellarg($zipFile) . " " . escapeshellarg($site);
        }

        $output = [];
        $return = 0;
        exec($cmd, $output, $return);

        if ($return !== 0) {
            throw new \Exception("Backup failed: " . implode("\n", $output));
        }

        return $this->resultText("Backup created at: " . str_replace($rootDir . '/', '', $zipFile));
    }
}
