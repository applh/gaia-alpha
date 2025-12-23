# Mail System Architecture & Roles

This document outlines the architecture of the Mail Core Plugin, defining the specific roles and responsibilities of each component. This modular approach allows the application to switch seamlessly between development (Fake) and production (Real) mail delivery methods.

## 1. The Manager Role (Orchestrator)
**Component**: `plugins/Mail/class/Mail.php`

The **Manager** acts as the central facade for all mail operations. It abstracts the underlying driver logic from the rest of the application.

*   **Responsibilities**:
    *   Reads the `mail_driver` configuration.
    *   Instantiates the appropriate driver (Log, SMTP, etc.).
    *   Implementing the Singleton pattern (optional) or static access methods for global availability.
    *   Delegates the actual sending to the selected driver.
*   **Usage**:
    ```php
    GaiaAlpha\Plugins\Mail\Mail::send('user@example.com', 'Subject', 'Body');
    ```

## 2. The Interface Role (Contract)
**Component**: `plugins/Mail/class/MailerInterface.php`

The **Interface** defines the strict contract that all drivers must adhere to. This ensures that the Manager can switch drivers without worrying about incompatible methods.

*   **Responsibilities**:
    *   Enforces the existence of the `send` method.
*   **Contract**:
    ```php
    interface MailerInterface {
        public function send(string $to, string $subject, string $body, array $headers = []): bool;
    }
    ```

## 3. The "Fake" Role (Development)
**Component**: `plugins/Mail/class/Drivers/LogDriver.php`

The **Fake** role is designed for local development and testing environments where a real SMTP server is unavailable or undesirable.

*   **Responsibilities**:
    *   **Capture**: Intercepts the email before it leaves the system.
    *   **Log**: Appends the email details (Timestamp, Recipient, Subject, Body) to a local log file (`my-data/mail.log`).
    *   **Safety**: Ensure no actual emails are sent to the outside world.

## 4. The "Real" Role (Production)
**Component**: `plugins/Mail/class/Drivers/PHPMailerDriver.php`

The **Real** role handles the actual delivery of emails over the network using standard protocols.

*   **Responsibilities**:
    *   **Transport**: Connects to an external SMTP server (e.g., Gmail, AWS SES).
    *   **Security**: Handles TLS/SSL encryption and authentication.
    *   **Compliance**: Ensures headers are correctly formatted for deliverability.
    *   **Library Wrapper**: Wraps the PHPMailer library to conform to the `MailerInterface`.

## 5. The Admin Role (Visibility)
**Component**: `plugins/Mail/class/MailController.php` & `resources/js/MailPanel.js`

The **Admin** role provides a user interface to interact with the mail system, primarily useful for the Fake driver.

*   **Responsibilities**:
    *   **Fake Inbox**: A viewing interface in the Admin Panel that reads the `my-data/mail.log` file, allowing developers to "read" the fake emails.
    *   **Test Sender**: A tool to manually trigger a test email to verify configuration.

## 6. Configuration

The system is configured via `my-config.php`:

```php
// ...
'mail_driver' => 'log', // Options: 'log', 'smtp'
'mail_host' => 'smtp.example.com',
'mail_port' => 587,
'mail_user' => 'apikey',
'mail_pass' => 'secret',
'mail_from' => 'noreply@myapp.com',
'mail_from_name' => 'MyApp System',
// ...
```
