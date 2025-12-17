<?php

namespace GaiaAlpha\Plugins\Mail\Drivers;

use GaiaAlpha\Plugins\Mail\MailerInterface;

class LogDriver implements MailerInterface
{

    protected $logFile;

    public function __construct()
    {
        // Define log file path relative to project root
        // Assuming this class is in plugins/Mail/class/Drivers/
        // Root is ../../../../
        $this->logFile = __DIR__ . '/../../../../my-data/mail.log';
    }

    public function send(string $to, string $subject, string $body, array $headers = []): bool
    {
        $entry = [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'headers' => $headers
        ];

        $logEntry = json_encode($entry) . PHP_EOL;

        // Ensure directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return (file_put_contents($this->logFile, $logEntry, FILE_APPEND) !== false);
    }
}
