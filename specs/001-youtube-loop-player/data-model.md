# Data Model: YouTube Loop Player

**Feature**: YouTube Loop Player
**Date**: 2025-10-22
**Purpose**: Define the data structures and state management for the application

## Overview

The YouTube Loop Player uses a client-side reactive state model powered by Vue.js 3 Composition API. All state is ephemeral (in-memory) except for user preferences which are persisted to browser LocalStorage.

## Entities

### 1. VideoSession

Represents the current video playback session.

**Attributes**:
- `videoId` (string | null): YouTube video ID (11 characters), null if no video loaded
- `playbackState` (enum): Current player state
  - Values: `'unstarted'`, `'playing'`, `'paused'`, `'buffering'`, `'ended'`, `'error'`
- `currentTime` (number): Current playback position in seconds
- `duration` (number): Total video duration in seconds
- `isPlaylistMode` (boolean): Whether currently playing a playlist
- `title` (string): Video title (provided by YouTube API)

**Lifecycle**:
- Created when user submits a valid YouTube URL
- Updated continuously during playback
- Destroyed when user loads a new video/playlist

**Validation Rules**:
- `videoId` must be 11 characters (YouTube ID format) or null
- `currentTime` must be >= 0 and <= `duration`
- `playbackState` must be one of the defined enum values

**State Transitions**:
```
unstarted → buffering → playing
playing → paused → playing
playing → ended → unstarted (if loop enabled)
any state → error (on playback failure)
```

**Example**:
```javascript
{
  videoId: 'dQw4w9WgXcQ',
  playbackState: 'playing',
  currentTime: 45.3,
  duration: 212,
  isPlaylistMode: false,
  title: 'Rick Astley - Never Gonna Give You Up'
}
```

---

### 2. PlaylistSession

Represents the current playlist session (only exists when in playlist mode).

**Attributes**:
- `playlistId` (string): YouTube playlist ID
- `videoIds` (string[]): Array of video IDs in the playlist
- `currentIndex` (number): Index of currently playing video (0-based)
- `totalVideos` (number): Total number of videos in playlist
- `title` (string): Playlist title (provided by YouTube API)
- `skippedIndices` (number[]): Indices of videos that couldn't be played (removed/restricted)

**Lifecycle**:
- Created when user submits a YouTube playlist URL
- Updated when navigating between playlist videos
- Destroyed when user loads a single video or new playlist

**Validation Rules**:
- `videoIds` array must not be empty
- `totalVideos` must equal `videoIds.length`
- `currentIndex` must be >= 0 and < `totalVideos`
- `playlistId` must match YouTube playlist ID format

**Invariants**:
- `currentIndex` always points to a valid (playable) video
- When a video can't play, it's added to `skippedIndices` and `currentIndex` advances

**Example**:
```javascript
{
  playlistId: 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf',
  videoIds: ['dQw4w9WgXcQ', 'yPYZpwSpKmA', '9bZkp7q19f0'],
  currentIndex: 1,
  totalVideos: 3,
  title: 'My Favorite Songs',
  skippedIndices: []
}
```

---

### 3. UserPreferences

Represents user settings that persist across sessions.

**Attributes**:
- `volume` (number): Volume level (0-100)
- `isMuted` (boolean): Whether audio is muted
- `loopEnabled` (boolean): Whether loop mode is enabled
- `lastPlayedVideoId` (string | null): Last played video ID (for resume feature - future)
- `lastPlayedPlaylistId` (string | null): Last played playlist ID (for resume feature - future)

**Persistence**:
- Stored in browser LocalStorage under key `youtube-loop-player-preferences`
- Serialized as JSON
- Loaded on app initialization
- Saved automatically on every change (via Vue watcher)

**Validation Rules**:
- `volume` must be integer between 0 and 100 (inclusive)
- `isMuted` must be boolean
- `loopEnabled` must be boolean
- Video/playlist IDs must match YouTube format or be null

**Default Values**:
```javascript
{
  volume: 75,
  isMuted: false,
  loopEnabled: true,  // Loop enabled by default (core feature)
  lastPlayedVideoId: null,
  lastPlayedPlaylistId: null
}
```

**Example (Stored in LocalStorage)**:
```json
{
  "volume": 60,
  "isMuted": false,
  "loopEnabled": false,
  "lastPlayedVideoId": "dQw4w9WgXcQ",
  "lastPlayedPlaylistId": null
}
```

---

### 4. PlayerState

Aggregated runtime state combining all entities and UI state.

**Attributes**:
- `videoSession` (VideoSession | null): Current video session
- `playlistSession` (PlaylistSession | null): Current playlist session (if in playlist mode)
- `preferences` (UserPreferences): User preferences
- `isLoading` (boolean): Whether video/playlist is currently loading
- `error` (ErrorState | null): Current error state

**Invariants**:
- When `playlistSession` is not null, `videoSession.isPlaylistMode` must be true
- When `videoSession` is null, `isLoading` should be false (unless first load)
- `preferences` is never null (always has default values)

---

### 5. ErrorState

Represents error information to display to the user.

**Attributes**:
- `code` (string): Error code from YouTube API or custom error code
  - YouTube codes: `'2'`, `'5'`, `'100'`, `'101'`, `'150'`
  - Custom codes: `'INVALID_URL'`, `'NETWORK_ERROR'`, `'PLAYLIST_TOO_LARGE'`
- `message` (string): User-friendly error message in Traditional Chinese
- `timestamp` (Date): When the error occurred
- `recoverable` (boolean): Whether user can retry or needs to change input

**Example**:
```javascript
{
  code: '101',
  message: '此影片不允許嵌入播放',
  timestamp: new Date('2025-10-22T10:30:00'),
  recoverable: false
}
```

---

## State Management

### Vue Composables

State is managed through Vue 3 Composition API composables:

**useYouTubePlayer** (YouTube API integration):
```javascript
export function useYouTubePlayer() {
  const player = ref(null)
  const videoSession = ref(null)

  const initPlayer = (elementId) => { /* ... */ }
  const loadVideo = (videoId) => { /* ... */ }
  const play = () => player.value?.playVideo()
  const pause = () => player.value?.pauseVideo()
  const setVolume = (vol) => player.value?.setVolume(vol)

  return { player, videoSession, initPlayer, loadVideo, play, pause, setVolume }
}
```

**usePlaylist** (Playlist management):
```javascript
export function usePlaylist(youtubePlayer) {
  const playlistSession = ref(null)

  const loadPlaylist = (playlistId) => { /* ... */ }
  const nextVideo = () => { /* ... */ }
  const previousVideo = () => { /* ... */ }
  const playVideoAt = (index) => { /* ... */ }

  return { playlistSession, loadPlaylist, nextVideo, previousVideo, playVideoAt }
}
```

**useLocalStorage** (Persistence):
```javascript
export function useLocalStorage(key, defaultValue) {
  const storedValue = ref(loadFromStorage(key, defaultValue))

  watch(storedValue, (newValue) => {
    localStorage.setItem(key, JSON.stringify(newValue))
  }, { deep: true })

  return storedValue
}
```

### Data Flow

```
User Input (URL)
  → useUrlParser (validate & extract IDs)
  → useYouTubePlayer / usePlaylist (load video/playlist)
  → VideoSession / PlaylistSession (update state)
  → UI Components (render current state)

User Control (play/pause/volume)
  → useYouTubePlayer (YouTube API call)
  → VideoSession (update state)
  → useLocalStorage (persist preferences)
  → UI Components (update visual feedback)

YouTube Event (video ended)
  → useYouTubePlayer (detect end)
  → Loop Logic (check preferences.loopEnabled)
  → If loop: seekTo(0) or nextVideo()
  → VideoSession (update state)
```

---

## Relationships

```
PlayerState (root)
  ├── VideoSession (1:1, required when playing)
  ├── PlaylistSession (1:1, optional, only in playlist mode)
  ├── UserPreferences (1:1, always exists)
  └── ErrorState (1:1, optional, only when error exists)

PlaylistSession
  └── contains many VideoSession (conceptually)
      └── current video is represented in VideoSession.videoId
```

---

## State Persistence

### LocalStorage Schema

**Key**: `youtube-loop-player-preferences`

**Value** (JSON):
```json
{
  "version": "1.0",
  "data": {
    "volume": 75,
    "isMuted": false,
    "loopEnabled": true,
    "lastPlayedVideoId": null,
    "lastPlayedPlaylistId": null
  }
}
```

**Versioning Strategy**:
- Include `version` field for future migrations
- Current version: `1.0`
- If schema changes in future, implement migration logic

**Error Handling**:
- If LocalStorage is unavailable (private browsing), use in-memory fallback
- If stored data is corrupted, reset to defaults and warn user
- If quota exceeded (unlikely), clear old data

---

## Validation Functions

### URL Validation

```javascript
function isValidYouTubeUrl(url) {
  try {
    const urlObj = new URL(url)
    const hostname = urlObj.hostname
    return hostname.includes('youtube.com') || hostname.includes('youtu.be')
  } catch {
    return false
  }
}
```

### Video ID Validation

```javascript
function isValidVideoId(videoId) {
  return /^[a-zA-Z0-9_-]{11}$/.test(videoId)
}
```

### Volume Validation

```javascript
function isValidVolume(volume) {
  return Number.isInteger(volume) && volume >= 0 && volume <= 100
}
```

---

## State Initialization

### App Startup Sequence

1. Load `UserPreferences` from LocalStorage (or use defaults)
2. Initialize `PlayerState` with null sessions and no error
3. Wait for user to submit URL
4. Parse URL → extract video ID or playlist ID
5. Create `VideoSession` or `PlaylistSession`
6. Initialize YouTube Player with video/playlist
7. Apply `UserPreferences` (volume, loop mode) to player
8. Update UI to reflect loaded state

---

## Example: Complete State Snapshot

```javascript
{
  playerState: {
    videoSession: {
      videoId: 'yPYZpwSpKmA',
      playbackState: 'playing',
      currentTime: 120.5,
      duration: 245,
      isPlaylistMode: true,
      title: 'GANGNAM STYLE'
    },
    playlistSession: {
      playlistId: 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf',
      videoIds: ['dQw4w9WgXcQ', 'yPYZpwSpKmA', '9bZkp7q19f0'],
      currentIndex: 1,
      totalVideos: 3,
      title: 'Viral Hits',
      skippedIndices: []
    },
    preferences: {
      volume: 80,
      isMuted: false,
      loopEnabled: true,
      lastPlayedVideoId: 'yPYZpwSpKmA',
      lastPlayedPlaylistId: 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf'
    },
    isLoading: false,
    error: null
  }
}
```

---

## Notes

- All state is reactive via Vue's `ref()` and `reactive()`
- No global state store (Vuex/Pinia) needed - composables provide sufficient modularity
- State is intentionally denormalized for simplicity (e.g., video title stored in both sessions)
- Future enhancement: Add history/queue management if user requests it
