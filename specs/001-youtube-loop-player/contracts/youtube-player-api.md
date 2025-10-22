# API Contract: YouTube IFrame Player API

**Feature**: YouTube Loop Player
**Date**: 2025-10-22
**Purpose**: Document the contract between our application and YouTube's IFrame Player API

## Overview

This contract defines how the YouTube Loop Player interacts with the YouTube IFrame Player API. This is a **third-party API contract** - we do not control the API, but we rely on its documented behavior.

**API Provider**: Google/YouTube
**API Documentation**: https://developers.google.com/youtube/iframe_api_reference
**API Stability**: Stable (part of YouTube's public API since 2010)
**Authentication**: None required for embed usage
**Rate Limits**: None for embed playback

---

## Contract Scope

This contract covers:
- Player initialization and lifecycle
- Video and playlist loading
- Playback control methods
- Event handling (state changes, errors)
- Parameter configuration

This contract does NOT cover:
- YouTube Data API v3 (not used in this project)
- YouTube Analytics API (not used)
- Video uploading or user authentication

---

## API Loading

### Load IFrame API Script

**Purpose**: Load the YouTube IFrame API JavaScript library

**Request**:
```html
<script src="https://www.youtube.com/iframe_api"></script>
```

**Response**:
- Script loads asynchronously
- Defines global `YT` object
- Triggers `onYouTubeIframeAPIReady` callback when ready

**Contract**:
```javascript
// Global callback function (must be defined before script loads)
window.onYouTubeIframeAPIReady = function() {
  // API is ready, can now create player instances
}
```

**Our Implementation**:
```javascript
export function loadYouTubeAPI() {
  return new Promise((resolve) => {
    if (window.YT && window.YT.Player) {
      resolve(window.YT)
      return
    }

    window.onYouTubeIframeAPIReady = () => {
      resolve(window.YT)
    }

    const script = document.createElement('script')
    script.src = 'https://www.youtube.com/iframe_api'
    document.head.appendChild(script)
  })
}
```

**Error Cases**:
- Network failure: Script fails to load → Catch with timeout, show error
- API already loaded: Check `window.YT` before adding script

---

## Player Initialization

### Create Player Instance

**Purpose**: Initialize a new YouTube player in a DOM element

**Request**:
```javascript
new YT.Player(elementId, {
  height: '390',
  width: '640',
  videoId: 'VIDEO_ID',
  playerVars: {
    autoplay: 1,
    controls: 0,
    modestbranding: 1,
    rel: 0
  },
  events: {
    onReady: onPlayerReady,
    onStateChange: onPlayerStateChange,
    onError: onPlayerError
  }
})
```

**Parameters**:
- `elementId` (string): DOM element ID where player will be embedded
- `height` (string): Player height in pixels
- `width` (string): Player width in pixels
- `videoId` (string, optional): Initial video ID to load
- `playerVars` (object): Player configuration parameters
- `events` (object): Event handler callbacks

**Player Variables** (commonly used):
- `autoplay` (0 | 1): Auto-play video on load
- `controls` (0 | 1): Show/hide player controls
- `loop` (0 | 1): Loop single video (requires `playlist` param)
- `modestbranding` (1): Minimal YouTube branding
- `rel` (0): Don't show related videos at end

**Response**:
- Returns `YT.Player` instance
- Player begins loading video/iframe
- Triggers `onReady` event when player is ready

**Contract Guarantees**:
- Player instance is created synchronously
- Events fire asynchronously after player loads
- `onReady` always fires before `onStateChange`

**Our Implementation**:
```javascript
player.value = new YT.Player('youtube-player', {
  height: '100%',
  width: '100%',
  videoId: videoId.value,
  playerVars: {
    autoplay: 1,
    controls: 0,      // We provide custom controls
    modestbranding: 1,
    rel: 0,           // Don't show related videos
    iv_load_policy: 3 // Hide annotations
  },
  events: {
    onReady: handlePlayerReady,
    onStateChange: handleStateChange,
    onError: handleError
  }
})
```

---

## Playback Control Methods

### loadVideoById

**Purpose**: Load and play a specific video by ID

**Request**:
```javascript
player.loadVideoById(videoId, startSeconds)
```

**Parameters**:
- `videoId` (string): YouTube video ID (11 characters)
- `startSeconds` (number, optional): Start playback at specific time

**Response**:
- Immediately stops current video (if playing)
- Loads new video
- Triggers `onStateChange` events as video loads

**Contract**:
- Video begins loading immediately (state → buffering)
- If video is invalid/unavailable, triggers `onError` event
- If video is playable, transitions to playing state

**Example**:
```javascript
player.loadVideoById('dQw4w9WgXcQ')
player.loadVideoById('dQw4w9WgXcQ', 30) // Start at 30 seconds
```

---

### loadPlaylist

**Purpose**: Load a YouTube playlist

**Request**:
```javascript
player.loadPlaylist({
  list: 'PLAYLIST_ID',
  index: 0,
  startSeconds: 0
})
```

**Parameters**:
- `list` (string): YouTube playlist ID
- `listType` (string, optional): `'playlist'` (default) or `'search'` or `'user_uploads'`
- `index` (number, optional): Start at specific video (0-based index)
- `startSeconds` (number, optional): Start playback at specific time

**Response**:
- Loads playlist and begins playing video at `index`
- Player enters playlist mode
- Triggers `onStateChange` events

**Contract**:
- Playlist videos load sequentially (API handles navigation)
- If playlist is empty/invalid, triggers `onError`
- Player remembers playlist context until new video/playlist loaded

**Example**:
```javascript
player.loadPlaylist({
  list: 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf',
  index: 0
})
```

---

### playVideo

**Purpose**: Play current video

**Request**:
```javascript
player.playVideo()
```

**Response**:
- Resumes playback if paused
- No effect if already playing
- Triggers `onStateChange` with state `YT.PlayerState.PLAYING` (1)

**Contract**:
- Must have a video loaded (otherwise no effect)
- Respects browser autoplay policies (may fail silently if blocked)

---

### pauseVideo

**Purpose**: Pause current video

**Request**:
```javascript
player.pauseVideo()
```

**Response**:
- Pauses playback
- No effect if already paused
- Triggers `onStateChange` with state `YT.PlayerState.PAUSED` (2)

---

### setVolume

**Purpose**: Set player volume

**Request**:
```javascript
player.setVolume(volume)
```

**Parameters**:
- `volume` (number): Volume level 0-100

**Response**:
- Volume changes immediately
- No event triggered

**Contract**:
- Volume persists across video changes within same player instance
- Volume does NOT persist across page reloads (we handle with LocalStorage)

---

### mute / unmute

**Purpose**: Mute or unmute player audio

**Request**:
```javascript
player.mute()
player.unmute()
```

**Response**:
- Mute state changes immediately
- No event triggered

---

### seekTo

**Purpose**: Seek to specific time in video

**Request**:
```javascript
player.seekTo(seconds, allowSeekAhead)
```

**Parameters**:
- `seconds` (number): Time to seek to
- `allowSeekAhead` (boolean): Whether to load unbuffered portions

**Response**:
- Playback jumps to specified time
- May trigger buffering state if seeking to unbuffered portion
- Triggers `onStateChange` event

**Used For**: Implementing single-video loop (seekTo(0) when video ends)

---

### Playlist Navigation Methods

#### nextVideo

**Purpose**: Play next video in playlist

**Request**:
```javascript
player.nextVideo()
```

**Response**:
- Loads and plays next video in playlist
- If at end of playlist, behavior depends on loop setting
- Triggers `onStateChange` events

**Contract**:
- Only works in playlist mode
- No effect if not in playlist or at end (without loop)

---

#### previousVideo

**Purpose**: Play previous video in playlist

**Request**:
```javascript
player.previousVideo()
```

**Response**:
- Loads and plays previous video in playlist
- No effect if at beginning of playlist

---

#### playVideoAt

**Purpose**: Play specific video in playlist by index

**Request**:
```javascript
player.playVideoAt(index)
```

**Parameters**:
- `index` (number): 0-based index in playlist

**Response**:
- Loads and plays video at specified index
- Triggers `onStateChange` events

**Contract**:
- Index must be valid (0 to playlist.length - 1)
- Invalid index may cause error or be ignored

**Used For**: Implementing playlist loop (playVideoAt(0) when reaching end)

---

## Query Methods (Getters)

### getPlayerState

**Purpose**: Get current player state

**Request**:
```javascript
const state = player.getPlayerState()
```

**Response** (number):
- `-1`: Unstarted
- `0`: Ended
- `1`: Playing
- `2`: Paused
- `3`: Buffering
- `5`: Video cued

**Contract**:
- Always returns current state synchronously
- State codes defined in `YT.PlayerState` enum

---

### getCurrentTime

**Purpose**: Get current playback time

**Request**:
```javascript
const time = player.getCurrentTime()
```

**Response** (number):
- Returns current playback position in seconds (float)

---

### getDuration

**Purpose**: Get video duration

**Request**:
```javascript
const duration = player.getDuration()
```

**Response** (number):
- Returns total video duration in seconds
- Returns `0` if no video loaded or metadata not yet available

---

### getPlaylist

**Purpose**: Get array of video IDs in current playlist

**Request**:
```javascript
const videoIds = player.getPlaylist()
```

**Response** (string[] | null):
- Returns array of video IDs if in playlist mode
- Returns `null` if not in playlist mode

**Contract**:
- Array order matches playlist order
- Only available in playlist mode

---

### getPlaylistIndex

**Purpose**: Get index of currently playing video in playlist

**Request**:
```javascript
const index = player.getPlaylistIndex()
```

**Response** (number):
- Returns 0-based index of current video in playlist
- Returns `-1` if not in playlist mode

**Used For**: Tracking position in playlist for loop logic

---

### getVolume

**Purpose**: Get current volume level

**Request**:
```javascript
const volume = player.getVolume()
```

**Response** (number):
- Returns volume level 0-100

---

### isMuted

**Purpose**: Check if player is muted

**Request**:
```javascript
const muted = player.isMuted()
```

**Response** (boolean):
- Returns `true` if muted, `false` otherwise

---

## Events

### onReady

**Purpose**: Triggered when player is fully loaded and ready

**Event Object**:
```javascript
{
  target: player // YT.Player instance
}
```

**Contract**:
- Fires once per player instance after initialization
- Safe to call player methods after this event
- Guaranteed to fire before any other events

**Our Handler**:
```javascript
function handlePlayerReady(event) {
  // Apply user preferences
  event.target.setVolume(preferences.value.volume)
  if (preferences.value.isMuted) {
    event.target.mute()
  }
}
```

---

### onStateChange

**Purpose**: Triggered when player state changes

**Event Object**:
```javascript
{
  target: player,  // YT.Player instance
  data: stateCode  // Number (-1, 0, 1, 2, 3, 5)
}
```

**State Codes**:
- `-1` (`YT.PlayerState.UNSTARTED`): Video not started
- `0` (`YT.PlayerState.ENDED`): Video ended
- `1` (`YT.PlayerState.PLAYING`): Video is playing
- `2` (`YT.PlayerState.PAUSED`): Video is paused
- `3` (`YT.PlayerState.BUFFERING`): Video is buffering
- `5` (`YT.PlayerState.CUED`): Video is cued (ready but not playing)

**Contract**:
- Fires whenever state changes
- Multiple rapid state changes possible (e.g., buffering → playing)
- State `ENDED` (0) is critical for loop logic

**Our Handler**:
```javascript
function handleStateChange(event) {
  const state = event.data

  if (state === YT.PlayerState.ENDED) {
    handleVideoEnd()
  } else if (state === YT.PlayerState.PLAYING) {
    videoSession.value.playbackState = 'playing'
  } else if (state === YT.PlayerState.PAUSED) {
    videoSession.value.playbackState = 'paused'
  } else if (state === YT.PlayerState.BUFFERING) {
    videoSession.value.playbackState = 'buffering'
  }
}

function handleVideoEnd() {
  if (!preferences.value.loopEnabled) {
    return // Don't loop
  }

  if (playlistSession.value) {
    // Playlist loop logic
    const index = player.value.getPlaylistIndex()
    const playlist = player.value.getPlaylist()

    if (index === playlist.length - 1) {
      // Last video, loop to start
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

---

### onError

**Purpose**: Triggered when playback error occurs

**Event Object**:
```javascript
{
  target: player,
  data: errorCode  // Number (2, 5, 100, 101, 150)
}
```

**Error Codes**:
- `2`: Invalid video ID
- `5`: HTML5 player error (usually geo-restriction or network issue)
- `100`: Video not found or private
- `101`: Video owner disallows embedding
- `150`: Same as 101 (alternate code)

**Contract**:
- Fires when video cannot play
- Player stops attempting to play
- User must take action (load different video)

**Our Handler**:
```javascript
function handleError(event) {
  const errorCode = event.data
  let message = ''

  switch (errorCode) {
    case 2:
      message = ERROR_MESSAGES.INVALID_URL
      break
    case 5:
      message = ERROR_MESSAGES.GEO_RESTRICTED
      break
    case 100:
      message = ERROR_MESSAGES.VIDEO_NOT_FOUND
      break
    case 101:
    case 150:
      message = ERROR_MESSAGES.EMBED_RESTRICTED
      break
    default:
      message = ERROR_MESSAGES.PLAYBACK_ERROR
  }

  error.value = {
    code: errorCode.toString(),
    message,
    timestamp: new Date(),
    recoverable: errorCode !== 101 && errorCode !== 150
  }

  // In playlist mode, skip to next video
  if (playlistSession.value) {
    const currentIndex = player.value.getPlaylistIndex()
    playlistSession.value.skippedIndices.push(currentIndex)
    player.value.nextVideo()
  }
}
```

---

## Contract Test Scenarios

### Test 1: Player Initialization

**Given**: YouTube API is loaded
**When**: Create new player with valid video ID
**Then**:
- `onReady` event fires
- Player state is `UNSTARTED` (-1) initially
- Video begins loading (state → `BUFFERING`)

---

### Test 2: Video End Loop

**Given**: Single video is playing with loop enabled
**When**: Video reaches end (state → `ENDED`)
**Then**:
- Our `handleVideoEnd` calls `seekTo(0)` and `playVideo()`
- Video restarts from beginning
- State transitions to `PLAYING`

---

### Test 3: Playlist End Loop

**Given**: Playlist is playing, currently at last video
**When**: Last video reaches end
**Then**:
- Our `handleVideoEnd` calls `playVideoAt(0)`
- First video in playlist begins playing
- Playlist loop continues indefinitely

---

### Test 4: Error Handling

**Given**: User submits video ID that doesn't exist
**When**: Player attempts to load invalid video
**Then**:
- `onError` event fires with code `100`
- Our handler displays "無法載入影片" error message
- Player stops attempting to play

---

### Test 5: Playlist with Unavailable Video

**Given**: Playlist contains 3 videos, middle one is private
**When**: Playlist reaches the private video
**Then**:
- `onError` fires with code `100` or `101`
- Our handler adds index to `skippedIndices`
- Our handler calls `nextVideo()` to skip
- Playback continues with next available video

---

## Assumptions and Limitations

### Assumptions

1. **YouTube API Stability**: YouTube IFrame API is stable and won't have breaking changes
2. **Event Reliability**: `onStateChange` and `onError` events fire reliably
3. **State Consistency**: Player state returned by `getPlayerState()` matches last `onStateChange` event
4. **Playlist Order**: Playlist order returned by `getPlaylist()` matches YouTube's playlist order

### Known Limitations

1. **Autoplay Policy**: Browser may block autoplay, no way to detect programmatically
2. **Network Issues**: API doesn't distinguish between geo-restriction and network failure (both code `5`)
3. **Playlist Size**: Very large playlists (>200 videos) may cause performance issues
4. **Embed Restrictions**: Some videos disallow embedding (error 101/150), no workaround
5. **No Download**: Cannot download video for offline playback (Terms of Service violation)

---

## Version Compatibility

**Tested With**:
- YouTube IFrame API version: Latest (auto-updated by YouTube)
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Not Supported**:
- Internet Explorer (YouTube API no longer supports IE)
- Very old mobile browsers (pre-2018)

---

## References

- [YouTube IFrame Player API Reference](https://developers.google.com/youtube/iframe_api_reference)
- [Player Parameters](https://developers.google.com/youtube/player_parameters)
- [YouTube Terms of Service](https://www.youtube.com/static?template=terms)
