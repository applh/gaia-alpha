<?php

namespace GaiaAlpha\Plugins\Mail;

use GaiaAlpha\App;
use GaiaAlpha\Plugins\Mail\Drivers\LogDriver;
use GaiaAlpha\Plugins\Mail\Drivers\PHPMailerDriver;

class Mail
{

    protected static $instance;

    /**
     * Send an email using the configured driver.
     */
    public static function send(string $to, string $subject, string $body, array $headers = []): bool
    {
        return self::getDriver()->send($to, $subject, $body, $headers);
    }

    /**
     * Get the configured mail driver instance.
     */
    protected static function getDriver(): MailerInterface
    {
        if (self::$instance) {
            return self::$instance;
        }

        $config = App::config();
        $driverName = $config['mail_driver'] ?? 'log'; // Default to log

        switch ($driverName) {
            case 'smtp':
                self::$instance = new PHPMailerDriver();
                break;
            case 'log':
            default:
                self::$instance = new LogDriver();
                break;
        }

        return self::$instance;
    }
}
