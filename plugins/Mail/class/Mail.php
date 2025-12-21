<?php

namespace Mail;

use GaiaAlpha\App;
use Mail\Drivers\LogDriver;
use Mail\Drivers\PHPMailerDriver;

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

        $driverName = \GaiaAlpha\Env::get('mail_driver', 'log');

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
