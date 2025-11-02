# 資料庫遷移執行指南

**Feature**: 003-line-login-auth
**Date**: 2025-11-01

## 前置條件

確保 Docker 容器正在運行:

```bash
# 檢查容器狀態
docker ps

# 或啟動容器 (如果未運行)
docker-compose up -d
```

## 執行遷移

### 方法 1: 進入後端容器執行

```bash
# 進入後端 PHP 容器
docker exec -it <backend_container_name> bash

# 在容器內執行遷移
php spark migrate

# 確認遷移成功
php spark migrate:status
```

### 方法 2: 直接執行 docker exec

```bash
# 不進入容器,直接執行命令
docker exec <backend_container_name> php spark migrate

# 確認遷移狀態
docker exec <backend_container_name> php spark migrate:status
```

## 查找容器名稱

```bash
# 列出所有運行中的容器
docker ps

# 查找包含 "backend" 或 "php" 的容器
docker ps | grep -E "backend|php"
```

## 預期結果

成功執行後應該看到:

```
Running: 2025110100_CreateLineLoginTables
Migrated: 2025110100_CreateLineLoginTables (001)
Done.
```

## 驗證遷移

### 方法 1: 使用 CodeIgniter CLI

```bash
docker exec <backend_container_name> php spark migrate:status
```

### 方法 2: 直接查詢資料庫

```bash
# 進入 MariaDB 容器
docker exec -it mariadb mysql -uroot -psecret free_youtube

# 顯示所有資料表
SHOW TABLES;

# 應該看到以下 7 個表:
# - users
# - user_tokens
# - video_library
# - playlists
# - playlist_items
# - guest_sessions
# - migrations (CodeIgniter 系統表)
```

## 回滾遷移 (如需要)

```bash
# 回滾最後一次遷移
docker exec <backend_container_name> php spark migrate:rollback

# 回滾所有遷移
docker exec <backend_container_name> php spark migrate:rollback -all
```

## 常見問題

### Q: 遷移失敗,顯示 "Database connection failed"

A: 確認:
1. MariaDB 容器正在運行: `docker ps | grep mariadb`
2. 後端 `.env` 中的資料庫設定正確
3. 網路連接正常: `docker network ls`

### Q: 遷移顯示 "Table already exists"

A: 表可能已經建立。檢查:
```bash
# 查看遷移狀態
docker exec <backend_container_name> php spark migrate:status

# 如需重新建立,先回滾再遷移
docker exec <backend_container_name> php spark migrate:rollback
docker exec <backend_container_name> php spark migrate
```

### Q: 外鍵約束錯誤

A: 確保遷移按正確順序執行:
1. users (父表)
2. user_tokens (依賴 users)
3. video_library (依賴 users)
4. playlists (依賴 users)
5. playlist_items (依賴 playlists)
6. guest_sessions (獨立)

---

**下一步**: 遷移成功後,繼續執行 Models 建立任務 (T007-T012)
