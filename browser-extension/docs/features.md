# YouTube Video Manager - Features Documentation

## Overview
YouTube Video Manager is a browser extension that allows users to quickly add YouTube videos to their personal library or playlists.

## Core Features

### 1. Authentication
- **LINE Login Integration**: Users can login using their LINE account via OAuth 2.0
- **Session Management**: Access tokens are automatically stored and refreshed
- **Secure Token Storage**: Tokens are encrypted using Web Crypto API (AES-GCM)
- **Logout**: Users can logout and clear their session data

**Files**: `src/services/auth.js`, `src/utils/token-manager.js`

### 2. Video Library Management

#### Add to Library
- Adds YouTube videos directly to the user's library
- Automatically fetches video metadata from YouTube API
- Supports batch video retrieval
- Graceful fallback when YouTube API quota is exhausted

**Files**: `src/popup/popup.js:257-318`, `src/services/api.js`

**Usage**:
```javascript
const videoData = {
  youtubeVideoId: 'dQw4w9WgXcQ',
  title: 'Video Title',
  thumbnailUrl: 'https://...',
  duration: 300,
  channelTitle: 'Channel Name'
};

const result = await addVideoToLibrary(videoData);
```

### 3. Playlist Management

#### Playlist Modes

**Predefined Mode** (默認模式):
- Automatically adds videos to a user-selected default playlist
- Faster workflow for users with a primary playlist
- Configured in Settings page
- Playlist name is cached for better UX

**Custom Mode** (自訂模式):
- Users select a playlist each time before adding a video
- Shows all available playlists in a modal dialog
- Displays video count for each playlist
- Provides immediate feedback on success/failure

#### Modal Selector Dialog
- **File**: `src/popup/popup.html:37-67`, `src/popup/popup.css:204-357`
- **Functions**:
  - `showPlaylistSelector()`: Opens modal and loads playlists
  - `renderPlaylistItems()`: Renders playlist list from array
  - `handlePlaylistSelection()`: Processes selected playlist and adds video
  - `closePlaylistModal()`: Cleans up modal state

**Features**:
- Loading state with spinner
- Error handling with user-friendly messages
- Empty state when no playlists exist
- Caching of playlist data to reduce API calls
- Disabled state during processing

### 4. Settings Page

**Location**: `src/settings/settings.html`

**Configurable Options**:
1. Playlist Mode Selection
   - Predefined: Select a default playlist for automatic adding
   - Custom: Choose playlist manually each time

2. Default Playlist Selection
   - Only visible when Predefined mode is selected
   - Shows count of videos in each playlist
   - Validates that a playlist is selected

3. Library Auto-Add
   - Option to automatically add videos to library along with playlist
   - Improves organization for users managing both library and playlists

**Persistence**: Settings saved to `browser.storage.local`

### 5. Caching System

**Purpose**: Reduce API calls and improve performance

**Cached Data**:
- Playlist list (`cache_playlists`)
- User profile information
- Authorization tokens

**TTL**: Configurable time-to-live for each cache entry

**Files**: `src/utils/cache.js`

**Usage**:
```javascript
// Check cache first
let playlists = await getCache('cache_playlists');

// If not cached, fetch from API
if (!playlists) {
  const response = await getPlaylists();
  playlists = response.playlists;
  await setCache('cache_playlists', playlists);
}
```

## Error Handling

### Common Errors and Recovery

1. **Authentication Errors** (401)
   - User is redirected to login
   - Clear error message: "請先登入"

2. **Network Errors**
   - Retry mechanism with exponential backoff
   - User-friendly message: "網路連線失敗，請稍後再試"

3. **Video Duplication**
   - Prevent adding same video twice to library/playlist
   - Informative message: "此影片已在播放清單中"

4. **YouTube API Quota**
   - Fallback strategy when quota exhausted
   - Partial data collection with user notification
   - Message includes "(部分資訊無法取得)"

5. **Invalid Playlist**
   - Validation before adding video
   - Clear error message with guidance

## UI/UX Improvements

### Notification System
- Toast notifications for user feedback
- Auto-dismiss after 3 seconds
- Different styling for success, error, info
- Non-intrusive positioning

### Modal Dialogs
- Overlay prevents background interaction
- Loading state with visual feedback (opacity change)
- Keyboard-accessible close buttons
- Smooth animations (fade in/out)

### Settings Page
- Clear section headers
- Radio buttons for mode selection
- Help text for each option
- Save/Reset buttons with confirmation
- Visual feedback during save operation

## Performance Optimizations

1. **Caching**: Avoid redundant API calls
2. **Lazy Loading**: Playlists loaded on demand
3. **Batching**: YouTube video info fetched in batches
4. **Retry Logic**: Exponential backoff for failed requests

## Testing

Test files are located in `tests/` directory:
- `tests/unit/popup.test.js`: Modal functionality tests
- `tests/unit/api.test.js`: API service tests
- `tests/unit/auth.test.js`: Authentication tests
- `tests/unit/youtube-service.test.js`: YouTube API tests

Run tests with:
```bash
npm test
npm run test:coverage
npm run test:watch
```

## Build Instructions

### Development
```bash
# Chrome
npm run dev:chrome

# Firefox
npm run dev:firefox
```

### Production Build
```bash
npm run build:chrome
npm run build:firefox
```

## Security Considerations

1. **Token Storage**: Encrypted using AES-GCM
2. **HTTPS Only**: All API calls use HTTPS
3. **Token Refresh**: Automatic refresh before expiry
4. **Input Validation**: Sanitize user inputs
5. **Content Security Policy**: Configured in manifest

## Future Enhancements

1. **Batch Operations**: Add multiple videos at once
2. **Advanced Filtering**: Filter playlists by tags/categories
3. **Keyboard Shortcuts**: Quick add without opening popup
4. **Analytics**: Track user actions (with consent)
5. **Multi-language Support**: i18n for UI translations
6. **Dark Theme**: Automatic theme switching
7. **Accessibility**: ARIA labels and keyboard navigation
