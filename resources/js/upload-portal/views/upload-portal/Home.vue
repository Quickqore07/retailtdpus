<template>
  <div class="home-page">
    <div v-if="loading" class="loader-container">
      <Spinner size="lg" text="Loading..." />
    </div>

    <div v-else>
      <div class="welcome-section">
        <h3>Welcome to Upload Portal</h3>
        <p>Manage and upload your documents securely</p>
      </div>

      <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon folders">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="stat-content">
          <h3>{{ stats.folder_count }}</h3>
          <p>Available Folders</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon documents">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total_documents }}</h3>
          <p>Total Documents</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon upload">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="stat-content">
          <h3>{{ stats.recent_uploads }}</h3>
          <p>Recent Uploads</p>
        </div>
      </div>
    </div>

    <div class="folders-section">
      <h3>Your Folders</h3>
      <div class="folders-list" v-if="folders.length > 0">
        <div v-for="folder in folders" :key="folder.id" class="folder-card" @click="openFolder(folder.id)">
          <div class="folder-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="folder-info">
            <h3>{{ folder.name }}</h3>
            <p>{{ folder.document_count }} documents</p>
            <p>Click to view documents</p>
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state">
        <p>No folders assigned yet</p>
      </div>
    </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '../../plugins/axios'
import Spinner from '@/components/ui/spinner.vue'

export default {
  name: 'UploadPortalHome',
  components: {
    Spinner
  },
  setup() {
    const router = useRouter()
    const loading = ref(true)
    const stats = ref({
      folder_count: 0,
      total_documents: 0,
      recent_uploads: 0
    })
    const folders = ref([])

    const folderCount = computed(() => stats.value.folder_count)

    const loadDashboard = async () => {
      try {
        loading.value = true
        const [statsResponse, foldersResponse] = await Promise.all([
          axios.get('/upload-portal/api/dashboard/stats'),
          axios.get('/upload-portal/api/folders')
        ])
        
        if (statsResponse.data.success) {
          stats.value = statsResponse.data.stats
        }
        
        if (foldersResponse.data.success) {
          folders.value = foldersResponse.data.folders || []
        }
      } catch (error) {
        console.error('Error loading dashboard:', error)
      } finally {
        loading.value = false
      }
    }

    const openFolder = (folderId) => {
      router.push({ name: 'upload-portal-documents', params: { folderId } })
    }

    onMounted(() => {
      loadDashboard()
    })

    return {
      loading,
      stats,
      folders,
      folderCount,
      openFolder
    }
  }
}
</script>

<style scoped>
.home-page {
  max-width: 1200px;
}

.loader-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.welcome-section {
  margin-bottom: 48px;
}

.welcome-section h1 {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.welcome-section p {
  font-size: 16px;
  color: #6b7280;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
  margin-bottom: 48px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon svg {
  width: 24px;
  height: 24px;
  stroke-width: 2;
}

.stat-icon.folders {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: white;
}

.stat-icon.documents {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.stat-icon.upload {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.stat-content h3 {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.stat-content p {
  font-size: 14px;
  color: #6b7280;
  margin: 4px 0 0 0;
}

.folders-section h2 {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 24px;
}

.folders-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.folder-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 10px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.folder-card:hover {
  border-color: #667eea;
}

.folder-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.folder-icon svg {
  width: 28px;
  height: 28px;
}

.folder-info h3 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.folder-info p {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #9ca3af;
}
</style>
