<template>
  <div class="line-login-logs">
    <div class="header">
      <h1>LINE 登入記錄</h1>
      <div class="filters">
        <select v-model="filterType" @change="fetchLogs" class="filter-select">
          <option value="all">所有記錄</option>
          <option value="errors">僅錯誤記錄</option>
        </select>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="搜尋 Session ID 或 LINE User ID..."
          class="search-input"
          @input="debouncedSearch"
        />
        <button @click="fetchLogs" class="refresh-btn" :disabled="loading">
          {{ loading ? '載入中...' : '重新整理' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-if="loading && logs.length === 0" class="loading">
      載入中...
    </div>

    <div v-else-if="logs.length === 0 && !loading" class="no-data">
      目前沒有登入記錄
    </div>

    <div v-else class="logs-container">
      <div class="stats">
        <span>總共 {{ logs.length }} 筆記錄</span>
        <span v-if="filterType === 'errors'" class="error-count">
          錯誤記錄: {{ logs.filter(log => log.status === 'error').length }} 筆
        </span>
      </div>

      <div class="logs-table-wrapper">
        <table class="logs-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Session ID</th>
              <th>步驟</th>
              <th>狀態</th>
              <th>LINE User ID</th>
              <th>IP 地址</th>
              <th>時間</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in filteredLogs"
              :key="log.id"
              :class="{
                'error-row': log.status === 'error',
                'warning-row': log.status === 'warning',
                'success-row': log.status === 'success'
              }"
            >
              <td>{{ log.id }}</td>
              <td class="session-id">
                <span class="truncate" :title="log.session_id">
                  {{ log.session_id.substring(0, 20) }}...
                </span>
              </td>
              <td>{{ log.step }}</td>
              <td>
                <span :class="'status-badge status-' + log.status">
                  {{ getStatusText(log.status) }}
                </span>
              </td>
              <td>{{ log.line_user_id || '-' }}</td>
              <td>{{ log.ip_address || '-' }}</td>
              <td class="timestamp">{{ formatDate(log.created_at) }}</td>
              <td>
                <button @click="viewDetails(log)" class="view-btn">查看詳情</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 詳情彈窗 -->
    <div v-if="selectedLog" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Log 詳情 #{{ selectedLog.id }}</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-section">
            <h3>基本資訊</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Session ID:</label>
                <div class="detail-value">{{ selectedLog.session_id }}</div>
              </div>
              <div class="detail-item">
                <label>步驟:</label>
                <div class="detail-value">{{ selectedLog.step }}</div>
              </div>
              <div class="detail-item">
                <label>狀態:</label>
                <div class="detail-value">
                  <span :class="'status-badge status-' + selectedLog.status">
                    {{ getStatusText(selectedLog.status) }}
                  </span>
                </div>
              </div>
              <div class="detail-item">
                <label>LINE User ID:</label>
                <div class="detail-value">{{ selectedLog.line_user_id || '-' }}</div>
              </div>
              <div class="detail-item">
                <label>IP 地址:</label>
                <div class="detail-value">{{ selectedLog.ip_address || '-' }}</div>
              </div>
              <div class="detail-item">
                <label>時間:</label>
                <div class="detail-value">{{ formatDate(selectedLog.created_at) }}</div>
              </div>
            </div>
          </div>

          <div v-if="selectedLog.user_agent" class="detail-section">
            <h3>User Agent</h3>
            <pre class="code-block">{{ selectedLog.user_agent }}</pre>
          </div>

          <div v-if="selectedLog.request_data" class="detail-section">
            <h3>Request Data</h3>
            <pre class="code-block">{{ formatJSON(selectedLog.request_data) }}</pre>
          </div>

          <div v-if="selectedLog.response_data" class="detail-section">
            <h3>Response Data</h3>
            <pre class="code-block">{{ formatJSON(selectedLog.response_data) }}</pre>
          </div>

          <div v-if="selectedLog.error_message" class="detail-section error-section">
            <h3>錯誤訊息</h3>
            <pre class="code-block error-block">{{ selectedLog.error_message }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api'

const logs = ref([])
const loading = ref(false)
const error = ref(null)
const filterType = ref('all')
const searchQuery = ref('')
const selectedLog = ref(null)

let searchTimeout = null

const filteredLogs = computed(() => {
  if (!searchQuery.value) return logs.value

  const query = searchQuery.value.toLowerCase()
  return logs.value.filter(log => {
    return (
      log.session_id.toLowerCase().includes(query) ||
      (log.line_user_id && log.line_user_id.toLowerCase().includes(query))
    )
  })
})

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // 搜尋邏輯已在 computed 中處理
  }, 300)
}

const fetchLogs = async () => {
  loading.value = true
  error.value = null

  try {
    const endpoint = filterType.value === 'errors'
      ? `${API_BASE_URL}/line-logs/errors`
      : `${API_BASE_URL}/line-logs`

    const response = await axios.get(endpoint, {
      params: {
        limit: 200
      }
    })

    if (response.data.success) {
      logs.value = response.data.data || []
    } else {
      error.value = '無法載入登入記錄'
    }
  } catch (err) {
    console.error('Error fetching logs:', err)
    error.value = err.response?.data?.message || '載入失敗，請稍後再試'
  } finally {
    loading.value = false
  }
}

const viewDetails = (log) => {
  selectedLog.value = log
}

const closeModal = () => {
  selectedLog.value = null
}

const getStatusText = (status) => {
  const statusMap = {
    success: '成功',
    error: '錯誤',
    warning: '警告'
  }
  return statusMap[status] || status
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const formatJSON = (data) => {
  if (!data) return '-'
  if (typeof data === 'string') {
    try {
      return JSON.stringify(JSON.parse(data), null, 2)
    } catch {
      return data
    }
  }
  return JSON.stringify(data, null, 2)
}

onMounted(() => {
  fetchLogs()
})
</script>

<style scoped>
.line-login-logs {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  margin-bottom: 30px;
}

.header h1 {
  margin-bottom: 20px;
  color: #333;
}

.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-select,
.search-input {
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 14px;
}

.filter-select {
  min-width: 150px;
}

.search-input {
  flex: 1;
  min-width: 200px;
}

.refresh-btn {
  padding: 10px 20px;
  background-color: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s;
}

.refresh-btn:hover:not(:disabled) {
  background-color: #5568d3;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  padding: 15px;
  background-color: #fee;
  border: 1px solid #fcc;
  border-radius: 5px;
  color: #c33;
  margin-bottom: 20px;
}

.loading,
.no-data {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 16px;
}

.stats {
  display: flex;
  gap: 20px;
  margin-bottom: 15px;
  padding: 10px;
  background-color: #f5f5f5;
  border-radius: 5px;
  font-size: 14px;
}

.error-count {
  color: #c33;
  font-weight: bold;
}

.logs-table-wrapper {
  overflow-x: auto;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.logs-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

.logs-table thead {
  background-color: #f8f9fa;
}

.logs-table th,
.logs-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.logs-table th {
  font-weight: 600;
  color: #555;
  font-size: 14px;
}

.logs-table td {
  font-size: 13px;
}

.logs-table tbody tr:hover {
  background-color: #f9f9f9;
}

.error-row {
  background-color: #fff5f5;
}

.warning-row {
  background-color: #fffbf0;
}

.success-row {
  background-color: #f0fff4;
}

.session-id {
  font-family: monospace;
  font-size: 12px;
}

.truncate {
  display: inline-block;
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.timestamp {
  white-space: nowrap;
  font-size: 12px;
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-success {
  background-color: #d4edda;
  color: #155724;
}

.status-error {
  background-color: #f8d7da;
  color: #721c24;
}

.status-warning {
  background-color: #fff3cd;
  color: #856404;
}

.view-btn {
  padding: 6px 12px;
  background-color: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  transition: background-color 0.3s;
}

.view-btn:hover {
  background-color: #5568d3;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #999;
  line-height: 1;
  padding: 0;
  width: 30px;
  height: 30px;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 20px;
}

.detail-section {
  margin-bottom: 25px;
}

.detail-section h3 {
  margin-bottom: 15px;
  font-size: 16px;
  color: #555;
  border-bottom: 2px solid #667eea;
  padding-bottom: 8px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 15px;
}

.detail-item label {
  display: block;
  font-weight: 600;
  color: #666;
  margin-bottom: 5px;
  font-size: 13px;
}

.detail-value {
  padding: 8px;
  background-color: #f8f9fa;
  border-radius: 4px;
  word-break: break-all;
  font-size: 14px;
}

.code-block {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 15px;
  overflow-x: auto;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.error-section h3 {
  border-bottom-color: #c33;
  color: #c33;
}

.error-block {
  background-color: #fff5f5;
  border-color: #fcc;
  color: #c33;
}

@media (max-width: 768px) {
  .filters {
    flex-direction: column;
  }

  .filter-select,
  .search-input,
  .refresh-btn {
    width: 100%;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
