#!/bin/bash

# YouTube Loop Player - Production Deployment Script
# 此腳本會構建專案並使用 Docker Compose 啟動生產環境

set -e  # 遇到錯誤時立即退出

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "================================================"
echo "YouTube Loop Player - Production Deployment"
echo "================================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Docker is not running${NC}"
    echo "Please start Docker and try again"
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

# Check if node_modules exists in frontend
if [ ! -d "frontend/node_modules" ]; then
    echo -e "${YELLOW}⚠️  Frontend dependencies not found. Will install them...${NC}"
fi

echo "📦 Step 1: Installing/updating frontend dependencies..."
if ! (cd frontend && npm ci); then
    echo -e "${YELLOW}⚠️  npm ci failed, trying npm install...${NC}"
    cd frontend && npm install && cd ..
else
    cd .. > /dev/null 2>&1
fi

echo ""
echo "🔨 Step 2: Building frontend production bundle..."
if [ -d "frontend/dist" ]; then
    echo "Cleaning previous build..."
    rm -rf frontend/dist
fi
cd frontend && npm run build && cd ..

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
