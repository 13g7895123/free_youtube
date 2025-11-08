import browser from 'webextension-polyfill';
import { loginWithLINE, logout, isAuthenticated, getCurrentUser } from '../services/auth.js';
import { addVideoToLibrary, addVideoToPlaylist, getPlaylists } from '../services/api.js';
import { getVideoInfo } from '../services/youtube.js';
import { parseYouTubeURL } from '../utils/url-parser.js';
import { getCache, setCache } from '../utils/cache.js';

/**
 * YouTube Extension Popup 主程式
 */

// DOM 元素
let authSection;
let mainSection;
let loginBtn;
let logoutBtn;
let settingsBtn;
let userInfoDiv;
let addToLibraryBtn;
let addToPlaylistBtn;
let playlistModal;
let modalCloseBtn;
let modalCancelBtn;
let playlistList;
let playlistEmpty;
let playlistLoading;
let playlistError;
let currentVideoId = null;
let pendingVideoData = null;

/**
 * 初始化 popup
 */
async function init() {
  try {
    // 取得 DOM 元素
    authSection = document.getElementById('auth-section');
    mainSection = document.getElementById('main-section');
    loginBtn = document.getElementById('login-btn');
    logoutBtn = document.getElementById('logout-btn');
    settingsBtn = document.getElementById('settings-btn');
    userInfoDiv = document.getElementById('user-info');
    addToLibraryBtn = document.getElementById('add-to-library-btn');
    addToPlaylistBtn = document.getElementById('add-to-playlist-btn');
    playlistModal = document.getElementById('playlist-modal');
    modalCloseBtn = document.getElementById('modal-close-btn');
    modalCancelBtn = document.getElementById('modal-cancel-btn');
    playlistList = document.getElementById('playlist-list');
    playlistEmpty = document.getElementById('playlist-empty');
    playlistLoading = document.getElementById('playlist-loading');
    playlistError = document.getElementById('playlist-error');

    // 註冊事件處理器
    loginBtn.addEventListener('click', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    settingsBtn.addEventListener('click', handleSettings);
    addToLibraryBtn.addEventListener('click', handleAddToLibrary);
    addToPlaylistBtn.addEventListener('click', handleAddToPlaylist);
    modalCloseBtn.addEventListener('click', closePlaylistModal);
    modalCancelBtn.addEventListener('click', closePlaylistModal);

    // 檢查當前分頁是否為 YouTube 影片頁
    await checkCurrentTab();

    // 檢查登入狀態並顯示對應介面
    await checkAuthStatus();

  } catch (error) {
    console.error('Failed to initialize popup:', error);
    showError('初始化失敗，請重新開啟擴充功能');
  }
}

/**
 * 檢查當前分頁是否為 YouTube 影片頁
 */
async function checkCurrentTab() {
  try {
    const tabs = await browser.tabs.query({ active: true, currentWindow: true });
    const currentTab = tabs[0];

    if (currentTab && currentTab.url) {
      const videoId = parseYouTubeURL(currentTab.url);
      if (videoId) {
        currentVideoId = videoId;
        // 啟用操作按鈕
        addToLibraryBtn.disabled = false;
        addToPlaylistBtn.disabled = false;
      } else {
        currentVideoId = null;
        // 停用操作按鈕並顯示提示
        addToLibraryBtn.disabled = true;
        addToPlaylistBtn.disabled = true;
        showInfo('請在 YouTube 影片頁面使用此功能');
      }
    }
  } catch (error) {
    console.error('Failed to check current tab:', error);
  }
}

/**
 * 檢查認證狀態並更新 UI
 */
async function checkAuthStatus() {
  try {
    const authenticated = await isAuthenticated();

    if (authenticated) {
      // 已登入，顯示主介面
      const user = await getCurrentUser();
      showMainSection(user);
    } else {
      // 未登入，顯示登入介面
      showAuthSection();
    }
  } catch (error) {
    console.error('Failed to check auth status:', error);
    showAuthSection();
  }
}

/**
 * 顯示登入區域
 */
function showAuthSection() {
  authSection.classList.remove('hidden');
  mainSection.classList.add('hidden');
}

/**
 * 顯示主區域
 * @param {Object} user - 使用者資訊
 */
function showMainSection(user) {
  authSection.classList.add('hidden');
  mainSection.classList.remove('hidden');

  // 顯示使用者資訊
  displayUserInfo(user);
}

/**
 * 顯示使用者資訊
 * @param {Object} user - 使用者資訊
 */
function displayUserInfo(user) {
  if (!user) {
    userInfoDiv.innerHTML = '<p>無法載入使用者資訊</p>';
    return;
  }

  const { displayName, profilePictureUrl } = user;

  userInfoDiv.innerHTML = `
    <div class="user-profile">
      ${profilePictureUrl ? `<img src="${profilePictureUrl}" alt="${displayName}" class="user-avatar">` : ''}
      <div class="user-details">
        <p class="user-name">${displayName || '使用者'}</p>
      </div>
    </div>
  `;
}

/**
 * 處理登入
 */
async function handleLogin() {
  try {
    // 顯示載入狀態
    loginBtn.disabled = true;
    loginBtn.textContent = '登入中...';

    // 執行 LINE 登入
    const result = await loginWithLINE();

    if (result.success) {
      // 登入成功
      showSuccess(result.isNewUser ? '歡迎！帳號已建立' : '登入成功');
      showMainSection(result.user);
    }

  } catch (error) {
    console.error('Login failed:', error);
    handleLoginError(error);
  } finally {
    // 恢復按鈕狀態
    loginBtn.disabled = false;
    loginBtn.textContent = 'LINE 登入';
  }
}

/**
 * 處理登入錯誤
 * @param {Error} error - 錯誤物件
 */
function handleLoginError(error) {
  let message = '登入失敗，請稍後再試';

  // 根據錯誤訊息提供更友善的提示
  if (error.message.includes('User cancelled')) {
    message = '您已取消登入';
  } else if (error.message.includes('network') || error.message.includes('fetch')) {
    message = '網路連線失敗，請檢查網路連線';
  } else if (error.message.includes('authorization code')) {
    message = 'LINE 授權失敗，請重試';
  }

  showError(message);
}

/**
 * 處理登出
 */
async function handleLogout() {
  try {
    // 顯示確認對話框
    const confirmed = confirm('確定要登出嗎？');
    if (!confirmed) {
      return;
    }

    // 顯示載入狀態
    logoutBtn.disabled = true;
    logoutBtn.textContent = '登出中...';

    // 執行登出
    await logout();

    // 登出成功
    showSuccess('已登出');
    showAuthSection();

  } catch (error) {
    console.error('Logout failed:', error);
    showError('登出失敗，請稍後再試');
  } finally {
    // 恢復按鈕狀態
    logoutBtn.disabled = false;
    logoutBtn.textContent = '登出';
  }
}

/**
 * 處理開啟設定頁面
 */
async function handleSettings() {
  try {
    // 開啟設定頁面於新分頁
    await browser.tabs.create({
      url: browser.runtime.getURL('src/settings/settings.html')
    });

    // 關閉 popup
    window.close();

  } catch (error) {
    console.error('Failed to open settings:', error);
    showError('無法開啟設定頁面');
  }
}

/**
 * 處理加入播放庫
 */
async function handleAddToLibrary() {
  if (!currentVideoId) {
    showError('請在 YouTube 影片頁面使用此功能');
    return;
  }

  try {
    addToLibraryBtn.disabled = true;
    addToLibraryBtn.textContent = '加入中...';

    // 1. 從 YouTube API 取得影片資訊
    let videoInfo;
    try {
      showInfo('正在取得影片資訊...');
      videoInfo = await getVideoInfo(currentVideoId);
    } catch (error) {
      console.error('Failed to get video info:', error);
      showError('無法取得影片資訊');
      return;
    }

    // 2. 準備影片資料
    const videoData = {
      youtubeVideoId: currentVideoId,
      title: videoInfo.title,
      thumbnailUrl: videoInfo.thumbnailUrl,
      duration: videoInfo.duration,
      channelTitle: videoInfo.channelTitle
    };

    // 3. 呼叫後端 API 加入播放庫
    const result = await addVideoToLibrary(videoData);

    // 4. 顯示結果
    if (result.success) {
      const message = videoInfo.isFallback
        ? '已加入播放庫（部分資訊無法取得）'
        : '已加入播放庫';
      showSuccess(message);
    } else if (result.error === 'VIDEO_ALREADY_EXISTS') {
      showInfo(result.message);
    } else {
      showError('加入播放庫失敗');
    }

  } catch (error) {
    console.error('Failed to add to library:', error);

    // 處理不同類型的錯誤
    if (error.message.includes('not authenticated') || error.message.includes('401')) {
      showError('請先登入');
    } else if (error.message.includes('network') || error.message.includes('fetch')) {
      showError('網路連線失敗，請稍後再試');
    } else {
      showError('加入播放庫失敗');
    }

  } finally {
    addToLibraryBtn.disabled = false;
    addToLibraryBtn.textContent = '加入播放庫';
  }
}

/**
 * 處理加入播放清單
 */
async function handleAddToPlaylist() {
  if (!currentVideoId) {
    showError('請在 YouTube 影片頁面使用此功能');
    return;
  }

  try {
    addToPlaylistBtn.disabled = true;
    addToPlaylistBtn.textContent = '加入中...';

    // 1. 讀取使用者設定
    const userSettings = await getUserSettings();

    // 2. 根據模式決定執行預設或自訂模式
    if (userSettings.playlistMode === 'default' && userSettings.defaultPlaylistId) {
      // 預設模式：直接加入到預設播放清單
      await addToDefaultPlaylist(userSettings.defaultPlaylistId);
    } else {
      // 自訂模式：顯示播放清單選擇器
      await showPlaylistSelector();
    }

  } catch (error) {
    console.error('Failed to add to playlist:', error);

    // 處理不同類型的錯誤
    if (error.message.includes('not authenticated') || error.message.includes('401')) {
      showError('請先登入');
    } else if (error.message.includes('network') || error.message.includes('fetch')) {
      showError('網路連線失敗，請稍後再試');
    } else {
      showError('加入播放清單失敗');
    }

  } finally {
    addToPlaylistBtn.disabled = false;
    addToPlaylistBtn.textContent = '加入播放清單';
  }
}

/**
 * 取得使用者設定
 * @returns {Promise<Object>} 使用者設定
 */
async function getUserSettings() {
  const result = await browser.storage.local.get('user_settings');
  const settings = result.user_settings || {};

  return {
    playlistMode: settings.playlistMode || 'custom', // 'default' 或 'custom'
    defaultPlaylistId: settings.defaultPlaylistId || null
  };
}

/**
 * 將影片加入預設播放清單
 * @param {string} playlistId - 播放清單 ID
 */
async function addToDefaultPlaylist(playlistId) {
  // 1. 從 YouTube API 取得影片資訊
  let videoInfo;
  try {
    showInfo('正在取得影片資訊...');
    videoInfo = await getVideoInfo(currentVideoId);
  } catch (error) {
    console.error('Failed to get video info:', error);
    showError('無法取得影片資訊');
    return;
  }

  // 2. 準備影片資料
  const videoData = {
    youtubeVideoId: currentVideoId,
    title: videoInfo.title,
    thumbnailUrl: videoInfo.thumbnailUrl,
    duration: videoInfo.duration,
    channelTitle: videoInfo.channelTitle
  };

  // 3. 呼叫後端 API 加入播放清單
  const result = await addVideoToPlaylist(playlistId, videoData);

  // 4. 顯示結果
  if (result.success) {
    // 取得播放清單名稱用於顯示訊息
    const playlistName = await getPlaylistName(playlistId);
    const message = videoInfo.isFallback
      ? `已加入 ${playlistName || '播放清單'}（部分資訊無法取得）`
      : `已加入 ${playlistName || '播放清單'}`;
    showSuccess(message);
  } else if (result.error === 'VIDEO_ALREADY_IN_PLAYLIST') {
    showInfo(result.message);
  } else {
    showError('加入播放清單失敗');
  }
}

/**
 * 取得播放清單名稱
 * @param {string} playlistId - 播放清單 ID
 * @returns {Promise<string|null>} 播放清單名稱
 */
async function getPlaylistName(playlistId) {
  try {
    // 嘗試從快取的播放清單列表取得名稱
    const cachedPlaylists = await getCache('cache_playlists');
    if (cachedPlaylists && Array.isArray(cachedPlaylists)) {
      const playlist = cachedPlaylists.find(p => p.id === playlistId);
      if (playlist) {
        return playlist.name;
      }
    }
  } catch (error) {
    console.error('Failed to get playlist name:', error);
  }
  return null;
}

/**
 * 顯示播放清單選擇器（自訂模式）
 */
async function showPlaylistSelector() {
  try {
    // 顯示 modal 和載入狀態
    playlistModal.classList.remove('hidden');
    playlistList.innerHTML = '';
    playlistEmpty.classList.add('hidden');
    playlistLoading.classList.remove('hidden');
    playlistError.classList.add('hidden');

    // 嘗試從快取取得播放清單
    let playlists = await getCache('cache_playlists');

    // 如果快取不存在，從 API 取得
    if (!playlists) {
      const response = await getPlaylists({ limit: 50 });
      playlists = response.playlists || [];

      // 快取結果
      await setCache('cache_playlists', playlists);
    }

    // 隱藏載入狀態
    playlistLoading.classList.add('hidden');

    // 渲染播放清單
    if (playlists && playlists.length > 0) {
      renderPlaylistItems(playlists);
      playlistEmpty.classList.add('hidden');
    } else {
      playlistEmpty.classList.remove('hidden');
    }

  } catch (error) {
    console.error('Failed to load playlists:', error);

    // 顯示錯誤訊息
    playlistLoading.classList.add('hidden');
    playlistEmpty.classList.add('hidden');
    playlistError.classList.remove('hidden');
    playlistError.querySelector('p').textContent = '無法載入播放清單列表';
  }
}

/**
 * 渲染播放清單項目
 * @param {Array} playlists - 播放清單陣列
 */
function renderPlaylistItems(playlists) {
  playlistList.innerHTML = '';

  playlists.forEach(playlist => {
    const item = document.createElement('div');
    item.className = 'playlist-item';
    item.innerHTML = `
      <div class="playlist-name">${playlist.name}</div>
      <div class="playlist-count">${playlist.videoCount || 0} 個影片</div>
    `;

    // 添加點擊事件處理器
    item.addEventListener('click', () => {
      handlePlaylistSelection(playlist);
    });

    playlistList.appendChild(item);
  });
}

/**
 * 處理播放清單選擇
 * @param {Object} playlist - 選定的播放清單
 */
async function handlePlaylistSelection(playlist) {
  try {
    // 禁用 modal 交互
    playlistModal.classList.add('loading');

    // 取得影片資訊
    let videoInfo;
    try {
      showInfo('正在取得影片資訊...');
      videoInfo = await getVideoInfo(currentVideoId);
    } catch (error) {
      console.error('Failed to get video info:', error);
      showError('無法取得影片資訊');
      playlistModal.classList.remove('loading');
      return;
    }

    // 準備影片資料
    const videoData = {
      youtubeVideoId: currentVideoId,
      title: videoInfo.title,
      thumbnailUrl: videoInfo.thumbnailUrl,
      duration: videoInfo.duration,
      channelTitle: videoInfo.channelTitle
    };

    // 呼叫後端 API 加入播放清單
    const result = await addVideoToPlaylist(playlist.id, videoData);

    // 關閉 modal
    closePlaylistModal();

    // 顯示結果
    if (result.success) {
      const message = videoInfo.isFallback
        ? `已加入 ${playlist.name}（部分資訊無法取得）`
        : `已加入 ${playlist.name}`;
      showSuccess(message);
    } else if (result.error === 'VIDEO_ALREADY_IN_PLAYLIST') {
      showInfo(result.message);
    } else {
      showError('加入播放清單失敗');
    }

  } catch (error) {
    console.error('Failed to add video to playlist:', error);

    // 處理不同類型的錯誤
    if (error.message.includes('not authenticated') || error.message.includes('401')) {
      showError('請先登入');
    } else if (error.message.includes('network') || error.message.includes('fetch')) {
      showError('網路連線失敗，請稍後再試');
    } else {
      showError('加入播放清單失敗');
    }

    playlistModal.classList.remove('loading');
  }
}

/**
 * 關閉播放清單選擇器 Modal
 */
function closePlaylistModal() {
  playlistModal.classList.add('hidden');
  playlistList.innerHTML = '';
  playlistEmpty.classList.add('hidden');
  playlistLoading.classList.add('hidden');
  playlistError.classList.add('hidden');
}

/**
 * 顯示成功訊息
 * @param {string} message - 訊息內容
 */
function showSuccess(message) {
  showNotification(message, 'success');
}

/**
 * 顯示錯誤訊息
 * @param {string} message - 訊息內容
 */
function showError(message) {
  showNotification(message, 'error');
}

/**
 * 顯示資訊訊息
 * @param {string} message - 訊息內容
 */
function showInfo(message) {
  showNotification(message, 'info');
}

/**
 * 顯示通知訊息
 * @param {string} message - 訊息內容
 * @param {string} type - 訊息類型（success, error, info）
 */
function showNotification(message, type = 'info') {
  // 移除現有通知
  const existingNotification = document.querySelector('.notification');
  if (existingNotification) {
    existingNotification.remove();
  }

  // 創建新通知
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  // 加入到頁面
  document.body.appendChild(notification);

  // 3 秒後自動移除
  setTimeout(() => {
    notification.classList.add('fade-out');
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// 當 DOM 載入完成時初始化
document.addEventListener('DOMContentLoaded', init);
