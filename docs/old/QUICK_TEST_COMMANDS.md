# 快速測試指令

## 測試依賴自動安裝功能

### 1. 完整測試（推薦）

```bash
# 停止並清理所有容器
docker compose -f docker-compose.prod.yml --env-file .env.prod down

# 完全重建（不使用緩存）
./deploy-prod.sh --full

# 查看日誌確認依賴檢查
docker compose -f docker-compose.prod.yml logs backend | grep -A 10 "Checking PHP dependencies"

# 驗證 firebase/php-jwt 已安裝
docker exec free_youtube_backend_prod ls -la /var/www/html/vendor/firebase/php-jwt/
```

### 2. 快速檢查（現有環境）

```bash
# 檢查依賴是否存在
docker exec free_youtube_backend_prod ls /var/www/html/vendor/firebase/

# 查看已安裝的套件
docker exec free_youtube_backend_prod composer show | grep firebase

# 檢查啟動日誌
docker compose -f docker-compose.prod.yml logs backend --tail=50 | grep dependencies
```

### 3. 模擬依賴缺失

```bash
# 進入容器
docker exec -it free_youtube_backend_prod sh

# 刪除 firebase 目錄
rm -rf /var/www/html/vendor/firebase

# 退出
exit

# 重啟容器（會觸發自動安裝）
docker compose -f docker-compose.prod.yml restart backend

# 等待 10 秒
sleep 10

# 查看日誌
docker compose -f docker-compose.prod.yml logs backend --tail=50

# 驗證已重新安裝
docker exec free_youtube_backend_prod ls /var/www/html/vendor/firebase/
```

### 4. 測試 JWT 功能

```bash
# 測試需要 JWT 的 API
curl -i http://localhost:9204/api/health

# 如果正常回應，表示依賴正確安裝
```

---

## 預期輸出

### 正常情況（依賴已存在）

```
Checking PHP dependencies...
✅ Found: firebase/php-jwt
✅ Found: codeigniter4/framework
✅ All required dependencies are installed
```

### 缺少依賴時

```
Checking PHP dependencies...
⚠️  Missing package: firebase/php-jwt
✅ Found: codeigniter4/framework

⚠️  Found 1 missing package(s), installing...
Installing firebase/php-jwt...
Using version ^6.10 for firebase/php-jwt
./composer.json has been updated
Loading composer repositories with package information
Updating dependencies
...
✅ firebase/php-jwt installed successfully!
Optimizing autoloader...
✅ All missing dependencies installed!
```

---

## 疑難排解指令

### 檢查容器狀態

```bash
# 查看所有容器
docker compose -f docker-compose.prod.yml ps

# 查看詳細日誌
docker compose -f docker-compose.prod.yml logs -f backend
```

### 手動安裝依賴（備用方案）

```bash
# 如果自動安裝失敗，手動執行
docker exec -it free_youtube_backend_prod sh
cd /var/www/html
composer require firebase/php-jwt
exit
```

### 清除並重建

```bash
# 完全清理
docker compose -f docker-compose.prod.yml down -v
docker rmi $(docker images | grep free_youtube_backend | awk '{print $3}') 2>/dev/null

# 重新部署
./deploy-prod.sh --full
```

---

## 驗證清單

- [ ] 容器正常啟動
- [ ] `/var/www/html/vendor/firebase/php-jwt/` 目錄存在
- [ ] `composer show` 列出 firebase/php-jwt
- [ ] 啟動日誌顯示依賴檢查通過
- [ ] API 端點正常回應
- [ ] 無 "Class not found" 錯誤
