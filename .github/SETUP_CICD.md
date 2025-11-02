# CI/CD Setup Guide

This guide will help you set up continuous integration and deployment for the Free YouTube project.

## Quick Start

### 1. Enable GitHub Actions (if not already enabled)

- Go to your repository on GitHub
- Navigate to **Settings** → **Actions** → **General**
- Under "Actions permissions", select **"Allow all actions and reusable workflows"**
- Click **Save**

### 2. Set Up Docker Hub (Required for Docker builds)

#### Create Docker Hub Account
1. Go to https://hub.docker.com and create an account
2. Click **Create Repository**
3. Create two public repositories:
   - `free-youtube-frontend`
   - `free-youtube-backend`

#### Generate Access Token
1. Click your profile → **Account Settings** → **Security**
2. Click **New Access Token**
3. Name: `github-actions`
4. Permissions: **Read, Write, Delete**
5. Click **Generate**
6. **Copy the token** (you won't see it again!)

#### Add Secrets to GitHub
1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add two secrets:
   - Name: `DOCKER_USERNAME`, Value: Your Docker Hub username
   - Name: `DOCKER_PASSWORD`, Value: The access token you just created

### 3. Enable Branch Protection (Recommended)

Protect your `main` branch from accidental pushes:

1. Go to **Settings** → **Branches**
2. Click **Add rule** (or **Add branch protection rule**)
3. Branch name pattern: `main`
4. Enable these options:
   - ✅ Require a pull request before merging
   - ✅ Require approvals: 1
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging
   - Select these status checks:
     - `Frontend Build & Test`
     - `Backend Build & Test`
     - `Frontend Code Quality`
     - `Backend Code Quality`
5. Click **Create**

### 4. Test the Workflows

#### Create a Test Branch
```bash
git checkout -b test/ci-cd-setup
git add .github/
git commit -m "chore: add CI/CD workflows"
git push origin test/ci-cd-setup
```

#### Create a Pull Request
1. Go to your repository on GitHub
2. Click **Pull requests** → **New pull request**
3. Base: `main`, Compare: `test/ci-cd-setup`
4. Click **Create pull request**
5. Watch the workflows run in the **Checks** tab!

## What Happens Now?

### On Every Push
- ✅ Frontend builds and tests run
- ✅ Backend builds and tests run
- 🐳 Docker images are built (on `main`/`develop` only)

### On Every Pull Request
- ✅ All the above, plus:
- 📝 PR title is validated (conventional commits)
- 🔒 Security scan runs
- 📊 Test coverage is reported
- 🔍 Dependencies are reviewed
- ⚔️ Merge conflicts are detected

### Every Monday at 9 AM
- 🤖 Dependabot checks for dependency updates
- 📦 Automated PRs are created for updates

## Workflow Files Overview

### Main Workflows
- **`.github/workflows/ci-cd.yml`**: Main CI/CD pipeline
- **`.github/workflows/pr-checks.yml`**: Quality checks for PRs
- **`.github/workflows/dependency-review.yml`**: Reviews dependency changes

### Configuration
- **`.github/dependabot.yml`**: Automated dependency updates

### Documentation
- **`.github/workflows/README.md`**: Detailed workflow documentation

## Troubleshooting

### "No status checks found"
- Workflows need to run at least once before they appear in branch protection
- Push a commit to trigger the workflows
- Wait for workflows to complete
- Then add them to branch protection

### Docker build fails with "unauthorized"
- Check that `DOCKER_USERNAME` and `DOCKER_PASSWORD` secrets are set correctly
- Verify the Docker Hub repositories exist
- Make sure the access token has Read, Write permissions

### Tests fail
- Run tests locally first: `npm test` (frontend), `vendor/bin/phpunit` (backend)
- Check that all dependencies are in `package.json` or `composer.json`
- Verify test database configuration

### Actions not running
- Check **Settings** → **Actions** → **General**
- Ensure "Allow all actions" is selected
- Check if branch protection is blocking workflows

## Next Steps

### Optional: Set Up Deployment

1. Add deployment server details to secrets:
   - `DEPLOY_HOST`: Your server's IP or domain
   - `DEPLOY_USER`: SSH username
   - `DEPLOY_KEY`: SSH private key (entire contents)

2. Uncomment the SSH deployment step in `.github/workflows/ci-cd.yml`

3. Update the deployment script with your server paths:
```yaml
script: |
  cd /path/to/your/project
  docker-compose pull
  docker-compose up -d
```

### Optional: Add Code Coverage Badge

Add to your README.md:
```markdown
![CI/CD](https://github.com/YOUR_USERNAME/YOUR_REPO/workflows/CI/CD%20Pipeline/badge.svg)
```

### Optional: Set Up Notifications

Get notified when workflows fail:
1. **Settings** → **Notifications**
2. Enable **Actions** notifications
3. Choose email or mobile

## Monitoring

View all workflow runs:
- **Actions** tab in your repository
- Click on a workflow to see individual runs
- Click on a run to see detailed logs

## Cost

- ✅ **Free** for public repositories
- ✅ 2,000 minutes/month free for private repos on GitHub Free
- 💰 Additional minutes: $0.008/minute for private repos

Current usage: Check **Settings** → **Billing** → **Actions**

## Support

If you encounter issues:
1. Check the workflow logs in the **Actions** tab
2. Review the troubleshooting section above
3. Check GitHub Actions documentation: https://docs.github.com/en/actions

---

🎉 **You're all set!** Your CI/CD pipeline is now configured and ready to use.
