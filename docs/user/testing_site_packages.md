
# Validating Site Packages Locally

This guide walks you through the complete workflow for validating **Site Packages**: creating a local domain, importing content, testing, and cleanly resetting.

This process allows you to simulate a production environment on your local machine using custom domains (e.g., `mysite.test`).

---

## 1. Setup Local Domain

To access your site via a custom domain (like `mysite.test`), you need to tell your computer that this domain points to your local machine (`127.0.0.1`). We do this by editing the **hosts file**.

### 🍎 macOS
1. Open Terminal.
2. Type `sudo nano /etc/hosts` and press Enter. Enter your password.
3. Add the following line at the bottom:
   ```text
   127.0.0.1   mysite.test
   ::1         mysite.test
   ```
4. Press `Ctrl+O` then `Enter` to save, and `Ctrl+X` to exit.
5. Flush the DNS cache: `sudo dscacheutil -flushcache; sudo killall -HUP mDNSResponder`.

### 🪟 Windows
1. Open **Notepad** as Administrator (Right-click > Run as administrator).
2. Open the file: `C:\Windows\System32\drivers\etc\hosts`.
3. Add the following line at the bottom:
   ```text
   127.0.0.1   mysite.test
   ::1         mysite.test
   ```
4. Save the file.

### 🐧 Linux (Ubuntu/Debian)
1. Open Terminal.
2. Type `sudo nano /etc/hosts` and press Enter.
3. Add the following line at the bottom:
   ```text
   127.0.0.1   mysite.test
   ::1         mysite.test
   ```
4. Press `Ctrl+O` then `Enter` to save, and `Ctrl+X` to exit.

---

## 2. Start the Server

Ensure your Gaia Alpha server is running.

```bash
php -S localhost:8000 -t www
```

*Note: Accessing `mysite.test:8000` might require additional configuration depending on your local network setup, but Gaia Alpha's Multi-Site system typically resolves domains via the `Host` header. For local dev with `php -S`, it's easiest to interact via CLI first and verify database creation.*

---

## 3. Create & Import

Now that your domain is defined, use the CLI to create the site and import your package in one go.

```bash
php cli.php site:create mysite.test --import=path/to/your/site-package
```

**Example:**
```bash
php cli.php site:create mysite.test --import=docs/examples/enterprise_site
```

**What happens:**
- A new database is created at `my-data/sites/mysite.test.sqlite`.
- The schema is initialized.
- All pages, forms, and templates from the package are imported securely.

---

## 4. Verify

You can verify the import via the CLI:

```bash
# Check if database exists
ls -l my-data/sites/mysite.test.sqlite

# Run a query to count pages
php cli.php sql "SELECT count(*) FROM cms_pages" --site=mysite.test
```

---

## 5. Reset (Delete)

Once you are done validating, you may want to reset everything to a clean state.

Use the `site:delete` command:

```bash
php cli.php site:delete mysite.test
```

You will be asked to confirm the deletion. This removes the SQLite database file, but leaves your original Site Package source folder untouched.

---

## Summary Command List

| Goal | Command |
| :--- | :--- |
| **Create & Import** | `php cli.php site:create <domain> --import=<dir>` |
| **Delete** | `php cli.php site:delete <domain>` |
| **List Sites** | `php cli.php site:list` |
