# Mail Plugin

The **Mail Plugin** provides core email sending capabilities and a robust Newsletter management system for Gaia Alpha.

## Features

### 1. Email Sending
- **Drivers**: Supports `PHPMailer` (SMTP) and `Native` (mail() function) drivers.
- **Inbox Logging**: Logs all sent emails to a local file for debugging and auditing (`my-data/mail.log`).
- **Test Mode**: Send test emails directly from the Admin Panel.

### 2. Newsletter Manager (v1.1)
- **Subscriber Management**: Manage lists and subscribers.
- **Newsletter Builder**:
    - **Markdown Support**: Write newsletters in Markdown directly.
    - **Live Preview**: See HTML rendering in real-time.
    - **Image Insertion**: Insert images from the Media Library or external URLs.
- **Sending**: Queue and send newsletters to subscriber lists (Mock implementation currently logs to file/database).

### 3. Database Tables
- `newsletters`: Stores newsletter content and status.
- `newsletter_lists`: Groups of subscribers.
- `newsletter_subscribers`: Subscriber details (email, name).
- `newsletter_subscriptions`: Links subscribers to lists.

## Configuration

Configure the mailer settings in your `my-data/config.php` or `env` file:

```php
// Mail Settings
Env::set('mail_driver', 'phpmailer'); // or 'native'
Env::set('mail_host', 'smtp.example.com');
Env::set('mail_port', 587);
Env::set('mail_user', 'user@example.com');
Env::set('mail_pass', 'secret');
Env::set('mail_from', 'noreply@example.com');
Env::set('mail_from_name', 'Gaia Alpha');
```

## Directory Structure

```text
plugins/Mail/
├── class/
│   ├── Controller/
│   │   ├── MailController.php       # Admin Inbox & Test Email
│   │   └── NewsletterController.php # Newsletter CRUD & Builder
│   ├── Drivers/
│   │   └── PHPMailerDriver.php
│   ├── Model/
│   │   └── Newsletter.php
│   └── Mail.php                     # Main Facade
├── lib/
│   └── src/                         # PHPMailer library files
├── resources/
│   └── js/
│       ├── MailPanel.js             # Inbox UI
│       ├── NewsletterManager.js     # Newsletter List UI
│       └── NewsletterBuilder.js     # Newsletter Editor
├── index.php                        # Plugin entry point & registration
└── plugin.json                      # Metadata
```

## Usage

### Sending an Email (Code)

```php
use Mail\Mail;

Mail::send('user@example.com', 'Hello', '<h1>Welcome!</h1>');
```

### Accessing the UI
- **Inbox**: Admin > Mail & Newsletters > Inbox
- **Newsletters**: Admin > Mail & Newsletters > Newsletters
