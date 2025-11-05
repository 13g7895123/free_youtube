#!/bin/bash
# LINE Login 日誌查詢工具
# 使用方式: ./check_line_logs.sh [command] [params]

API_URL="http://localhost:8080/api/auth/line/logs"

# 顏色設定
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 顯示說明
show_help() {
    echo -e "${BLUE}LINE Login 日誌查詢工具${NC}"
    echo ""
    echo "使用方式:"
    echo "  $0 errors [limit]              - 查詢最近的錯誤日誌"
    echo "  $0 session <session_id>        - 查詢特定 session 的完整流程"
    echo "  $0 user <line_user_id> [limit] - 查詢特定用戶的登入歷史"
    echo "  $0 db-errors [limit]           - 直接查詢資料庫的錯誤記錄"
    echo "  $0 db-recent [limit]           - 直接查詢資料庫的最近記錄"
    echo "  $0 db-stats                    - 顯示今日統計"
    echo ""
    echo "範例:"
    echo "  $0 errors 10"
    echo "  $0 session line_login_673569a4e2d7f8.12345678"
    echo "  $0 user U1234567890abcdef 20"
    echo ""
}

# API 查詢 - 錯誤日誌
query_errors() {
    local limit=${1:-10}
    echo -e "${YELLOW}查詢最近 ${limit} 筆錯誤日誌...${NC}"

    # 檢查是否有 jq
    if command -v jq &> /dev/null; then
        curl -s "${API_URL}/errors?limit=${limit}" | jq -r '
            .data[] |
            "[\(.created_at)] Session: \(.session_id)\n" +
            "  步驟: \(.step) | 狀態: \(.status)\n" +
            "  錯誤: \(.error_message // "N/A")\n" +
            "  IP: \(.ip_address)\n"
        '
    else
        # 沒有 jq，改用資料庫查詢
        echo -e "${BLUE}(使用資料庫直接查詢)${NC}"
        db_query_errors "$limit"
    fi
}

# API 查詢 - Session 流程
query_session() {
    local session_id=$1
    if [ -z "$session_id" ]; then
        echo -e "${RED}錯誤: 請提供 session_id${NC}"
        return 1
    fi

    echo -e "${YELLOW}查詢 Session: ${session_id}${NC}"

    # 檢查是否有 jq
    if command -v jq &> /dev/null; then
        curl -s "${API_URL}/session/${session_id}" | jq -r '
            "Session ID: \(.session_id)\n總計: \(.count) 個步驟\n" +
            "---\n" +
            (.data[] |
            "[\(.created_at)] \(.step)\n" +
            "  狀態: \(.status)\n" +
            (if .error_message then "  錯誤: \(.error_message)\n" else "" end) +
            (if .request_data then "  請求: \(.request_data)\n" else "" end) +
            "")
        '
    else
        # 沒有 jq，改用資料庫查詢
        echo -e "${BLUE}(使用資料庫直接查詢)${NC}"
        docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as time,
                step,
                status,
                LEFT(error_message, 80) as error,
                LEFT(request_data, 80) as request
            FROM line_login_logs
            WHERE session_id = '${session_id}'
            ORDER BY id ASC;
        " 2>/dev/null
    fi
}

# API 查詢 - 用戶歷史
query_user() {
    local user_id=$1
    local limit=${2:-10}

    if [ -z "$user_id" ]; then
        echo -e "${RED}錯誤: 請提供 LINE User ID${NC}"
        return 1
    fi

    echo -e "${YELLOW}查詢用戶 ${user_id} 的最近 ${limit} 筆記錄...${NC}"

    # 檢查是否有 jq
    if command -v jq &> /dev/null; then
        curl -s "${API_URL}/user/${user_id}?limit=${limit}" | jq -r '
            "LINE User ID: \(.line_user_id)\n總計: \(.count) 筆記錄\n" +
            "---\n" +
            (.data[] |
            "[\(.created_at)] Session: \(.session_id)\n" +
            "  步驟: \(.step) | 狀態: \(.status)\n" +
            (if .error_message then "  錯誤: \(.error_message)\n" else "" end) +
            "")
        '
    else
        # 沒有 jq，改用資料庫查詢
        echo -e "${BLUE}(使用資料庫直接查詢)${NC}"
        docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as time,
                session_id,
                step,
                status,
                LEFT(error_message, 80) as error
            FROM line_login_logs
            WHERE line_user_id = '${user_id}'
            ORDER BY id DESC
            LIMIT ${limit};
        " 2>/dev/null
    fi
}

# 直接查詢資料庫 - 錯誤記錄
db_query_errors() {
    local limit=${1:-10}
    echo -e "${YELLOW}從資料庫查詢最近 ${limit} 筆錯誤...${NC}"
    docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
        SELECT
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as time,
            session_id,
            step,
            status,
            LEFT(error_message, 80) as error,
            ip_address
        FROM line_login_logs
        WHERE status = 'error'
        ORDER BY id DESC
        LIMIT ${limit};
    " 2>/dev/null
}

# 直接查詢資料庫 - 最近記錄
db_query_recent() {
    local limit=${1:-10}
    echo -e "${YELLOW}從資料庫查詢最近 ${limit} 筆記錄...${NC}"
    docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
        SELECT
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as time,
            session_id,
            step,
            status,
            line_user_id,
            LEFT(error_message, 50) as error
        FROM line_login_logs
        ORDER BY id DESC
        LIMIT ${limit};
    " 2>/dev/null
}

# 直接查詢資料庫 - 統計
db_query_stats() {
    echo -e "${YELLOW}查詢今日統計...${NC}"
    docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
        SELECT
            COUNT(*) as total_logs,
            COUNT(DISTINCT session_id) as total_sessions,
            SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) as success_count,
            SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as error_count,
            SUM(CASE WHEN status='warning' THEN 1 ELSE 0 END) as warning_count,
            SUM(CASE WHEN step='complete' AND status='success' THEN 1 ELSE 0 END) as completed_logins
        FROM line_login_logs
        WHERE DATE(created_at) = CURDATE();
    " 2>/dev/null

    echo ""
    echo -e "${YELLOW}最常見的錯誤 (Top 5):${NC}"
    docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
        SELECT
            step,
            LEFT(error_message, 60) as error,
            COUNT(*) as count
        FROM line_login_logs
        WHERE status = 'error' AND DATE(created_at) = CURDATE()
        GROUP BY step, error_message
        ORDER BY count DESC
        LIMIT 5;
    " 2>/dev/null
}

# 主程式
case "$1" in
    errors)
        query_errors "$2"
        ;;
    session)
        query_session "$2"
        ;;
    user)
        query_user "$2" "$3"
        ;;
    db-errors)
        db_query_errors "$2"
        ;;
    db-recent)
        db_query_recent "$2"
        ;;
    db-stats)
        db_query_stats
        ;;
    help|--help|-h|"")
        show_help
        ;;
    *)
        echo -e "${RED}未知指令: $1${NC}"
        echo ""
        show_help
        exit 1
        ;;
esac
