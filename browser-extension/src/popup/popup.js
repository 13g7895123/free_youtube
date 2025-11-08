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
let currentVideoId = null;

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

    // 註冊事件處理器
    loginBtn.addEventListener('click', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    settingsBtn.addEventListener('click', handleSettings);
    addToLibraryBtn.addEventListener('click', handleAddToLibrary);
    addToPlaylistBtn.addEventListener('click', handleAddToPlaylist);

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
      url: browser.runtime.getURL('src/popup/settings.html')
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
    const message = videoInfo.isFallback
      ? '已加入播放清單（部分資訊無法取得）'
      : '已加入播放清單';
    showSuccess(message);
  } else if (result.error === 'VIDEO_ALREADY_IN_PLAYLIST') {
    showInfo(result.message);
  } else {
    showError('加入播放清單失敗');
  }
}

/**
 * 顯示播放清單選擇器（自訂模式）
 * 暫時為 TODO，將在 Phase 6 實作
 */
async function showPlaylistSelector() {
  showInfo('自訂播放清單模式將在下一版本推出');
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
