<template>
  <div class="playlist-manager">
    <div class="header">
      <h1>
        <QueueListIcon class="header-icon" />
        播放清單管理
      </h1>
      <div class="header-actions">
        <div class="export-import-buttons">
          <button
            @click="handleExport"
            class="btn btn-success"
            aria-label="匯出播放清單"
          >
            <ArrowUpTrayIcon class="icon" />
            <span>匯出</span>
          </button>
          <button
            @click="triggerImport"
            class="btn btn-info"
            aria-label="匯入播放清單"
          >
            <ArrowDownTrayIcon class="icon" />
            <span>匯入</span>
          </button>
          <input
            ref="fileInput"
            type="file"
            accept=".json"
            @change="handleImport"
            style="display: none"
          />
        </div>
        <button
          @click="showCreateModal = true"
          class="btn btn-primary"
          v-tooltip="'建立新的播放清單'"
          aria-label="新建播放清單"
        >
          <PlusIcon class="icon" />
          新建播放清單
        </button>
      </div>
    </div>

    <LoadingSpinner v-if="loading" size="large" message="載入播放清單中..." />

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
          <button
            @click="handleViewItems(playlist)"
            class="btn btn-secondary"
            v-tooltip="'查看播放清單內容'"
            aria-label="查看播放清單項目"
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
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal" class="modal-overlay" @click="showCreateModal = false">
          <div class="modal" @click.stop role="dialog" aria-labelledby="modal-title">
            <div class="modal-header">
              <h2 id="modal-title">{{ editingPlaylist ? '編輯播放清單' : '新建播放清單' }}</h2>
              <button
                @click="showCreateModal = false"
                class="btn-close-icon"
                v-tooltip="'關閉'"
                aria-label="關閉"
              >
                <XMarkIcon class="icon" />
              </button>
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
                <button type="submit" class="btn btn-primary">儲存</button>
                <button @click="showCreateModal = false" type="button" class="btn btn-secondary">
                  取消
                </button>
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
              <button @click="cancelDelete" class="btn btn-secondary">取消</button>
              <button @click="confirmDelete" class="btn btn-danger">刪除</button>
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
              <button @click="cancelImport" class="btn btn-secondary">取消</button>
              <button @click="confirmImport" class="btn btn-primary">確認匯入</button>
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
import { useToast } from '@/composables/useToast'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import {
  QueueListIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  FilmIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const playlistStore = usePlaylistStore()
const videoStore = useVideoStore()
const toast = useToast()

const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const showConfirmModal = ref(false)
const editingPlaylist = ref(null)
const deletingPlaylist = ref(null)
const pendingImportFile = ref(null)
const formData = ref({ name: '', description: '', is_active: true })
const fileInput = ref(null)

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
  // Navigate to playlist detail page using Vue Router (SPA navigation)
  router.push(`/playlists/${playlist.id}`)
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
  try {
    const result = await playlistStore.exportPlaylists()
    toast.success(`成功匯出 ${result.count} 個播放清單`)
  } catch (err) {
    toast.error('匯出失敗: ' + err.message)
  }
}

const triggerImport = () => {
  fileInput.value.click()
}

const handleImport = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  pendingImportFile.value = file
  showConfirmModal.value = true
  event.target.value = ''
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

onMounted(() => {
  fetchPlaylists()
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

.export-import-buttons {
  display: flex;
  gap: var(--space-2);
}

/* 使用全域統一的 .btn 和 .btn-primary 樣式 */

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
  border-radius: 4px;
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
