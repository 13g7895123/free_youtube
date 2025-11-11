# Quick Start: LINE Login 會員認證系統

**Feature**: 003-line-login-auth
**Spec**: [spec.md](./spec.md)
**Research**: [research.md](./research.md)
**Data Model**: [data-model.md](./data-model.md)
**API Contracts**: [contracts/openapi.yaml](./contracts/openapi.yaml)

## 前置需求

### 1. LINE Developers 設定

1. 前往 [LINE Developers Console](https://developers.line.biz/console/)
2. 建立新的 LINE Login channel
3. 設定 Callback URL: `http://localhost:8080/api/auth/line/callback` (開發環境)
4. 記錄以下資訊:
   - Channel ID
   - Channel Secret

### 2. 環境變數設定

編輯 `/home/jarvis/project/idea/free_youtube/backend/.env`:

```env
# LINE Login 設定
LINE_LOGIN_CHANNEL_ID=your_channel_id_here
LINE_LOGIN_CHANNEL_SECRET=your_channel_secret_here
LINE_LOGIN_CALLBACK_URL=http://localhost:8080/api/auth/line/callback

# Session 設定
SESSION_DRIVER=files
SESSION_EXPIRATION=2592000  # 30 天 (秒)
```

### 3. 資料庫遷移

```bash
cd /home/jarvis/project/idea/free_youtube/backend
php spark migrate
```

這會執行 `app/Database/Migrations/2025110100_create_line_login_tables.php`,建立所有必要的表。

## 開發流程

### 後端 (CodeIgniter 4)

#### 1. 建立認證控制器

檔案位置: `backend/app/Controllers/Auth.php`

```php
<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserTokenModel;

class Auth extends BaseController
{
    public function lineLogin()
    {
        // 產生 state 防 CSRF
        $state = bin2hex(random_bytes(16));
        session()->set('line_oauth_state', $state);

        $params = [
            'response_type' => 'code',
            'client_id' => getenv('LINE_LOGIN_CHANNEL_ID'),
            'redirect_uri' => getenv('LINE_LOGIN_CALLBACK_URL'),
            'state' => $state,
            'scope' => 'profile openid email'
        ];

        $url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
        return redirect()->to($url);
    }

    public function lineCallback()
    {
        // 實作見 research.md 的 LINE OAuth 最佳實踐
    }

    public function getCurrentUser()
    {
        $userId = $this->getUserIdFromToken();
        if (!$userId) {
            return $this->respond([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => '請先登入']
            ], 401);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        return $this->respond(['success' => true, 'data' => $user]);
    }

    private function getUserIdFromToken()
    {
        $cookie = $this->request->getCookie('access_token');
        if (!$cookie) return null;

        $tokenModel = new UserTokenModel();
        $token = $tokenModel->findByAccessToken($cookie);

        return $token ? $token['user_id'] : null;
    }
}
```

#### 2. 設定路由

檔案位置: `backend/app/Config/Routes.php`

```php
$routes->group('api', function($routes) {
    // 認證路由
    $routes->get('auth/line/login', 'Auth::lineLogin');
    $routes->get('auth/line/callback', 'Auth::lineCallback');
    $routes->get('auth/user', 'Auth::getCurrentUser');
    $routes->post('auth/logout', 'Auth::logout');

    // 影片庫路由 (需認證)
    $routes->group('video-library', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'VideoLibrary::index');
        $routes->post('/', 'VideoLibrary::add');
        $routes->delete('(:segment)', 'VideoLibrary::remove/$1');
    });

    // 播放清單路由 (需認證)
    $routes->group('playlists', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Playlists::index');
        $routes->post('/', 'Playlists::create');
        $routes->get('(:num)', 'Playlists::show/$1');
        $routes->put('(:num)', 'Playlists::update/$1');
        $routes->delete('(:num)', 'Playlists::delete/$1');
        $routes->post('(:num)/items', 'Playlists::addItem/$1');
        $routes->delete('(:num)/items/(:num)', 'Playlists::removeItem/$1/$2');
    });
});
```

#### 3. 建立認證過濾器

檔案位置: `backend/app/Filters/AuthFilter.php`

```php
<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\UserTokenModel;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $cookie = $request->getCookie('access_token');
        if (!$cookie) {
            return service('response')->setJSON([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => '請先登入']
            ])->setStatusCode(401);
        }

        $tokenModel = new UserTokenModel();
        $token = $tokenModel->findByAccessToken($cookie);

        if (!$token) {
            return service('response')->setJSON([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Token 無效或已過期']
            ])->setStatusCode(401);
        }

        // 將 user_id 儲存到 request,供後續使用
        $request->userId = $token['user_id'];
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
```

### 前端 (Vue 3)

#### 1. 建立 Auth Store

檔案位置: `frontend/src/stores/auth.js`

```javascript
import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    isLoading: false
  }),

  getters: {
    isGuest: (state) => !state.isAuthenticated,
    userDisplayName: (state) => state.user?.displayName || '訪客',
    userAvatar: (state) => state.user?.avatarUrl || null
  },

  actions: {
    async checkAuth() {
      this.isLoading = true
      try {
        const response = await axios.get('/api/auth/user')
        if (response.data.success) {
          this.user = response.data.data
          this.isAuthenticated = true
        }
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
      } finally {
        this.isLoading = false
      }
    },

    async login() {
      // 重定向到後端 LINE Login endpoint
      window.location.href = '/api/auth/line/login'
    },

    async logout() {
      try {
        await axios.post('/api/auth/logout')
        this.user = null
        this.isAuthenticated = false
        this.$router.push('/')
      } catch (error) {
        console.error('Logout failed:', error)
      }
    }
  }
})
```

#### 2. 設定 Axios Interceptor

檔案位置: `frontend/src/services/axios.js`

```javascript
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8080'
axios.defaults.withCredentials = true // 重要:攜帶 cookies

// Response Interceptor 處理 401
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.user = null
      authStore.isAuthenticated = false
      // 可選:顯示提示訊息或重定向
    }
    return Promise.reject(error)
  }
)

export default axios
```

#### 3. 路由守衛

檔案位置: `frontend/src/router/index.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/library',
    name: 'VideoLibrary',
    component: () => import('@/views/VideoLibrary.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/playlists',
    name: 'Playlists',
    component: () => import('@/views/Playlists.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Home' })
  } else {
    next()
  }
})

export default router
```

#### 4. App.vue 初始化

檔案位置: `frontend/src/App.vue`

```vue
<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  // 檢查認證狀態
  authStore.checkAuth()
})
</script>

<template>
  <div id="app">
    <!-- 導航列 -->
    <nav>
      <div v-if="authStore.isAuthenticated">
        <img :src="authStore.userAvatar" :alt="authStore.userDisplayName" />
        <span>{{ authStore.userDisplayName }}</span>
        <button @click="authStore.logout">登出</button>
      </div>
      <div v-else>
        <button @click="authStore.login">LINE 登入</button>
      </div>
    </nav>

    <router-view />
  </div>
</template>
```

## 測試流程

### 1. 手動測試

1. 啟動後端: `cd backend && php spark serve`
2. 啟動前端: `cd frontend && npm run dev`
3. 訪問 http://localhost:5173
4. 點擊「LINE 登入」按鈕
5. 完成 LINE OAuth 授權
6. 驗證:
   - 右上角顯示使用者資訊
   - 導航選單顯示「影片庫」和「播放清單」
   - 可以新增影片到影片庫
   - 可以建立播放清單

### 2. 自動化測試

#### 後端單元測試

檔案位置: `backend/tests/unit/UserModelTest.php`

```php
<?php
namespace Tests\Unit;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class UserModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;

    public function testFindByLineUserId()
    {
        $model = new UserModel();
        $data = [
            'line_user_id' => 'U1234567890',
            'display_name' => 'Test User',
            'status' => 'active'
        ];
        $model->insert($data);

        $user = $model->findByLineUserId('U1234567890');
        $this->assertEquals('Test User', $user['display_name']);
    }
}
```

#### 前端單元測試

檔案位置: `frontend/tests/stores/auth.spec.js`

```javascript
import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

vi.mock('axios')

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('should check auth and set user', async () => {
    axios.get.mockResolvedValue({
      data: {
        success: true,
        data: { id: 1, displayName: 'Test User' }
      }
    })

    const store = useAuthStore()
    await store.checkAuth()

    expect(store.isAuthenticated).toBe(true)
    expect(store.user.displayName).toBe('Test User')
  })
})
```

## 疑難排解

### 問題 1: CORS 錯誤

**症狀**: 前端無法存取後端 API

**解決方法**: 在 `backend/app/Config/Filters.php` 啟用 CORS:

```php
public $globals = [
    'before' => [
        'cors', // 新增這一行
    ],
];
```

### 問題 2: Cookie 未設置

**症狀**: 登入後仍顯示未登入

**解決方法**: 確認:
1. `axios.defaults.withCredentials = true`
2. Cookie 的 `SameSite` 屬性設為 `Lax` (開發環境)
3. 前後端在同一網域 (或使用 proxy)

### 問題 3: Migration 失敗

**症狀**: `php spark migrate` 報錯

**解決方法**: 檢查資料庫連線設定 `backend/app/Config/Database.php`

## 下一步

完成 Quick Start 後,執行 `/speckit.tasks` 產生詳細的任務分解。
