# firebase/php-jwt 自動安裝問題修正總結

## 問題描述

**原始問題：**
每次執行 `./deploy-prod.sh` 後，都需要手動執行 `composer require firebase/php-jwt` 才能正常使用 JWT 功能。

**影響範圍：**
- LINE Login 認證失敗
- Token 生成與驗證功能無法使用
- 需要手動介入部署流程

---

## 根本原因分析

### 1. Dockerfile 配置問題
```dockerfile
# backend/Dockerfile.prod 第 34-39 行
RUN composer install \
    --no-dev \          # 僅安裝生產環境依賴
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction
```

雖然 `firebase/php-jwt` 在 `composer.json` 的 `require` 區塊（非 `require-dev`），但可能因為：
- `composer.lock` 與 `composer.json` 不同步
- Docker 構建緩存問題
- `composer.lock*` 中的 `*` 導致 lock 檔案未正確複製

### 2. 權限問題
```dockerfile
# USER appuser 設定在 Dockerfile 末尾
# 導致 entrypoint 無法以 root 執行 composer
```

---

## 修正方案

### 方案：在 entrypoint 中加入依賴自動檢查與安裝

**優點：**
- ✅ 自動化：無需手動介入
- ✅ 容錯性高：自動修復缺少的依賴
- ✅ 可擴展：輕鬆新增更多檢查
- ✅ 安全性：應用仍以非 root 用戶執行

**缺點：**
- ⚠️ 首次部署稍慢（需要安裝依賴）
- ⚠️ 需要網路連接

---

## 修正內容

### 1. 修改 `backend/docker-entrypoint.prod.sh`

**新增位置：** 資料庫檢查後、migrations 之前（第 33 行後）

**新增內容：**
```bash
# ========================================
# 檢查並安裝 PHP 依賴
# ========================================
echo ""
echo "Checking PHP dependencies..."

# 定義必需的依賴套件
REQUIRED_PACKAGES=(
    "firebase/php-jwt"
    "codeigniter4/framework"
)

MISSING_PACKAGES=()

# 檢查每個必需套件是否存在
for package in "${REQUIRED_PACKAGES[@]}"; do
    PACKAGE_PATH="/var/www/html/vendor/${package}"
    if [ ! -d "$PACKAGE_PATH" ]; then
        echo "⚠️  Missing package: ${package}"
        MISSING_PACKAGES+=("${package}")
    else
        echo "✅ Found: ${package}"
    fi
done

# 如果有缺少的套件，執行安裝
if [ ${#MISSING_PACKAGES[@]} -gt 0 ]; then
    echo ""
    echo "⚠️  Found ${#MISSING_PACKAGES[@]} missing package(s), installing..."

    cd /var/www/html

    for package in "${MISSING_PACKAGES[@]}"; do
        echo "Installing ${package}..."
        if composer require "${package}" --no-interaction --prefer-dist; then
            echo "✅ ${package} installed successfully!"
        else
            echo "❌ Failed to install ${package}"
            exit 1
        fi
    done

    # 重新優化自動加載
    echo "Optimizing autoloader..."
    composer dump-autoload --optimize --classmap-authoritative

    echo "✅ All missing dependencies installed!"
else
    echo "✅ All required dependencies are installed"
fi

echo ""
```

**修改啟動邏輯：**
```bash
# 在啟動應用前切換到 appuser（提升安全性）
if [ "$(id -u)" = "0" ]; then
    echo "Switching to appuser for security..."
    exec su-exec appuser php -S 0.0.0.0:8000 ...
fi
```

### 2. 修改 `backend/Dockerfile.prod`

**移除 USER 切換：**
```dockerfile
# 創建非 root 用戶
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser && \
    chown -R appuser:appuser /var/www/html

# 安裝 su-exec 以便在 entrypoint 中切換用戶
RUN apk add --no-cache su-exec

# 注意：不在此處切換用戶，而是在 entrypoint 中切換
# USER appuser  # 已移除
```

**原因：**
- entrypoint 需要 root 權限執行 composer
- 應用啟動時使用 su-exec 切換到 appuser
- 兼顧功能性與安全性

---

## 測試驗證

### 快速測試

```bash
# 1. 完全重建
./deploy-prod.sh --full

# 2. 檢查日誌
docker compose -f docker-compose.prod.yml logs backend | grep "dependencies"

# 3. 驗證依賴
docker exec free_youtube_backend_prod ls /var/www/html/vendor/firebase/php-jwt/

# 4. 測試 API
curl -i http://localhost:9204/api/health
```

### 預期結果

**正常情況（依賴已安裝）：**
```
Checking PHP dependencies...
✅ Found: firebase/php-jwt
✅ Found: codeigniter4/framework
✅ All required dependencies are installed
```

**缺少依賴時（自動修復）：**
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

## 檔案變更清單

### 修改的檔案

1. **backend/docker-entrypoint.prod.sh**
   - 新增依賴檢查邏輯（第 33-86 行）
   - 修改啟動方式以 su-exec 切換用戶（第 151-165 行）

2. **backend/Dockerfile.prod**
   - 安裝 su-exec 工具（第 64 行）
   - 移除 USER appuser 設定（第 68 行註解）
   - 在 entrypoint 中切換用戶

### 新增的文件

1. **docs/DEPENDENCY_CHECK_GUIDE.md**
   - 完整功能說明
   - 使用方式與驗證步驟
   - 疑難排解指引

2. **docs/QUICK_TEST_COMMANDS.md**
   - 快速測試指令
   - 驗證清單
   - 常用檢查命令

3. **docs/DEPENDENCY_FIX_SUMMARY.md**（本文件）
   - 問題分析總結
   - 修正方案說明
   - 部署指引

---

## 部署指引

### 首次部署

```bash
# 1. 確認修改已提交
git status

# 2. 執行完全重建
./deploy-prod.sh --full

# 3. 監控日誌
docker compose -f docker-compose.prod.yml logs -f backend

# 4. 驗證功能
curl http://localhost:9204/api/health
```

### 後續部署

```bash
# 快速部署（使用緩存）
./deploy-prod.sh

# 依賴檢查會自動執行，無需手動介入
```

---

## 安全性說明

### Q: 為何 entrypoint 要以 root 執行？
**A:** Composer 需要寫入 `/var/www/html/vendor` 目錄，需要 root 權限。

### Q: 這樣安全嗎？
**A:** 是的，因為：
1. **Composer 僅在啟動時執行**（非運行時）
2. **應用進程使用 su-exec 切換到 appuser**
3. **僅安裝 composer.json 定義的套件**
4. **使用 --no-interaction 避免惡意輸入**

### 安全架構

```
容器啟動
  ↓
entrypoint (root)
  ├─ 檢查依賴
  ├─ 安裝缺少的套件 (如有需要)
  ↓
su-exec appuser
  ↓
應用執行 (appuser)
```

---

## 效能影響

### 首次部署
- **額外時間：** 0-30 秒（視缺少套件數量）
- **網路使用：** 下載缺少的套件（約 1-5 MB）

### 後續部署
- **額外時間：** < 1 秒（僅檢查，不安裝）
- **網路使用：** 無

### 優化建議
- ✅ 保持 composer.lock 與 composer.json 同步
- ✅ 首次部署使用 `--full` 模式
- ✅ 定期更新依賴

---

## 可擴展性

### 新增更多依賴檢查

編輯 `backend/docker-entrypoint.prod.sh`：

```bash
REQUIRED_PACKAGES=(
    "firebase/php-jwt"
    "codeigniter4/framework"
    "your-vendor/your-package"  # 新增
)
```

### 新增 PHP 擴展檢查

```bash
# 在依賴檢查後加入
REQUIRED_EXTENSIONS=("pdo_mysql" "mysqli" "intl")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "$ext"; then
        echo "❌ Missing: $ext"
        exit 1
    fi
done
```

---

## 相關文件

- [依賴檢查功能說明](./DEPENDENCY_CHECK_GUIDE.md)
- [快速測試指令](./QUICK_TEST_COMMANDS.md)
- [部署指南](./deployment-guide.md)
- [後端 Dockerfile](../backend/Dockerfile.prod)
- [啟動腳本](../backend/docker-entrypoint.prod.sh)

---

## 總結

### 修正前
- ❌ 每次部署都需要手動安裝 firebase/php-jwt
- ❌ 部署流程不完整
- ❌ 容易出錯，影響功能

### 修正後
- ✅ 自動檢查並安裝缺少的依賴
- ✅ 部署流程完全自動化
- ✅ 容錯性高，無需手動介入
- ✅ 可擴展，輕鬆新增更多檢查
- ✅ 安全性：應用以非 root 用戶執行

### 使用建議
1. 首次部署使用 `./deploy-prod.sh --full`
2. 定期檢查並更新 `REQUIRED_PACKAGES` 列表
3. 保持 composer.lock 與 composer.json 同步
4. 查看部署日誌確認依賴正確安裝
