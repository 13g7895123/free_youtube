FROM php:8.1-fpm-alpine

# 安裝系統依賴
RUN apk add --no-cache \
    mysql-client \
    nginx \
    supervisor \
    curl \
    git \
    && docker-php-ext-install pdo pdo_mysql mysqli

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 複製應用程式
COPY . .

# 安裝 PHP 依賴
RUN composer install --no-dev --optimize-autoloader

# 設定權限
RUN chown -R www-data:www-data /var/www/html

# 暴露端口
EXPOSE 8080

# 啟動 PHP-FPM
CMD ["php-fpm"]
