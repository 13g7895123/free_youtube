# Zero-Downtime 藍綠部署指南

## 架構概述

本專案採用藍綠部署 (Blue-Green Deployment) 策略實現零停機部署。

### 架構圖

```
                    ┌─────────────────┐
                    │   使用者流量      │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │    Gateway      │  ◄── Port 80/443
                    │    (Nginx)      │
                    └────────┬────────┘
                             │
              ┌──────────────┴──────────────┐
              │      nginx.upstream.conf     │
              │   (動態切換 Blue/Green)       │
              └──────────────┬──────────────┘
                             │
         ┌───────────────────┴───────────────────┐
         │                                       │
         ▼                                       ▼
┌─────────────────┐                     ┌─────────────────┐
│   Blue 環境      │                     │   Green 環境     │
│  ┌───────────┐  │                     │  ┌───────────┐  │
│  │ Frontend  │  │                     │  │ Frontend  │  │
│  └─────┬─────┘  │                     │  └─────┬─────┘  │
│        │        │                     │        │        │
│  ┌─────▼─────┐  │                     │  ┌─────▼─────┐  │
│  │ Backend   │  │                     │  │ Backend   │  │
│  └─────┬─────┘  │                     │  └─────┬─────┘  │
└────────┼────────┘                     └────────┼────────┘
         │                                       │
         └───────────────────┬───────────────────┘
                             │
                    ┌────────▼────────┐
                    │    MariaDB      │  ◄── 獨立運行，不參與切換
                    │   phpMyAdmin    │
                    └─────────────────┘
```

## 檔案結構

```
├── docker-compose.db.yml       # 資料庫服務 (獨立運行)
├── docker-compose.app.yml      # 應用程式服務 (藍綠部署)
├── docker-compose.gateway.yml  # Gateway 服務 (流量入口)
├── nginx.gateway.conf          # Gateway Nginx 主配置
├── nginx.upstream.conf         # Upstream 配置 (動態切換)
├── deploy-zero-downtime.sh     # 部署腳本
├── .deploy-state               # 部署狀態檔案 (自動生成)
└── .env.prod                   # 環境變數檔案
```

## 使用方式

### 首次部署

1. **初始化環境** (建立網路、Volume、啟動資料庫和 Gateway)
   ```bash
   ./deploy-zero-downtime.sh --init
   ```

2. **執行第一次部署**
   ```bash
   ./deploy-zero-downtime.sh
   ```

### 日常部署

每次有新代碼需要部署時，只需執行：

```bash
./deploy-zero-downtime.sh
```

腳本會自動：
1. 判斷目前活躍的環境 (Blue 或 Green)
2. 構建並啟動另一個環境
3. 等待健康檢查通過
4. 切換流量到新環境
5. 清理舊環境

### 其他命令

```bash
# 查看目前環境狀態
./deploy-zero-downtime.sh --status

# 回滾到上一個環境
./deploy-zero-downtime.sh --rollback

# 顯示幫助
./deploy-zero-downtime.sh --help
```

## 部署流程詳解

### 正常部署流程

假設目前 Blue 環境正在服務：

1. **構建 Green 映像** - 不影響線上服務
2. **啟動 Green 容器** - Blue 繼續服務
3. **Green 健康檢查** - 確保新環境可用
4. **切換 upstream** - 修改 nginx.upstream.conf
5. **重載 Gateway** - nginx -s reload (無縫切換)
6. **清理 Blue** - 等待舊連線完成後關閉

整個過程中，使用者無感知，服務不中斷。

### 回滾流程

如果發現新版本有問題：

```bash
./deploy-zero-downtime.sh --rollback
```

這會將 upstream 切回上一個環境（如果還存在）。

## 注意事項

### 資料庫遷移

由於 Blue 和 Green 共用同一個資料庫，資料庫遷移需要特別注意：

1. **向後相容** - 新版本的資料庫變更必須向後相容舊版本
2. **分階段遷移** - 大型遷移應該分多次部署完成
3. **先遷移再部署** - 確保資料庫結構先更新

### 健康檢查

確保應用程式有正確的健康檢查端點：

- Frontend: `GET /` 回傳 200
- Backend: `GET /api/health` 回傳 200

### 日誌查看

```bash
# 查看 Gateway 日誌
docker logs -f free_youtube_gateway

# 查看資料庫日誌
docker logs -f free_youtube_db_prod

# 查看應用程式日誌 (需知道目前環境)
docker compose --env-file .env.prod -f docker-compose.app.yml -p app-blue logs -f
docker compose --env-file .env.prod -f docker-compose.app.yml -p app-green logs -f
```

### 手動操作

如需手動操作各服務：

```bash
# 資料庫服務
docker compose --env-file .env.prod -f docker-compose.db.yml up -d
docker compose --env-file .env.prod -f docker-compose.db.yml down

# Gateway 服務
docker compose --env-file .env.prod -f docker-compose.gateway.yml up -d
docker compose --env-file .env.prod -f docker-compose.gateway.yml down

# 應用程式服務 (Blue)
docker compose --env-file .env.prod -f docker-compose.app.yml -p app-blue up -d
docker compose --env-file .env.prod -f docker-compose.app.yml -p app-blue down

# 應用程式服務 (Green)
docker compose --env-file .env.prod -f docker-compose.app.yml -p app-green up -d
docker compose --env-file .env.prod -f docker-compose.app.yml -p app-green down
```

## 從舊版部署遷移

如果您之前使用 `deploy-prod.sh` (非零停機版本)，遷移步驟如下：

1. **停止舊服務**
   ```bash
   docker compose --env-file .env.prod -f docker-compose.prod.yml down
   ```

2. **初始化新架構**
   ```bash
   ./deploy-zero-downtime.sh --init
   ```

3. **執行部署**
   ```bash
   ./deploy-zero-downtime.sh
   ```

> ⚠️ 注意：遷移過程中會有短暫停機，建議在低流量時段進行。

## 疑難排解

### Gateway 無法連接到應用程式

1. 檢查網路是否正確建立：
   ```bash
   docker network inspect free_youtube_app_network_prod
   ```

2. 確認容器在同一網路：
   ```bash
   docker network inspect free_youtube_app_network_prod --format '{{range .Containers}}{{.Name}} {{end}}'
   ```

### 健康檢查失敗

1. 檢查容器日誌：
   ```bash
   docker logs app-blue-backend-1
   docker logs app-blue-frontend-1
   ```

2. 手動測試健康端點：
   ```bash
   docker exec app-blue-backend-1 curl -f http://localhost:8000/api/health
   ```

### 流量沒有切換

1. 檢查 upstream 配置：
   ```bash
   cat nginx.upstream.conf
   ```

2. 驗證 Gateway 配置：
   ```bash
   docker exec free_youtube_gateway nginx -t
   ```

3. 手動重載：
   ```bash
   docker exec free_youtube_gateway nginx -s reload
   ```
