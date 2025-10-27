<template>
  <div class="playlist-manager">
    <div class="header">
      <h1>📋 播放清單管理</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        新建播放清單
      </button>
    </div>

    <div v-if="loading" class="loading">
      <p>載入中...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="fetchPlaylists" class="btn-retry">重新載入</button>
    </div>

    <div v-else-if="playlists.length === 0" class="empty">
      <p>沒有播放清單</p>
    </div>

    <div v-else>
      <div class="playlist-grid">
        <div v-for="playlist in playlists" :key="playlist.id" class="playlist-card">
          <div class="playlist-header">
            <h3>{{ playlist.name }}</h3>
            <div class="actions">
              <button @click="handleEdit(playlist)" class="btn-icon" title="編輯">
                ✏️
              </button>
              <button @click="handleDelete(playlist)" class="btn-icon" title="刪除">
                🗑️
              </button>
            </div>
          </div>
          <p class="description">{{ truncateText(playlist.description, 100) }}</p>
          <div class="stats">
            <span>📹 {{ playlist.item_count }} 個影片</span>
            <span
              :class="playlist.is_active ? 'active' : 'inactive'"
              class="status"
            >
              {{ playlist.is_active ? '啟用' : '停用' }}
            </span>
          </div>
          <button
            @click="handleViewItems(playlist)"
            class="btn btn-secondary"
          >
            查看項目
          </button>
        </div>
      </div>

      <div class="pagination" v-if="totalPages > 1">
        <button
          @click="currentPage > 1 && fetchPlaylists(currentPage - 1)"
          :disabled="currentPage === 1"
          class="btn"
        >
          上一頁
        </button>
        <span>第 {{ currentPage }} / {{ totalPages }} 頁</span>
        <button
          @click="currentPage < totalPages && fetchPlaylists(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="btn"
        >
          下一頁
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click="showCreateModal = false">
      <div class="modal" @click.stop>
        <h2>{{ editingPlaylist ? '編輯播放清單' : '新建播放清單' }}</h2>
        <form @submit.prevent="savePlaylist">
          <div class="form-group">
            <label>名稱</label>
            <input v-model="formData.name" type="text" required />
          </div>
          <div class="form-group">
            <label>描述</label>
            <textarea v-model="formData.description" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label>
              <input v-model="formData.is_active" type="checkbox" />
              啟用
            </label>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">儲存</button>
            <button @click="showCreateModal = false" type="button" class="btn">
              取消
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePlaylistStore } from '@/stores/playlistStore'

const playlistStore = usePlaylistStore()

const showCreateModal = ref(false)
const editingPlaylist = ref(null)
const formData = ref({ name: '', description: '', is_active: true })

const playlists = computed(() => playlistStore.playlists)
const loading = computed(() => playlistStore.loading)
const error = computed(() => playlistStore.error)
const currentPage = computed(() => playlistStore.currentPage)
const totalPages = computed(() => playlistStore.totalPages)

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const fetchPlaylists = async (page = 1) => {
  await playlistStore.fetchPlaylists(page)
}

const handleEdit = (playlist) => {
  editingPlaylist.value = playlist
  formData.value = { ...playlist }
  showCreateModal.value = true
}

const handleDelete = (playlist) => {
  if (confirm(`確定要刪除 "${playlist.name}" 嗎？`)) {
    playlistStore.deletePlaylist(playlist.id)
  }
}

const handleViewItems = (playlist) => {
  console.log('View items for playlist:', playlist)
  // 可以導航到播放清單詳情頁面
}

const savePlaylist = async () => {
  try {
    if (editingPlaylist.value) {
      await playlistStore.updatePlaylist(editingPlaylist.value.id, formData.value)
    } else {
      await playlistStore.createPlaylist(formData.value)
    }
    showCreateModal.value = false
    editingPlaylist.value = null
    formData.value = { name: '', description: '', is_active: true }
  } catch (err) {
    alert('操作失敗: ' + err.message)
  }
}

onMounted(() => {
  fetchPlaylists()
})
</script>

<style scoped>
.playlist-manager {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h1 {
  margin: 0;
  font-size: 28px;
}

.btn-primary {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.loading,
.error,
.empty {
  text-align: center;
  padding: 48px 24px;
  color: #666;
}

.playlist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.playlist-card {
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
  display: flex;
  flex-direction: column;
}

.playlist-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 12px;
}

.playlist-header h3 {
  margin: 0;
  flex: 1;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 4px 8px;
}

.description {
  margin: 0 0 12px 0;
  color: #666;
  font-size: 14px;
}

.stats {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 12px;
}

.status {
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 500;
}

.status.active {
  background: #d4edda;
  color: #155724;
}

.status.inactive {
  background: #f8d7da;
  color: #721c24;
}

.btn-secondary {
  width: 100%;
  padding: 8px 12px;
  background: #e0e0e0;
  color: #333;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 4px;
  font-weight: 500;
}

.form-group input[type="text"],
.form-group textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.form-actions {
  display: flex;
  gap: 8px;
  margin-top: 24px;
}

.form-actions button {
  flex: 1;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.form-actions .btn-primary {
  background: #667eea;
  color: white;
}

.form-actions .btn {
  background: #e0e0e0;
  color: #333;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.btn {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}
</style>
