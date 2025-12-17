<?php

namespace GaiaAlpha\Plugins\Mail\Drivers;

use GaiaAlpha\Plugins\Mail\MailerInterface;
use GaiaAlpha\App;

class PHPMailerDriver implements MailerInterface
{

    public function send(string $to, string $subject, string $body, array $headers = []): bool
    {

        // Check if PHPMailer class exists, if not try to load from vendor/src
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $vendorDir = __DIR__ . '/../../vendor/src/';
            if (file_exists($vendorDir . 'PHPMailer.php')) {
                require_once $vendorDir . 'Exception.php';
                require_once $vendorDir . 'PHPMailer.php';
                require_once $vendorDir . 'SMTP.php';
            }
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            error_log("Mail Error: PHPMailer class not found. Please install phpmailer/phpmailer or check plugins/Mail/vendor/src.");
            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $config = App::config();

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['mail_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $config['mail_user'] ?? '';
            $mail->Password = $config['mail_pass'] ?? '';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // or ENCRYPTION_SMTPS
            $mail->Port = $config['mail_port'] ?? 587;

            // Recipients
            $from = $config['mail_from'] ?? 'noreply@example.com';
            $fromName = $config['mail_from_name'] ?? 'Gaia Alpha System';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            //$mail->AltBody = strip_tags($body);

            // Custom headers
            foreach ($headers as $key => $value) {
                $mail->addCustomHeader($key, $value);
            }

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
