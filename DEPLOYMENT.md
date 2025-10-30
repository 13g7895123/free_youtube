# Deployment Guide

This document provides detailed instructions for deploying the Free YouTube application.

## Quick Start

```bash
# Make sure Docker is running
docker info

# Run the deployment script
./deploy.sh
```

The application will be available at:
- **Frontend**: http://localhost (or the port specified in FRONTEND_PORT)
- **Backend API**: http://localhost:8080 (or the port specified in BACKEND_PORT)
- **phpMyAdmin**: http://localhost:8081 (or the port specified in PHPMYADMIN_PORT)

## Prerequisites

1. **Docker**: Version 20.10 or higher
2. **Docker Compose**: Version 2.0 or higher
3. **Node.js**: Version 20 or higher (for local development/building)
4. **Git**: For pulling updates

### Verify Prerequisites

```bash
# Check Docker
docker --version
docker compose version

# Check Node.js (if building locally)
node --version
npm --version
```

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
```

Key configuration options:

```env
# Frontend Port (default: 80)
FRONTEND_PORT=80

# Backend API Port (default: 8080)
BACKEND_PORT=8080

# phpMyAdmin Port (default: 8081)
PHPMYADMIN_PORT=8081

# Database Configuration
MYSQL_PORT=3307
MYSQL_ROOT_PASSWORD=secret
MYSQL_DATABASE=free_youtube
MYSQL_USER=app_user
MYSQL_PASSWORD=app_password
```

## Deployment Steps

### Automated Deployment

The `deploy.sh` script handles everything automatically:

```bash
./deploy.sh
```

This script will:
1. ✅ Check Docker is running
2. ✅ Verify .env file exists
3. ✅ Install/update frontend dependencies
4. ✅ Build frontend production bundle
5. ✅ Stop existing containers
6. ✅ Build Docker images
7. ✅ Start services
8. ✅ Verify deployment

### Manual Deployment

If you prefer manual steps:

```bash
# 1. Install frontend dependencies
cd frontend && npm ci && cd ..

# 2. Build frontend
cd frontend && npm run build && cd ..

# 3. Stop existing containers
docker compose down

# 4. Build images
docker compose build

# 5. Start services
docker compose up -d

# 6. Check status
docker compose ps
```

## Services

### Frontend (Nginx)
- **Container**: `free_youtube_frontend`
- **Port**: 80 (configurable via FRONTEND_PORT)
- **Technology**: Nginx serving Vue.js SPA
- **Health Check**: Automatic via Nginx

### Backend (PHP/CodeIgniter)
- **Container**: `free_youtube_backend`
- **Port**: 8080 (configurable via BACKEND_PORT)
- **Technology**: PHP 8.1 with CodeIgniter 4
- **Health Check**: None (to be added)

### Database (MariaDB)
- **Container**: `free_youtube_db`
- **Port**: 3307 (configurable via MYSQL_PORT)
- **Technology**: MariaDB 10.6
- **Health Check**: `mysqladmin ping`
- **Data Volume**: `mariadb_data`

### Database Admin (phpMyAdmin)
- **Container**: `free_youtube_phpmyadmin`
- **Port**: 8081 (configurable via PHPMYADMIN_PORT)
- **Technology**: phpMyAdmin latest

## Troubleshooting

### Docker Not Running

**Error**: `ERROR: Docker is not running`

**Solution**:
```bash
# Start Docker Desktop (macOS/Windows)
# or
sudo systemctl start docker  # Linux
```

### Build Failed - dist folder not found

**Error**: `Frontend build failed - dist folder not created`

**Cause**: Build process failed

**Solution**:
```bash
# Check Node.js version
node --version  # Should be 20+

# Try building manually
cd frontend
npm ci
npm run build

# Check for errors in build output
```

### Container Failed to Start

**Error**: `Containers failed to start`

**Solution**:
```bash
# View logs
docker compose logs --tail=100

# Check specific service
docker compose logs backend
docker compose logs frontend
docker compose logs mariadb

# Verify ports are not in use
netstat -an | grep 8080  # Backend
netstat -an | grep 80    # Frontend
netstat -an | grep 3307  # MariaDB
```

### Port Already in Use

**Error**: `Error starting userland proxy: listen tcp4 0.0.0.0:80: bind: address already in use`

**Solution**:
```bash
# Option 1: Stop conflicting service
sudo lsof -i :80  # Find process using port 80
sudo kill -9 <PID>

# Option 2: Change port in .env
echo "FRONTEND_PORT=8000" >> .env
./deploy.sh
```

### Database Connection Failed

**Error**: Backend cannot connect to database

**Solution**:
```bash
# Check MariaDB is healthy
docker compose ps mariadb

# View MariaDB logs
docker compose logs mariadb

# Verify credentials match
cat .env | grep MYSQL

# Restart backend
docker compose restart backend
```

### Frontend Shows 404 for Routes

**Cause**: Nginx configuration issue

**Solution**:
```bash
# Verify nginx.conf is correct
cat nginx.conf

# Rebuild frontend container
docker compose build frontend
docker compose up -d frontend
```

### Permission Denied

**Error**: `permission denied while trying to connect to the Docker daemon socket`

**Solution**:
```bash
# Add user to docker group (Linux)
sudo usermod -aG docker $USER
newgrp docker

# Or use sudo
sudo ./deploy.sh
```

## Monitoring

### View Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f frontend
docker compose logs -f backend
docker compose logs -f mariadb

# Last 100 lines
docker compose logs --tail=100
```

### Check Status

```bash
# List running containers
docker compose ps

# Check resource usage
docker stats

# View networks
docker network ls

# View volumes
docker volume ls
```

### Health Checks

```bash
# Check if services are responding
curl http://localhost:80              # Frontend
curl http://localhost:8080/api/health # Backend (if health endpoint exists)
curl http://localhost:8081            # phpMyAdmin

# Check database
docker compose exec mariadb mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SELECT 1"
```

## Updating

### Pull Latest Changes

```bash
# Pull from git
git pull origin main

# Redeploy
./deploy.sh
```

### Update Dependencies Only

```bash
# Frontend
cd frontend && npm update && cd ..
./deploy.sh
```

## Backup and Restore

### Backup Database

```bash
# Export database
docker compose exec mariadb mysqldump -u root -p$MYSQL_ROOT_PASSWORD free_youtube > backup.sql

# Or using docker exec
docker exec free_youtube_db mysqldump -u root -psecret free_youtube > backup.sql
```

### Restore Database

```bash
# Import database
docker compose exec -T mariadb mysql -u root -p$MYSQL_ROOT_PASSWORD free_youtube < backup.sql

# Or using docker exec
docker exec -i free_youtube_db mysql -u root -psecret free_youtube < backup.sql
```

### Backup Volumes

```bash
# Backup all data
docker run --rm -v free_youtube_mariadb_data:/data -v $(pwd):/backup alpine tar czf /backup/mariadb-backup.tar.gz /data
```

## Stopping and Cleaning

### Stop Services

```bash
# Stop all containers
docker compose stop

# Stop and remove containers
docker compose down

# Stop and remove containers + volumes (WARNING: deletes data)
docker compose down -v
```

### Clean Up

```bash
# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove all stopped containers
docker container prune

# Full cleanup (WARNING: removes everything)
docker system prune -a --volumes
```

## Security

### Production Recommendations

1. **Change default passwords** in `.env`
2. **Use environment-specific .env files**
3. **Enable HTTPS** with a reverse proxy (nginx/traefik)
4. **Restrict database access** to internal network only
5. **Regular backups** of database
6. **Update dependencies** regularly
7. **Monitor logs** for suspicious activity

### HTTPS Setup (Optional)

Use a reverse proxy like Caddy or Traefik:

```yaml
# Example with Caddy
caddy:
  image: caddy:latest
  ports:
    - "443:443"
    - "80:80"
  volumes:
    - ./Caddyfile:/etc/caddy/Caddyfile
    - caddy_data:/data
  depends_on:
    - frontend
```

## Performance Tuning

### Database Optimization

Edit `docker-compose.yml`:

```yaml
mariadb:
  command: --max-connections=200 --innodb-buffer-pool-size=512M
```

### Nginx Caching

Already configured in `nginx.conf`:
- Static assets cached for 1 year
- Gzip compression enabled

### Docker Resource Limits

```yaml
backend:
  deploy:
    resources:
      limits:
        cpus: '0.5'
        memory: 512M
```

## CI/CD Integration

The application includes GitHub Actions workflows for automated deployment.

See `.github/workflows/ci-cd.yml` for details.

Required secrets:
- `SSH_HOST`: Server hostname/IP
- `SSH_USER`: SSH username
- `SSH_PRIVATE_KEY`: SSH private key
- `DOCKER_USERNAME`: Docker Hub username (optional)
- `DOCKER_PASSWORD`: Docker Hub token (optional)

## Support

If you encounter issues not covered here:

1. Check logs: `docker compose logs -f`
2. Verify configuration: `.env` file
3. Check Docker status: `docker ps -a`
4. Review GitHub issues
5. Check Docker documentation

---

**Last Updated**: 2025-10-30
**Version**: 1.0.0
