#!/bin/sh
set -e

REPO_URL=${REPO_URL:-"https://github.com/applh/gaia-alpha.git"}
BRANCH=${BRANCH:-"main"}

# If the directory is empty or doesn't have .git, clone
git config --global --add safe.directory /var/www/html

if [ ! -d ".git" ]; then
    echo "Cloning repository from $REPO_URL..."
    # Clone into current directory (must be empty usually, or use . for current)
    # We use temporary dir and move to avoid basic file conflicts if any exist
    if [ "$(ls -A)" ]; then
         echo "Directory not empty, attempting to init and pull..."
         git init
         git remote add origin "$REPO_URL"
         git fetch origin
         git checkout -t "origin/$BRANCH" -f
    else
         git clone -b "$BRANCH" "$REPO_URL" .
    fi
else
    echo "Repository already exists. Pulling latest changes..."
    git pull origin "$BRANCH"
fi

# Create admin user
ADMIN_USER=${ADMIN_USER:-"admin"}
ADMIN_PASS=${ADMIN_PASS:-"admin"}
ADMIN_LEVEL=${ADMIN_LEVEL:-100}

echo "Ensuring admin user exists..."
# Ensure data dir exists so DB can be created if missing
mkdir -p /var/www/html/my-data
# Parse error to ignore "already exists" but let other errors show? 
# Simplest is to allow failure for now, or check output. 
# Since we want to update password if it exists? No, user only asked to create.
php cli.php user:create "$ADMIN_USER" "$ADMIN_PASS" "$ADMIN_LEVEL" || true

# Ensure permissions
mkdir -p /var/www/html/my-data
chown -R www-data:www-data /var/www/html

# Run the command passed to docker run (usually php-fpm)
exec "$@"
