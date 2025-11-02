FROM php:8.1-cli-alpine

# 安裝系統依賴和 PHP 擴展
RUN apk add --no-cache \
    mysql-client \
    curl \
    git \
    zip \
    unzip \
    icu-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli intl

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 複製 composer 檔案
COPY composer.json ./

# 安裝 PHP 依賴
RUN composer install --no-scripts --no-autoloader

# 複製應用程式
COPY . .

# 完成 composer 安裝
RUN composer dump-autoload --optimize

# 創建並設定 writable 目錄權限 (CI4 需要此目錄用於緩存、日誌、會話等)
RUN mkdir -p /var/www/html/writable/cache \
    /var/www/html/writable/logs \
    /var/www/html/writable/session \
    /var/www/html/writable/uploads \
    && chmod -R 777 /var/www/html/writable

# 複製並設定啟動腳本權限
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# 暴露端口
EXPOSE 8000

# 使用啟動腳本
ENTRYPOINT ["/docker-entrypoint.sh"]
