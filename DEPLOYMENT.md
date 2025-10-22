# YouTube Loop Player - 部署指南

本指南說明如何使用 Docker 部署 YouTube Loop Player 到生產環境。

## 📋 前置需求

- Docker (v20.10+)
- Docker Compose (v2.0+)
- Node.js 18+ (用於構建)
- npm 或 yarn

## 🚀 快速部署

### 1. 配置環境變數

首次部署前，請配置 `.env` 文件：

```bash
# 複製範例配置文件
cp .env.example .env

# 編輯 .env 並設置你想要的端口
# 預設端口是 8080
PORT=8080
```

### 2. 執行部署腳本

運行一鍵部署腳本：

```bash
./deploy.sh
```

部署腳本會自動執行以下步驟：
1. 安裝 npm 依賴
2. 構建生產版本
3. 停止現有容器
4. 構建 Docker 鏡像
5. 啟動容器

### 3. 訪問應用

部署完成後，在瀏覽器中訪問：

```
http://localhost:8080
```

（如果你修改了 `.env` 中的 `PORT`，請使用相應的端口）

## 📁 部署文件說明

- **`Dockerfile`** - Docker 鏡像構建配置
- **`docker-compose.yml`** - Docker Compose 服務配置
- **`nginx.conf`** - Nginx Web 服務器配置
- **`.env`** - 環境變數配置（不會提交到 Git）
- **`.env.example`** - 環境變數範例
- **`deploy.sh`** - 一鍵部署腳本
- **`.dockerignore`** - Docker 構建時忽略的文件

## 🔧 常用命令

### 查看日誌

```bash
docker-compose logs -f
```

### 停止服務

```bash
docker-compose down
```

### 重啟服務

```bash
docker-compose restart
```

### 重新構建並啟動

```bash
docker-compose up -d --build
```

### 查看運行狀態

```bash
docker-compose ps
```

## 🔄 更新部署

當你修改了代碼後，重新部署：

```bash
./deploy.sh
```

或手動執行：

```bash
npm run build
docker-compose down
docker-compose up -d --build
```

## 🛠️ 故障排除

### 端口已被占用

如果看到端口錯誤，請修改 `.env` 文件中的 `PORT` 值：

```bash
# .env
PORT=9090
```

然後重新部署。

### 容器無法啟動

檢查 Docker 日誌：

```bash
docker-compose logs
```

### 構建失敗

確保已經正確構建了生產版本：

```bash
npm install
npm run build
```

確認 `dist/` 目錄存在且包含構建文件。

## 🔐 生產環境建議

1. **反向代理**：在生產環境中，建議在前面配置 Nginx 或 Traefik 作為反向代理
2. **HTTPS**：配置 SSL/TLS 證書（可使用 Let's Encrypt）
3. **防火牆**：只開放必要的端口
4. **資源限制**：在 `docker-compose.yml` 中配置資源限制
5. **監控**：設置日誌收集和監控系統

## 📝 自定義配置

### 修改 Nginx 配置

編輯 `nginx.conf` 文件來自定義 Web 服務器行為。

### 添加環境變數

在 `.env` 中添加新的變數，並在 `docker-compose.yml` 中引用。

## 📮 支援

如有問題，請查看：
- 專案 README.md
- Docker 官方文檔
- Nginx 官方文檔
