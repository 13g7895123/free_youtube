# 依賴自動檢查與安裝功能說明

## 功能概述

為了解決 `firebase/php-jwt` 套件在部署時未正確安裝的問題，我們在 `docker-entrypoint.prod.sh` 中加入了**自動依賴檢查與安裝**功能。

### 解決的問題

**原始問題：**
- 每次執行 `./deploy-prod.sh` 後，`firebase/php-jwt` 套件未安裝
- 需要手動執行 `docker exec ... composer require firebase/php-jwt`
- 部署流程不完整，容易出錯

**根本原因：**
- Dockerfile.prod 使用 `composer install --no-dev`
- composer.lock 可能與實際需求不同步
- 構建緩存導致依賴未正確更新

---

## 修正內容

### 1. docker-entrypoint.prod.sh 新增功能

**檢查邏輯：**
```bash
# 定義必需的依賴套件
REQUIRED_PACKAGES=(
    "firebase/php-jwt"
    "codeigniter4/framework"
)

# 檢查每個套件是否存在
for package in "${REQUIRED_PACKAGES[@]}"; do
    if [ ! -d "/var/www/html/vendor/${package}" ]; then
        # 記錄缺少的套件
        MISSING_PACKAGES+=("${package}")
    fi
done

# 如果有缺少的套件，自動安裝
if [ ${#MISSING_PACKAGES[@]} -gt 0 ]; then
    for package in "${MISSING_PACKAGES[@]}"; do
        composer require "${package}" --no-interaction --prefer-dist
    done
    composer dump-autoload --optimize --classmap-authoritative
fi
```

**執行時機：**
- 資料庫連接成功後
- 執行 migrations 之前
- 應用啟動之前

### 2. Dockerfile.prod 權限調整

**變更說明：**
```dockerfile
# 移除 USER appuser（不在 Dockerfile 中切換用戶）
# 改為在 entrypoint 中切換用戶

# 安裝 su-exec 工具
RUN apk add --no-cache su-exec

# entrypoint 以 root 執行（可執行 composer）
# 應用啟動時才切換到 appuser（提升安全性）
```

**安全性說明：**
- entrypoint 以 root 執行（需要安裝依賴）
- 應用啟動時使用 `su-exec` 切換到 `appuser`
- 確保應用進程以非 root 用戶執行

---

## 使用方式

### 正常部署（無需手動介入）

```bash
# 執行部署腳本
./deploy-prod.sh --full

# 或快速部署
./deploy-prod.sh
```

**部署過程中會自動：**
1. 檢查 `firebase/php-jwt` 是否存在
2. 若缺少，自動執行 `composer require firebase/php-jwt`
3. 重新優化 autoloader
4. 繼續正常啟動流程

### 檢查日誌

```bash
# 查看 backend 日誌
docker compose --env-file .env.prod -f docker-compose.prod.yml logs backend

# 應該看到類似輸出：
# Checking PHP dependencies...
# ✅ Found: firebase/php-jwt
# ✅ Found: codeigniter4/framework
# ✅ All required dependencies are installed
```

**或者（首次部署/缺少依賴時）：**
```
Checking PHP dependencies...
⚠️  Missing package: firebase/php-jwt
✅ Found: codeigniter4/framework
⚠️  Found 1 missing package(s), installing...
Installing firebase/php-jwt...
✅ firebase/php-jwt installed successfully!
Optimizing autoloader...
✅ All missing dependencies installed!
```

---

## 驗證步驟

### 1. 檢查容器內的 vendor 目錄

```bash
# 檢查 firebase 目錄是否存在
docker exec free_youtube_backend_prod ls -la /var/www/html/vendor/firebase/

# 應該看到：
# drwxr-xr-x 3 appuser appuser 4096 Nov  3 12:00 php-jwt
```

### 2. 測試 JWT 功能

```bash
# 測試需要 JWT 的 API 端點
curl -i http://localhost:9204/api/auth/line/login

# 應該正常回應，不會出現 Class not found 錯誤
```

### 3. 檢查 Composer 套件列表

```bash
# 查看已安裝的套件
docker exec free_youtube_backend_prod composer show | grep firebase

# 應該輸出：
# firebase/php-jwt  v6.11.1  A simple library to encode and decode JSON Web Tokens (JWT) in PHP.
```

---

## 測試場景

### 場景 1：首次部署（全新環境）

```bash
# 1. 清理所有容器和映像
docker compose -f docker-compose.prod.yml down -v
docker rmi $(docker images | grep free_youtube | awk '{print $3}')

# 2. 執行完全重建
./deploy-prod.sh --full

# 3. 檢查日誌
docker compose -f docker-compose.prod.yml logs backend | grep "dependencies"

# 預期結果：
# ✅ 所有依賴自動安裝
# ✅ 無需手動介入
```

### 場景 2：更新部署（已有環境）

```bash
# 1. 快速部署（使用緩存）
./deploy-prod.sh

# 2. 檢查日誌
docker compose -f docker-compose.prod.yml logs backend | grep "dependencies"

# 預期結果：
# ✅ 檢測到依賴已存在
# ✅ 跳過安裝，快速啟動
```

### 場景 3：手動刪除依賴（模擬問題）

```bash
# 1. 進入容器刪除 firebase 目錄
docker exec -it free_youtube_backend_prod sh
rm -rf /var/www/html/vendor/firebase
exit

# 2. 重啟容器
docker compose -f docker-compose.prod.yml restart backend

# 3. 檢查日誌
docker compose -f docker-compose.prod.yml logs backend | grep "dependencies"

# 預期結果：
# ⚠️  檢測到缺少 firebase/php-jwt
# ✅ 自動重新安裝
# ✅ 應用正常啟動
```

---

## 疑難排解

### 問題 1：Composer 安裝失敗

**症狀：**
```
❌ Failed to install firebase/php-jwt
```

**檢查步驟：**
```bash
# 1. 檢查網路連接
docker exec free_youtube_backend_prod ping -c 3 packagist.org

# 2. 檢查 composer.json 格式
docker exec free_youtube_backend_prod cat /var/www/html/composer.json

# 3. 手動測試安裝
docker exec -it free_youtube_backend_prod sh
cd /var/www/html
composer require firebase/php-jwt -vvv
```

**解決方案：**
- 確認網路連接正常
- 檢查 composer.json 語法正確
- 清除 Composer 緩存：`docker exec free_youtube_backend_prod composer clear-cache`

### 問題 2：權限錯誤

**症狀：**
```
Permission denied: /var/www/html/vendor
```

**原因：**
- entrypoint 沒有以 root 執行
- 或 vendor 目錄權限不正確

**解決方案：**
```bash
# 1. 確認 Dockerfile 沒有設置 USER appuser（在 ENTRYPOINT 之前）

# 2. 重新構建映像
./deploy-prod.sh --full

# 3. 檢查容器用戶
docker exec free_youtube_backend_prod id
# 應該在啟動前顯示 uid=0(root)
```

### 問題 3：依賴檢查未執行

**症狀：**
日誌中看不到 "Checking PHP dependencies..."

**檢查步驟：**
```bash
# 1. 確認 entrypoint 腳本存在且可執行
docker exec free_youtube_backend_prod ls -la /docker-entrypoint.sh

# 2. 查看完整日誌
docker compose -f docker-compose.prod.yml logs backend

# 3. 檢查腳本語法
docker exec free_youtube_backend_prod sh -n /docker-entrypoint.sh
```

**解決方案：**
- 確認 `docker-entrypoint.prod.sh` 已正確複製到映像
- 檢查腳本權限：`chmod +x docker-entrypoint.prod.sh`
- 重新構建映像

---

## 新增依賴檢查

### 如何新增更多必需套件

編輯 `backend/docker-entrypoint.prod.sh`，在 `REQUIRED_PACKAGES` 陣列中新增：

```bash
REQUIRED_PACKAGES=(
    "firebase/php-jwt"
    "codeigniter4/framework"
    "your-vendor/your-package"  # 新增你的套件
)
```

### 如何檢查 PHP 擴展

在依賴檢查後加入：

```bash
# 檢查 PHP 擴展
echo "Checking PHP extensions..."
REQUIRED_EXTENSIONS=("pdo_mysql" "mysqli" "intl" "opcache")

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "$ext"; then
        echo "❌ Missing PHP extension: $ext"
        exit 1
    else
        echo "✅ Found extension: $ext"
    fi
done
```

---

## 效能影響

### 首次部署
- **額外時間：** 0-30 秒（視缺少套件數量）
- **網路使用：** 下載缺少的套件（約 1-5 MB）

### 後續部署
- **額外時間：** < 1 秒（僅檢查，不安裝）
- **網路使用：** 無

### 建議
- ✅ 保持此功能啟用（容錯性高）
- ✅ 定期更新 composer.lock
- ✅ 首次部署使用 `--full` 模式

---

## 安全性說明

### Root 用戶執行 Composer
**Q: 為何 entrypoint 要以 root 執行？**
A: Composer 需要寫入 `/var/www/html/vendor` 目錄，需要 root 權限。

**Q: 這樣安全嗎？**
A: 是的，因為：
1. Composer 僅在啟動時執行（非運行時）
2. 應用進程使用 `su-exec` 切換到 `appuser` 執行
3. 僅安裝 composer.json 定義的套件（不執行任意代碼）

### 最佳實踐
- ✅ entrypoint 以 root 執行（安裝依賴）
- ✅ 應用進程以 appuser 執行（運行應用）
- ✅ 僅安裝預定義的必需套件
- ✅ 使用 `--no-interaction` 避免惡意輸入

---

## 相關文件

- [backend/docker-entrypoint.prod.sh](../backend/docker-entrypoint.prod.sh) - 啟動腳本
- [backend/Dockerfile.prod](../backend/Dockerfile.prod) - 生產環境 Dockerfile
- [deploy-prod.sh](../deploy-prod.sh) - 部署腳本
- [composer.json](../backend/composer.json) - PHP 依賴定義

---

## 總結

### 優點
- ✅ **自動化**：無需手動安裝依賴
- ✅ **容錯性**：自動修復缺少的套件
- ✅ **可擴展**：輕鬆新增更多檢查項目
- ✅ **透明度**：清楚的日誌輸出
- ✅ **安全性**：應用以非 root 用戶執行

### 限制
- ⚠️ 需要網路連接（首次安裝時）
- ⚠️ 首次部署可能稍慢（安裝依賴）
- ⚠️ 僅檢查預定義的套件列表

### 建議
- 定期檢查並更新 `REQUIRED_PACKAGES` 列表
- 保持 `composer.lock` 與 `composer.json` 同步
- 使用 `./deploy-prod.sh --full` 進行重大更新
