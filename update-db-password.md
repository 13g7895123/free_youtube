# 更新資料庫密碼完整流程

> **重要提醒**：此操作會影響生產環境，請在低流量時段進行，並確保已備份資料庫。

## 前置準備

### 1. 備份當前資料庫

```bash
# 進入專案目錄
cd /home/jarvis/project/idea/free_youtube

# 建立備份目錄
mkdir -p backups/$(date +%Y%m%d)

# 匯出資料庫（使用當前密碼）
docker exec free_youtube_db_prod mysqldump \
  -u root \
  -p${當前的MYSQL_ROOT_PASSWORD} \
  --all-databases \
  --single-transaction \
  --quick \
  --lock-tables=false \
  > backups/$(date +%Y%m%d)/free_youtube_backup_$(date +%Y%m%d_%H%M%S).sql

# 確認備份檔案存在
ls -lh backups/$(date +%Y%m%d)/
```

### 2. 記錄當前配置

```bash
# 備份當前的 .env.prod 檔案
cp .env.prod .env.prod.backup_$(date +%Y%m%d_%H%M%S)
```

## 更新流程

### 步驟 1: 停止服務

```bash
# 停止所有容器
docker-compose -f docker-compose.prod.yml down

# 確認容器已停止
docker ps | grep free_youtube
```

### 步驟 2: 更新環境變數檔案

編輯 `.env.prod` 檔案，更新以下密碼相關變數：

```bash
vim .env.prod
```

需要更新的欄位：
```env
# 資料庫 root 密碼（新密碼）
MYSQL_ROOT_PASSWORD=your_new_root_password_here

# 資料庫應用程式使用者密碼（新密碼）
MYSQL_PASSWORD=your_new_app_password_here
```

### 步驟 3: 更新 MariaDB 容器內的密碼

由於 MariaDB 容器已經初始化過，我們需要手動更新資料庫內的密碼：

```bash
# 啟動 MariaDB 容器（單獨啟動）
docker-compose -f docker-compose.prod.yml up -d mariadb

# 等待 MariaDB 啟動完成（約 10-15 秒）
sleep 15

# 進入 MariaDB 容器
docker exec -it free_youtube_db_prod mysql -u root -p舊密碼
```

在 MySQL 命令列中執行：

```sql
-- 更新 root 使用者密碼
ALTER USER 'root'@'localhost' IDENTIFIED BY '新的root密碼';
ALTER USER 'root'@'%' IDENTIFIED BY '新的root密碼';

-- 更新應用程式使用者密碼（假設使用者名稱是 app_user）
ALTER USER 'app_user'@'%' IDENTIFIED BY '新的app密碼';

-- 刷新權限
FLUSH PRIVILEGES;

-- 驗證連線（使用新密碼）
SELECT user, host FROM mysql.user WHERE user IN ('root', 'app_user');

-- 退出
EXIT;
```

### 步驟 4: 測試新密碼連線

```bash
# 使用新密碼測試連線
docker exec -it free_youtube_db_prod mysql -u root -p新密碼 -e "SELECT 1;"

# 測試應用程式使用者
docker exec -it free_youtube_db_prod mysql -u app_user -p新密碼 -e "SELECT DATABASE();"
```

### 步驟 5: 重新啟動所有服務

```bash
# 停止 MariaDB
docker-compose -f docker-compose.prod.yml down

# 啟動所有服務
docker-compose -f docker-compose.prod.yml up -d

# 查看服務狀態
docker-compose -f docker-compose.prod.yml ps
```

### 步驟 6: 驗證服務運作

```bash
# 1. 檢查容器狀態
docker-compose -f docker-compose.prod.yml ps

# 2. 檢查 Backend 日誌（確認資料庫連線成功）
docker logs free_youtube_backend_prod --tail 50

# 3. 測試 API 健康檢查
curl http://localhost:8080/api/health

# 4. 測試資料庫連線
docker exec free_youtube_backend_prod php -r "
\$db = new mysqli('mariadb', 'app_user', '新密碼', 'free_youtube');
if (\$db->connect_error) {
    die('Connection failed: ' . \$db->connect_error);
}
echo 'Database connection successful!';
\$db->close();
"

# 5. 檢查前端是否正常
curl http://localhost/

# 6. 測試 phpMyAdmin（如果有開啟端口或透過 /pma）
curl http://localhost/pma/
```

## 故障排除

### 問題 1: Backend 無法連接資料庫

**症狀**：Backend 日誌顯示 "Access denied for user"

**解決方法**：
```bash
# 確認 .env.prod 中的密碼已更新
cat .env.prod | grep MYSQL_PASSWORD

# 重新啟動 backend 容器
docker-compose -f docker-compose.prod.yml restart backend

# 檢查日誌
docker logs free_youtube_backend_prod -f
```

### 問題 2: phpMyAdmin 無法登入

**症狀**：phpMyAdmin 顯示 "Access denied"

**解決方法**：
```bash
# 確認 PMA 使用的是新密碼
docker-compose -f docker-compose.prod.yml restart phpmyadmin

# 或手動登入時使用新密碼
```

### 問題 3: 容器持續重啟

**症狀**：`docker ps` 顯示容器一直在重啟

**解決方法**：
```bash
# 查看詳細錯誤訊息
docker logs free_youtube_backend_prod --tail 100

# 確認資料庫密碼在 MySQL 中已更新
docker exec -it free_youtube_db_prod mysql -u root -p新密碼 -e "SHOW GRANTS FOR 'app_user'@'%';"
```

## 回滾流程（如果出現問題）

### 回滾步驟

```bash
# 1. 停止所有服務
docker-compose -f docker-compose.prod.yml down

# 2. 恢復舊的 .env.prod
cp .env.prod.backup_日期時間 .env.prod

# 3. 恢復資料庫（如果需要）
cat backups/日期/free_youtube_backup_*.sql | \
  docker exec -i free_youtube_db_prod mysql -u root -p舊密碼

# 4. 重新啟動服務
docker-compose -f docker-compose.prod.yml up -d
```

## 安全建議

1. **使用強密碼**
   ```bash
   # 生成強密碼範例
   openssl rand -base64 32
   ```

2. **限制 .env.prod 檔案權限**
   ```bash
   chmod 600 .env.prod
   ```

3. **定期更換密碼**
   - 建議每 3-6 個月更換一次資料庫密碼

4. **保管好備份**
   ```bash
   # 加密備份檔案
   tar -czf - backups/$(date +%Y%m%d)/ | \
     openssl enc -aes-256-cbc -salt -out backups/backup_$(date +%Y%m%d).tar.gz.enc
   ```

## 檢查清單

部署後確認事項：

- [ ] 資料庫備份已完成
- [ ] .env.prod 已更新新密碼
- [ ] MariaDB 內部密碼已更新
- [ ] 所有容器正常運行（`docker-compose ps`）
- [ ] Backend 可以連接資料庫
- [ ] API 健康檢查通過
- [ ] 前端可以正常載入
- [ ] phpMyAdmin 可以登入（如果使用）
- [ ] 應用程式功能正常（登入、查詢等）
- [ ] 舊的 .env.prod 備份已保存

## 相關檔案

- **環境配置**: `.env.prod`
- **Docker Compose**: `docker-compose.prod.yml`
- **部署腳本**: `deploy-prod.sh`
- **資料庫遷移**: `backend/database/migrations.sql`

## 注意事項

⚠️ **重要提醒**：
1. 此操作會造成短暫的服務中斷（約 1-3 分鐘）
2. 請確保在低流量時段進行
3. 務必先備份資料庫再進行密碼更新
4. 更新完成後立即測試所有功能
5. 保留舊密碼資訊直到確認新密碼運作正常
6. 更新完成後通知相關開發人員新密碼資訊

## 預估時間

- **準備與備份**: 5-10 分鐘
- **更新密碼**: 3-5 分鐘
- **服務重啟**: 2-3 分鐘
- **驗證測試**: 5-10 分鐘
- **總計**: 約 15-30 分鐘

---

**最後更新**: 2025-11-05
**適用版本**: Production Environment (docker-compose.prod.yml)
