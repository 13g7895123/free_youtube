# API Reference

## Overview

The extension communicates with a backend API to manage user libraries and playlists. All API calls use HTTPS with JWT token authentication.

## Base URL

```
https://api.example.com/api
```

## Authentication

### Request Headers
All authenticated requests must include:
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

### Token Management
- Access token valid for 1 hour
- Refresh token valid for 30 days
- Tokens automatically refreshed before expiry
- Expired tokens return 401 Unauthorized

## Endpoints

### Authentication

#### LINE Login
```
POST /auth/line/login
Content-Type: application/json

Request:
{
  "code": "authorization_code",
  "codeVerifier": "pkce_code_verifier"
}

Response (Success - 200):
{
  "success": true,
  "isNewUser": false,
  "user": {
    "id": "user123",
    "displayName": "使用者名稱",
    "profilePictureUrl": "https://..."
  },
  "accessToken": "jwt_token",
  "refreshToken": "refresh_token"
}

Response (Error - 400/401):
{
  "success": false,
  "error": "INVALID_CODE",
  "message": "Authorization code expired"
}
```

#### Refresh Token
```
POST /auth/refresh
Content-Type: application/json
Authorization: Bearer <refresh_token>

Response (Success - 200):
{
  "success": true,
  "accessToken": "new_jwt_token"
}

Response (Error - 401):
{
  "success": false,
  "error": "INVALID_REFRESH_TOKEN",
  "message": "Refresh token expired"
}
```

#### Logout
```
POST /auth/logout
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "message": "Logged out successfully"
}
```

### Video Library

#### Add to Library
```
POST /library/videos
Authorization: Bearer <access_token>
Content-Type: application/json

Request:
{
  "youtubeVideoId": "dQw4w9WgXcQ",
  "title": "Video Title",
  "channelTitle": "Channel Name",
  "duration": 300,
  "thumbnailUrl": "https://..."
}

Response (Success - 200):
{
  "success": true,
  "videoId": "video123",
  "message": "Video added to library"
}

Response (Already Exists - 409):
{
  "success": false,
  "error": "VIDEO_ALREADY_EXISTS",
  "message": "This video is already in your library"
}

Response (Error - 400):
{
  "success": false,
  "error": "INVALID_REQUEST",
  "message": "Missing required field: title"
}
```

#### Get Library Videos
```
GET /library/videos?page=1&limit=20
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "videos": [
    {
      "id": "video123",
      "youtubeVideoId": "dQw4w9WgXcQ",
      "title": "Video Title",
      "channelTitle": "Channel Name",
      "duration": 300,
      "thumbnailUrl": "https://...",
      "addedAt": "2025-01-01T12:00:00Z"
    }
  ],
  "total": 42,
  "page": 1,
  "limit": 20
}
```

### Playlists

#### Get User Playlists
```
GET /playlists?page=1&limit=50
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "playlists": [
    {
      "id": "playlist1",
      "name": "我的最愛",
      "description": "My favorite videos",
      "videoCount": 15,
      "createdAt": "2025-01-01T12:00:00Z",
      "updatedAt": "2025-01-10T12:00:00Z"
    }
  ],
  "total": 5,
  "page": 1,
  "limit": 50
}
```

#### Create Playlist
```
POST /playlists
Authorization: Bearer <access_token>
Content-Type: application/json

Request:
{
  "name": "新播放清單",
  "description": "Optional description"
}

Response (Success - 201):
{
  "success": true,
  "playlist": {
    "id": "playlist_new",
    "name": "新播放清單",
    "description": "Optional description",
    "videoCount": 0,
    "createdAt": "2025-01-10T12:00:00Z"
  }
}
```

#### Add Video to Playlist
```
POST /playlists/:playlistId/videos
Authorization: Bearer <access_token>
Content-Type: application/json

Request:
{
  "youtubeVideoId": "dQw4w9WgXcQ",
  "title": "Video Title",
  "channelTitle": "Channel Name",
  "duration": 300,
  "thumbnailUrl": "https://..."
}

Response (Success - 200):
{
  "success": true,
  "message": "Video added to playlist"
}

Response (Video Already in Playlist - 409):
{
  "success": false,
  "error": "VIDEO_ALREADY_IN_PLAYLIST",
  "message": "This video is already in this playlist"
}

Response (Playlist Not Found - 404):
{
  "success": false,
  "error": "PLAYLIST_NOT_FOUND",
  "message": "The specified playlist does not exist"
}
```

#### Get Playlist Videos
```
GET /playlists/:playlistId/videos?page=1&limit=20
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "videos": [
    {
      "id": "video123",
      "youtubeVideoId": "dQw4w9WgXcQ",
      "title": "Video Title",
      "channelTitle": "Channel Name",
      "duration": 300,
      "thumbnailUrl": "https://...",
      "addedAt": "2025-01-01T12:00:00Z"
    }
  ],
  "total": 15,
  "page": 1,
  "limit": 20
}
```

#### Remove Video from Playlist
```
DELETE /playlists/:playlistId/videos/:videoId
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "message": "Video removed from playlist"
}

Response (Error - 404):
{
  "success": false,
  "error": "VIDEO_NOT_FOUND",
  "message": "Video not found in this playlist"
}
```

#### Delete Playlist
```
DELETE /playlists/:playlistId
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "message": "Playlist deleted"
}
```

### User Profile

#### Get Current User
```
GET /users/me
Authorization: Bearer <access_token>

Response (Success - 200):
{
  "success": true,
  "user": {
    "id": "user123",
    "displayName": "使用者名稱",
    "profilePictureUrl": "https://...",
    "email": "user@example.com",
    "createdAt": "2025-01-01T12:00:00Z"
  }
}
```

## Error Responses

### Common Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| INVALID_REQUEST | 400 | Missing or invalid parameters |
| UNAUTHORIZED | 401 | Invalid or expired token |
| FORBIDDEN | 403 | User doesn't have permission |
| NOT_FOUND | 404 | Resource doesn't exist |
| CONFLICT | 409 | Resource already exists |
| RATE_LIMITED | 429 | Too many requests |
| SERVER_ERROR | 500 | Internal server error |

### Error Response Format
```json
{
  "success": false,
  "error": "ERROR_CODE",
  "message": "Human-readable error message",
  "details": {
    "field": "Additional details if applicable"
  }
}
```

## Rate Limiting

- **Limit**: 100 requests per minute per user
- **Headers**:
  - `X-RateLimit-Limit`: 100
  - `X-RateLimit-Remaining`: Number of remaining requests
  - `X-RateLimit-Reset`: Unix timestamp when limit resets

When rate limited (429):
```json
{
  "success": false,
  "error": "RATE_LIMITED",
  "message": "Too many requests. Please try again later.",
  "retryAfter": 60
}
```

## Pagination

Query parameters:
- `page` (default: 1): Page number starting from 1
- `limit` (default: 20, max: 100): Items per page

Response includes:
- `total`: Total number of items
- `page`: Current page
- `limit`: Items per page
- `data`: Array of items

## Retry Strategy

The extension implements exponential backoff for failed requests:

```
Attempt 1: Immediate
Attempt 2: After 1 second
Attempt 3: After 2 seconds
Attempt 4: After 4 seconds
Maximum: 3 retries
```

Retried on:
- Network timeout
- 5xx server errors
- 429 Rate Limited

Not retried on:
- 4xx client errors (except 429)
- 401 Unauthorized (user logged out)

## Examples

### Adding a Video to Library
```javascript
const videoData = {
  youtubeVideoId: 'dQw4w9WgXcQ',
  title: 'Rick Astley - Never Gonna Give You Up',
  channelTitle: 'Rick Astley',
  duration: 213,
  thumbnailUrl: 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg'
};

const response = await fetch('https://api.example.com/api/library/videos', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(videoData)
});

const result = await response.json();
```

### Getting User Playlists
```javascript
const response = await fetch('https://api.example.com/api/playlists?limit=50', {
  headers: {
    'Authorization': `Bearer ${accessToken}`
  }
});

const data = await response.json();
console.log(data.playlists); // Array of playlists
```

### Adding Video to Playlist
```javascript
const response = await fetch(
  `https://api.example.com/api/playlists/${playlistId}/videos`,
  {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${accessToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(videoData)
  }
);

const result = await response.json();
```

## Versioning

Current API version: **v1**

Future versions will use `/api/v2`, `/api/v3`, etc.

Breaking changes will be announced 3 months in advance.
