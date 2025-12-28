# Deploying Gaia Alpha on a VPS

This guide walks you through setting up a production-ready environment for Gaia Alpha using Docker and Traefik on a Virtual Private Server (VPS).

## Prerequisites

-   **VPS**: A server running a standard Linux distribution (Ubuntu 22.04 LTS recommended) with at least 2GB of RAM.
-   **Domain Name**: A domain (e.g., `example.com`) pointed to your VPS IP address.
-   **SSH Access**: Root or sudo access to the server.

## 1. Initial Server Setup

Update your system packages and install basic utilities:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git
```

## 2. Install Docker & Docker Compose

We will use the official Docker installation script for convenience:

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

Verify the installation:

```bash
docker --version
docker compose version
```

## 3. Deployment Configuration

Clone the Gaia Alpha repository (or your fork) to your server:

```bash
git clone https://github.com/applh/gaia-alpha.git /opt/gaia-alpha
cd /opt/gaia-alpha
```

### The Environment File

Create a `.env` file for your secret credentials:

```bash
# .env
DOMAIN_NAME=example.com
ACME_EMAIL=your-email@example.com
# Database Credentials
DB_ROOT_PASS=secure_root_password
GAIA_DB_PASS=secure_db_password
```

### Production Docker Compose

We use the special production compose file `docker/deployment/docker-compose.prod.yml` which differs from the dev version:
-   **Traefik**: Acts as a reverse proxy, handling incoming traffic on ports 80 and 443.
-   **Automatic SSL**: Traefik automatically requests and renews certificates from Let's Encrypt.
-   **Internal Networking**: The database and PHP backend are not exposed to the public internet.

## 4. Deploying the Stack

First, create the external proxy network:

```bash
docker network create proxy
```

Then start the application stack:

```bash
docker compose -f docker/deployment/docker-compose.prod.yml up -d
```

Check the status of your containers:

```bash
docker compose -f docker/deployment/docker-compose.prod.yml ps
```

Your site should now be accessible at `https://example.com` with a valid SSL certificate.

## 5. Architecture & Performance Comparison

Why use this Docker/Traefik setup versus other common methods?

| Feature | Docker + Traefik (Recommended) | Native LAMP/LEMP | Managed PaaS (Heroku/Render) |
| :--- | :--- | :--- | :--- |
| **Setup Complexity** | Medium (One file to manage) | High (Manual config of 4+ services) | Low (Push to deploy) |
| **SSL Management** | **Automatic** (Zero-touch) | Manual (Certbot, cron jobs) | Automatic |
| **Isolation** | **High** (Containerized) | Low (Shared system libs) | High |
| **Portability** | **Excellent** (Run anywhere) | Poor (OS dependent) | Low (Vendor lock-in) |
| **Performance** | **Near Native** | Native | Variable (Shared resources) |
| **Cost** | Low (VPS cost only) | Low (VPS cost only) | High (Per service pricing) |

### Performance Note
The overhead of Docker networking and one layer of reverse proxy (Traefik) is negligible (< 1ms) for most PHP applications. The benefits of automated HTTPS and identical environments across dev/prod far outweigh the microscopic raw performance difference of a bare-metal setup.

## 6. Maintenance

### Updating Application Code

To update Gaia Alpha to the latest version:

```bash
cd /opt/gaia-alpha
# Pull latest code
git pull origin main
# Restart containers to apply changes
docker compose -f docker/deployment/docker-compose.prod.yml restart php
```

### Backups

The database data is stored in the `db_data` volume. To back it up:

```bash
# Dump the database from the running container
docker exec gaia-alpha-db-1 mysqldump -u gaia_user -p'secure_db_password' gaia > backup.sql
```

## Appendix: VPS Provider Cost Comparison

Choosing the right VPS is critical for running the Mail Server (ClamAV), which requires **4GB+ RAM**.

| Provider | Plan Name | RAM | vCPU | Storage | Price (Approx.) | Recommendation |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **GCP** | `e2-medium` | 4 GB | 2 | *(Separate)* | ~$25.00/mo | **Flexible**, but expensive. Good for scaling. |
| **OVHcloud** | `VPS-1` | **8 GB** | 4 | 75 GB | ~$5.00/mo | **Best Value**. 8GB RAM ensures ClamAV never crashes. |
| **IONOS** | `VPS Linux M` | 4 GB | 2 | 120 GB | ~$4.00/mo | **Good Budget Option**. Promotional pricing usually applies. |

> [!TIP]
> **Why OVHcloud?**
> The Mail Server with ClamAV enabled consumes ~2GB of RAM alone. A 4GB instance is the *minimum* safe requirement. OVH provides **8GB** for the price of a coffee, giving you ample headroom for the Gaia application, database, and Redis/Worker processes.
