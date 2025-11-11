#!/bin/bash

# YouTube Loop Player - Production Deployment Script
# 此腳本會構建專案並使用 Docker Compose 啟動生產環境

set -e  # 遇到錯誤時立即退出

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get the script directory and change to project root
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "================================================"
echo "YouTube Loop Player - Production Deployment"
echo "================================================"
echo "Working directory: $(pwd)"
echo "Script location: ${BASH_SOURCE[0]}"
echo ""

# Debug: List directory contents
echo "📂 Project structure:"
ls -la | head -15
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Docker is not running${NC}"
    echo "Please start Docker and try again"
    exit 1
fi

# Verify project structure
if [ ! -d "frontend" ]; then
    echo -e "${RED}❌ Error: frontend directory not found${NC}"
    echo "Current directory: $(pwd)"
    echo "Directory contents:"
    ls -la
    exit 1
fi

if [ ! -d "backend" ]; then
    echo -e "${RED}❌ Error: backend directory not found${NC}"
    echo "Current directory: $(pwd)"
    exit 1
fi

# 檢查是否存在 .env 文件
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env file not found. Creating from .env.example...${NC}"
    if [ ! -f ".env.example" ]; then
        echo -e "${RED}❌ Error: .env.example not found${NC}"
        exit 1
    fi
    cp .env.example .env
    echo -e "${GREEN}✅ Created .env file. Please configure it before running again.${NC}"
    echo ""
    exit 1
fi

# 載入環境變數
source .env
BACKEND_PORT=${BACKEND_PORT:-8080}

echo ""
echo "📋 Checking backend dependencies and configuration..."

# 檢查後端 .env 文件
if [ ! -f "backend/.env" ]; then
    echo -e "${YELLOW}⚠️  backend/.env not found. Creating from backend/.env.example...${NC}"
    if [ ! -f "backend/.env.example" ]; then
        echo -e "${RED}❌ Error: backend/.env.example not found${NC}"
        exit 1
    fi
    cp backend/.env.example backend/.env
    echo -e "${GREEN}✅ Created backend/.env file${NC}"
else
    echo -e "${GREEN}✅ backend/.env exists${NC}"
fi

# 檢查 vendor 目錄 (Composer dependencies)
if [ ! -d "backend/vendor" ]; then
    echo -e "${YELLOW}⚠️  backend/vendor not found. Installing Composer dependencies...${NC}"

    # 嘗試使用本地 composer (如果有安裝)
    if command -v composer &> /dev/null; then
        echo "Using local Composer installation..."
        if ! (cd backend && composer install --no-dev --optimize-autoloader && cd ..); then
            echo -e "${RED}❌ Error: Failed to install backend dependencies with local Composer${NC}"
            exit 1
        fi
    else
        # 如果沒有 composer，使用 Docker 容器來安裝
        echo "Composer not found locally. Using Docker to install dependencies..."
        if ! docker run --rm \
            -v "$(pwd)/backend:/app" \
            -w /app \
            composer:2 \
            install --no-dev --optimize-autoloader --ignore-platform-reqs; then
            echo -e "${RED}❌ Error: Failed to install backend dependencies with Docker${NC}"
            exit 1
        fi
    fi
    echo -e "${GREEN}✅ Backend dependencies installed${NC}"
else
    echo -e "${GREEN}✅ backend/vendor exists${NC}"
fi

# 檢查 writable 目錄
if [ ! -d "backend/writable" ]; then
    echo -e "${YELLOW}⚠️  backend/writable not found. Creating directory...${NC}"
    mkdir -p backend/writable
    mkdir -p backend/writable/cache
    mkdir -p backend/writable/logs
    mkdir -p backend/writable/session
    mkdir -p backend/writable/uploads

    # 設置權限 (對於 Linux/Unix 系統)
    if [ "$(uname)" != "Darwin" ]; then
        chmod -R 777 backend/writable
    fi

    echo -e "${GREEN}✅ backend/writable directory created${NC}"
else
    echo -e "${GREEN}✅ backend/writable exists${NC}"

    # 確保權限正確
    if [ "$(uname)" != "Darwin" ]; then
        chmod -R 777 backend/writable 2>/dev/null || true
    fi
fi

# Check if node_modules exists in frontend
if [ ! -d "frontend/node_modules" ]; then
    echo -e "${YELLOW}⚠️  Frontend dependencies not found. Will install them...${NC}"
fi

echo "📦 Step 1: Installing/updating frontend dependencies..."

# 檢查是否有 npm 可用
if command -v npm &> /dev/null; then
    echo "Using local npm installation..."
    if ! (cd frontend && npm ci && cd ..); then
        echo -e "${YELLOW}⚠️  npm ci failed, trying npm install...${NC}"
        if ! (cd frontend && npm install && cd ..); then
            echo -e "${RED}❌ Error: Failed to install frontend dependencies with local npm${NC}"
            exit 1
        fi
    fi
else
    # 如果沒有 npm，使用 Docker 容器來安裝
    echo "npm not found locally. Using Docker to install dependencies..."

    # 清理可能存在的權限問題的 node_modules
    if [ -d "frontend/node_modules" ]; then
        echo "Cleaning existing node_modules to avoid permission issues..."
        rm -rf frontend/node_modules || sudo rm -rf frontend/node_modules
    fi

    # 獲取當前使用者的 UID 和 GID
    USER_ID=$(id -u)
    GROUP_ID=$(id -g)

    # 使用 Docker 安裝依賴，並設定正確的使用者權限
    if ! docker run --rm \
        -u "${USER_ID}:${GROUP_ID}" \
        -v "$(pwd)/frontend:/app" \
        -w /app \
        node:22-alpine \
        sh -c "npm ci || npm install"; then
        echo -e "${RED}❌ Error: Failed to install frontend dependencies with Docker${NC}"
        exit 1
    fi
fi

echo ""
echo "🔨 Step 2: Building frontend production bundle..."
if [ -d "frontend/dist" ]; then
    echo "Cleaning previous build..."
    rm -rf frontend/dist
fi

# 檢查是否有 npm 可用來執行 build
if command -v npm &> /dev/null; then
    echo "Using local npm for build..."
    if ! (cd frontend && npm run build && cd ..); then
        echo -e "${RED}❌ Error: Frontend build command failed with local npm${NC}"
        exit 1
    fi
else
    # 使用 Docker 來執行 build
    echo "Using Docker for build..."

    # 獲取當前使用者的 UID 和 GID
    USER_ID=$(id -u)
    GROUP_ID=$(id -g)

    if ! docker run --rm \
        -u "${USER_ID}:${GROUP_ID}" \
        -v "$(pwd)/frontend:/app" \
        -w /app \
        node:22-alpine \
        npm run build; then
        echo -e "${RED}❌ Error: Frontend build command failed with Docker${NC}"
        exit 1
    fi
fi

# Verify build output
if [ ! -d "frontend/dist" ] || [ ! -f "frontend/dist/index.html" ]; then
    echo -e "${RED}❌ Error: Frontend build failed - dist folder not created${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Frontend build successful${NC}"

echo ""
echo "🐳 Step 3: Stopping existing containers..."
docker compose down --remove-orphans

echo ""
echo "🐳 Step 4: Building Docker images..."
docker compose build --no-cache

echo ""
echo "🚀 Step 5: Starting production server..."
docker compose up -d

# Wait for services to be healthy
echo ""
echo "⏳ Waiting for services to start..."
sleep 5

# Check if containers are running
if ! docker compose ps | grep -q "Up"; then
    echo -e "${RED}❌ Error: Containers failed to start${NC}"
    echo "Showing logs:"
    docker compose logs --tail=50
    exit 1
fi

echo ""
echo "================================================"
echo -e "${GREEN}✅ Deployment Complete!${NC}"
echo "================================================"
echo ""
echo "🌐 Application is running at: http://localhost:${BACKEND_PORT}"
echo "🔍 phpMyAdmin is running at: http://localhost:${PHPMYADMIN_PORT:-8081}"
echo ""
echo "Useful commands:"
echo "  - View logs:        docker compose logs -f"
echo "  - View backend logs: docker compose logs -f backend"
echo "  - Stop server:      docker compose down"
echo "  - Restart server:   docker compose restart"
echo "  - Check status:     docker compose ps"
echo ""
