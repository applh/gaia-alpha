# Mail Server Documentation

Gaia Alpha uses [docker-mailserver](https://github.com/docker-mailserver/docker-mailserver) for a full-stack but simple mail server solution, integrated with Traefik for automatic SSL certificate management.

## 1. Initial Setup

### DNS Configuration
Before deploying, you must configure the following DNS records for your domain (`example.com`).

| Type | Name | Value | Priority | purpose |
|---|---|---|---|---|
| **A** | `mail` | `<YOUR_VPS_IP>` | | Points `mail.example.com` to your server. |
| **MX** | `@` | `mail.example.com.` | `10` | Directs email to your server. |
| **TXT** | `@` | `v=spf1 mx ~all` | | **SPF**: Authorizes your server to send email. |
| **TXT** | `_dmarc`| `v=DMARC1; p=none; rua=mailto:admin@example.com` | | **DMARC**: Policy for handling auth failures. |
| **TXT** | `mail._domainkey` | `(See DKIM section below)` | | **DKIM**: Signs emails to prove authenticity. |

### Deployment
The mail server is deployed automatically via Ansible or Docker Compose.
The configuration creates a default admin account based on your Ansible variables (`mail_user` and `mail_pass`).

## 2. Server Configuration Details

### Architecture
The project uses `mailserver/docker-mailserver` which aggregates standard Linux mail tools:
- **Postfix** (SMTP)
- **Dovecot** (IMAP/POP3)
- **SpamAssassin** (Spam filtering)
- **ClamAV** (Antivirus)
- **Fail2Ban** (Brute-force protection)
- **OpenDKIM** (Email signing)

### Key Environment Variables
These are configured in `docker-compose.prod.yml`:

| Variable | Default | Description |
|---|---|---|
| `ENABLE_SPAMASSASSIN` | `1` | Enables spam filtering. High memory usage (~1GB+). |
| `ENABLE_CLAMAV` | `1` | Enables antivirus. Very high memory usage (~2GB+). Disable for low-RAM servers. |
| `ENABLE_FAIL2BAN` | `1` | Protects against brute-force login attempts. |
| `SSL_TYPE` | `manual` | We use `manual` because we inject certificates dumped from Traefik. |
| `ONE_DIR` | `1` | Persists all config/state in a single directory structure. |

### Volumes & Persistence
Data is stored in `docker-data/dms` inside your project directory:

- `mail-data`: Stores the actual email contents. **Backup this.**
- `mail-state`: Stores SpamAssassin/ClamAV databases and state.
- `mail-logs`: Mail server logs.
- `config`: Configuration overrides, accounts (`postfix-accounts.cf`), and DKIM keys.

### SSL/TLS Certificate Strategy
We use a **Split-Horizon SSL** setup:
1.  **Ingress**: `Traefik` handles ACME (Let's Encrypt) for `mail.example.com` (web portal) and standard routes.
2.  **Internal**: Traefik stores certs in `acme.json`.
3.  **Extraction**: The `cert-dumper` container watches `acme.json` and extracts certificates to `./ssl`.
4.  **Mail Server**: The `mail` container mounts `./ssl` and uses them for SMTP/IMAP TLS.

## 3. Management (CRUD)

We provide a helper script `bin/manage_mail.sh` to simplify managing email accounts and aliases.

### Usage
```bash
./bin/manage_mail.sh [command] [arguments]
```

### Adding an Account
To add a new email address:
```bash
./bin/manage_mail.sh add user@example.com password123
```

### Removing an Account
To delete an email address:
```bash
./bin/manage_mail.sh del user@example.com
```

### Listing Accounts
To see all configured accounts:
```bash
./bin/manage_mail.sh list
```

### Managing Aliases
Aliases allow you to receive mail for one address (e.g., `info@example.com`) at another address (e.g., `user@example.com`).
```bash
./bin/manage_mail.sh alias add info@example.com user@example.com
./bin/manage_mail.sh alias list
./bin/manage_mail.sh alias del info@example.com user@example.com
```

## 4. DKIM Configuration (Important!)
To prevent your emails from going to Spam, you **must** set up DKIM.

1.  Generate DKIM keys on the server:
    ```bash
    ./bin/manage_mail.sh dkim
    ```
2.  Restart the mail container to apply changes:
    ```bash
    docker restart mail
    ```
3.  Retrieve the public key:
    ```bash
    cat docker-data/dms/config/opendkim/keys/example.com/mail.txt
    ```
4.  Add the output as a **TXT** record in your DNS provider for the host `mail._domainkey`.

## 5. Client Configuration

Configure your email client (Outlook, Thunderbird, Apple Mail, etc.) with the following settings:

| Setting | Value |
|---|---|
| **Username** | `user@example.com` (Full email address) |
| **Password** | Your password |
| **Incoming Server (IMAP)** | `mail.example.com` |
| **Incoming Port** | `993` (SSL/TLS) |
| **Outgoing Server (SMTP)** | `mail.example.com` |
| **Outgoing Port** | `587` (STARTTLS) or `465` (SSL/TLS) |

> **Note**: If `mail.example.com` does not have a valid SSL certificate immediately (due to DNS propagation), you may get a warning. Ensure `cert-dumper` has run successfully.
