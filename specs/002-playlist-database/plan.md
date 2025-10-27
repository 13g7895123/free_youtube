# 技術實作計畫：播放清單與資料庫整合

**功能分支**: `002-playlist-database`  
**建立日期**: 2025-10-27  
**技術框架**: CodeIgniter 4 (CI4)  
**資料庫**: MariaDB 10.6+

## 技術架構概覽

### 後端技術棧
- **PHP 框架**: CodeIgniter 4 (最新穩定版)
- **PHP 版本**: 8.1+
- **資料庫**: MariaDB 10.6+
- **ORM**: CodeIgniter 4 Model (Query Builder)
- **API 風格**: RESTful API

### 前端技術棧
- **框架**: Vue.js 3.x (Composition API)
- **建置工具**: Vite
- **API 整合**: YouTube IFrame Player API
- **樣式**: Modern CSS / Tailwind CSS

### 開發工具
- **套件管理**: Composer (後端), npm (前端)
- **版本控制**: Git
- **容器化**: Docker + Docker Compose
- **資料庫管理**: phpMyAdmin (http://localhost:8081)

## 實作階段規劃

### 第一階段：後端基礎建置（第 1 週）
- [ ] 建立 CodeIgniter 4 專案結構
- [ ] 設定 MariaDB 資料庫連線
- [ ] 啟動 phpMyAdmin 容器進行資料庫管理
- [ ] 建立資料庫遷移檔案（Migrations）
- [ ] 使用 phpMyAdmin 驗證資料表結構
- [ ] 實作 Models 和 Entities
- [ ] 設定 CORS 過濾器
- [ ] 建立 API 回應格式輔助函數
- [ ] 設定路由（Routes）

### 第二階段：影片 API 開發（第 1-2 週）
- [ ] 實作 VideoController 所有 CRUD 方法
- [ ] 新增影片驗證規則
- [ ] 實作搜尋功能（標題、頻道）
- [ ] 實作 YouTube 影片 ID 重複檢查
- [ ] 建立 VideoModel 單元測試
- [ ] 測試 API 端點

### 第三階段：播放清單 API 開發（第 2-3 週）
- [ ] 實作 PlaylistController
- [ ] 實作 PlaylistItemController
- [ ] 實作位置管理邏輯（自動重排）
- [ ] 實作批次重新排序功能
- [ ] 建立播放清單相關單元測試
- [ ] 測試複雜的關聯查詢

### 第四階段：前端整合（第 3-4 週）
- [ ] 建立 Vue.js API 服務層
- [ ] 實作影片庫 UI 介面
- [ ] 實作播放清單管理 UI
- [ ] 實作拖曳排序功能（drag-and-drop）
- [ ] 整合既有的 YouTube 播放器
- [ ] 實作播放清單播放功能

### 第五階段：進階功能（第 4-5 週）
- [ ] 實作搜尋與篩選功能
- [ ] 實作分頁載入
- [ ] 新增載入狀態與錯誤處理
- [ ] 優化資料庫查詢效能
- [ ] 新增快取層（Redis 選用）
- [ ] 實作資料匯出/匯入功能

### 第六階段：測試與部署（第 5-6 週）
- [ ] 整合測試
- [ ] 效能測試與優化
- [ ] 安全性稽核
- [ ] Docker 部署環境設定
- [ ] 撰寫 API 文件（Swagger/OpenAPI）
- [ ] 撰寫使用者手冊

## 環境設定檔

### .env（資料庫設定）
```env
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = development

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = mariadb
database.default.database = free_youtube
database.default.username = root
database.default.password = secret
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'http://localhost:8080'
app.indexPage = ''
app.forceGlobalSecureRequests = false
```

### docker-compose.yml（新增 MariaDB 與 phpMyAdmin 服務）
```yaml
version: '3.8'

services:
  # MariaDB 資料庫服務
  mariadb:
    image: mariadb:10.6
    container_name: free_youtube_db
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: free_youtube
      MYSQL_USER: app_user
      MYSQL_PASSWORD: app_password
      TZ: Asia/Taipei
    ports:
      - "3306:3306"
    volumes:
      - mariadb_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    networks:
      - app_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  # phpMyAdmin 資料庫管理工具
  phpmyadmin:
    image: phpmyadmin:latest
    container_name: free_youtube_phpmyadmin
    environment:
      PMA_HOST: mariadb
      PMA_PORT: 3306
      PMA_USER: root
      PMA_PASSWORD: secret
      UPLOAD_LIMIT: 100M
      MEMORY_LIMIT: 512M
      MAX_EXECUTION_TIME: 600
    ports:
      - "8081:80"
    depends_on:
      mariadb:
        condition: service_healthy
    networks:
      - app_network
    restart: unless-stopped

  # CodeIgniter 4 後端服務
  ci4_backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: free_youtube_backend
    volumes:
      - ./backend:/var/www/html
    ports:
      - "8080:8080"
    depends_on:
      mariadb:
        condition: service_healthy
    networks:
      - app_network
    environment:
      - CI_ENVIRONMENT=development

  # Vue.js 前端服務（開發模式）
  vue_frontend:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: free_youtube_frontend
    volumes:
      - ./src:/app/src
      - ./public:/app/public
    ports:
      - "5173:5173"
    depends_on:
      - ci4_backend
    networks:
      - app_network

volumes:
  mariadb_data:
    driver: local

networks:
  app_network:
    driver: bridge
```

### 存取資訊

- **phpMyAdmin 網址**: http://localhost:8081
- **登入帳號**: root
- **登入密碼**: secret
- **資料庫名稱**: free_youtube

## 安全性考量

### 1. SQL 注入防護
- 全程使用 CodeIgniter Query Builder 或 ORM
- 絕不使用原始 SQL 字串拼接
- 啟用預處理語句（Prepared Statements）

### 2. CSRF 保護
- 啟用 CI4 CSRF 過濾器
- 在表單中加入 CSRF token
- API 使用 Token 驗證

### 3. 輸入驗證
- 使用 CI4 內建驗證規則
- 自訂驗證規則處理特殊需求
- 白名單驗證所有使用者輸入

### 4. XSS 防護
- 輸出時使用 `esc()` 函數
- 設定 Content Security Policy (CSP)
- 限制 HTML 輸入

### 5. API 安全
- 實作速率限制（Rate Limiting）
- 使用 JWT 或 API Key 認證（選用）
- 正確設定 CORS 規則
- HTTPS 加密傳輸

## 效能優化策略

### 1. 資料庫優化
- **索引設計**: 在常查詢欄位建立索引
- **查詢優化**: 使用 EXPLAIN 分析慢查詢
- **連線池**: 設定適當的連線池大小
- **讀寫分離**: 大流量時考慮主從架構

### 2. 應用層快取
- **查詢快取**: 快取常用的資料庫查詢結果
- **Redis 整合**: 使用 Redis 儲存快取資料
- **HTTP 快取**: 設定適當的 Cache-Control 標頭

### 3. API 效能
- **分頁載入**: 避免一次載入大量資料
- **欄位選擇**: 只查詢需要的欄位
- **預先載入**: 使用 JOIN 避免 N+1 查詢問題
- **壓縮**: 啟用 Gzip 壓縮回應內容

### 4. 前端優化
- **虛擬滾動**: 大量列表使用虛擬滾動
- **延遲載入**: 圖片和組件按需載入
- **快取策略**: 利用 Service Worker 快取資源

## 測試策略

### 單元測試（PHPUnit）
```php
// tests/unit/VideoModelTest.php
class VideoModelTest extends CIUnitTestCase
{
    public function testCreateVideo()
    {
        $model = new VideoModel();
        $data = [
            'video_id' => 'test123',
            'title' => '測試影片',
            'youtube_url' => 'https://youtube.com/watch?v=test123'
        ];
        $id = $model->insert($data);
        $this->assertIsNumeric($id);
    }
}
```

### 整合測試
- 測試 API 端點回應
- 測試資料庫交易
- 測試 CRUD 完整流程

### E2E 測試
- 使用者建立播放清單流程
- 影片新增與播放流程
- 拖曳排序功能測試

## 部署檢查清單

### 生產環境設定
- [ ] 設定 `CI_ENVIRONMENT = production`
- [ ] 更新資料庫連線資訊
- [ ] 啟用查詢快取
- [ ] 設定資料庫自動備份
- [ ] 設定 SSL/HTTPS 憑證
- [ ] 設定監控與日誌系統
- [ ] 優化 Composer autoloader (`--optimize`)
- [ ] 停用除錯模式
- [ ] 設定正確的檔案權限（writable 目錄）
- [ ] 設定防火牆規則

### 效能指標
- API 回應時間 < 200ms（95% 請求）
- 資料庫查詢時間 < 50ms（平均）
- 支援 1000+ 影片不影響效能
- 處理 100+ 並發使用者
- 系統可用率 99.9%
- 零 SQL 注入漏洞

## 文件需求

### API 文件
- 使用 OpenAPI 3.0 格式
- 包含所有端點說明
- 提供請求/回應範例
- 說明錯誤代碼

### 技術文件
- 資料庫架構圖
- 系統架構圖
- 安裝與設定指南
- 開發者指南

### 使用者文件
- 功能使用手冊
- 常見問題 FAQ
- 疑難排解指南

## 未來擴充功能

- 使用者認證與多使用者支援
- 雲端儲存整合（縮圖存儲）
- 進階搜尋（Elasticsearch）
- WebSocket 即時更新
- 行動應用程式（使用相同 API）
- 影片統計分析
- 社交功能（分享、評論）
- 從 YouTube 匯入播放清單

---

**文件版本**: 1.0  
**最後更新**: 2025-10-27  
**維護者**: 開發團隊
