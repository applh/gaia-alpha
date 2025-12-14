#!/bin/bash
# Quick reinstall script for Gaia Alpha

# Change to project root directory
cd "$(dirname "$0")/.."

echo "🔄 Reinstalling Gaia Alpha..."

# 1. Backup existing data
if [ -d "my-data" ]; then
    echo "📦 Backing up existing data..."
    mv my-data my-data.backup.$(date +%s)
fi

# 2. Create fresh my-data directory
mkdir -p my-data

# 3. Run SQL migrations
echo "📊 Creating database tables..."
for sql_file in templates/sql/*.sql; do
    echo "  - Running $(basename $sql_file)..."
    sqlite3 my-data/db.sqlite < "$sql_file"
done

# 4. Create admin user
echo "👤 Creating admin user..."
echo "Enter admin username (default: admin):"
read username
username=${username:-admin}

echo "Enter admin password (default: admin):"
read -s password
password=${password:-admin}

# Hash password (simple md5 for now, should use better hashing)
password_hash=$(echo -n "$password" | md5)

sqlite3 my-data/db.sqlite "INSERT INTO users (username, password_hash, level) VALUES ('$username', '$password_hash', 100);"

# 5. Run seeder
echo "🌱 Seeding database..."
php cli.php data:seed

echo "✅ Installation complete!"
echo ""
echo "Login credentials:"
echo "  Username: $username"
echo "  Password: $password"
echo ""
echo "Start server: php -S localhost:8000 -t www"

