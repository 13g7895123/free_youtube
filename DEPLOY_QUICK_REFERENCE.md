# Deployment Quick Reference

## Quick Deploy

```bash
./deploy.sh
```

That's it! The script handles everything.

## What It Does

1. ✅ Checks Docker is running
2. ✅ Verifies project structure
3. ✅ Installs frontend dependencies
4. ✅ Builds frontend (creates `dist/` folder)
5. ✅ Stops old containers
6. ✅ Builds Docker images
7. ✅ Starts all services
8. ✅ Verifies deployment

## After Deployment

Access your application:

| Service | URL | Port |
|---------|-----|------|
| Frontend (Web UI) | http://localhost | 80 |
| Backend API | http://localhost:8080 | 8080 |
| phpMyAdmin | http://localhost:8081 | 8081 |
| MariaDB | localhost:3307 | 3307 |

## Common Commands

```bash
# View all logs
docker compose logs -f

# View specific service logs
docker compose logs -f backend
docker compose logs -f frontend

# Check service status
docker compose ps

# Restart services
docker compose restart

# Stop everything
docker compose down

# Stop and remove volumes (⚠️ deletes data)
docker compose down -v
```

## Troubleshooting

### "Docker is not running"
```bash
# Start Docker Desktop (Mac/Windows)
# Or on Linux:
sudo systemctl start docker
```

### "frontend directory not found"
```bash
# Make sure you're in project root
cd /path/to/free_youtube
./deploy.sh
```

### "Port already in use"
```bash
# Check what's using the port
sudo lsof -i :80

# Or change port in .env
echo "FRONTEND_PORT=8000" >> .env
```

### Build Failed
```bash
# Check Node.js version
node --version  # Should be 20+

# Manual build
cd frontend
npm ci
npm run build
cd ..
```

### Containers Won't Start
```bash
# View detailed logs
docker compose logs --tail=100

# Rebuild everything
docker compose down
docker compose build --no-cache
docker compose up -d
```

## File Locations

```
/home/jarvis/project/idea/free_youtube/
├── deploy.sh              # Main deployment script
├── docker-compose.yml     # Services configuration
├── .env                   # Environment variables
├── Dockerfile            # Frontend container
├── nginx.conf            # Nginx configuration
├── frontend/
│   ├── dist/             # Built files (generated)
│   └── package.json
└── backend/
    └── Dockerfile        # Backend container
```

## Environment Variables

Edit `.env` to customize:

```env
# Frontend port (default: 80)
FRONTEND_PORT=80

# Backend API port (default: 8080)
BACKEND_PORT=8080

# Database port (default: 3307)
MYSQL_PORT=3307

# Database credentials
MYSQL_ROOT_PASSWORD=secret
MYSQL_DATABASE=free_youtube
MYSQL_USER=app_user
MYSQL_PASSWORD=app_password
```

## Health Checks

```bash
# Test frontend
curl http://localhost

# Test backend API
curl http://localhost:8080/api/health

# Test database
docker compose exec mariadb mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SELECT 1"
```

## Backup Database

```bash
# Export
docker compose exec mariadb mysqldump -u root -psecret free_youtube > backup.sql

# Import
docker compose exec -T mariadb mysql -u root -psecret free_youtube < backup.sql
```

## CI/CD Deployment

Automatic deployment on push to `master`:

1. Code pushed to GitHub
2. GitHub Actions runs tests
3. Connects to server via SSH
4. Pulls latest code
5. Runs `./deploy.sh`
6. Deployment complete!

Required GitHub Secrets:
- `SSH_HOST`: Server IP
- `SSH_USER`: SSH username
- `SSH_PRIVATE_KEY`: SSH key

## Support

- **Full Guide**: See `DEPLOYMENT.md`
- **Error Fix**: See `DEPLOYMENT_FIX.md`
- **CI/CD Setup**: See `.github/workflows/README.md`

---

**Quick Help**
```bash
./deploy.sh              # Deploy
docker compose ps        # Status
docker compose logs -f   # Logs
docker compose down      # Stop
```
