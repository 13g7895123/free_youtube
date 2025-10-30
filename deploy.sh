#!/bin/bash

# YouTube Loop Player - Production Deployment Script
# 此腳本會構建專案並使用 Docker Compose 啟動生產環境

set -e  # 遇到錯誤時立即退出

echo "================================================"
echo "YouTube Loop Player - Production Deployment"
echo "================================================"
echo ""

# 檢查是否存在 .env 文件
if [ ! -f ".env" ]; then
    echo "⚠️  .env file not found. Creating from .env.example..."
    cp .env.example .env
    echo "✅ Created .env file. Please configure it before running again."
    echo ""
    exit 1
fi

# 載入環境變數
source .env
BACKEND_PORT=${BACKEND_PORT:-8080}

echo "📦 Step 1: Installing frontend dependencies..."
cd frontend && npm install && cd ..

echo ""
echo "🔨 Step 2: Building frontend production bundle..."
cd frontend && npm run build && cd ..

echo ""
echo "🐳 Step 3: Stopping existing containers..."
docker compose down

echo ""
echo "🐳 Step 4: Building Docker image..."
docker compose build

echo ""
echo "🚀 Step 5: Starting production server..."
docker compose up -d

echo ""
echo "================================================"
echo "✅ Deployment Complete!"
echo "================================================"
echo ""
echo "🌐 Application is running at: http://localhost:${BACKEND_PORT}"
echo ""
echo "Useful commands:"
echo "  - View logs:        docker compose logs -f"
echo "  - Stop server:      docker compose down"
echo "  - Restart server:   docker compose restart"
echo ""
