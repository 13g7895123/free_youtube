# Research: YouTube Loop Player

**Feature**: YouTube Loop Player
**Date**: 2025-10-22
**Research Phase**: Technical decisions and best practices

## Overview

This document captures research decisions for implementing a YouTube loop player using Vue.js 3 and the YouTube IFrame Player API. All technical unknowns from the implementation plan have been resolved through this research.

## Research Areas

### 1. YouTube IFrame Player API Integration

**Decision**: Use YouTube IFrame Player API for video playback

**Rationale**:
- Official API provided by YouTube with reliable support
- Handles all video streaming complexity (quality selection, buffering, DRM)
- Provides comprehensive event system for player state changes
- Supports both single videos and playlists natively
- No server-side proxy required (direct client-to-YouTube communication)
- Free to use with no quota limitations for embed usage

**Alternatives Considered**:
- **youtube-dl + custom player**: Rejected - violates YouTube's Terms of Service, requires backend server
- **Third-party libraries (youtube-player.js)**: Considered but unnecessary - adds abstraction layer without significant benefit
- **Direct iframe embed**: Rejected - lacks programmatic control needed for loop functionality

**Implementation Approach**:
- Load YouTube IFrame API script dynamically in Vue component
- Use `onYouTubeIframeAPIReady` callback to initialize player
- Wrap player in Vue composable (`useYouTubePlayer`) for reactive state management
- Listen to `onStateChange` event to detect video end and trigger loop/next video

**Key API Methods**:
- `new YT.Player()`: Initialize player instance
- `loadVideoById(videoId)`: Load single video
- `loadPlaylist({list: playlistId})`: Load playlist
- `playVideo()`, `pauseVideo()`: Playback control
- `setVolume(volume)`: Volume control (0-100)
- `getPlayerState()`: Get current state (playing, paused, ended, etc.)

**References**:
- https://developers.google.com/youtube/iframe_api_reference
- https://developers.google.com/youtube/player_parameters

---

### 2. Vue.js 3 Composition API Best Practices

**Decision**: Use Vue 3 Composition API with composables pattern for business logic

**Rationale**:
- Composition API provides better code organization and reusability
- Composables enable separation of concerns (UI vs business logic)
- Better TypeScript support (though we're using JavaScript)
- Reactive state management without Vuex/Pinia overhead
- Easier to test composables independently from components

**Alternatives Considered**:
- **Options API**: Rejected - older pattern, less flexible for complex logic sharing
- **Vuex/Pinia**: Rejected - unnecessary for single-user, no complex state tree needed
- **Class-based components**: Rejected - not recommended for Vue 3

**Composables Structure**:
```javascript
// useYouTubePlayer.js - YouTube API integration
export function useYouTubePlayer() {
  const player = ref(null)
  const playerState = ref('unstarted')
  const currentVideoId = ref(null)

  const initPlayer = (elementId) => { /* ... */ }
  const loadVideo = (videoId) => { /* ... */ }
  const onPlayerReady = (event) => { /* ... */ }
  const onStateChange = (event) => { /* ... */ }

  return { player, playerState, currentVideoId, initPlayer, loadVideo }
}

// useLocalStorage.js - Preferences persistence
export function useLocalStorage(key, defaultValue) {
  const storedValue = ref(JSON.parse(localStorage.getItem(key)) ?? defaultValue)

  watch(storedValue, (newValue) => {
    localStorage.setItem(key, JSON.stringify(newValue))
  })

  return storedValue
}

// useUrlParser.js - URL validation and parsing
export function useUrlParser() {
  const parseYouTubeUrl = (url) => { /* ... */ }
  const extractVideoId = (url) => { /* ... */ }
  const extractPlaylistId = (url) => { /* ... */ }
  const isValidYouTubeUrl = (url) => { /* ... */ }

  return { parseYouTubeUrl, extractVideoId, extractPlaylistId, isValidYouTubeUrl }
}

// usePlaylist.js - Playlist management
export function usePlaylist() {
  const playlist = ref([])
  const currentIndex = ref(0)
  const isPlaylistMode = ref(false)

  const loadPlaylist = (playlistId) => { /* ... */ }
  const nextVideo = () => { /* ... */ }
  const previousVideo = () => { /* ... */ }

  return { playlist, currentIndex, isPlaylistMode, loadPlaylist, nextVideo, previousVideo }
}
```

**References**:
- https://vuejs.org/guide/reusability/composables.html
- https://vuejs.org/api/composition-api-setup.html

---

### 3. URL Parsing and Validation Patterns

**Decision**: Use regular expressions with URL object parsing for YouTube URL validation

**Rationale**:
- URL object parsing handles encoding/decoding automatically
- Regex patterns cover all YouTube URL formats reliably
- Client-side validation provides immediate feedback
- No external library needed for this simple use case

**URL Formats to Support**:
```javascript
// Standard watch URL
https://www.youtube.com/watch?v=VIDEO_ID
https://youtube.com/watch?v=VIDEO_ID

// Short URL
https://youtu.be/VIDEO_ID

// Embed URL
https://www.youtube.com/embed/VIDEO_ID

// Playlist URLs
https://www.youtube.com/playlist?list=PLAYLIST_ID
https://www.youtube.com/watch?v=VIDEO_ID&list=PLAYLIST_ID
```

**Implementation Pattern**:
```javascript
function extractVideoId(url) {
  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/
  ]

  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match) return match[1]
  }

  return null
}

function extractPlaylistId(url) {
  const urlObj = new URL(url)
  return urlObj.searchParams.get('list')
}
```

**Alternatives Considered**:
- **Third-party URL parsing library**: Rejected - adds unnecessary dependency
- **Server-side validation**: Rejected - no backend in this architecture
- **YouTube Data API for validation**: Rejected - requires API key and quota management

**References**:
- https://webapps.stackexchange.com/questions/54443/format-for-id-of-youtube-video
- YouTube video ID format: 11 characters, alphanumeric + underscore + hyphen

---

### 4. LocalStorage for State Persistence

**Decision**: Use browser LocalStorage for persisting user preferences

**Rationale**:
- Built-in browser API, no external dependencies
- Synchronous API simplifies Vue reactivity integration
- Sufficient storage (5-10MB) for our needs (volume + loop state)
- Data persists across sessions and page refreshes
- No server-side infrastructure required
- Works offline

**Data Structure**:
```json
{
  "volume": 75,
  "loopEnabled": true,
  "lastPlayedVideoId": "dQw4w9WgXcQ",
  "lastPlayedPlaylistId": null
}
```

**Alternatives Considered**:
- **SessionStorage**: Rejected - data lost when tab closes, not suitable for user preferences
- **IndexedDB**: Rejected - overkill for small key-value data, async API complicates reactivity
- **Cookies**: Rejected - smaller storage limit (4KB), sent with every request (unnecessary overhead)
- **Server-side storage**: Rejected - no backend, adds authentication complexity

**Implementation Considerations**:
- Wrap LocalStorage access in try-catch (browser may block in private mode)
- Validate data on read (handle corrupted/missing data gracefully)
- Use JSON serialization for complex objects
- Watch Vue refs and sync to LocalStorage on change

**References**:
- https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage
- https://caniuse.com/namevalue-storage (99%+ browser support)

---

### 5. Playlist Management Strategy

**Decision**: Use YouTube IFrame API's native playlist support with client-side index tracking

**Rationale**:
- YouTube API handles playlist loading and video sequencing
- API provides events when playlist changes (onStateChange with playlist info)
- Client-side tracking gives us control over loop logic
- No need to call YouTube Data API (avoids quota limits)

**Implementation Approach**:
1. Detect if URL contains playlist parameter (`list=`)
2. Load playlist using `player.loadPlaylist({list: playlistId})`
3. Track current video index using `player.getPlaylistIndex()`
4. Listen to video end event, increment index, or loop to start if at end
5. Handle skipped videos (unavailable/restricted) by catching errors and advancing

**Playlist Loop Logic**:
```javascript
function onVideoEnd() {
  if (!loopEnabled.value) {
    return // Stop playback
  }

  if (isPlaylistMode.value) {
    const currentIndex = player.value.getPlaylistIndex()
    const playlistLength = player.value.getPlaylist().length

    if (currentIndex === playlistLength - 1) {
      // Last video in playlist, loop to start
      player.value.playVideoAt(0)
    } else {
      // Play next video
      player.value.nextVideo()
    }
  } else {
    // Single video loop
    player.value.seekTo(0)
    player.value.playVideo()
  }
}
```

**Edge Case Handling**:
- **Unavailable videos**: YouTube API skips automatically, track in `onError` event
- **Playlist size limit**: Enforce 200 video limit in UI, warn if exceeded
- **Empty playlists**: Show error message, don't initialize player

**Alternatives Considered**:
- **YouTube Data API for playlist info**: Rejected - requires API key, adds quota management, slower
- **Manual playlist management**: Rejected - duplicates YouTube's playlist logic, error-prone

**References**:
- https://developers.google.com/youtube/iframe_api_reference#Playback_controls
- https://developers.google.com/youtube/iframe_api_reference#loadPlaylist

---

### 6. Testing Strategy for YouTube Player Integration

**Decision**: Use mocked YouTube IFrame API for unit/integration tests, Playwright for E2E

**Rationale**:
- YouTube API requires DOM and network, not suitable for fast unit tests
- Mocking allows testing business logic without external dependencies
- Playwright can test real YouTube embeds in controlled environment
- Contract tests verify assumptions about YouTube API behavior

**Testing Layers**:

**Unit Tests (Vitest)**:
- Test composables in isolation with mocked YouTube API
- Test URL parsing, validation, playlist index logic
- Test LocalStorage persistence with mocked localStorage
- Fast (<1s per test), deterministic, no network required

**Integration Tests (Vitest + jsdom)**:
- Test component interactions with mocked player
- Test state synchronization between components
- Test error handling flows

**Contract Tests (Playwright)**:
- Verify YouTube IFrame API event contracts
- Test with real (but controlled) YouTube videos
- Use short test videos (5-10 seconds) for fast execution
- Mock YouTube API only when testing loop logic (to avoid 24-hour tests)

**E2E Tests (Playwright - selective)**:
- Critical user journeys only (paste URL → play → loop)
- Use shorter videos for faster test execution
- Test on Chrome only (reduce test matrix complexity)

**Mock Structure Example**:
```javascript
// Mock YouTube Player for unit tests
class MockYouTubePlayer {
  constructor() {
    this.state = -1 // unstarted
    this.videoId = null
    this.playlist = []
    this.playlistIndex = 0
  }

  loadVideoById(videoId) {
    this.videoId = videoId
    this.state = 1 // playing
  }

  getPlayerState() {
    return this.state
  }

  // ... other methods
}
```

**References**:
- https://vitest.dev/guide/mocking.html
- https://playwright.dev/

---

### 7. Build Tool and Development Setup

**Decision**: Use Vite as build tool for development and production builds

**Rationale**:
- Official Vue.js recommendation for new projects
- Extremely fast HMR (Hot Module Replacement) during development
- Optimized production builds with tree-shaking and code-splitting
- Native ES modules support, no complex webpack configuration
- Built-in dev server with instant startup
- Better developer experience than webpack

**Development Configuration**:
```javascript
// vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    target: 'es2020',
    rollupOptions: {
      output: {
        manualChunks: {
          'youtube-api': ['@/composables/useYouTubePlayer']
        }
      }
    }
  },
  server: {
    port: 3000
  }
})
```

**Alternatives Considered**:
- **Webpack**: Rejected - slower builds, more complex configuration
- **Rollup**: Rejected - Vite uses Rollup internally, Vite adds dev server
- **Parcel**: Rejected - less Vue.js ecosystem support

**Production Optimization**:
- Vite automatically tree-shakes unused code
- CSS and JS minification enabled by default
- Modern browser builds (ES2020) for smaller bundles
- Lazy loading for non-critical components

**References**:
- https://vitejs.dev/guide/
- https://vitejs.dev/guide/build.html

---

### 8. Error Handling and User Messaging

**Decision**: Centralized error message constants with error boundary pattern in Vue

**Rationale**:
- Consistent error messages across the application
- Easy localization (all messages in one place)
- Error boundaries prevent entire app crashes
- User-friendly Traditional Chinese messages

**Error Categories**:
```javascript
// utils/errorMessages.js
export const ERROR_MESSAGES = {
  INVALID_URL: '網址格式不正確，請輸入有效的 YouTube 影片或播放清單網址',
  VIDEO_NOT_FOUND: '無法載入影片，影片可能已被移除或設為私人',
  PLAYLIST_NOT_FOUND: '無法載入播放清單，播放清單可能已被移除或設為私人',
  NETWORK_ERROR: '網路連線異常，請檢查網路設定後重試',
  PLAYBACK_ERROR: '播放發生錯誤，請嘗試重新載入',
  EMBED_RESTRICTED: '此影片不允許嵌入播放',
  GEO_RESTRICTED: '此影片在您的地區無法播放',
  PLAYLIST_TOO_LARGE: '播放清單包含超過 200 個影片，可能影響效能'
}
```

**Error Handling Pattern**:
```vue
<!-- ErrorMessage.vue -->
<template>
  <div v-if="error" class="error-message" role="alert">
    <p>{{ error }}</p>
    <button @click="clearError">關閉</button>
  </div>
</template>
```

**YouTube API Error Codes**:
- `2`: Invalid video ID
- `5`: HTML5 player error (usually geo-restriction)
- `100`: Video not found or private
- `101`: Video owner doesn't allow embedding
- `150`: Same as 101

**References**:
- https://developers.google.com/youtube/iframe_api_reference#onError
- WCAG error message guidelines: https://www.w3.org/WAI/WCAG21/Understanding/error-identification.html

---

## Summary of Technical Decisions

| Decision Area | Choice | Key Benefit |
|--------------|--------|-------------|
| Video Playback | YouTube IFrame Player API | Official support, no backend needed |
| Frontend Framework | Vue.js 3 Composition API | Modern, reactive, composable logic |
| Build Tool | Vite | Fast dev builds, optimized production |
| State Management | Composables + ref/reactive | Simple, no external state library needed |
| Persistence | LocalStorage | Built-in, synchronous, offline support |
| URL Parsing | Regex + URL object | No external dependency, covers all formats |
| Playlist Management | YouTube API native + client tracking | Leverages YouTube's infrastructure |
| Testing | Vitest + Playwright | Fast units, reliable E2E |
| Error Handling | Centralized messages + boundaries | Consistent UX, easy localization |

## Next Steps

With research complete, proceed to **Phase 1: Design & Contracts**:
1. Generate `data-model.md` (entities and state structure)
2. Generate `contracts/youtube-player-api.md` (API contract documentation)
3. Generate `quickstart.md` (developer onboarding guide)
4. Update agent context with technology stack
