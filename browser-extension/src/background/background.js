// Background Service Worker for YouTube Video Manager Extension

// 監聽擴充程式安裝事件
chrome.runtime.onInstalled.addListener((details) => {
  console.log('Extension installed:', details.reason);

  if (details.reason === 'install') {
    // 首次安裝時的初始化邏輯
    console.log('First time installation');
  } else if (details.reason === 'update') {
    // 更新時的邏輯
    console.log('Extension updated');
  }
});

// 監聽來自 popup 或其他腳本的訊息
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  console.log('Message received:', request);

  // 處理不同類型的訊息
  if (request.type === 'OAUTH_CALLBACK') {
    // 處理 OAuth callback
    handleOAuthCallback(request.data);
  }

  return true; // 保持訊息通道開啟以支援非同步回應
});

// OAuth callback 處理（將在後續任務中實作）
function handleOAuthCallback(data) {
  console.log('OAuth callback data:', data);
  // TODO: 實作 OAuth callback 邏輯
}
