<?php

namespace GaiaAlpha\Plugins\Mail\Controller;

use GaiaAlpha\Plugins\Mail\Mail;

use GaiaAlpha\Framework;
use GaiaAlpha\Request;
use GaiaAlpha\Response;

class MailController
{

    protected $logFile;

    public function registerRoutes()
    {
        \GaiaAlpha\Router::get('/@/admin/mail/inbox', [$this, 'inbox']);
        \GaiaAlpha\Router::post('/@/admin/mail/send-test', [$this, 'sendTest']);
        \GaiaAlpha\Router::post('/@/admin/mail/clear', [$this, 'clearInbox']);
    }

    public function __construct()
    {
        $this->logFile = __DIR__ . '/../../../../my-data/mail.log';
    }

    /**
     * list logs
     * GET /@/admin/mail/inbox
     */
    public function inbox()
    {
        if (!file_exists($this->logFile)) {
            return Response::json(['emails' => []]);
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Reverse order to show newest first
        $lines = array_reverse($lines);

        $emails = [];
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $emails[] = $data;
            }
        }

        return Response::json(['emails' => $emails]);
    }

    /**
     * Send a test email
     * POST /@/admin/mail/send-test
     */
    public function sendTest()
    {
        $to = Request::input('to', 'test@example.com');
        $subject = Request::input('subject', 'Test Email from Gaia Alpha');
        $body = "<h1>This is a test email</h1><p>Sent at " . date('Y-m-d H:i:s') . "</p>";

        $success = Mail::send($to, $subject, $body);

        if ($success) {
            return Response::json(['status' => 'success', 'message' => 'Email sent successfully']);
        } else {
            return Response::json(['status' => 'error', 'message' => 'Failed to send email'], 500);
        }
    }

    /**
     * Clear the inbox (log file)
     * POST /@/admin/mail/clear
     */
    public function clearInbox()
    {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, ''); // Empty the file
        }
        return Response::json(['status' => 'success']);
    }
}
