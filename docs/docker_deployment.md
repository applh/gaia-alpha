# Docker Deployment Architectures

Gaia Alpha is designed to be flexible. This document outlines three common Docker deployment strategies, ranging from simple local testing to production-ready setups.

## 1. Simple PHP Container (Development/Testing)
This is the simplest way to run Gaia Alpha, using the built-in PHP development server (`php -S`). This is **not recommended for production**.

### `Dockerfile`
```dockerfile
FROM php:8.2-cli-alpine

# Install extensions (SQLite is included in basic PHP images usually, but we ensure PDO)
RUN docker-php-ext-install pdo pdo_sqlite

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
    command: sh -c "docker-php-ext-install pdo pdo_mysql && php-fpm"
    environment:
      - DB_HOST=db
      - DB_USER=gaia
      - DB_PASS=secret
      - DB_NAME=gaia_alpha
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

// Use MySQL DSN
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

define('GAIA_DB_DSN', "mysql:host=$host;dbname=$db;charset=utf8mb4");
// Note: You might need to handle user/pass connection in Database.php or pass them via DSN context if not using default PDO construction.
// Gaia Alpha's current Database class takes a single DSN argument.
// For MySQL with user/pass, you typically need to pass user/pass to the PDO constructor.
// *Architecture Note*: If using this mode, ensure you update GaiaAlpha\Controller\DbController::connect() or GaiaAlpha\Database to handle user/password auth if required by the DSN driver.
```
