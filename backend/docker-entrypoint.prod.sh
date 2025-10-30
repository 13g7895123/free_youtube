#!/bin/sh
set -e

echo "================================"
echo "Starting Backend - Production"
echo "================================"

# 顯示環境信息
echo "Environment: ${CI_ENVIRONMENT:-production}"
echo "PHP Version: $(php -v | head -n 1)"

# 等待資料庫就緒
echo "Waiting for database to be ready..."
MAX_RETRIES=30
RETRY_COUNT=0

until mysql -h "${MYSQL_HOST:-mariadb}" \
           -u "${MYSQL_USER:-app_user}" \
           -p"${MYSQL_PASSWORD}" \
           --skip-ssl \
           -e "SELECT 1" >/dev/null 2>&1; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "❌ Error: Database connection failed after ${MAX_RETRIES} retries"
        exit 1
    fi
    echo "Database is unavailable - retry ${RETRY_COUNT}/${MAX_RETRIES}"
    sleep 2
done

echo "✅ Database is ready!"

# 檢查數據庫是否存在
DB_EXISTS=$(mysql -h "${MYSQL_HOST:-mariadb}" \
                  -u "${MYSQL_USER:-app_user}" \
                  -p"${MYSQL_PASSWORD}" \
                  --skip-ssl \
                  -e "SHOW DATABASES LIKE '${MYSQL_DATABASE:-free_youtube}';" | grep -c "${MYSQL_DATABASE:-free_youtube}" || true)

if [ "$DB_EXISTS" -eq 0 ]; then
    echo "⚠️  Database does not exist, creating..."
    mysql -h "${MYSQL_HOST:-mariadb}" \
          -u root \
          -p"${MYSQL_ROOT_PASSWORD}" \
          --skip-ssl \
          -e "CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE:-free_youtube}\`;"
    echo "✅ Database created!"
fi

# 執行 migrations
echo "Running database migrations..."
if [ -f /var/www/html/database/migrations.sql ]; then
    mysql -h "${MYSQL_HOST:-mariadb}" \
          -u "${MYSQL_USER:-app_user}" \
          -p"${MYSQL_PASSWORD}" \
          --skip-ssl \
          "${MYSQL_DATABASE:-free_youtube}" < /var/www/html/database/migrations.sql 2>&1
    echo "✅ Migrations completed successfully!"
else
    echo "⚠️  Migration file not found, skipping..."
fi

# 清理舊的緩存
echo "Cleaning cache..."
rm -rf /var/www/html/writable/cache/* 2>/dev/null || true
echo "✅ Cache cleaned!"

# 顯示啟動信息
echo ""
echo "================================"
echo "🚀 Starting PHP Server"
echo "================================"
echo "Listening on: 0.0.0.0:8000"
echo "Document root: /var/www/html/public"
echo ""

# 啟動 PHP 內建伺服器
cd /var/www/html/public
exec php -S 0.0.0.0:8000 \
    -d display_errors=0 \
    -d error_reporting=E_ALL \
    -d log_errors=1 \
    -d error_log=/var/www/html/writable/logs/php-error.log
