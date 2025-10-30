# GitHub Actions CI/CD Workflows

This directory contains GitHub Actions workflows for continuous integration and deployment.

## Workflows

### 1. CI/CD Pipeline (`ci-cd.yml`)

Main workflow that runs on push to main branches and pull requests.

**Triggers:**
- Push to `main`, `develop`, `001-youtube-loop-player`, `002-playlist-database`
- Pull requests to `main`, `develop`

**Jobs:**

#### Frontend Build & Test
- Sets up Node.js 20
- Installs dependencies with `npm ci`
- Runs linter (`npm run lint`)
- Runs tests (`npm run test`)
- Builds production bundle (`npm run build`)
- Uploads build artifacts

#### Backend Build & Test
- Sets up PHP 8.1
- Starts MariaDB service for tests
- Validates `composer.json`
- Installs PHP dependencies
- Checks PHP syntax
- Runs PHPUnit tests (if configured)

#### Docker Build
- Builds and pushes Docker images to Docker Hub
- Only runs on push to `main` or `develop`
- Tags images with branch name, commit SHA, and `latest`
- Uses layer caching for faster builds

#### Deploy
- Deploys to production server
- Only runs on push to `main`
- Currently contains placeholder - configure based on your deployment target

### 2. Pull Request Checks (`pr-checks.yml`)

Quality checks that run on pull requests.

**Jobs:**

#### PR Title Check
- Validates PR title follows conventional commits format
- Allowed types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `chore`

#### Frontend Code Quality
- Runs ESLint
- Checks code formatting with Prettier
- Runs tests with coverage
- Posts coverage report as PR comment

#### Backend Code Quality
- Checks PHP syntax
- Runs PHP_CodeSniffer (if configured)
- Validates code style against PSR-12

#### Security Scan
- Runs Trivy vulnerability scanner
- Uploads results to GitHub Security tab

#### Merge Conflict Check
- Detects potential merge conflicts with base branch

### 3. Dependency Review (`dependency-review.yml`)

Reviews dependency changes in pull requests.

**Features:**
- Analyzes dependency changes
- Fails on moderate or higher severity vulnerabilities
- Posts summary comment in PR

## Required Secrets

Configure these in your GitHub repository settings (Settings → Secrets and variables → Actions):

### Docker Hub (Required for Docker build)
- `DOCKER_USERNAME`: Your Docker Hub username
- `DOCKER_PASSWORD`: Your Docker Hub access token

### Deployment (Optional - for deploy job)
- `DEPLOY_HOST`: SSH host for deployment server
- `DEPLOY_USER`: SSH username
- `DEPLOY_KEY`: SSH private key

## Setup Instructions

### 1. Enable GitHub Actions

GitHub Actions are automatically enabled for public repositories. For private repositories, enable them in Settings → Actions → General.

### 2. Configure Docker Hub

1. Create a Docker Hub account at https://hub.docker.com
2. Create two repositories:
   - `free-youtube-frontend`
   - `free-youtube-backend`
3. Generate an access token in Docker Hub settings
4. Add secrets to GitHub repository:
   - Go to Settings → Secrets and variables → Actions
   - Add `DOCKER_USERNAME` and `DOCKER_PASSWORD`

### 3. Configure Branch Protection (Recommended)

For `main` branch:
1. Go to Settings → Branches → Add rule
2. Branch name pattern: `main`
3. Enable:
   - Require a pull request before merging
   - Require status checks to pass before merging
   - Require branches to be up to date before merging
   - Select status checks:
     - `Frontend Build & Test`
     - `Backend Build & Test`
     - `Frontend Code Quality`
     - `Backend Code Quality`

### 4. Customize Deployment (Optional)

To enable automatic deployment:

1. Add deployment secrets (host, user, SSH key)
2. Uncomment the SSH deployment step in `ci-cd.yml`
3. Update the deployment script path and commands

Example SSH deployment:
```yaml
- name: Deploy via SSH
  uses: appleboy/ssh-action@master
  with:
    host: ${{ secrets.DEPLOY_HOST }}
    username: ${{ secrets.DEPLOY_USER }}
    key: ${{ secrets.DEPLOY_KEY }}
    script: |
      cd /path/to/project
      git pull
      docker-compose pull
      docker-compose up -d
```

## Local Testing

Test workflows locally using [act](https://github.com/nektos/act):

```bash
# Install act
brew install act  # macOS
# or
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# Run workflows
act pull_request  # Test PR checks
act push          # Test CI/CD pipeline
```

## Monitoring

View workflow runs:
1. Go to Actions tab in GitHub repository
2. Select a workflow from the left sidebar
3. Click on a specific run to see logs

## Troubleshooting

### Frontend build fails
- Check Node.js version matches `NODE_VERSION` in workflow
- Verify `package-lock.json` is committed
- Run `npm ci` locally to reproduce

### Backend build fails
- Check PHP version matches `PHP_VERSION` in workflow
- Verify `composer.lock` is committed
- Run `composer install` locally to reproduce

### Docker push fails
- Verify `DOCKER_USERNAME` and `DOCKER_PASSWORD` secrets are set
- Check Docker Hub repositories exist
- Verify you have push permissions

### Tests fail
- Run tests locally: `npm test` (frontend) or `vendor/bin/phpunit` (backend)
- Check test database configuration
- Verify all test dependencies are installed

## Performance Optimization

Current optimizations:
- `npm ci` instead of `npm install` for faster, reproducible installs
- Dependency caching for Node.js and PHP
- Docker layer caching with GitHub Actions cache
- Parallel job execution where possible

## Security

Security features:
- Dependency review on PRs
- Vulnerability scanning with Trivy
- Docker image scanning
- Secrets management with GitHub Secrets
- Minimal required permissions

## Cost Optimization

GitHub Actions are free for public repositories. For private repositories:
- 2,000 minutes/month free for GitHub Free
- Use caching to reduce build times
- Cancel redundant workflow runs
- Use `concurrency` groups to cancel outdated runs

Example concurrency configuration:
```yaml
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true
```
