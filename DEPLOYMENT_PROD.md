# YouTube Loop Player - 正式環境部署文檔

## 概述

本文檔說明如何將 YouTube Loop Player 部署到正式環境。正式環境使用獨立的配置文件，不會影響開發環境。

## 文件結構

```
正式環境相關文件：
├── docker-compose.prod.yml          # 正式環境 Docker Compose 配置
├── Dockerfile.prod                  # 前端正式環境 Dockerfile
├── nginx.prod.conf                  # Nginx 正式環境配置
├── backend/
│   ├── Dockerfile.prod              # 後端正式環境 Dockerfile
│   └── docker-entrypoint.prod.sh    # 後端正式環境啟動腳本
├── deploy-prod.sh                   # 正式環境部署腳本
└── .env.prod.example                # 正式環境配置範例

開發環境文件（不受影響）：
├── docker-compose.yml               # 開發環境配置
├── Dockerfile                       # 開發環境前端 Dockerfile
├── deploy.sh                        # 開發環境部署腳本
└── .env.example                     # 開發環境配置範例
```

## 正式環境特性

### 1. 多階段構建
- 前端使用多階段構建，減小最終鏡像大小
- 構建階段使用 Node.js 22 編譯前端資源
- 運行階段僅包含 Nginx 和靜態文件

### 2. 安全性增強
- 後端使用非 root 用戶運行
- 啟用 OPcache 提升 PHP 性能
- 配置完善的安全標頭
- 禁止訪問敏感文件
- 強制使用強密碼

### 3. 日誌管理
- 所有容器配置日誌輪轉
- 限制日誌文件大小（10MB）
- 保留最近 3 個日誌文件

### 4. 健康檢查
- 所有服務配置健康檢查
- 自動重啟不健康的容器
- 部署腳本驗證服務健康狀態

### 5. 性能優化
- Gzip 壓縮
- 靜態資源緩存（1年）
- OPcache 配置
- Composer 自動加載優化

## 部署步驟

### 前置要求

1. 已安裝 Docker 和 Docker Compose
2. 有足夠的磁盤空間（建議至少 5GB）
3. 開放必要的端口（80, 8080, 8081）

### 步驟 1: 準備配置文件

```bash
# 複製正式環境配置範例
cp .env.prod.example .env

# 編輯配置文件，修改密碼和端口
nano .env
```

**重要配置項：**

```env
# ⚠️ 必須修改為強密碼！
MYSQL_ROOT_PASSWORD=your_strong_root_password_here
MYSQL_PASSWORD=your_strong_user_password_here

# 端口配置（根據需要調整）
FRONTEND_PORT=80
BACKEND_PORT=8080
PHPMYADMIN_PORT=8081
```

### 步驟 2: 執行部署

```bash
# 賦予執行權限（首次部署需要）
chmod +x deploy-prod.sh

# 執行部署
./deploy-prod.sh
```

部署腳本會自動執行以下操作：

1. ✅ 檢查 Docker 運行狀態
2. ✅ 驗證專案結構
3. ✅ 檢查環境配置
4. ✅ 備份現有數據（可選）
5. ✅ 停止舊服務
6. ✅ 構建新的 Docker 鏡像
7. ✅ 啟動服務
8. ✅ 健康檢查
9. ✅ 驗證部署

### 步驟 3: 驗證部署

部署完成後，訪問以下 URL：

- **前端應用**: http://localhost:80
- **後端 API**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

## 管理命令

### 查看日誌

```bash
# 查看所有服務日誌
docker compose -f docker-compose.prod.yml logs -f

# 查看特定服務日誌
docker compose -f docker-compose.prod.yml logs -f backend
docker compose -f docker-compose.prod.yml logs -f frontend
docker compose -f docker-compose.prod.yml logs -f mariadb
```

### 查看服務狀態

```bash
docker compose -f docker-compose.prod.yml ps
```

### 重啟服務

```bash
# 重啟所有服務
docker compose -f docker-compose.prod.yml restart

# 重啟特定服務
docker compose -f docker-compose.prod.yml restart backend
```

### 停止服務

```bash
# 停止但保留數據
docker compose -f docker-compose.prod.yml down

# 停止並刪除所有數據（危險！）
docker compose -f docker-compose.prod.yml down -v
```

### 更新應用

```bash
# 拉取最新代碼
git pull

# 重新部署
./deploy-prod.sh
```

## 數據備份

### 手動備份數據庫

```bash
# 創建備份目錄
mkdir -p backups

# 備份數據庫
docker compose -f docker-compose.prod.yml exec mariadb \
  mysqldump -u root -p"${MYSQL_ROOT_PASSWORD}" \
  --all-databases > backups/backup_$(date +%Y%m%d_%H%M%S).sql
```

### 恢復數據庫

```bash
# 從備份恢復
docker compose -f docker-compose.prod.yml exec -T mariadb \
  mysql -u root -p"${MYSQL_ROOT_PASSWORD}" < backups/backup_YYYYMMDD_HHMMSS.sql
```

## 監控建議

### 1. 資源使用監控

```bash
# 查看容器資源使用
docker stats

# 查看磁盤使用
df -h
docker system df
```

### 2. 日誌監控

定期檢查以下日誌：

- 應用程式錯誤日誌
- Nginx 訪問日誌和錯誤日誌
- PHP 錯誤日誌
- 數據庫日誌

### 3. 健康檢查

```bash
# 檢查容器健康狀態
docker compose -f docker-compose.prod.yml ps

# 手動測試端點
curl http://localhost:80
curl http://localhost:8080/health
```

## 安全建議

### 1. 密碼安全
- ✅ 使用強密碼（至少 16 位，包含大小寫字母、數字、特殊字符）
- ✅ 定期更換密碼
- ✅ 不要將密碼提交到版本控制

### 2. 網路安全
- ✅ 配置防火牆規則
- ✅ 僅開放必要端口
- ✅ 考慮使用反向代理（如 Nginx）
- ✅ 啟用 HTTPS（使用 Let's Encrypt）

### 3. 訪問控制
- ✅ 限制 phpMyAdmin 訪問
- ✅ 配置 IP 白名單
- ✅ 使用 VPN 訪問管理介面

### 4. 定期維護
- ✅ 定期備份數據
- ✅ 定期更新依賴
- ✅ 監控日誌和錯誤
- ✅ 檢查安全漏洞

## 常見問題

### Q1: 容器無法啟動

```bash
# 檢查日誌
docker compose -f docker-compose.prod.yml logs

# 檢查端口佔用
netstat -tulpn | grep -E '80|8080|8081|3307'

# 清理並重新構建
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.prod.yml up -d
```

### Q2: 資料庫連接失敗

```bash
# 檢查資料庫是否就緒
docker compose -f docker-compose.prod.yml ps mariadb

# 檢查資料庫日誌
docker compose -f docker-compose.prod.yml logs mariadb

# 測試連接
docker compose -f docker-compose.prod.yml exec backend \
  mysql -h mariadb -u app_user -p"${MYSQL_PASSWORD}" -e "SELECT 1"
```

### Q3: 前端無法訪問

```bash
# 檢查前端容器狀態
docker compose -f docker-compose.prod.yml ps frontend

# 檢查 Nginx 配置
docker compose -f docker-compose.prod.yml exec frontend nginx -t

# 檢查前端日誌
docker compose -f docker-compose.prod.yml logs frontend
```

### Q4: 磁盤空間不足

```bash
# 清理未使用的 Docker 資源
docker system prune -a

# 清理舊的日誌文件
find ./backend/writable/logs -name "*.log" -mtime +30 -delete

# 清理數據庫舊的備份
find ./backups -name "*.sql" -mtime +30 -delete
```

## 效能調優

### 1. Nginx 調優

編輯 `nginx.prod.conf`：

```nginx
worker_processes auto;
worker_connections 1024;
```

### 2. PHP 調優

編輯 `backend/Dockerfile.prod` 的 OPcache 設置：

```dockerfile
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### 3. 資料庫調優

創建 `mysql.cnf` 並掛載到容器：

```ini
[mysqld]
max_connections=200
innodb_buffer_pool_size=256M
```

## CI/CD 整合

### GitHub Actions 範例

```yaml
- name: Deploy to Production
  run: |
    ssh user@server "cd /path/to/project && ./deploy-prod.sh"
```

## 支援

如有問題，請：

1. 查看日誌：`docker compose -f docker-compose.prod.yml logs`
2. 檢查 GitHub Issues
3. 參考本文檔的常見問題部分

## 版本說明

- Docker Compose: 3.8+
- Docker: 20.10+
- Node.js: 22 (構建時)
- PHP: 8.1
- MariaDB: 10.6
- Nginx: Alpine latest
