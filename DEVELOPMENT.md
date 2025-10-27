# 開發環境設置指南

## 目錄結構

```
free_youtube/
├── backend/              # CodeIgniter 4 後端 API
│   ├── app/
│   ├── public/
│   ├── tests/
│   ├── composer.json
│   ├── .env
│   ├── Dockerfile
│   └── README.md
├── frontend/             # Vue.js 3 前端應用
│   ├── src/
│   ├── public/
│   ├── package.json
│   ├── vite.config.js
│   ├── Dockerfile
│   └── .env
├── docker-compose.yml    # Docker 服務編排
├── docker-startup.md     # Docker 啟動指南
└── README.md
```

## 開發流程

### 1. 前端開發

```bash
cd frontend
npm install
npm run dev
```

訪問: http://localhost:5173

### 2. 後端開發

需要在 Docker 環境中運行 CI4：

```bash
# 使用 Docker 進行後端開發
docker-compose up -d
docker exec free_youtube_backend php spark migrate
docker exec free_youtube_backend php spark serve
```

訪問: http://localhost:8080/api

### 3. 資料庫管理

使用 phpMyAdmin 管理資料庫:
- http://localhost:8081
- 帳號: root
- 密碼: secret

## 常用命令

### Frontend
```bash
cd frontend

# 開發
npm run dev

# 構建
npm run build

# 測試
npm run test

# 代碼檢查
npm run lint
npm run lint:fix

# 代碼格式化
npm run format
```

### Backend (Docker)
```bash
# 進入後端容器
docker exec -it free_youtube_backend sh

# 運行 migrations
docker exec free_youtube_backend php spark migrate

# 創建新 controller
docker exec free_youtube_backend php spark make:controller VideoController -r

# 創建新 model
docker exec free_youtube_backend php spark make:model VideoModel

# 查看路由
docker exec free_youtube_backend php spark routes
```

## 環境變數

### Frontend (.env)
```env
VITE_API_URL=http://localhost:8080/api
```

### Backend (.env)
```env
CI_ENVIRONMENT=development
database.default.hostname=mariadb
database.default.database=free_youtube
database.default.username=root
database.default.password=secret
```

## API 端點

基礎 URL: `http://localhost:8080/api`

## 代碼風格

- **Frontend**: ESLint + Prettier (自動格式化)
- **Backend**: PSR-12 (CodeIgniter 推薦)

## 常見問題

### 資料庫連線失敗
確保 MariaDB 容器正在運行：
```bash
docker-compose ps
docker logs free_youtube_db
```

### 前端無法連接到後端
檢查 CORS 配置和 `VITE_API_URL` 環境變數

### Port 衝突
修改 `docker-compose.yml` 中的 port 映射
