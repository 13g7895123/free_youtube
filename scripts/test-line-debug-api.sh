#!/bin/bash

# LINE 登入 Debug API 測試腳本

# 設定
API_BASE_URL="${1:-https://your-domain.com}"

echo "==========================================="
echo "LINE 登入 Debug API 測試"
echo "==========================================="
echo ""
echo "API Base URL: $API_BASE_URL"
echo ""

# 顏色
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 測試 1: 查詢最近的 logs
echo -e "${YELLOW}測試 1: 查詢最近的 logs (limit=10)${NC}"
response=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE_URL/api/auth/line/logs?limit=10")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

if [ "$http_code" = "200" ]; then
    echo -e "${GREEN}✓ 成功 (HTTP $http_code)${NC}"
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
else
    echo -e "${RED}✗ 失敗 (HTTP $http_code)${NC}"
    echo "$body"
fi

echo ""
echo "-------------------------------------------"
echo ""

# 測試 2: 查詢錯誤 logs
echo -e "${YELLOW}測試 2: 查詢錯誤 logs${NC}"
response=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE_URL/api/auth/line/errors?limit=10")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

if [ "$http_code" = "200" ]; then
    echo -e "${GREEN}✓ 成功 (HTTP $http_code)${NC}"
    
    # 顯示錯誤數量
    error_count=$(echo "$body" | jq '.count' 2>/dev/null)
    if [ ! -z "$error_count" ] && [ "$error_count" != "null" ]; then
        echo -e "${YELLOW}發現 $error_count 個錯誤${NC}"
    fi
    
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
else
    echo -e "${RED}✗ 失敗 (HTTP $http_code)${NC}"
    echo "$body"
fi

echo ""
echo "-------------------------------------------"
echo ""

# 測試 3: 查詢特定狀態
echo -e "${YELLOW}測試 3: 查詢特定狀態 (status=error)${NC}"
response=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE_URL/api/auth/line/logs?status=error&limit=5")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

if [ "$http_code" = "200" ]; then
    echo -e "${GREEN}✓ 成功 (HTTP $http_code)${NC}"
    echo "$body" | jq '.data[] | {id, step, status, error_message, created_at}' 2>/dev/null || echo "$body"
else
    echo -e "${RED}✗ 失敗 (HTTP $http_code)${NC}"
    echo "$body"
fi

echo ""
echo "==========================================="
echo "測試完成"
echo "==========================================="
echo ""
echo "使用說明:"
echo "  $0 [API_BASE_URL]"
echo ""
echo "範例:"
echo "  $0 https://your-domain.com"
echo ""
