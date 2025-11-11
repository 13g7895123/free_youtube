# Cron Job 設定指南

## 軟刪除會員資料清理任務

### 功能說明
定時清理超過 30 天的軟刪除會員資料，確保符合資料保留政策。

### 清理範圍
- 刪除超過 30 天的會員記錄
- CASCADE 刪除相關資料：
  - 會員 tokens
  - 影片庫
  - 播放清單及項目

---

## 設定方式

### 1. Docker 環境（推薦）

在 `docker-compose.yml` 中加入 cron 服務：

```yaml
services:
  # ... 其他服務

  cron:
    image: php:8.1-cli
    container_name: free_youtube_cron
    volumes:
      - ./backend:/var/www/html
    working_dir: /var/www/html
    command: >
      sh -c "
        echo '0 2 * * * cd /var/www/html && php spark cleanup:deleted-users >> /var/log/cron.log 2>&1' > /etc/cron.d/cleanup &&
        chmod 0644 /etc/cron.d/cleanup &&
        crontab /etc/cron.d/cleanup &&
        cron -f
      "
    depends_on:
      - db
```

**說明**：每日凌晨 2 點執行清理任務

---

### 2. Linux 伺服器

編輯 crontab：

```bash
crontab -e
```

加入以下行（每日凌晨 2 點執行）：

```cron
0 2 * * * cd /path/to/backend && php spark cleanup:deleted-users >> /var/log/youtube-cleanup.log 2>&1
```

---

### 3. Windows Server（使用 Task Scheduler）

#### 建立排程任務

1. 開啟「工作排程器」
2. 點擊「建立基本工作」
3. 設定如下：

**一般**：
- 名稱：`YouTube 清理軟刪除會員`
- 描述：`清理超過 30 天的軟刪除會員資料`

**觸發程序**：
- 每日
- 凌晨 2:00

**動作**：
- 啟動程式
- 程式：`C:\php\php.exe`
- 引數：`spark cleanup:deleted-users`
- 開始於：`C:\path\to\backend`

---

## 手動執行

### 預覽模式（不實際刪除）

```bash
# Docker
docker exec free_youtube_backend_prod php spark cleanup:deleted-users --dry-run

# 本機
cd backend
php spark cleanup:deleted-users --dry-run
```

### 實際執行清理

```bash
# Docker
docker exec free_youtube_backend_prod php spark cleanup:deleted-users

# 本機
cd backend
php spark cleanup:deleted-users
```

---

## 檢查 Cron 執行記錄

### Docker 環境

```bash
# 查看 cron 日誌
docker exec free_youtube_cron cat /var/log/cron.log

# 查看最近 50 行
docker exec free_youtube_cron tail -n 50 /var/log/cron.log
```

### Linux 伺服器

```bash
# 查看日誌
tail -f /var/log/youtube-cleanup.log

# 查看 CodeIgniter 日誌
tail -f backend/writable/logs/log-*.log
```

---

## 監控與告警

### 建議監控指標

1. **執行狀態**：確認 cron 任務每日執行
2. **清理數量**：記錄每次清理的會員數量
3. **錯誤日誌**：監控是否有刪除失敗

### 告警設定範例

```bash
# 檢查昨日是否有執行記錄
if ! grep -q "$(date -d yesterday +%Y-%m-%d)" /var/log/youtube-cleanup.log; then
    echo "警告：昨日未執行清理任務" | mail -s "Cron Alert" admin@example.com
fi
```

---

## 測試清理任務

### 1. 建立測試資料

```sql
-- 建立一個超過 30 天的軟刪除會員
INSERT INTO users (line_user_id, display_name, deleted_at, created_at)
VALUES ('test_deleted_user', '測試已刪除會員', DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY));
```

### 2. 執行預覽模式

```bash
php spark cleanup:deleted-users --dry-run
```

應該會看到測試會員在清理列表中。

### 3. 執行實際清理

```bash
php spark cleanup:deleted-users
```

### 4. 驗證結果

```sql
-- 檢查是否已刪除
SELECT * FROM users WHERE line_user_id = 'test_deleted_user';
-- 應該返回 0 筆資料
```

---

## 故障排除

### Cron 沒有執行

1. **檢查 cron 服務狀態**：
   ```bash
   service cron status
   ```

2. **檢查 crontab 設定**：
   ```bash
   crontab -l
   ```

3. **檢查權限**：
   ```bash
   ls -la /path/to/backend/spark
   chmod +x /path/to/backend/spark
   ```

### 清理失敗

1. **檢查資料庫連線**
2. **檢查外鍵約束**
3. **查看 CodeIgniter 日誌**

---

## 安全性注意事項

1. **備份**：清理前建議備份資料庫
2. **測試**：先在測試環境驗證
3. **日誌**：保留執行日誌至少 90 天
4. **權限**：確保只有授權人員可執行
5. **監控**：設定告警機制

---

## 相關文件

- [CodeIgniter CLI Commands](https://codeigniter.com/user_guide/cli/cli_commands.html)
- [Linux Crontab 教學](https://crontab.guru/)
- [Windows Task Scheduler](https://docs.microsoft.com/en-us/windows/win32/taskschd/task-scheduler-start-page)
