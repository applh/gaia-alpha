# Domain Names, Subdomains, and Wildcards

This guide explains how to configure your DNS and Gaia Alpha setup to handle custom domains, subdomains, and wildcard certificates.

## 1. Basic DNS Configuration

To make your VPS accessible via a domain name, you need to configure DNS records at your registrar (e.g., Namecheap, GoDaddy, Cloudflare).

### Required Records

| Type | Name | Content | Purpose |
| :--- | :--- | :--- | :--- |
| **A** | `@` | `203.0.113.10` | Points `example.com` to your VPS IP |
| **CNAME** | `www` | `example.com` | Points `www.example.com` to your main domain |

> [!TIP]
> If using Cloudflare, turn **Proxy Status: Only DNS** (Grey Cloud) initially to ensure Let's Encrypt can verify your server directly. You can generic Orange Cloud later, but it adds complexity with SSL modes (use "Full (Strict)").

## 2. Configuring Traefik for Multiple Domains

By default, our `docker-compose.prod.yml` listens for `DOMAIN_NAME`. To add more domains (e.g., `www.example.com`), update the Traefik labels in the **Nginx** service.

**Edit `docker/deployment/docker-compose.prod.yml`**:

```yaml
services:
  web:
    # ...
    labels:
      - "traefik.enable=true"
      # MODIFY THIS LINE:
      - "traefik.http.routers.gaia-web.rule=Host(`${DOMAIN_NAME}`) || Host(`www.${DOMAIN_NAME}`)"
      # ...
```

Restart the container to apply changes:
```bash
docker compose -f docker/deployment/docker-compose.prod.yml up -d
```

## 3. Comparison: Specific Subdomains vs. Wildcards

When adding subdomains (e.g., `api.example.com`, `admin.example.com`), you can choose between two strategies.

| Feature | Option A: Specific Subdomains | Option B: Wildcard (`*.example.com`) |
| :--- | :--- | :--- |
| **Complexity** | **Low** | Medium (Requires DNS API) |
| **DNS Config** | Standard `A` or `CNAME` records | Requires `DNS-01` Challenge |
| **SSL Validation** | **HTTP-01** (Default, easier) | **DNS-01** (Requires API Keys) |
| **Privacy** | New subdomains appear in Certificate Logs | **High** (Logs only show `*.example.com`) |
| **Maintenance** | Must update `labels` for every new subdomain | **Zero** (All subdomains work automatically) |
| **Best For** | Static list of 1-5 subdomains | SaaS, multi-tenant apps, or >10 subdomains |

## 4. Wildcard Certificates (`*.example.com`)

Standard SSL validation uses **HTTP-01** (Traefik puts a file on your web server). However, **Wildcard Certificates** require **DNS-01** validation (Traefik must talk to your DNS provider API to create a TXT record).

### Why use Wildcards?
-   You want `user1.example.com`, `user2.example.com`, etc., to work automatically.
-   You don't want to issue a new certificate for every subdomain.

### Configuration (Example using Cloudflare)

1.  **Get API Token**: Generate a Cloudflare API Token with `Zone:DNS:Edit` permissions.
2.  **Update `.env`**:
    ```bash
    CF_API_EMAIL=your@email.com
    CF_DNS_API_TOKEN=your_token_here
    ```
3.  **Update `docker-compose.prod.yml`**:

    **Traefik Command**:
    ```yaml
    command:
      # ...
      # Remove HTTP Challenge lines
      # - "--certificatesresolvers.myresolver.acme.httpchallenge=true"
      # ...
      # Add DNS Challenge
      - "--certificatesresolvers.myresolver.acme.dnschallenge=true"
      - "--certificatesresolvers.myresolver.acme.dnschallenge.provider=cloudflare"
      # ...
    ```

    **Traefik Environment**:
    ```yaml
    environment:
      - CF_API_EMAIL=${CF_API_EMAIL}
      - CF_DNS_API_TOKEN=${CF_DNS_API_TOKEN}
    ```

    **Web Service Labels**:
    ```yaml
    labels:
        # Match root AND valid subdomains
        - "traefik.http.routers.gaia-web.rule=Host(`${DOMAIN_NAME}`) || HostRegexp(`{subdomain:[a-z0-9-]+}.${DOMAIN_NAME}`)"
        - "traefik.http.routers.gaia-web.tls.domains[0].main=${DOMAIN_NAME}"
        - "traefik.http.routers.gaia-web.tls.domains[0].sans=*.${DOMAIN_NAME}"
    ```

### Supported Providers

Traefik supports dozens of providers. Here are the configurations for common ones:

#### OVH
1.  **Get Credentials**: Create an API key at [api.ovh.com/createApp/](https://api.ovh.com/createApp/) with `GET/PUT/POST/DELETE` rights on `/domain/zone/*`.
2.  **Update `.env`**:
    ```bash
    OVH_ENDPOINT=ovh-eu # or ovh-ca
    OVH_APPLICATION_KEY=your_app_key
    OVH_APPLICATION_SECRET=your_app_secret
    OVH_CONSUMER_KEY=your_consumer_key
    ```
3.  **Update `docker-compose.prod.yml`**:
    ```yaml
    command:
      # ...
      - "--certificatesresolvers.myresolver.acme.dnschallenge.provider=ovh"
    environment:
      - OVH_ENDPOINT=${OVH_ENDPOINT}
      - OVH_APPLICATION_KEY=${OVH_APPLICATION_KEY}
      - OVH_APPLICATION_SECRET=${OVH_APPLICATION_SECRET}
      - OVH_CONSUMER_KEY=${OVH_CONSUMER_KEY}
    ```

#### IONOS
1.  **Get Credentials**: Get your API Key from the IONOS Developer Portal. It usually comes in a `prefix.secret` format.
2.  **Update `.env`**:
    ```bash
    IONOS_API_KEY=your_public_prefix.your_secret_key
    ```
3.  **Update `docker-compose.prod.yml`**:
    ```yaml
    command:
      # ...
      - "--certificatesresolvers.myresolver.acme.dnschallenge.provider=ionos"
    environment:
      - IONOS_API_KEY=${IONOS_API_KEY}
    ```

For other providers (AWS, DigitalOcean, Google Cloud, etc.), refer to the [Traefik DNS Challenge Documentation](https://doc.traefik.io/traefik/https/acme/#providers).
