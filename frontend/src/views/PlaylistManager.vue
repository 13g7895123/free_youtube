<template>
  <div class="playlist-manager">
    <div class="header">
      <h1>
        <QueueListIcon class="header-icon" />
        播放清單管理
      </h1>
      <div class="header-actions">
        <ExportImportButtons
          :can-export="playlists.length > 0"
          @export="handleExport"
          @import="handleImportFile"
        />
        <BaseButton
          variant="primary"
          :icon="PlusIcon"
          aria-label="新建播放清單"
          @click="showCreateModal = true"
        >
          新建播放清單
        </BaseButton>
      </div>
    </div>

    <LoadingSpinner v-if="loading" size="large" message="載入播放清單中..." />

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="fetchPlaylists" class="btn-retry">重新載入</button>
    </div>

    <div v-else-if="allPlaylists.length === 0" class="empty">
      <p>沒有播放清單</p>
    </div>

    <div v-else>
      <div class="playlist-grid">
        <div v-for="playlist in allPlaylists" :key="playlist.id" class="playlist-card">
          <div class="playlist-header">
            <h3>
              {{ playlist.name }}
              <span v-if="playlist.is_system" class="system-badge">系統</span>
            </h3>
            <div class="actions" v-if="!playlist.is_system">
              <button
                @click="handleEdit(playlist)"
                class="btn-icon"
                v-tooltip="'編輯'"
                aria-label="編輯播放清單"
              >
                <PencilIcon class="icon" />
              </button>
              <button
                @click="handleDelete(playlist)"
                class="btn-icon"
                v-tooltip="'刪除'"
                aria-label="刪除播放清單"
              >
                <TrashIcon class="icon" />
              </button>
            </div>
          </div>
          <p class="description">{{ truncateText(playlist.description, 100) }}</p>
          <div class="stats">
            <span class="stat-item">
              <FilmIcon class="stat-icon" />
              {{ playlist.item_count }} 個影片
            </span>
            <span
              :class="playlist.is_active ? 'active' : 'inactive'"
              class="status"
            >
              {{ playlist.is_active ? '啟用' : '停用' }}
            </span>
          </div>
          <div class="card-actions">
            <BaseButton
              variant="primary"
              :icon="PlayIcon"
              aria-label="直接播放"
              :disabled="playlist.item_count === 0"
              @click="handlePlayPlaylist(playlist)"
            >
              播放
            </BaseButton>
            <BaseButton
              variant="secondary"
              aria-label="查看播放清單項目"
              @click="handleViewItems(playlist)"
            >
              查看項目
            </BaseButton>
          </div>
        </div>
      </div>

      <div class="pagination" v-if="totalPages > 1">
        <BaseButton
          variant="secondary"
          :disabled="currentPage === 1"
          @click="fetchPlaylists(currentPage - 1)"
        >
          上一頁
        </BaseButton>
        <span>第 {{ currentPage }} / {{ totalPages }} 頁</span>
        <BaseButton
          variant="secondary"
          :disabled="currentPage === totalPages"
          @click="fetchPlaylists(currentPage + 1)"
        >
          下一頁
        </BaseButton>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal" class="modal-overlay" @click="showCreateModal = false">
          <div class="modal" @click.stop role="dialog" aria-labelledby="modal-title">
            <div class="modal-header">
              <h2 id="modal-title">{{ editingPlaylist ? '編輯播放清單' : '新建播放清單' }}</h2>
              <BaseButton
                variant="ghost"
                icon-only
                :icon="XMarkIcon"
                aria-label="關閉"
                @click="showCreateModal = false"
              />
            </div>
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
                <label class="checkbox-label">
                  <input v-model="formData.is_active" type="checkbox" />
                  啟用
                </label>
              </div>
              <div class="form-actions">
                <BaseButton variant="primary" type="submit">儲存</BaseButton>
                <BaseButton variant="secondary" type="button" @click="showCreateModal = false">
                  取消
                </BaseButton>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Confirm Delete Modal -->
      <Transition name="modal">
        <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
          <div class="modal confirm-modal" @click.stop role="dialog">
            <div class="modal-header">
              <h2>確認刪除</h2>
            </div>
            <div class="modal-body">
              <p>確定要刪除 "{{ deletingPlaylist?.name }}" 嗎？此操作無法復原。</p>
            </div>
            <div class="modal-footer">
              <BaseButton variant="secondary" @click="cancelDelete">取消</BaseButton>
              <BaseButton variant="danger" @click="confirmDelete">刪除</BaseButton>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Confirm Import Modal -->
      <Transition name="modal">
        <div v-if="showConfirmModal" class="modal-overlay" @click="cancelImport">
          <div class="modal confirm-modal" @click.stop role="dialog">
            <div class="modal-header">
              <h2>確認匯入</h2>
            </div>
            <div class="modal-body">
              <p>確定要匯入播放清單資料嗎？這將會建立新的播放清單。</p>
            </div>
            <div class="modal-footer">
              <BaseButton variant="secondary" @click="cancelImport">取消</BaseButton>
              <BaseButton variant="primary" @click="confirmImport">確認匯入</BaseButton>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePlaylistStore } from '@/stores/playlistStore'
import { useVideoStore } from '@/stores/videoStore'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'
import { useToast } from '@/composables/useToast'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ExportImportButtons from '@/components/ExportImportButtons.vue'
import BaseButton from '@/components/BaseButton.vue'
import {
  QueueListIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  FilmIcon,
  XMarkIcon,
  PlayIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const playlistStore = usePlaylistStore()
const videoStore = useVideoStore()
const globalPlayerStore = useGlobalPlayerStore()
const toast = useToast()

const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const showConfirmModal = ref(false)
const editingPlaylist = ref(null)
const deletingPlaylist = ref(null)
const pendingImportFile = ref(null)
const formData = ref({ name: '', description: '', is_active: true })

const playlists = computed(() => playlistStore.playlists)
const loading = computed(() => playlistStore.loading)
const error = computed(() => playlistStore.error)
const currentPage = computed(() => playlistStore.currentPage)
const totalPages = computed(() => playlistStore.totalPages)

// 虛擬的「所有影片」播放清單
const allVideosPlaylist = computed(() => ({
  id: 'all-videos',
  name: '所有影片',
  description: '影片庫中的所有影片',
  item_count: videoStore.allVideos?.length || 0,
  is_active: true,
  is_system: true, // 標記為系統播放清單
  created_at: new Date().toISOString()
}))

// 合併系統播放清單和使用者播放清單
const allPlaylists = computed(() => {
  const hasVideos = videoStore.allVideos && videoStore.allVideos.length > 0
  if (hasVideos) {
    return [allVideosPlaylist.value, ...playlists.value]
  }
  return playlists.value
})

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
  deletingPlaylist.value = playlist
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  if (deletingPlaylist.value) {
    try {
      await playlistStore.deletePlaylist(deletingPlaylist.value.id)
      toast.success('播放清單已刪除')
      showDeleteModal.value = false
      deletingPlaylist.value = null
    } catch (err) {
      toast.error('刪除失敗: ' + err.message)
    }
  }
}

const cancelDelete = () => {
  showDeleteModal.value = false
  deletingPlaylist.value = null
}

const handleViewItems = (playlist) => {
  // 統一導向播放清單詳情頁面
  router.push(`/playlists/${playlist.id}`)
}

const handlePlayPlaylist = async (playlist) => {
  if (playlist.item_count === 0) {
    toast.warning('此播放清單沒有影片')
    return
  }

  try {
    // 特殊處理「所有影片」系統播放清單
    if (playlist.id === 'all-videos') {
      // 從 videoStore 獲取所有影片（不限於第一頁）
      if (!videoStore.allVideos || videoStore.allVideos.length === 0) {
        await videoStore.fetchAllVideos()
      }

      // 將 allVideos 轉換為 playlist items 格式
      const items = (videoStore.allVideos || []).map((video, index) => ({
        id: `all-videos-item-${video.id}`,
        video_id: video.video_id || video.id,
        title: video.title,
        duration: video.duration,
        thumbnail_url: video.thumbnail_url,
        youtube_url: video.youtube_url,
        position: index
      }))

      globalPlayerStore.playPlaylist({
        id: playlist.id,
        name: playlist.name,
        items: items
      }, 0)
    } else {
      // 一般播放清單：獲取完整的播放清單數據和項目
      const playlistData = await playlistStore.getPlaylist(playlist.id)
      if (playlistData && playlistData.items && playlistData.items.length > 0) {
        globalPlayerStore.playPlaylist({
          id: playlistData.id,
          name: playlistData.name,
          items: playlistData.items
        }, 0)
      } else {
        toast.warning('播放清單沒有影片')
      }
    }
  } catch (error) {
    console.error('Failed to play playlist:', error)
    toast.error('播放播放清單失敗: ' + error.message)
  }
}

const savePlaylist = async () => {
  try {
    if (editingPlaylist.value) {
      await playlistStore.updatePlaylist(editingPlaylist.value.id, formData.value)
      toast.success('播放清單已更新')
    } else {
      await playlistStore.createPlaylist(formData.value)
      toast.success('播放清單已建立')
    }
    showCreateModal.value = false
    editingPlaylist.value = null
    formData.value = { name: '', description: '', is_active: true }
  } catch (err) {
    toast.error('操作失敗: ' + err.message)
  }
}

const handleExport = async () => {
  if (playlists.value.length === 0) {
    toast.warning('沒有播放清單可以匯出')
    return
  }

  try {
    const result = await playlistStore.exportPlaylists()
    toast.success(`成功匯出 ${result.count} 個播放清單`)
  } catch (err) {
    toast.error('匯出失敗: ' + err.message)
  }
}

const handleImportFile = (file) => {
  if (!file) return

  pendingImportFile.value = file
  showConfirmModal.value = true
}

const confirmImport = async () => {
  if (!pendingImportFile.value) return

  showConfirmModal.value = false
  try {
    const result = await playlistStore.importPlaylists(pendingImportFile.value, videoStore)
    toast.success(
      `匯入完成！播放清單 - 成功: ${result.successCount}, 失敗: ${result.failCount}；項目 - 成功: ${result.totalItemsImported || 0}, 失敗: ${result.totalItemsFailed || 0}`
    )
  } catch (err) {
    toast.error('匯入失敗: ' + err.message)
  }
  pendingImportFile.value = null
}

const cancelImport = () => {
  showConfirmModal.value = false
  pendingImportFile.value = null
}

onMounted(async () => {
  await fetchPlaylists()
  // 載入所有影片數據以顯示「所有影片」播放清單
  if (!videoStore.allVideos || videoStore.allVideos.length === 0) {
    await videoStore.fetchAllVideos()
  }
})
</script>

<style scoped>
.playlist-manager {
  padding: var(--space-6);
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-6);
}

.header h1 {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin: 0;
  font-size: var(--font-size-3xl);
  color: var(--text-primary);
}

.header-icon {
  width: var(--icon-xl);
  height: var(--icon-xl);
  color: var(--color-brand-primary);
}

.header-actions {
  display: flex;
  gap: var(--space-3);
  align-items: center;
}

/* 使用全域統一的 .btn 和 .btn-primary 樣式 */
/* export-import-buttons 樣式移至共用元件 */

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
  border-radius: var(--radius-md);
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
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.system-badge {
  display: inline-flex;
  align-items: center;
  padding: var(--space-1) var(--space-2);
  background: var(--color-info-alpha);
  color: var(--color-info);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  border-radius: var(--radius-sm);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.actions {
  display: flex;
  gap: var(--space-2);
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.btn-icon:hover {
  background: var(--color-neutral-100);
  color: var(--text-primary);
}

.btn-icon .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.description {
  margin: 0 0 12px 0;
  color: #666;
  font-size: 14px;
}

.stats {
  display: flex;
  gap: var(--space-3);
  margin-bottom: var(--space-3);
  font-size: var(--font-size-sm);
}

.card-actions {
  display: flex;
  gap: var(--space-2);
  margin-top: auto;
}

.card-actions > * {
  flex: 1;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--text-secondary);
}

.stat-icon {
  width: var(--icon-sm);
  height: var(--icon-sm);
}

.status {
  padding: var(--space-1) var(--space-2);
  border-radius: var(--radius-sm);
  font-weight: var(--font-weight-medium);
  font-size: var(--font-size-xs);
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
  border-radius: var(--radius-sm);
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
  border-radius: var(--radius-xl);
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-2xl);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-5);
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
  margin: 0;
  font-size: var(--font-size-xl);
  color: var(--text-primary);
}

.btn-close-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: var(--radius-full);
  cursor: pointer;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.btn-close-icon:hover {
  background: var(--color-neutral-100);
  color: var(--text-primary);
}

.btn-close-icon .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.modal form {
  padding: var(--space-5);
}

.form-group {
  margin-bottom: var(--space-4);
}

.form-group label {
  display: block;
  margin-bottom: var(--space-2);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
}

.form-group input[type="text"],
.form-group textarea {
  width: 100%;
  padding: var(--space-3);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  font-family: inherit;
  font-size: var(--font-size-sm);
  transition: all var(--transition-fast);
}

.form-group input[type="text"]:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--color-info);
  box-shadow: 0 0 0 3px var(--color-info-alpha);
}

.form-actions {
  display: flex;
  gap: var(--space-3);
  margin-top: var(--space-5);
}

.form-actions button {
  flex: 1;
  padding: var(--space-3) var(--space-4);
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-weight: var(--font-weight-medium);
}

.form-actions .btn-primary {
  background: var(--color-brand-primary);
  color: white;
}

.form-actions .btn-primary:hover {
  background: var(--color-brand-primary-dark);
}

.form-actions .btn-secondary {
  background: var(--color-neutral-200);
  color: var(--text-primary);
}

.form-actions .btn-secondary:hover {
  background: var(--color-neutral-300);
}

.confirm-modal .modal-body {
  padding: var(--space-5);
}

.confirm-modal .modal-body p {
  margin: 0;
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  line-height: var(--line-height-relaxed);
}

.modal-footer {
  display: flex;
  gap: var(--space-3);
  padding: var(--space-5);
  border-top: 1px solid var(--border-color);
}

.modal-footer .btn {
  flex: 1;
  padding: var(--space-3) var(--space-4);
  border: none;
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-danger {
  background: var(--color-error);
  color: white;
}

.btn-danger:hover {
  background: var(--color-error-dark);
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
  border-radius: var(--radius-sm);
  cursor: pointer;
}

.btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

/* Modal 動畫 */
.modal-enter-active,
.modal-leave-active {
  transition: opacity var(--transition-base);
}

.modal-enter-active .modal,
.modal-leave-active .modal {
  transition: transform var(--transition-base);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal,
.modal-leave-to .modal {
  transform: scale(0.95) translateY(20px);
}

/* 響應式設計 */
@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-4);
  }

  .header-actions {
    width: 100%;
    justify-content: space-between;
  }

  .playlist-grid {
    grid-template-columns: 1fr;
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .btn-export,
  .btn-import,
  .btn-primary,
  .btn-icon,
  .modal-enter-active,
  .modal-leave-active {
    transition: none;
  }

  .btn-export:hover,
  .btn-import:hover,
  .btn-primary:hover {
    transform: none;
  }
}
</style>
