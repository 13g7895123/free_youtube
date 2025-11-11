# Free YouTube 播放清單管理系統

## 專案結構

```
free_youtube/
├── backend/                    # CodeIgniter 4 後端 API
│   ├── app/
│   │   ├── Config/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Entities/
│   │   └── Database/
│   ├── public/
│   └── tests/
├── frontend/                   # Vue.js 3 前端應用
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── stores/
│   │   ├── services/
│   │   └── router/
│   ├── public/
│   ├── package.json
│   ├── vite.config.js
│   └── vitest.config.js
├── docker-compose.yml          # Docker 服務編排
├── .gitignore
└── README.md

```

## 服務端口

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8080/api
- **phpMyAdmin**: http://localhost:8081
- **MariaDB**: localhost:3306

## 快速開始

### 前提條件
- Node.js 18+
- Docker & Docker Compose
- Composer (for backend)

### 開發環境設置

1. **啟動 Docker 服務**
   ```bash
   docker-compose up -d
   ```

2. **前端開發**
   ```bash
   cd frontend
   npm install
   npm run dev
   ```

3. **後端開發**
   ```bash
   cd backend
   composer install
   php spark serve
   ```

### 資料庫管理
訪問 phpMyAdmin: http://localhost:8081
- 帳號: root
- 密碼: secret

## 技術棧

- **後端**: CodeIgniter 4 + MariaDB
- **前端**: Vue.js 3 + Composition API + Vite
- **容器化**: Docker + Docker Compose
- **API**: RESTful

## 功能特性

- ✅ YouTube 影片儲存與管理
- ✅ 播放清單建立與組織
- ✅ 自動順序播放
- ✅ 拖曳排序
- ✅ 搜尋與篩選
- ✅ 響應式設計

## 開發指南

詳見 `/specs/002-playlist-database/` 中的規劃文件：
- `spec.md` - 功能規格書
- `plan.md` - 技術實作計畫
- `tasks.md` - 實作任務列表
- `data-model.md` - 資料庫設計
