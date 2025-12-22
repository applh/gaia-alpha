<?php

namespace Mail\Drivers;

use Mail\MailerInterface;
use GaiaAlpha\App;

class PHPMailerDriver implements MailerInterface
{

    public function send(string $to, string $subject, string $body, array $headers = []): bool
    {

        // Check if PHPMailer class exists, if not try to load from lib/src
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $libDir = __DIR__ . '/../../lib/src/';
            if (file_exists($libDir . 'PHPMailer.php')) {
                require_once $libDir . 'Exception.php';
                require_once $libDir . 'PHPMailer.php';
                require_once $libDir . 'SMTP.php';
            }
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            error_log("Mail Error: PHPMailer class not found. Please install phpmailer/phpmailer or check plugins/Mail/lib/src.");
            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = \GaiaAlpha\Env::get('mail_host', '');
            $mail->SMTPAuth = true;
            $mail->Username = \GaiaAlpha\Env::get('mail_user', '');
            $mail->Password = \GaiaAlpha\Env::get('mail_pass', '');
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = \GaiaAlpha\Env::get('mail_port', 587);

            // Recipients
            $from = \GaiaAlpha\Env::get('mail_from', 'noreply@example.com');
            $fromName = \GaiaAlpha\Env::get('mail_from_name', 'Gaia Alpha System');
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
