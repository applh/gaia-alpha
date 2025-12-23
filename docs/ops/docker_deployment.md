# Docker Deployment Architectures

Gaia Alpha is designed to be flexible. This document outlines three common Docker deployment strategies, ranging from simple local testing to production-ready setups.

## 1. Simple PHP Container (Development/Testing)
This is the simplest way to run Gaia Alpha, using the built-in PHP development server (`php -S`). This is **not recommended for production**.

### `Dockerfile`
```dockerfile
FROM php:8.2-cli-alpine

# Install extensions
# Note: We include drivers for all supported databases
RUN docker-php-ext-install pdo pdo_sqlite pdo_mysql pdo_pgsql

WORKDIR /app

# Copy application code
COPY . /app

# Expose port
EXPOSE 8000

# Start server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "www"]
```

### Run Command
```bash
docker build -t gaia-simple .
docker run -p 8000:8000 -v $(pwd)/my-data:/app/my-data gaia-simple
```

### Development Mode (Live Sync)
To reflect code changes immediately without rebuilding the image, mount your local directory to the container's application root (`/app`).

```bash
docker run -p 8000:8000 \
    -v $(pwd):/app \
    gaia-simple
```
*Note: This mounts your current directory over `/app` in the container, so any changes you make locally are instantly visible.*


## 2. Nginx + PHP-FPM (Production-Ready Code)
For better performance and stability, use Nginx as a reverse proxy in front of PHP-FPM.

### `docker-compose.yml`
```yaml
version: '3.8'

services:
  web:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./www:/var/www/html/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

  php:
    image: php:8.2-fpm-alpine
    volumes:
      - .:/var/www/html
    # Ensure dependencies for SQLite/Media are installed
    command: sh -c "docker-php-ext-install pdo pdo_sqlite && php-fpm"
```

### `nginx.conf`
```nginx
fastcgi_cache_path /var/cache/nginx/gaia_assets levels=1:2 keys_zone=gaia_assets:10m inactive=60m;

server {
    listen 80;
    server_name localhost;
    root /var/www/html/www;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache Minified Assets (FastCGI Cache)
    # This caches the output of PHP for minified assets, serving them as static files
    location ~ ^/min/(css|js)/ {
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        
        fastcgi_cache gaia_assets;
        fastcgi_cache_valid 200 60m;
        fastcgi_cache_use_stale error timeout updating http_500;
        fastcgi_cache_lock on;
        add_header X-FastCGI-Cache $upstream_cache_status;
    }
    
    # Secure access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

## 3. Nginx + PHP-FPM + External Database (Enterprise)
This architecture separates the database into its own container. While Gaia Alpha defaults to SQLite, you can switch to MySQL/MariaDB or PostgreSQL easily.

### `docker-compose.yml`
```yaml
version: '3.8'

services:
  web:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./www:/var/www/html/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

  php:
    image: php:8.2-fpm-alpine
    volumes:
      - .:/var/www/html
      - ./my-config.docker.php:/var/www/html/my-config.php
    # Install MySQL/Postgres drivers
    command: sh -c "docker-php-ext-install pdo pdo_mysql pdo_pgsql && php-fpm"
    environment:
      - GAIA_DB_TYPE=mysql # Options: sqlite, mysql, pgsql
      - GAIA_DB_HOST=db
      - GAIA_DB_NAME=gaia_alpha
      - GAIA_DB_USER=gaia
      - GAIA_DB_PASS=secret
    depends_on:
      - db

  db:
    image: mariadb:10.6
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: gaia_alpha
      MYSQL_USER: gaia
      MYSQL_PASSWORD: secret
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### `my-config.docker.php`
You need to inject a configuration file that tells Gaia Alpha to use the external database.

```php
<?php
// my-config.docker.php mapped to /var/www/html/my-config.php

define('GAIA_DATA_PATH', '/var/www/html/my-data');

// Example: Using Environment Variables to configure DB
// In a real scenario, InstallController usually generates the config file.
// However, if you are manually configuring for Docker:

$dbType = getenv('GAIA_DB_TYPE') ?: 'sqlite';

if ($dbType === 'mysql') {
    $host = getenv('GAIA_DB_HOST');
    $db   = getenv('GAIA_DB_NAME');
    $user = getenv('GAIA_DB_USER');
    $pass = getenv('GAIA_DB_PASS');
    define('GAIA_DB_DSN', "mysql:host=$host;dbname=$db;charset=utf8mb4");
    define('GAIA_DB_USER', $user);
    define('GAIA_DB_PASS', $pass);
} elseif ($dbType === 'pgsql') {
    $host = getenv('GAIA_DB_HOST');
    $db   = getenv('GAIA_DB_NAME');
    $user = getenv('GAIA_DB_USER');
    $pass = getenv('GAIA_DB_PASS');
    define('GAIA_DB_DSN', "pgsql:host=$host;dbname=$db");
    define('GAIA_DB_USER', $user);
    define('GAIA_DB_PASS', $pass);
} else {
    // SQLite Default
    define('GAIA_DB_DSN', 'sqlite:' . GAIA_DATA_PATH . '/gaia.sqlite');
}
```


## 4. Multimedia Processing Server (Feature-Rich)
This setup includes all necessary tools for Gaia Alpha's advanced features, including `ffmpeg` for video processing and `qrencode` for QR code generation.

### Directory Structure
We have provided a ready-to-use configuration in `docker/multimedia`.

```
docker/
  multimedia/
    Dockerfile          # PHP + FFMpeg + Qrencode + GD
    docker-compose.yml  # Nginx + Custom PHP Build
    nginx.conf          # Nginx Config with higher upload limits
```

### `docker/multimedia/Dockerfile`
This image extends `php:8.2-fpm-alpine` and adds:
- **System**: `ffmpeg`, `qrencode`, `imagemagick`, `sqlite`, `git`
- **PHP extensions**: `gd`, `imagick`, `intl`, `mbstring`, `zip`, `exif`, `bcmath`
- **Configuration**: Increased memory limit (512M) and upload size (100M)

### How to Run
Navigate to the `docker/multimedia` directory and run:

```bash
cd docker/multimedia
docker-compose up --build -d
```

The application will be available at `http://localhost:8080`.


## 5. Instant Deployment (Auto-Clone)
This setup automatically clones the repository from GitHub when the container starts. This is ideal for quick deployments on fresh servers without manually transferring code.

### Directory Structure
`docker/deployment/` contains the setup.

### Usage
1. **Configure**: Edit `docker/deployment/docker-compose.yml` to set your `REPO_URL` (defaults to main Gaia Alpha repo).
2. **Run**:
   ```bash
   cd docker/deployment
   docker compose up -d --build
   ```
3. **Wait**: The container will clone the repo on first launch. It may take a few seconds before the site is available at `http://localhost:8090`.

### Resetting the Environment
To completely reset the environment (including the database and codebase) and verify the auto-setup process:

```bash
docker compose down -v
docker compose up -d --build
```
*Warning: This destroys all data in the volumes.*

## 6. Performance Comparison



| Feature | Simple PHP | Nginx + PHP-FPM | Enterprise (Split DB) |
| :--- | :---: | :---: | :---: |
| **Setup Complexity** | Low | Medium | High |
| **Static Asset Speed** | Slow (Served by PHP) | Fast (Served by Nginx) | Fast (Served by Nginx) |
| **Concurrency** | Low (Single-threaded) | High (Multithreaded FPM) | High + Scalable DB |
| **Resource Usage** | Low | Medium | High (Multiple containers) |
| **Scalability** | None | Vertical (CPU/RAM) | Horizontal (Separate DB node) |
| **Best Use Case** | Local Dev / Testing | Production (Small/Medium) | Production (High Traffic) |
