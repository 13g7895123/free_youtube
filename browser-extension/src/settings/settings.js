import browser from 'webextension-polyfill';
import { getPlaylists } from '../services/api.js';
import { getCache, setCache } from '../utils/cache.js';

/**
 * 設定頁面主程式
 */

// DOM 元素
const modeRadios = document.querySelectorAll('input[name="playlistMode"]');
const defaultPlaylistSection = document.getElementById('default-playlist-section');
const playlistSelect = document.getElementById('default-playlist-select');
const playlistLoading = document.getElementById('playlist-loading');
const playlistError = document.getElementById('playlist-error');
const autoAddLibraryCheckbox = document.getElementById('auto-add-library');
const saveBtn = document.getElementById('save-btn');
const resetBtn = document.getElementById('reset-btn');
const messageDiv = document.getElementById('message');

/**
 * 初始化設定頁面
 */
async function init() {
  try {
    // 1. 載入當前設定
    await loadSettings();

    // 2. 註冊事件監聽器
    registerEventListeners();

    // 3. 載入播放清單列表
    await loadPlaylists();

  } catch (error) {
    console.error('Failed to initialize settings page:', error);
    showMessage('初始化設定頁面失敗', 'error');
  }
}

/**
 * 載入當前設定
 */
async function loadSettings() {
  const result = await browser.storage.local.get('user_settings');
  const settings = result.user_settings || {};

  // 設定播放清單模式
  const mode = settings.playlistMode || 'custom';
  const modeRadio = document.getElementById(`mode-${mode}`);
  if (modeRadio) {
    modeRadio.checked = true;
  }

  // 設定預設播放清單
  if (settings.defaultPlaylistId) {
    playlistSelect.value = settings.defaultPlaylistId;
  }

  // 設定自動加入播放庫
  if (settings.autoAddToLibrary) {
    autoAddLibraryCheckbox.checked = true;
  }

  // 更新 UI
  updatePlaylistSectionVisibility();
}

/**
 * 註冊事件監聽器
 */
function registerEventListeners() {
  // 播放清單模式變更
  modeRadios.forEach(radio => {
    radio.addEventListener('change', updatePlaylistSectionVisibility);
  });

  // 保存按鈕
  saveBtn.addEventListener('click', handleSave);

  // 重置按鈕
  resetBtn.addEventListener('click', handleReset);
}

/**
 * 更新預設播放清單區塊的可見性
 */
function updatePlaylistSectionVisibility() {
  const selectedMode = document.querySelector('input[name="playlistMode"]:checked').value;

  if (selectedMode === 'default') {
    defaultPlaylistSection.style.display = 'block';
  } else {
    defaultPlaylistSection.style.display = 'none';
  }
}

/**
 * 載入播放清單列表
 */
async function loadPlaylists() {
  try {
    // 顯示載入狀態
    playlistLoading.style.display = 'block';
    playlistError.style.display = 'none';

    // 嘗試從快取取得
    let playlists = await getCache('cache_playlists');

    // 如果快取不存在，從 API 取得
    if (!playlists) {
      const response = await getPlaylists({ limit: 50 });
      playlists = response.playlists || [];

      // 快取結果
      await setCache('cache_playlists', playlists);
    }

    // 填充選擇框
    populatePlaylistSelect(playlists);

    // 隱藏載入狀態
    playlistLoading.style.display = 'none';

  } catch (error) {
    console.error('Failed to load playlists:', error);

    // 顯示錯誤訊息
    playlistLoading.style.display = 'none';
    playlistError.style.display = 'block';
    playlistError.querySelector('p').textContent = '無法載入播放清單列表';
  }
}

/**
 * 填充播放清單選擇框
 * @param {Array} playlists - 播放清單陣列
 */
function populatePlaylistSelect(playlists) {
  // 移除現有的選項（除了第一個預設選項）
  while (playlistSelect.options.length > 1) {
    playlistSelect.remove(1);
  }

  // 添加播放清單選項
  if (playlists && playlists.length > 0) {
    playlists.forEach(playlist => {
      const option = document.createElement('option');
      option.value = playlist.id;
      option.textContent = `${playlist.name} (${playlist.videoCount || 0} 個影片)`;
      playlistSelect.appendChild(option);
    });
  } else {
    // 如果沒有播放清單，顯示提示
    const option = document.createElement('option');
    option.disabled = true;
    option.textContent = '-- 無可用的播放清單 --';
    playlistSelect.appendChild(option);
  }
}

/**
 * 處理儲存設定
 */
async function handleSave() {
  try {
    // 驗證設定
    const selectedMode = document.querySelector('input[name="playlistMode"]:checked').value;

    // 預設模式下必須選擇播放清單
    if (selectedMode === 'default' && !playlistSelect.value) {
      showMessage('請選擇預設播放清單', 'error');
      return;
    }

    // 禁用保存按鈕
    saveBtn.disabled = true;
    saveBtn.textContent = '儲存中...';

    // 構建設定物件
    const settings = {
      playlistMode: selectedMode,
      defaultPlaylistId: selectedMode === 'default' ? playlistSelect.value : null,
      autoAddToLibrary: autoAddLibraryCheckbox.checked,
      updatedAt: new Date().toISOString()
    };

    // 儲存到 storage
    await browser.storage.local.set({ user_settings: settings });

    // 顯示成功訊息
    showMessage('設定已儲存', 'success');

    // 恢復按鈕
    saveBtn.disabled = false;
    saveBtn.textContent = '儲存設定';

  } catch (error) {
    console.error('Failed to save settings:', error);
    showMessage('儲存設定失敗', 'error');
    saveBtn.disabled = false;
    saveBtn.textContent = '儲存設定';
  }
}

/**
 * 處理重置設定
 */
async function handleReset() {
  if (!confirm('確定要重置為預設設定嗎？')) {
    return;
  }

  try {
    // 清除所有設定
    await browser.storage.local.remove('user_settings');

    // 重新載入設定
    await loadSettings();

    // 顯示成功訊息
    showMessage('已重置為預設設定', 'success');

  } catch (error) {
    console.error('Failed to reset settings:', error);
    showMessage('重置設定失敗', 'error');
  }
}

/**
 * 顯示訊息
 * @param {string} message - 訊息內容
 * @param {string} type - 訊息類型 (success, error, info)
 */
function showMessage(message, type = 'info') {
  messageDiv.textContent = message;
  messageDiv.className = `message ${type}`;

  // 3 秒後自動隱藏
  setTimeout(() => {
    messageDiv.classList.add('hidden');
  }, 3000);
}

// 當 DOM 載入完成時初始化
document.addEventListener('DOMContentLoaded', init);
