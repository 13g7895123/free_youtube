# Quickstart Guide: YouTube Loop Player

**Feature**: YouTube Loop Player
**Date**: 2025-10-22
**Purpose**: Get new developers up and running on this project

## Overview

This guide will help you set up the YouTube Loop Player development environment and run the application locally. Expected time to complete: **15-20 minutes**.

---

## Prerequisites

### Required Software

| Tool | Minimum Version | Check Command | Install Link |
|------|----------------|---------------|--------------|
| Node.js | 18.x or higher | `node --version` | https://nodejs.org/ |
| npm | 9.x or higher | `npm --version` | (included with Node.js) |
| Git | 2.x or higher | `git --version` | https://git-scm.com/ |

### Optional Tools

- **VS Code**: Recommended editor with Vue.js extensions
- **Vue DevTools**: Browser extension for debugging Vue apps

### Knowledge Requirements

- Basic JavaScript (ES2020+)
- Familiarity with Vue.js 3 Composition API (or willingness to learn)
- Understanding of async/await and Promises
- Basic HTML/CSS

---

## Project Setup

### 1. Clone Repository

```bash
git clone <repository-url>
cd youtube-loop-player
```

### 2. Checkout Feature Branch

```bash
git checkout 001-youtube-loop-player
```

### 3. Install Dependencies

```bash
npm install
```

**Expected Output**:
```
added XXX packages in XXs
```

**Dependencies Installed**:
- `vue`: ^3.3.x - Vue.js framework
- `vite`: ^5.0.x - Build tool and dev server
- `vitest`: ^1.0.x - Unit test runner
- `playwright`: ^1.40.x - E2E testing framework
- `@vitejs/plugin-vue`: Vue.js plugin for Vite
- `eslint`: ^8.x - JavaScript linter
- `prettier`: ^3.x - Code formatter

### 4. Verify Installation

```bash
npm run dev
```

**Expected Output**:
```
  VITE v5.x.x  ready in XXX ms

  ➜  Local:   http://localhost:3000/
  ➜  Network: use --host to expose
```

Open browser to `http://localhost:3000/` - you should see the application (or a blank page if development hasn't started yet).

Press `Ctrl+C` to stop the dev server.

---

## Development Workflow

### Running the Development Server

```bash
npm run dev
```

Features:
- **Hot Module Replacement (HMR)**: Changes reflect instantly without full page reload
- **Fast startup**: <1s initial load
- **Instant updates**: Changes appear in <100ms

### Project Structure

```
youtube-loop-player/
├── src/
│   ├── components/       # Vue components
│   │   ├── VideoPlayer.vue
│   │   ├── UrlInput.vue
│   │   ├── PlayerControls.vue
│   │   ├── LoopToggle.vue
│   │   ├── PlaylistInfo.vue
│   │   └── ErrorMessage.vue
│   ├── composables/      # Reusable reactive logic
│   │   ├── useYouTubePlayer.js
│   │   ├── useUrlParser.js
│   │   ├── useLocalStorage.js
│   │   └── usePlaylist.js
│   ├── utils/           # Pure utility functions
│   │   ├── urlValidator.js
│   │   └── errorMessages.js
│   ├── App.vue          # Root component
│   ├── main.js          # Application entry
│   └── style.css        # Global styles
├── tests/
│   ├── unit/            # Unit tests (Vitest)
│   ├── integration/     # Integration tests
│   └── contract/        # YouTube API contract tests
├── public/              # Static assets
├── specs/               # Feature specifications
├── package.json
├── vite.config.js
└── vitest.config.js
```

---

## Testing

### Run All Tests

```bash
npm test
```

### Run Unit Tests Only

```bash
npm run test:unit
```

### Run Tests in Watch Mode

```bash
npm run test:watch
```

### Run E2E Tests

```bash
npm run test:e2e
```

### View Test Coverage

```bash
npm run test:coverage
```

**Expected Coverage**: Minimum 80% (per constitution requirement)

---

## Code Quality

### Linting

```bash
npm run lint
```

Checks for:
- ESLint rule violations
- Vue.js best practices
- Code style issues

### Auto-fix Linting Issues

```bash
npm run lint:fix
```

### Format Code

```bash
npm run format
```

Uses Prettier to format all `.js`, `.vue`, and `.css` files.

### Pre-commit Checks

Before committing, always run:

```bash
npm run lint && npm test
```

---

## Building for Production

### Create Production Build

```bash
npm run build
```

**Output**: `dist/` directory with optimized static files

**Optimizations Applied**:
- JavaScript minification and tree-shaking
- CSS minification
- Asset hashing for cache busting
- Code splitting for lazy loading

### Preview Production Build

```bash
npm run preview
```

Serves the production build locally at `http://localhost:4173/`

---

## Common Tasks

### Adding a New Component

1. Create file in `src/components/MyComponent.vue`
2. Define component using Composition API:

```vue
<template>
  <div>
    {{ message }}
  </div>
</template>

<script setup>
import { ref } from 'vue'

const message = ref('Hello World')
</script>

<style scoped>
div {
  color: blue;
}
</style>
```

3. Import and use in parent component:

```vue
<script setup>
import MyComponent from './components/MyComponent.vue'
</script>

<template>
  <MyComponent />
</template>
```

### Adding a New Composable

1. Create file in `src/composables/useFeature.js`
2. Export composable function:

```javascript
import { ref } from 'vue'

export function useFeature() {
  const state = ref(null)

  function doSomething() {
    // logic here
  }

  return { state, doSomething }
}
```

3. Use in component:

```vue
<script setup>
import { useFeature } from '@/composables/useFeature'

const { state, doSomething } = useFeature()
</script>
```

### Adding a Test

1. Create test file: `tests/unit/feature.test.js`
2. Write test using Vitest:

```javascript
import { describe, it, expect } from 'vitest'
import { useFeature } from '@/composables/useFeature'

describe('useFeature', () => {
  it('should do something', () => {
    const { state, doSomething } = useFeature()

    doSomething()

    expect(state.value).toBe('expected value')
  })
})
```

---

## Debugging

### Vue DevTools

Install browser extension:
- Chrome: https://chrome.google.com/webstore (search "Vue.js devtools")
- Firefox: https://addons.mozilla.org/firefox/ (search "Vue.js devtools")

Features:
- Inspect component tree
- View reactive state
- Time-travel debugging
- Performance monitoring

### Console Logging

Use `console.log()` for quick debugging:

```javascript
console.log('Current state:', state.value)
```

**Important**: Remove console.logs before committing (linter will warn)

### Browser DevTools

Press `F12` to open browser DevTools:
- **Sources tab**: Set breakpoints in source code
- **Network tab**: Inspect YouTube API calls
- **Application tab**: View LocalStorage data

---

## Environment Configuration

### Development vs Production

**Development** (`npm run dev`):
- Source maps enabled
- Detailed error messages
- Hot module replacement
- No minification

**Production** (`npm run build`):
- Source maps disabled (smaller bundle)
- Generic error messages (no stack traces)
- Minified and optimized
- Tree-shaking removes unused code

### Environment Variables

Create `.env.local` file (not committed to git):

```
VITE_API_BASE_URL=http://localhost:3000
```

Access in code:

```javascript
const apiUrl = import.meta.env.VITE_API_BASE_URL
```

**Note**: Only variables prefixed with `VITE_` are exposed to the client.

---

## Troubleshooting

### Problem: "Cannot find module 'vue'"

**Solution**:
```bash
rm -rf node_modules package-lock.json
npm install
```

### Problem: Dev server won't start (port in use)

**Solution**: Change port in `vite.config.js`:

```javascript
export default defineConfig({
  server: {
    port: 3001 // Change to available port
  }
})
```

### Problem: Tests fail with "Cannot find module"

**Solution**: Ensure test file uses correct import paths:

```javascript
// Correct
import { useFeature } from '@/composables/useFeature'

// Incorrect
import { useFeature } from '../src/composables/useFeature'
```

### Problem: YouTube Player API not loading

**Cause**: Network issue or script blocked by ad blocker

**Solution**:
1. Check browser console for errors
2. Disable ad blocker for localhost
3. Verify network connection

### Problem: LocalStorage not persisting

**Cause**: Private browsing mode or browser security settings

**Solution**: Test in normal browsing mode, or check browser settings

---

## Next Steps

### For New Developers

1. Read `specs/001-youtube-loop-player/spec.md` - Understand requirements
2. Read `specs/001-youtube-loop-player/plan.md` - Understand architecture
3. Review `specs/001-youtube-loop-player/data-model.md` - Learn state structure
4. Review `specs/001-youtube-loop-player/contracts/youtube-player-api.md` - YouTube API integration
5. Read Vue.js Composition API docs: https://vuejs.org/guide/introduction.html

### For Contributors

1. Check constitution: `.specify/memory/constitution.md`
2. Follow TDD workflow (tests first, then implementation)
3. Ensure 80% code coverage before PR
4. Run `npm run lint` and `npm test` before committing

### Generate Tasks

Once ready to implement:

```bash
/speckit.tasks
```

This generates `specs/001-youtube-loop-player/tasks.md` with implementation tasks.

---

## Useful Commands Cheat Sheet

| Command | Description |
|---------|-------------|
| `npm run dev` | Start development server |
| `npm test` | Run all tests |
| `npm run test:watch` | Run tests in watch mode |
| `npm run lint` | Check code quality |
| `npm run lint:fix` | Auto-fix linting issues |
| `npm run format` | Format code with Prettier |
| `npm run build` | Create production build |
| `npm run preview` | Preview production build |
| `npm run test:coverage` | Generate coverage report |

---

## Additional Resources

### Vue.js

- Official Guide: https://vuejs.org/guide/
- Composition API: https://vuejs.org/api/composition-api-setup.html
- Composables Guide: https://vuejs.org/guide/reusability/composables.html

### Vite

- Getting Started: https://vitejs.dev/guide/
- Configuration: https://vitejs.dev/config/

### Vitest

- API Reference: https://vitest.dev/api/
- Mocking Guide: https://vitest.dev/guide/mocking.html

### YouTube IFrame API

- API Reference: https://developers.google.com/youtube/iframe_api_reference
- Player Parameters: https://developers.google.com/youtube/player_parameters

---

## Getting Help

- **Bug or Issue**: Check GitHub Issues or create new issue
- **Question**: Ask in team chat or create discussion thread
- **Documentation**: Check `specs/` directory for detailed specs

---

**Estimated Setup Time**: 15-20 minutes (including dependency installation)

**Ready to Code?** Run `npm run dev` and start building! 🚀
