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

# 設定權限
RUN chmod -R 777 /var/www/html/writable

# 暴露端口
EXPOSE 8000

# 啟動 CodeIgniter 開發伺服器
CMD ["php", "spark", "serve", "--host=0.0.0.0", "--port=8000"]
