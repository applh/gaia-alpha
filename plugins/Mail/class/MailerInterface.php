<?php

namespace GaiaAlpha\Plugins\Mail;

interface MailerInterface {
    /**
     * Send an email.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email HTML body
     * @param array $headers Optional additional headers
     * @return bool True on success, false on failure
     */
    public function send(string $to, string $subject, string $body, array $headers = []): bool;
}
