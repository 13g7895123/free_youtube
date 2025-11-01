#!/bin/bash

# LINE 登入除錯系統部署腳本

set -e

echo "========================================="
echo "LINE 登入除錯系統 - 快速部署"
echo "========================================="
echo ""

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 檢查是否在專案根目錄
if [ ! -f "docker-compose.prod.yml" ]; then
    echo -e "${RED}錯誤: 請在專案根目錄執行此腳本${NC}"
    exit 1
fi

echo -e "${YELLOW}步驟 1: 建立資料庫表...${NC}"

# 從 docker-compose.prod.yml 或 .env 讀取資料庫設定
DB_HOST=${database_default_hostname:-"localhost"}
DB_NAME=${database_default_database:-"free_youtube"}
DB_USER=${database_default_username:-"root"}

echo "資料庫主機: $DB_HOST"
echo "資料庫名稱: $DB_NAME"
echo "資料庫用戶: $DB_USER"
echo ""

read -s -p "請輸入資料庫密碼: " DB_PASS
echo ""

# 執行 SQL
echo -e "${YELLOW}正在建立 line_login_logs 資料表...${NC}"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < backend/database/migrations/2025-11-01-000001_create_line_login_logs.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ 資料表建立成功${NC}"
else
    echo -e "${RED}✗ 資料表建立失敗${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}步驟 2: 重新部署服務...${NC}"
echo "正在重建 backend 和 frontend..."
docker-compose -f docker-compose.prod.yml up -d --build backend frontend

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ 服務重新部署成功${NC}"
else
    echo -e "${RED}✗ 服務部署失敗${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}步驟 3: 檢查容器狀態...${NC}"
sleep 5
docker-compose -f docker-compose.prod.yml ps backend frontend

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}部署完成！${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "測試 API:"
echo ""
echo "1. 查詢最近的 logs:"
echo "   curl -X GET 'https://your-domain.com/api/auth/line/logs?limit=20'"
echo ""
echo "2. 查詢錯誤:"
echo "   curl -X GET 'https://your-domain.com/api/auth/line/errors?limit=20'"
echo ""
echo "3. 使用測試腳本:"
echo "   ./test-line-debug-api.sh https://your-domain.com"
echo ""
echo "詳細文件請參考: docs/LINE_LOGIN_DEBUG.md"
echo ""
