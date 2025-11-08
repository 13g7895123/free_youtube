# Development Guide

## Project Structure

```
browser-extension/
├── src/
│   ├── background/          # Background service worker
│   ├── popup/               # Extension popup UI
│   │   ├── popup.html       # Popup template
│   │   ├── popup.js         # Popup logic & modal handlers
│   │   └── popup.css        # Popup styles
│   ├── settings/            # Settings page
│   │   ├── settings.html    # Settings template
│   │   ├── settings.js      # Settings logic
│   │   └── settings.css     # Settings styles
│   ├── services/            # API and service layer
│   │   ├── api.js           # Backend API calls
│   │   ├── auth.js          # Authentication logic
│   │   └── youtube.js       # YouTube API integration
│   └── utils/               # Utility functions
│       ├── cache.js         # TTL-based caching
│       ├── token-manager.js # Token encryption/decryption
│       ├── retry.js         # Retry with exponential backoff
│       └── url-parser.js    # YouTube URL parsing
├── tests/
│   ├── unit/               # Unit tests
│   └── integration/        # Integration tests
├── icons/                  # Extension icons
├── manifest-chrome.json    # Chrome manifest (V3)
├── manifest-firefox.json   # Firefox manifest (V2)
├── jest.config.js          # Jest configuration
└── package.json            # Dependencies
```

## Setup

### Prerequisites
- Node.js 14+
- npm or yarn

### Installation
```bash
npm install
```

### Environment Variables
Copy `.env.example` to `.env` and update with your configuration:
```bash
cp .env.example .env
```

Required variables:
- `VITE_BACKEND_API_URL`: Backend API endpoint
- `VITE_LINE_CHANNEL_ID`: LINE Login channel ID

## Development Workflow

### Running in Development Mode

**Chrome**:
```bash
npm run dev:chrome
```
Opens Chrome with extension loaded in development mode. Auto-reloads on file changes.

**Firefox**:
```bash
npm run dev:firefox
```
Opens Firefox with extension loaded in development mode.

### Building

```bash
npm run build:chrome
npm run build:firefox
```

Creates a `manifest.json` symlink to the appropriate manifest file.

### Testing

**Run all tests**:
```bash
npm test
```

**Run tests in watch mode**:
```bash
npm run test:watch
```

**Generate coverage report**:
```bash
npm run test:coverage
```

## Code Standards

### JavaScript/ES6+
- Use modern ES6+ syntax
- Async/await for asynchronous operations
- Arrow functions for callbacks
- Template literals for string interpolation

### Naming Conventions
- `camelCase` for variables and functions
- `UPPER_SNAKE_CASE` for constants
- `PascalCase` for classes and constructors
- Prefix private functions with `_`

### Comments and Documentation
- JSDoc comments for public functions
- Explain "why" not "what" in comments
- Keep comments updated with code changes

### File Organization
- One main export per module
- Related functions grouped together
- Imports at the top, organized by source

## Key Components

### Modal Dialog System

**Files Involved**:
- `src/popup/popup.html`: Modal HTML structure
- `src/popup/popup.css`: Modal styling and animations
- `src/popup/popup.js`: Modal logic functions

**Key Functions**:
```javascript
showPlaylistSelector()      // Open modal and load playlists
renderPlaylistItems()       // Create DOM elements for playlist items
handlePlaylistSelection()   // Process selected playlist
closePlaylistModal()        // Clean up and hide modal
```

**State Management**:
- `currentVideoId`: Currently selected video
- `pendingVideoData`: Video data being processed
- Modal element visibility controlled by `.hidden` class

### Authentication Flow

1. User clicks "LINE 登入"
2. `handleLogin()` calls `loginWithLINE()`
3. OAuth popup opens for LINE authorization
4. Backend exchanges auth code for tokens
5. Tokens encrypted and stored locally
6. User info cached for future use
7. Popup switches to main interface

**Token Refresh**:
- Before each API call, tokens are validated
- If access token expired, refresh token is used
- If refresh fails, user is logged out
- Automatic retry prevents user interruption

### Caching Strategy

1. **Playlist Cache** (`cache_playlists`)
   - Loaded when playlist selector opens
   - Used in both popup and settings pages
   - Reduces API calls significantly

2. **User Cache**
   - Stores user profile information
   - Reduces API calls for user display

3. **TTL-Based Invalidation**
   - Configurable cache lifetime
   - Automatic cleanup of expired entries

## Error Handling Patterns

### API Errors
```javascript
try {
  const result = await apiCall();
  if (result.success) {
    // Handle success
  } else {
    // Handle API error response
    handleError(result.error);
  }
} catch (error) {
  // Handle network/connection errors
  if (error.message.includes('not authenticated')) {
    // Redirect to login
  } else if (error.message.includes('network')) {
    // Show network error message
  }
}
```

### User Notifications
```javascript
showSuccess(message);  // Green notification, auto-dismisses
showError(message);    // Red notification, user attention needed
showInfo(message);     // Blue notification, informational
```

## Adding New Features

### Checklist
- [ ] Create feature branch from main
- [ ] Implement functionality
- [ ] Write unit tests (minimum 70% coverage)
- [ ] Test in both Chrome and Firefox
- [ ] Add/update documentation
- [ ] Create pull request with description
- [ ] Address code review feedback

### Feature Phases
1. **Implementation Phase**: Core functionality
2. **Testing Phase**: Unit and integration tests
3. **Polish Phase**: UI/UX, documentation, edge cases
4. **Release Phase**: Version bump, changelog

## Debugging

### Browser DevTools
- Right-click extension popup → "Inspect Popup"
- Right-click extension icon (Chrome) → "Extension Options"
- View console logs and errors

### Logging
```javascript
console.log('Debug message:', variable);
console.error('Error:', error.message);
console.warn('Warning:', message);
```

### Mock Data for Testing
```javascript
const mockPlaylist = {
  id: 'playlist1',
  name: '我的最愛',
  videoCount: 5
};
```

## Performance Tips

1. **Cache Aggressively**: Use `getCache()` before API calls
2. **Batch Operations**: Fetch multiple items in one request
3. **Lazy Load**: Load resources only when needed
4. **Optimize Assets**: Minify CSS/JS, optimize icons
5. **Monitor Memory**: Check for memory leaks in devtools

## Common Issues

### Issue: Popup doesn't respond to clicks
**Solution**: Check if modal has `.loading` class which disables clicks

### Issue: Playlists not loading
**Solution**: Verify cache validity, check network tab for API errors

### Issue: Videos not being added
**Solution**: Check authentication status, verify video ID is valid

### Issue: Settings not persisting
**Solution**: Verify `browser.storage.local` API is working

## Browser Compatibility

- **Chrome**: 95+ (Manifest V3)
- **Firefox**: 90+ (Manifest V2)

Note: Manifest V2 deprecated in Chrome. Consider V2 migration when needed.

## Contributing

See main project README for contribution guidelines.
