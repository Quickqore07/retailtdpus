<template>
  <div class="folders-page">
    <div class="page-header">
      <h1>My Folders</h1>
      <p>View and manage your accessible folders</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <Spinner size="lg" text="Loading folders..." />
    </div>

    <div v-else-if="folders.length > 0" class="folders-grid">
      <div v-for="folder in folders" :key="folder.id" class="folder-card">
        <div class="folder-icon">
          <SvgIcon name="folder" />
        </div>
        <div class="folder-info">
          <h3>{{ folder.name }}</h3>
          <p>{{ folder.document_count }} documents</p>
        </div>
        <div class="folder-actions">
          <Button variant="outline-primary" size="sm" icon-left="arrow-right" @click="openFolder(folder.id)">Open</Button>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <SvgIcon name="folder" size="lg" class="empty-icon" />
      <h3>No folders assigned</h3>
      <p>You don't have access to any folders yet. Please contact your administrator.</p>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from '../../plugins/axios'
import SvgIcon from '@/components/SvgIcon.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import { useRouter } from 'vue-router'
export default {
  name: 'Folders',
  components: {
    SvgIcon,
    Button,
    Spinner
  },
  setup() {
    const folders = ref([])
    const loading = ref(false)
    const router = useRouter()
    const loadFolders = async () => {
      loading.value = true
      try {
        const response = await axios.get('/upload-portal/api/folders')
        if (response.data.success) {
          folders.value = response.data.folders || []
        }
      } catch (error) {
        console.error('Error loading folders:', error)
      } finally {
        loading.value = false
      }
    }

    const openFolder = (folderId) => {
      router.push({ name: 'upload-portal-documents', params: { folderId } })
    }

    onMounted(() => {
      loadFolders()
    })

    return {
      folders,
      loading,
      openFolder
    }
  }
}
</script>

<style scoped>
.folders-page {
  max-width: 1200px;
}

.page-header {
  margin-bottom: 32px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.page-header p {
  font-size: 14px;
  color: #6b7280;
}

.folders-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 24px;
}

.folder-card {
  padding: 10px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.2s ease;
}

.folder-card:hover {
  border-color: #667eea;
}

.folder-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.folder-icon svg {
  width: 32px;
  height: 32px;
}

.folder-info {
  flex: 1;
}

.folder-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.folder-info p {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

.folder-actions {
  display: flex;
  gap: 8px;
}

.btn-action {
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-action:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.empty-state {
  text-align: center;
  padding: 80px 24px;
}

.empty-icon {
  width: 80px;
  height: 80px;
  color: #d1d5db;
  margin: 0 auto 24px;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px 0;
}

.empty-state p {
  font-size: 14px;
  color: #6b7280;
  max-width: 400px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
}
</style>
