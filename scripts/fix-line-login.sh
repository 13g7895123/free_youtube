#!/bin/bash

# ========================================
# LINE Login 修復腳本
# ========================================
# 修復 composer.json 缺少 App 命名空間導致的問題
# 執行方式: ./fix-line-login.sh

set -e  # 遇到錯誤時立即退出

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 獲取腳本目錄
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo ""
echo "========================================================"
echo -e "${BLUE}LINE Login 修復腳本${NC}"
echo "========================================================"
echo "時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# ========================================
# 1. 檢查環境
# ========================================
echo -e "${BLUE}📋 Step 1: 檢查環境${NC}"

if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker 未運行${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker 運行中${NC}"

if ! docker ps | grep -q "free_youtube_backend_prod"; then
    echo -e "${RED}❌ 找不到 free_youtube_backend_prod 容器${NC}"
    exit 1
fi
echo -e "${GREEN}✅ 後端容器運行中${NC}"

# ========================================
# 2. 備份原始檔案
# ========================================
echo ""
echo -e "${BLUE}📋 Step 2: 備份原始檔案${NC}"

BACKUP_DIR="./backups"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="${BACKUP_DIR}/composer.json.backup.$(date +%Y%m%d_%H%M%S)"

if [ -f "backend/composer.json" ]; then
    cp "backend/composer.json" "$BACKUP_FILE"
    echo -e "${GREEN}✅ 備份完成: ${BACKUP_FILE}${NC}"
else
    echo -e "${RED}❌ 找不到 backend/composer.json${NC}"
    exit 1
fi

# ========================================
# 3. 修改 composer.json
# ========================================
echo ""
echo -e "${BLUE}📋 Step 3: 修改 composer.json${NC}"

# 使用 Python 修改 JSON（比 jq 更可靠）
python3 << 'EOF'
import json

# 讀取 composer.json
with open('backend/composer.json', 'r') as f:
    data = json.load(f)

# 檢查是否已存在 App\\ 配置
if 'autoload' in data and 'psr-4' in data['autoload']:
    psr4 = data['autoload']['psr-4']

    if 'App\\' not in psr4:
        # 添加 App\\ 命名空間（放在第一位）
        new_psr4 = {'App\\': 'app/'}
        new_psr4.update(psr4)
        data['autoload']['psr-4'] = new_psr4

        # 寫回檔案
        with open('backend/composer.json', 'w') as f:
            json.dump(data, f, indent=4, ensure_ascii=False)

        print('✅ 已添加 App\\ 命名空間配置')
    else:
        print('ℹ️  App\\ 命名空間已存在')
else:
    print('❌ autoload.psr-4 配置不存在')
    exit(1)
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ composer.json 修改完成${NC}"
else
    echo -e "${RED}❌ 修改失敗${NC}"
    exit 1
fi

# 顯示修改內容
echo ""
echo "修改後的 autoload 配置:"
python3 -c "import json; data=json.load(open('backend/composer.json')); print(json.dumps(data.get('autoload', {}), indent=2))"

# ========================================
# 4. 部署到容器
# ========================================
echo ""
echo -e "${BLUE}📋 Step 4: 部署到容器${NC}"

echo "複製 composer.json 到容器..."
docker cp backend/composer.json free_youtube_backend_prod:/var/www/html/composer.json

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ 複製成功${NC}"
else
    echo -e "${RED}❌ 複製失敗${NC}"
    exit 1
fi

echo ""
echo "重新生成 autoload..."
docker exec free_youtube_backend_prod composer dump-autoload --optimize --no-dev

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Autoload 重新生成完成${NC}"
else
    echo -e "${RED}❌ Autoload 生成失敗${NC}"
    exit 1
fi

# ========================================
# 5. 重啟容器
# ========================================
echo ""
echo -e "${BLUE}📋 Step 5: 重啟後端容器${NC}"

echo "正在重啟..."
docker restart free_youtube_backend_prod > /dev/null

# 等待容器就緒
echo "等待容器啟動..."
sleep 8

if docker ps | grep -q "free_youtube_backend_prod.*Up"; then
    echo -e "${GREEN}✅ 容器已重啟${NC}"
else
    echo -e "${RED}❌ 容器重啟失敗${NC}"
    exit 1
fi

# ========================================
# 6. 驗證修復
# ========================================
echo ""
echo -e "${BLUE}📋 Step 6: 驗證修復${NC}"

# 測試 1: JwtHelper 是否能載入
echo ""
echo "測試 1: 檢查 JwtHelper 類別..."
RESULT=$(docker exec free_youtube_backend_prod php -r "
require '/var/www/html/vendor/autoload.php';
try {
    if (class_exists('App\Helpers\JwtHelper')) {
        echo 'OK';
    } else {
        echo 'NOT_FOUND';
    }
} catch (Exception \$e) {
    echo 'ERROR';
}
" 2>&1)

if [ "$RESULT" = "OK" ]; then
    echo -e "${GREEN}✅ JwtHelper 可以正常載入${NC}"
else
    echo -e "${RED}❌ JwtHelper 仍然無法載入: $RESULT${NC}"
    echo -e "${YELLOW}⚠️  可能需要重新建置映像檔${NC}"
fi

# 測試 2: Health 端點
echo ""
echo "測試 2: 檢查 Health 端點..."
if curl -f -s -o /dev/null "http://localhost:9204/api/health"; then
    echo -e "${GREEN}✅ Health 端點正常${NC}"
else
    echo -e "${YELLOW}⚠️  Health 端點無響應${NC}"
fi

# 測試 3: LINE Login 端點
echo ""
echo "測試 3: 檢查 LINE Login 端點..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:9204/api/auth/line/login")

if [ "$HTTP_CODE" = "302" ]; then
    echo -e "${GREEN}✅ LINE Login 端點返回 302 重定向 (正常)${NC}"
elif [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ LINE Login 端點返回 200${NC}"
else
    echo -e "${YELLOW}⚠️  LINE Login 端點返回 HTTP $HTTP_CODE${NC}"
fi

# ========================================
# 7. 完成
# ========================================
echo ""
echo "========================================================"
echo -e "${GREEN}✅ 修復完成！${NC}"
echo "========================================================"
echo ""
echo "📋 修復摘要:"
echo "  - composer.json 已更新（添加 App\\ 命名空間）"
echo "  - Autoload 已重新生成"
echo "  - 容器已重啟"
echo ""
echo "🔧 備份檔案:"
echo "  - ${BACKUP_FILE}"
echo ""
echo "🧪 下一步測試:"
echo "  1. 執行 LINE 登入測試"
echo "  2. 查看 Debug API:"
echo "     curl \"https://free.youtube.mercylife.cc/api/debug/line-login/recent?limit=5\""
echo ""
echo "📊 如果還有問題:"
echo "  - 查看容器日誌: docker logs free_youtube_backend_prod --tail 50"
echo "  - 查看 LINE 日誌: curl \"https://free.youtube.mercylife.cc/api/debug/line-login/errors\""
echo ""
