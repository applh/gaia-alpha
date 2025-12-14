# Multi-Site Support

Gaia Alpha supports a **One Database Per Site** architecture, allowing a single installation to serve multiple domains with complete data isolation.

## Architecture

- **Host Detection**: The system detects the `HTTP_HOST` (e.g., `brand-a.com`, `test.local`).
- **Database Mapping**: It maps the domain to a dedicated SQLite file in `my-data/sites/{domain}.sqlite`.
- **Default Fallback**: If no specific site database exists, it falls back to the default `my-data/database.sqlite`.

## Managing Sites

We provide CLI tools to manage these sites effectively.

### Creating a Site
To initialize a new site (creates the database and schema):
```bash
php cli.php site:create example.com
```

### Listing Sites
To see all active site databases:
```bash
php cli.php site:list
```

### Running Commands for a Specific Site
You can run ANY CLI command against a specific site using the `--site` flag:
```bash
# List users for example.com
php cli.php user:list --site=example.com

# Run migrations for example.com
php cli.php db:migrate --site=example.com
```

## Local Development Setup

To test multi-site locally (e.g., `test.local`):

1.  **Configure DNS**: Add the domain to your hosts file.
    *   **Mac/Linux**: `/etc/hosts` -> `127.0.0.1 test.local`
    *   **Windows**: `C:\Windows\System32\drivers\etc\hosts` -> `127.0.0.1 test.local`

2.  **Start Server**: Bind to all interfaces to avoid IPv6 binding issues.
    ```bash
    php -S 0.0.0.0:8000 -t www
    ```

3.  **Access**: Visit `http://test.local:8000`.

> **Note**: User accounts are NOT shared between sites. You must create an admin account for each new site:
> `php cli.php user:create admin securepass --site=test.local`
