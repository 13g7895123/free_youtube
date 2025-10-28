#!/bin/sh
set -e

echo "================================"
echo "Starting Backend Initialization"
echo "================================"

# 等待資料庫就緒
echo "Waiting for database to be ready..."
until mysql -h mariadb -u root -psecret --skip-ssl -e "SELECT 1" >/dev/null 2>&1; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is ready!"

# 執行 migrations
echo "Running database migrations..."
if [ -f /var/www/html/database/migrations.sql ]; then
    mysql -h mariadb -u root -psecret --skip-ssl free_youtube < /var/www/html/database/migrations.sql 2>&1
    echo "✅ Migrations completed successfully!"
else
    echo "⚠️  Migration file not found, skipping..."
fi

# 啟動 PHP 內建伺服器
echo "Starting PHP built-in server..."
cd /var/www/html/public
exec php -S 0.0.0.0:8000
