# Docker 啟動指南

## 前置條件

確保已安裝：
- Docker Desktop
- Docker Compose v2+

## 啟動服務

```bash
# 從專案根目錄啟動所有服務
docker-compose up -d

# 查看服務狀態
docker-compose ps

# 查看日誌
docker-compose logs -f mariadb
docker-compose logs -f phpmyadmin
```

## 驗證服務

### 1. MariaDB (Port 3306)
```bash
# 從本機連線到資料庫
mysql -h localhost -P 3306 -u root -p
# 密碼: secret
```

### 2. phpMyAdmin (http://localhost:8081)
- 帳號: root
- 密碼: secret
- 資料庫: free_youtube

### 3. 後端 API (http://localhost:8080)
```bash
curl http://localhost:8080/api/health
```

### 4. 前端 (http://localhost:5173)
自動開啟在瀏覽器

## 停止服務

```bash
docker-compose down
```

## 清理資料

```bash
# 停止並移除容器與卷
docker-compose down -v
```

## 常見問題

### Port 已被佔用
修改 `docker-compose.yml` 中的 port 映射

### 資料庫連線失敗
檢查 MariaDB 容器健康狀態：
```bash
docker-compose ps
docker logs free_youtube_db
```

### 重新初始化資料庫
```bash
docker-compose down -v
docker-compose up -d
```
