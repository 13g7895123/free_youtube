# Implementation Plan: YouTube Loop Player

**Branch**: `001-youtube-loop-player` | **Date**: 2025-10-22 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-youtube-loop-player/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Building a YouTube loop player that allows users to paste YouTube URLs (single videos or playlists) and play them with automatic looping functionality. The application will be a web-based single-page application using Vue.js for the frontend, providing simple controls for play/pause, volume adjustment, and loop toggle. The player leverages YouTube's IFrame Player API for video playback and uses browser LocalStorage for persisting user preferences.

## Technical Context

**Language/Version**: JavaScript ES2020+ with Vue.js 3.x (Composition API)
**Primary Dependencies**: Vue.js 3.x, YouTube IFrame Player API, Vite (build tool)
**Storage**: Browser LocalStorage for user preferences (volume, loop mode)
**Testing**: Vitest for unit tests, Playwright for integration tests
**Target Platform**: Modern web browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
**Project Type**: Web application (single frontend project, no backend required)
**Performance Goals**:
  - Initial page load: <2s on 3G
  - Time to interactive: <3s
  - Bundle size: <500KB initial load
  - Video start time: <3s after URL submission
  - Control responsiveness: <100ms visual feedback
**Constraints**:
  - Must work within YouTube's embed restrictions
  - No server-side processing (client-only application)
  - Playlist size limited to 200 videos for optimal performance
**Scale/Scope**:
  - Single-user application (no multi-user features)
  - Expected usage: continuous playback up to 24 hours
  - Support for playlists up to 200 videos

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### I. Code Quality First ✅

**Compliance:**
- Vue.js Composition API encourages clean separation of concerns
- Single File Components (SFC) naturally enforce component responsibility boundaries
- Composables pattern will be used for reusable logic (URL validation, localStorage management)
- ESLint + Prettier for consistent code style
- Vue components kept under 300 lines, functions under 50 lines

**Status**: PASS - Architecture supports code quality principles

### II. Test-Driven Development ✅

**Compliance:**
- Unit tests for all business logic (URL parsing, validation, playlist management)
- Integration tests for YouTube Player API interactions
- Contract tests for YouTube IFrame API event handling
- Target: 80% code coverage minimum
- Tests will be written before implementation (Red-Green-Refactor)

**Status**: PASS - TDD workflow will be followed

### III. User Experience Consistency ✅

**Compliance:**
- <100ms visual feedback for all controls (play/pause, volume, loop toggle)
- Clear error messages in traditional Chinese
- Loading indicators during video load
- Keyboard shortcuts for common actions
- Responsive design for desktop and mobile
- WCAG 2.1 Level AA accessibility (keyboard navigation, ARIA labels)

**Status**: PASS - UX requirements aligned with constitution

### IV. Performance as a Feature ✅

**Compliance:**
- Bundle size budget: <500KB initial load
- Lazy loading for non-critical components
- Performance monitoring via browser DevTools
- Vite for optimized production builds
- Performance targets defined in Technical Context above

**Status**: PASS - Performance budgets established

### Constitution Check Summary

**Result**: ✅ ALL GATES PASSED

No violations detected. The proposed architecture (Vue.js SPA with YouTube IFrame API) aligns with all four core principles. No complexity justifications required.

## Project Structure

### Documentation (this feature)

```text
specs/001-youtube-loop-player/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   └── youtube-player-api.md
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
youtube-loop-player/
├── public/
│   └── index.html           # Entry HTML file
├── src/
│   ├── components/
│   │   ├── VideoPlayer.vue      # Main player component
│   │   ├── UrlInput.vue         # URL input field
│   │   ├── PlayerControls.vue   # Play/pause/volume controls
│   │   ├── LoopToggle.vue       # Loop on/off toggle
│   │   ├── PlaylistInfo.vue     # Playlist progress display
│   │   └── ErrorMessage.vue     # Error display component
│   ├── composables/
│   │   ├── useYouTubePlayer.js  # YouTube IFrame API integration
│   │   ├── useUrlParser.js      # URL parsing and validation
│   │   ├── useLocalStorage.js   # LocalStorage persistence
│   │   └── usePlaylist.js       # Playlist management logic
│   ├── utils/
│   │   ├── urlValidator.js      # URL validation functions
│   │   └── errorMessages.js     # Error message constants
│   ├── App.vue                  # Root component
│   ├── main.js                  # Application entry point
│   └── style.css                # Global styles
├── tests/
│   ├── unit/
│   │   ├── urlValidator.test.js
│   │   ├── useLocalStorage.test.js
│   │   ├── useUrlParser.test.js
│   │   └── usePlaylist.test.js
│   ├── integration/
│   │   ├── player-lifecycle.test.js
│   │   ├── playlist-loop.test.js
│   │   └── preferences-persistence.test.js
│   └── contract/
│       └── youtube-iframe-api.test.js
├── package.json
├── vite.config.js
├── vitest.config.js
└── README.md
```

**Structure Decision**:

Selected **Web application (single frontend project)** structure because:
- No backend required (YouTube IFrame API handles video streaming)
- User preferences stored in browser LocalStorage (no database needed)
- Client-side URL parsing and playlist management
- Simple deployment (static files to CDN/web server)

The structure follows Vue.js best practices:
- **components/**: Presentational UI components
- **composables/**: Reusable reactive logic (Vue 3 Composition API pattern)
- **utils/**: Pure utility functions (no Vue reactivity)
- **tests/**: Organized by test type (unit, integration, contract)

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

**No violations detected** - This section is intentionally empty as the Constitution Check passed all gates without requiring complexity justifications.
