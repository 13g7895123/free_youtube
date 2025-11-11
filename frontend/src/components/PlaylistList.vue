<template>
  <div class="playlist-list">
    <div class="list-header">
      <h2>Playlists</h2>
      <button @click="createNew" class="btn btn-primary">
        + New Playlist
      </button>
    </div>

    <div v-if="playlists.length === 0" class="empty-state">
      <p>No playlists yet. Create one to get started!</p>
    </div>

    <div v-else class="playlists-container">
      <PlaylistCard
        v-for="playlist in playlists"
        :key="playlist.id"
        :playlist="playlist"
        @edit="onEditPlaylist"
        @delete="onDeletePlaylist"
        @view="onViewPlaylist"
      />
    </div>

    <!-- Create/Edit Modal -->
    <CreatePlaylistModal
      v-if="showModal"
      :playlist="editingPlaylist"
      @save="onSavePlaylist"
      @close="showModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePlaylistStore } from '@/stores/playlistStore'
import PlaylistCard from './PlaylistCard.vue'
import CreatePlaylistModal from './modals/CreatePlaylistModal.vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const playlistStore = usePlaylistStore()

const showModal = ref(false)
const editingPlaylist = ref(null)

const playlists = computed(() => playlistStore.playlists)

onMounted(async () => {
  await playlistStore.fetchPlaylists()
})

const createNew = () => {
  editingPlaylist.value = null
  showModal.value = true
}

const onEditPlaylist = (playlist) => {
  editingPlaylist.value = { ...playlist }
  showModal.value = true
}

const onDeletePlaylist = async (id) => {
  try {
    await playlistStore.deletePlaylist(id)
  } catch (error) {
    console.error('Failed to delete playlist:', error)
  }
}

const onViewPlaylist = (playlist) => {
  router.push(`/playlists/${playlist.id}`)
}

const onSavePlaylist = async (playlistData) => {
  try {
    if (editingPlaylist.value?.id) {
      await playlistStore.updatePlaylist(editingPlaylist.value.id, playlistData)
    } else {
      await playlistStore.createPlaylist(playlistData)
    }
    showModal.value = false
  } catch (error) {
    console.error('Failed to save playlist:', error)
  }
}
</script>

<style scoped>
.playlist-list {
  padding: 20px;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.list-header h2 {
  margin: 0;
  font-size: 28px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.btn-primary {
  background: #1976d2;
  color: white;
}

.btn-primary:hover {
  background: #1565c0;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #999;
}

.playlists-container {
  max-width: 600px;
}
</style>
