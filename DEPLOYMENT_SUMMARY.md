# YouTube Loop Player - 部署架構總結

## 📋 部署文件清單

### 🎯 正式環境 (Production)
```
docker-compose.prod.yml          # 正式環境 Docker Compose 配置
Dockerfile.prod                  # 前端正式環境 Dockerfile (多階段構建)
nginx.prod.conf                  # Nginx 正式環境配置
backend/Dockerfile.prod          # 後端正式環境 Dockerfile
backend/docker-entrypoint.prod.sh # 後端正式環境啟動腳本
deploy-prod.sh                   # 正式環境部署腳本
.env.prod.example                # 正式環境配置範例
DEPLOYMENT_PROD.md               # 正式環境部署文檔
```

### 🔧 開發環境 (Development)
```
docker-compose.yml               # 開發環境 Docker Compose 配置
Dockerfile                       # 前端開發環境 Dockerfile
nginx.conf                       # Nginx 開發環境配置
backend/Dockerfile               # 後端開發環境 Dockerfile
backend/docker-entrypoint.sh     # 後端開發環境啟動腳本
deploy.sh                        # 開發環境部署腳本
.env.example                     # 開發環境配置範例
```

### 🚀 CI/CD
```
.github/workflows/ci-cd.yml      # GitHub Actions CI/CD 配置
CICD_GUIDE.md                    # CI/CD 使用指南
```

## 🏗️ 架構對比

| 特性 | 開發環境 | 正式環境 |
|-----|---------|---------|
| **構建方式** | 單階段構建 | 多階段構建 |
| **鏡像大小** | 較大 | 優化過，較小 |
| **啟動速度** | 較快 | 正常 |
| **安全性** | 基本 | 增強 (非 root、安全標頭) |
| **性能優化** | 無 | OPcache、Gzip、緩存 |
| **日誌管理** | 基本 | 輪轉、限制大小 |
| **健康檢查** | 基本 | 完整 |
| **容器名稱** | free_youtube_* | free_youtube_*_prod |
| **網路名稱** | app_network | app_network_prod |
| **數據卷** | mariadb_data | mariadb_prod_data |
| **部署腳本** | deploy.sh | deploy-prod.sh |
| **觸發分支** | develop | master |

## 📊 部署流程圖

```
┌─────────────────────────────────────────────────────────────┐
│                      開發流程                                │
└─────────────────────────────────────────────────────────────┘

開發人員 → feature branch → develop branch → CI/CD
                                            ↓
                                    deploy-development job
                                            ↓
                                    執行 deploy.sh
                                            ↓
                                    開發環境 (port: 80, 8080)

┌─────────────────────────────────────────────────────────────┐
│                      正式發布流程                             │
└─────────────────────────────────────────────────────────────┘

develop branch → PR → master branch → CI/CD
                                        ↓
                                deploy-production job
                                        ↓
                                執行 deploy-prod.sh
                                        ↓
                        ┌───────────────┴───────────────┐
                        │                               │
                    前置檢查                         數據備份
                        │                               │
                        └───────────────┬───────────────┘
                                        ↓
                            構建 Docker 鏡像 (多階段)
                                        ↓
                                    啟動服務
                                        ↓
                                    健康檢查
                                        ↓
                            正式環境 (port: 80, 8080)
```

## 🔄 CI/CD 自動化流程

### Master 分支 (正式環境)
```yaml
觸發: push to master
↓
GitHub Actions: deploy-production
↓
1. Checkout code
2. Setup SSH
3. Connect to VPS
4. Pull latest code
5. chmod +x deploy-prod.sh
6. Execute ./deploy-prod.sh
   ├─ 前置檢查
   ├─ 環境配置驗證
   ├─ 可選數據備份
   ├─ 停止舊服務
   ├─ 構建新鏡像
   ├─ 啟動服務
   ├─ 健康檢查
   └─ 驗證部署
7. Notify status
```

### Develop 分支 (開發環境)
```yaml
觸發: push to develop
↓
GitHub Actions: deploy-development
↓
1. Checkout code
2. Setup SSH
3. Connect to VPS
4. Pull latest code
5. chmod +x deploy.sh
6. Execute ./deploy.sh
   ├─ 檢查 Docker
   ├─ 驗證目錄結構
   ├─ 安裝依賴
   ├─ 構建前端
   ├─ 啟動 Docker Compose
   └─ 驗證服務
7. Notify status
```

## 🎯 使用指南

### 本地開發
```bash
# 1. 克隆專案
git clone <repository>
cd free_youtube

# 2. 配置環境
cp .env.example .env

# 3. 啟動開發環境
./deploy.sh

# 4. 訪問應用
open http://localhost:80
```

### 部署到開發環境 (VPS)
```bash
# 方式 1: 自動部署
git checkout develop
git add .
git commit -m "feat: new feature"
git push origin develop
# GitHub Actions 自動部署

# 方式 2: 手動部署
ssh user@vps
cd /home/jarvis/project/idea/free_youtube
git pull
./deploy.sh
```

### 部署到正式環境 (VPS)
```bash
# 方式 1: 自動部署 (推薦)
git checkout develop
# ... 開發和測試 ...
git checkout master
git merge develop
git push origin master
# GitHub Actions 自動部署到正式環境

# 方式 2: 手動部署
ssh user@vps
cd /home/jarvis/project/idea/free_youtube
git checkout master
git pull
./deploy-prod.sh
```

## 🔧 環境變數配置

### 開發環境 (.env)
```bash
MYSQL_ROOT_PASSWORD=secret
MYSQL_PASSWORD=password
MYSQL_DATABASE=free_youtube
FRONTEND_PORT=80
BACKEND_PORT=8080
```

### 正式環境 (.env)
```bash
MYSQL_ROOT_PASSWORD=<strong-random-password>
MYSQL_PASSWORD=<strong-random-password>
MYSQL_DATABASE=free_youtube
FRONTEND_PORT=80
BACKEND_PORT=8080
```

## 📦 Docker 服務

### 開發環境容器
```
free_youtube_db              # MariaDB 10.6
free_youtube_frontend        # Nginx + Vue.js (開發模式)
free_youtube_backend         # PHP 8.1 + CodeIgniter
free_youtube_phpmyadmin      # phpMyAdmin
```

### 正式環境容器
```
free_youtube_db_prod         # MariaDB 10.6
free_youtube_frontend_prod   # Nginx + Vue.js (生產構建)
free_youtube_backend_prod    # PHP 8.1 + CodeIgniter (優化)
free_youtube_phpmyadmin_prod # phpMyAdmin
```

## 🚨 重要注意事項

### ⚠️ 環境隔離
- 開發和正式環境**完全獨立**
- 使用不同的容器、網路、數據卷
- 可以在同一台機器上**同時運行**
- 端口配置需注意避免衝突

### ⚠️ 密碼安全
- 正式環境**必須**使用強密碼
- 不要將 `.env` 提交到版本控制
- 定期更換密碼
- 使用密碼管理工具

### ⚠️ 部署前檢查
```bash
# 開發環境
✅ 本地測試通過
✅ 代碼 review
✅ 提交到 develop 分支

# 正式環境
✅ 在開發環境充分測試
✅ Code review 通過
✅ 更新文檔和 CHANGELOG
✅ 確認 .env 配置正確
✅ 備份數據 (如有必要)
```

## 📚 相關文檔

- [**DEPLOYMENT_PROD.md**](DEPLOYMENT_PROD.md) - 正式環境完整部署指南
- [**CICD_GUIDE.md**](CICD_GUIDE.md) - CI/CD 使用指南
- [**README.md**](README.md) - 專案概述

## 🔍 快速檢查清單

### 首次部署正式環境
- [ ] 複製 `.env.prod.example` 為 `.env`
- [ ] 修改 `.env` 中的所有密碼
- [ ] 確認端口配置不衝突
- [ ] 設定 GitHub Secrets (SSH_PRIVATE_KEY, SSH_HOST, SSH_USER)
- [ ] 測試 SSH 連接
- [ ] 執行 `./deploy-prod.sh` 或推送到 master 分支
- [ ] 驗證服務運行正常
- [ ] 配置防火牆規則
- [ ] 設置定期備份

### 日常更新
- [ ] 在 develop 分支開發和測試
- [ ] 確認開發環境運行正常
- [ ] 合併到 master 分支
- [ ] 推送觸發自動部署
- [ ] 監控部署日誌
- [ ] 驗證正式環境功能
- [ ] 檢查服務健康狀態

## 🎓 學習路徑

1. **本地開發** → 使用 `deploy.sh` 熟悉部署流程
2. **開發環境部署** → 推送到 develop 分支，觀察 CI/CD
3. **正式環境部署** → 合併到 master，體驗生產部署
4. **監控和維護** → 學習日誌查看和問題排查
5. **優化和擴展** → 根據需求調整配置

## 💡 最佳實踐

### 分支策略
```
feature/* → develop → master → production
   ↓          ↓         ↓
 開發      開發環境   正式環境
```

### 部署頻率
- **開發環境**: 每次提交自動部署
- **正式環境**: 經過充分測試後部署 (每週/每兩週)

### 回滾策略
- 保留最近 3-5 個 Docker 鏡像版本
- 使用 Git 標籤標記正式發布版本
- 準備快速回滾腳本

## 📞 支援

如有問題：
1. 查看對應環境的部署文檔
2. 檢查 GitHub Actions 日誌
3. 查看 Docker 容器日誌
4. 參考常見問題解答
5. 提交 GitHub Issue

---

**記住**: 開發環境用於測試，正式環境用於生產。保持兩者獨立可以確保開發不會影響線上服務！
