# GitHub Actions CI/CD Workflows

本目錄包含專案的 CI/CD 自動化配置。

## 工作流程文件

### ci-cd.yml
主要的 CI/CD pipeline，包含兩個部署任務：

#### 1. deploy-production (正式環境部署)
- **觸發**: master 分支推送
- **執行**: `deploy-prod.sh`
- **目標**: 生產環境

#### 2. deploy-development (開發環境部署)
- **觸發**: develop 分支推送
- **執行**: `deploy.sh`
- **目標**: 開發環境

## 快速開始

### 配置 GitHub Secrets

在 Repository Settings → Secrets and variables → Actions 中添加：

```
SSH_PRIVATE_KEY    # SSH 私鑰內容
SSH_HOST          # VPS 主機地址
SSH_USER          # SSH 用戶名
```

### 觸發部署

```bash
# 部署到開發環境
git checkout develop
git add .
git commit -m "feat: new feature"
git push origin develop

# 部署到正式環境
git checkout master
git merge develop
git push origin master
```

## 查看部署日誌

1. 進入 GitHub Repository
2. 點擊 "Actions" 標籤
3. 選擇最近的 workflow run
4. 查看 deploy-production 或 deploy-development job

## 相關文檔

- [CI/CD 使用指南](../../CICD_GUIDE.md)
- [正式環境部署](../../DEPLOYMENT_PROD.md)
- [部署架構總結](../../DEPLOYMENT_SUMMARY.md)
