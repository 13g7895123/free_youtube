# CI/CD 部署指南

## 概述

本專案使用 GitHub Actions 實現自動化部署，支援開發環境和正式環境的分離部署。

## 部署流程

### 🔄 自動觸發規則

```yaml
master 分支推送  → 自動部署到正式環境 (deploy-prod.sh)
develop 分支推送 → 自動部署到開發環境 (deploy.sh)
```

### 📋 部署任務

#### 1. Production Deployment (正式環境)
- **觸發條件**: `master` 分支 push 事件
- **部署腳本**: `deploy-prod.sh`
- **環境配置**: 使用 `.env` (從 `.env.prod.example` 複製)
- **Docker 配置**: `docker-compose.prod.yml`
- **特性**:
  - 多階段構建優化
  - 生產環境安全配置
  - 健康檢查和日誌管理
  - 自動備份提示

#### 2. Development Deployment (開發環境)
- **觸發條件**: `develop` 分支 push 事件
- **部署腳本**: `deploy.sh`
- **環境配置**: 使用 `.env` (從 `.env.example` 複製)
- **Docker 配置**: `docker-compose.yml`
- **特性**:
  - 快速構建
  - 開發工具集成
  - 熱重載支援

## 部署架構

```
┌─────────────────────────────────────────────────────────┐
│                     GitHub Repository                    │
└───────────────┬─────────────────────┬───────────────────┘
                │                     │
                │                     │
    ┌───────────▼──────────┐  ┌──────▼──────────────┐
    │   master branch      │  │  develop branch     │
    │   (Production)       │  │  (Development)      │
    └───────────┬──────────┘  └──────┬──────────────┘
                │                     │
                │ GitHub Actions      │ GitHub Actions
                │                     │
    ┌───────────▼──────────┐  ┌──────▼──────────────┐
    │  deploy-production   │  │  deploy-development │
    │  Job                 │  │  Job                │
    └───────────┬──────────┘  └──────┬──────────────┘
                │                     │
                │ SSH                 │ SSH
                │                     │
    ┌───────────▼──────────┐  ┌──────▼──────────────┐
    │  VPS Server          │  │  VPS Server         │
    │  deploy-prod.sh      │  │  deploy.sh          │
    └───────────┬──────────┘  └──────┬──────────────┘
                │                     │
                │                     │
    ┌───────────▼──────────┐  ┌──────▼──────────────┐
    │  Production Env      │  │  Development Env    │
    │  Port: 80, 8080      │  │  Port: 80, 8080     │
    └──────────────────────┘  └─────────────────────┘
```

## 所需的 GitHub Secrets

在 GitHub Repository 設定中配置以下 Secrets：

```
SSH_PRIVATE_KEY    # SSH 私鑰 (用於連接 VPS)
SSH_HOST          # VPS 主機地址
SSH_USER          # SSH 用戶名
```

### 設定步驟

1. **生成 SSH 金鑰** (如果還沒有)
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@deploy"
   ```

2. **添加公鑰到 VPS**
   ```bash
   ssh-copy-id -i ~/.ssh/id_ed25519.pub user@vps-host
   ```

3. **在 GitHub 設定 Secrets**
   - 進入 Repository → Settings → Secrets and variables → Actions
   - 添加以下 secrets:
     - `SSH_PRIVATE_KEY`: 私鑰內容 (cat ~/.ssh/id_ed25519)
     - `SSH_HOST`: VPS IP 或域名
     - `SSH_USER`: SSH 用戶名

## 部署流程詳解

### Production Deployment 流程

```bash
1. 📥 Checkout source code
2. 🔑 Setup SSH key
3. 🔗 Connect to VPS via SSH
4. 📥 Pull latest changes (git pull)
5. 🔧 Make scripts executable
   - chmod +x deploy-prod.sh
   - chmod +x backend/docker-entrypoint.prod.sh
6. 🚀 Execute deployment
   - ./deploy-prod.sh
7. ✅ Verify deployment
```

### deploy-prod.sh 執行內容

```bash
1. ✅ 前置檢查 (Docker, 專案結構)
2. ✅ 環境配置檢查 (.env, 密碼驗證)
3. ✅ 數據備份 (可選)
4. ✅ 停止現有服務
5. ✅ 構建 Docker 鏡像 (多階段構建)
6. ✅ 啟動服務
7. ✅ 健康檢查
8. ✅ 驗證部署
```

## 部署監控

### 查看 GitHub Actions 日誌

1. 進入 Repository → Actions
2. 選擇最近的 workflow run
3. 查看 `deploy-production` 或 `deploy-development` job 日誌

### 查看 VPS 部署日誌

```bash
# SSH 連接到 VPS
ssh -p 8022 user@vps-host

# 查看 Docker 日誌
cd /home/jarvis/project/idea/free_youtube

# 正式環境日誌
docker compose -f docker-compose.prod.yml logs -f

# 開發環境日誌
docker compose logs -f
```

## 手動部署

如果需要手動部署：

### 正式環境

```bash
# SSH 到 VPS
ssh -p 8022 user@vps-host

# 進入專案目錄
cd /home/jarvis/project/idea/free_youtube

# 拉取最新代碼
git pull

# 執行部署
./deploy-prod.sh
```

### 開發環境

```bash
# SSH 到 VPS
ssh -p 8022 user@vps-host

# 進入專案目錄
cd /home/jarvis/project/idea/free_youtube

# 拉取最新代碼
git pull

# 執行部署
./deploy.sh
```

## 回滾策略

### 使用 Git 回滾

```bash
# SSH 到 VPS
ssh -p 8022 user@vps-host
cd /home/jarvis/project/idea/free_youtube

# 查看提交歷史
git log --oneline -10

# 回滾到特定版本
git reset --hard <commit-hash>

# 重新部署
./deploy-prod.sh  # 或 ./deploy.sh
```

### 使用 Docker 回滾

```bash
# 查看 Docker 鏡像歷史
docker images | grep free_youtube

# 使用舊的鏡像標籤重啟
docker compose -f docker-compose.prod.yml down
# 修改 docker-compose.prod.yml 使用舊的鏡像
docker compose -f docker-compose.prod.yml up -d
```

## 常見問題

### Q1: 部署失敗怎麼辦？

1. 檢查 GitHub Actions 日誌
2. SSH 到 VPS 檢查詳細日誌
   ```bash
   docker compose -f docker-compose.prod.yml logs
   ```
3. 檢查 `.env` 配置是否正確
4. 檢查磁盤空間是否充足
   ```bash
   df -h
   docker system df
   ```

### Q2: SSH 連接失敗

- 檢查 SSH_PRIVATE_KEY 是否正確設定
- 檢查 SSH_HOST 和 SSH_USER 是否正確
- 確認 VPS 防火牆規則允許 GitHub Actions IP

### Q3: Docker 構建失敗

- 檢查 Dockerfile 語法
- 檢查磁盤空間
- 清理舊的 Docker 資源
  ```bash
  docker system prune -a
  ```

### Q4: 服務無法訪問

- 檢查容器狀態
  ```bash
  docker compose -f docker-compose.prod.yml ps
  ```
- 檢查端口是否被佔用
  ```bash
  netstat -tulpn | grep -E '80|8080'
  ```
- 檢查防火牆規則

## 最佳實踐

### 1. 分支管理
- `master` 分支: 僅用於正式環境，保持穩定
- `develop` 分支: 開發環境，測試新功能
- 使用 Pull Request 合併到 master

### 2. 部署前檢查
- ✅ 本地測試通過
- ✅ Code Review 完成
- ✅ 更新 CHANGELOG
- ✅ 確認環境變數配置正確

### 3. 部署後驗證
- ✅ 檢查服務健康狀態
- ✅ 訪問應用確認功能正常
- ✅ 查看日誌確認無錯誤
- ✅ 監控資源使用情況

### 4. 安全建議
- 🔒 定期更換 SSH 密鑰
- 🔒 使用強密碼
- 🔒 啟用 2FA (如果可能)
- 🔒 限制 SSH 訪問 IP
- 🔒 定期備份數據

## 環境變數管理

### Production (.env)
```bash
# 從範例複製
cp .env.prod.example .env

# 修改為正式環境配置
MYSQL_ROOT_PASSWORD=<strong-password>
MYSQL_PASSWORD=<strong-password>
FRONTEND_PORT=80
BACKEND_PORT=8080
```

### Development (.env)
```bash
# 從範例複製
cp .env.example .env

# 可以使用較簡單的配置
MYSQL_ROOT_PASSWORD=secret
MYSQL_PASSWORD=password
```

## 監控和告警

建議設置以下監控：

1. **應用監控**
   - 服務健康狀態
   - 響應時間
   - 錯誤率

2. **資源監控**
   - CPU 使用率
   - 記憶體使用率
   - 磁盤空間

3. **日誌監控**
   - 錯誤日誌
   - 訪問日誌
   - 安全日誌

## 相關文檔

- [正式環境部署文檔](DEPLOYMENT_PROD.md)
- [開發環境設置](README.md)
- [Docker 配置說明](docker-compose.prod.yml)

## 支援

如有問題，請：
1. 查看 GitHub Actions 日誌
2. 查看 Docker 容器日誌
3. 參考本文檔的常見問題部分
4. 提交 GitHub Issue
