<template>
  <div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-start mb-8">
      <div>
        <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 !mb-1">All Documents</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">Upload and manage all your documents</p>
      </div>
      <Button 
        v-if="can('add')"
        @click="showModal = true"
        variant="primary"
        size="sm"
        icon-left="plus"
        icon-size="md"
      >
        Add Document
      </Button>
    </div>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Folder Filter -->
        <DynamicDropdown
          v-model="filters.folder"
          label="Filter by Folder"
          :custom-options="allFolders"
          display-name="name"
          placeholder="All Folders"
          :required="false"
          @change="applyFilters"
        />

        <!-- Company Filter -->
        <DynamicDropdown
          v-model="filters.company"
          label="Filter by Company"
          :custom-options="allCompanies"
          display-name="name"
          placeholder="All Companies"
          :required="false"
          @change="applyFilters"
        />

        <!-- Name Filter -->
        <Input
          v-model="filters.name"
          label="Filter by Name"
          placeholder="Search by name..."
          @input="debounceFilter"
        />

        <!-- Date From Filter -->
        <Input
          v-model="filters.dateFrom"
          label="Date From"
          type="date"
          @change="applyFilters"
        />

        <!-- Date To Filter -->
        <Input
          v-model="filters.dateTo"
          label="Date To"
          type="date"
          @change="applyFilters"
        />
      </div>

      <!-- Clear Filters Button -->
      <div class="flex justify-end mt-4">
        <Button
          @click="clearFilters"
          variant="outline-secondary"
          size="sm"
        >
          Clear Filters
        </Button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <Spinner size="lg" text="Loading documents..." />
    </div>

    <!-- Documents Table -->
    <div v-else-if="documents.length > 0" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 dark:border-gray-700">
            <tr>
              <Th align="left" custom-class="py-4">
                Document
              </Th>
              <Th align="left" custom-class="py-4">
                Folder
              </Th>
              <Th align="left" custom-class="py-4">
                Company
              </Th>
              <Th align="left" custom-class="py-4">
                Type
              </Th>
              <Th align="left" custom-class="py-4">
                Size
              </Th>
              <Th align="left" custom-class="py-4">
                Date
              </Th>
              <Th align="left" custom-class="py-4">
                Uploaded By
              </Th>
              <Th align="right" custom-class="py-4">
                Actions
              </Th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr 
              v-for="document in documents" 
              :key="document.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
            >
              <Td custom-class="py-4">
                <div class="flex items-center gap-3">
                  <div 
                    class="w-8 h-8 rounded-lg bg-gradient-to-br flex items-center justify-center text-white flex-shrink-0"
                    :class="getColorForMimeType(document.file_type)"
                  >
                    <svg-icon :name="getIconForMimeType(document.file_type)" size="sm" />
                  </div>
                  <div class="min-w-0">
                    <h6 class="truncate !mb-0">{{ document.name }}</h6>
                  </div>
                </div>
              </Td>
              <Td custom-class="py-4">
                <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">
                  {{ document.folder?.name || 'N/A' }}
                </span>
              </Td>
              <Td custom-class="py-4">
                <span class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                  {{ document.company?.name || 'N/A' }}
                </span>
              </Td>
              <Td color="secondary" custom-class="py-4">
                <span class="text-xs font-mono">{{ formatMimeType(document.file_type) }}</span>
              </Td>
              <Td color="secondary" custom-class="py-4">
                {{ formatFileSize(document.file_size) }}
              </Td>
              <Td color="secondary" custom-class="py-4">
                {{ formatDate(document.created_at) }}
              </Td>
              <Td color="secondary" custom-class="py-4">
                {{ document.created_by?.name || 'N/A' }}
              </Td>
              <Td align="right" custom-class="py-4">
                  <div class="flex items-center justify-end gap-2">
                    <Button 
                      v-if="can('edit')"
                      @click="openEditModal(document)"
                      variant="outline-secondary"
                      size="sm"
                      icon-left="edit"
                    >
                    </Button>
                    <Button 
                      @click="downloadDocument(document)"
                      variant="outline-secondary"
                      size="sm"
                      icon-left="download"
                    >
                    </Button>
                  <Button 
                    v-if="can('delete')"
                    @click="deleteDocument(document)"
                    variant="outline-danger"
                    size="sm"
                    icon-left="trash"
                    :disabled="deletingId === document.id"
                    :loading="deletingId === document.id"
                  >
                  </Button>
                  </div>
              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="documents.length > 0" class="mt-6">
      <Pagination
        :collection="pagination"
        :loading="loading"
        @page-change="onPageChange"
      />
    </div>
    <!-- Message -->
    <div v-if="message && message.includes('You do not have permission')" class="text-center py-20">
      <p class="text-sm text-gray-600 dark:text-gray-400">You do not have permission to access this page.</p>
    </div>
    <!-- Empty State -->
    <div v-else class="text-center py-20">
      <svg class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-3">No documents yet</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">Upload your first document to get started</p>
    </div>

    <!-- Upload Modal -->
    <Modal v-if="showModal" :model-value="showModal" @update:model-value="closeModal" title="Add Document">
      <div>

        <!-- Modal Body -->
        <form @submit.prevent="handleSubmit" class="space-y-5">
          <!-- Folder Dropdown -->
          <DynamicDropdown
            v-model="form.folder"
            label="Folder"
            :custom-options="allFolders"
            display-name="name"
            placeholder="Select folder"
            :required="true"
          />

          <!-- Workgroup Dropdown -->
          <DynamicDropdown
            v-model="form.workgroup"
            label="Workgroup"
            :custom-options="allWorkgroups"
            display-name="name"
            placeholder="Select workgroup"
            :required="true"
            @change="onWorkgroupChange"
          />

          <!-- Company Dropdown -->
          <DynamicDropdown
            v-model="form.company"
            label="Company"
            :custom-options="companies"
            display-name="name"
            placeholder="Select company"
            :required="true"
            :disabled="!form.workgroup || loadingCompanies"
          />

          <!-- Document Name -->
          <Input
            v-model="form.name"
            label="Document Name"
            placeholder="Enter document name"
            :required="true"
          />

          <!-- File Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
              File <span class="text-red-500">*</span>
            </label>
            <input 
              type="file" 
              @change="onFileChange" 
              required
              class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300"
            />
            <p v-if="form.file" class="text-xs text-gray-600 dark:text-gray-400 !mt-2">
              Selected: {{ form.file.name }}
            </p>
          </div>
        </form>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3  py-4 border-t border-gray-200 dark:border-gray-700 mt-3">
          <Button
            type="button" 
            @click="closeModal"
            variant="outline-secondary"
            size="md"
          >
            Cancel
          </Button>
          <Button
            type="submit" 
            @click="handleSubmit"
            :disabled="uploading"
            variant="primary"
            size="md"
          >
            {{ uploading ? 'Uploading...' : 'Upload Document' }}
          </Button>
        </div>
      </div>
    </Modal>

    <!-- Edit Modal -->
    <Modal v-if="showEditModal" :model-value="showEditModal" @update:model-value="closeEditModal" title="Edit Document">
      <div>

        <!-- Modal Body -->
        <form @submit.prevent="handleEdit" class="space-y-5">
          <!-- Document Name -->
          <Input
            v-model="editForm.name"
            label="Document Name"
            placeholder="Enter document name"
            :required="true"
          />

          <!-- Company Dropdown -->
          <DynamicDropdown
            v-model="editForm.company"
            label="Company"
            :custom-options="allCompanies"
            display-name="name"
            placeholder="Select company"
            :required="true"
          />

          <!-- Info Note -->
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
            <p class="text-xs text-blue-700 dark:text-blue-300 !mb-0">
              Note: You can only edit the document name and company. The uploaded file cannot be changed.
            </p>
          </div>
        </form>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3  py-4 border-t border-gray-200 dark:border-gray-700 mt-3">
          <Button
            type="button" 
            @click="closeEditModal"
            variant="outline-secondary"
            size="md"
          >
            Cancel
          </Button>
          <Button
            type="submit" 
            @click="handleEdit"
            :disabled="updating"
            variant="primary"
            size="md"
          >
            {{ updating ? 'Updating...' : 'Update Document' }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'
import axios from '../../plugins/axios'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Button from '@/components/ui/button.vue'
import Modal from '@/components/common/Modal.vue'
import Pagination from '@/components/ui/pagination.vue'
import Spinner from '@/components/ui/spinner.vue'
import { getIconForMimeType, getColorForMimeType } from '@/utils/mimeTypeIcons.js'
import { assignValidatedFile } from '@/utils/documentUpload'
import { useUser } from '../../composables/useUser'

export default {
  name: 'AllDocuments',
  components: {
    Input,
    DynamicDropdown,
    SvgIcon,
    Th,
    Td,
    Button,
    Modal,
    Pagination,
    Spinner
  },
  setup() {
    const { can } = useUser()
    
    const documents = ref([])
    const loading = ref(false)
    const showModal = ref(false)
    const showEditModal = ref(false)
    const uploading = ref(false)
    const updating = ref(false)
    const deletingId = ref(null)
    const message = ref('')
    const allWorkgroups = ref([])
    const companies = ref([])
    const allCompanies = ref([])
    const allFolders = ref([])
    const loadingCompanies = ref(false)

    const filters = ref({
      folder: null,
      company: null,
      name: '',
      dateFrom: '',
      dateTo: ''
    })

    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 25,
      total: 0,
      from: 0,
      to: 0,
      has_prev: false,
      has_next: false
    })

    let filterDebounce = null

    const form = ref({
      folder: null,
      workgroup: null,
      company: null,
      name: '',
      file: null
    })

    const editForm = ref({
      id: null,
      name: '',
      company: null
    })

    const loadDocuments = async (page = 1, perPage = 25) => {
      loading.value = true
      try {
        const params = {
          page,
          per_page: perPage
        }

        if (filters.value.folder?.id) {
          params.folder_id = filters.value.folder.id
        }

        if (filters.value.company?.id) {
          params.company_id = filters.value.company.id
          params.company_type = filters.value.company.type
        }

        if (filters.value.name) {
          params.name = filters.value.name
        }

        if (filters.value.dateFrom) {
          params.date_from = filters.value.dateFrom
        }

        if (filters.value.dateTo) {
          params.date_to = filters.value.dateTo
        }

        const response = await axios.get('/upload-portal/api/documents', { params })
        if (response.data.success) {
          documents.value = response.data.documents || []
          
          if (response.data.pagination) {
            pagination.value = response.data.pagination
          }
        }
      } catch (error) {
        console.error('Error loading documents:', error)
        message.value = error.response?.data?.message || 'Error loading documents. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const loadFolders = async () => {
      try {
        const response = await axios.get('/upload-portal/api/folders')
        if (response.data.success) {
          allFolders.value = response.data.folders || []
        }
      } catch (error) {
        console.error('Error loading folders:', error)
      }
    }

    const loadWorkgroups = async () => {
      try {
        const response = await axios.get('/api/search/workgroups', {
          params: { query: '', column: 'name', all: true }
        })

        allWorkgroups.value = response.data?.collection || []
      } catch (error) {
        console.error('Error loading workgroups:', error)
      }
    }

    const loadAllCompanies = async () => {
      try {
        const response = await axios.get('/upload-portal/api/all-companies')
        
        allCompanies.value = response.data?.companies || []
      } catch (error) {
        console.error('Error loading companies:', error)
      }
    }

    const loadCompaniesByWorkgroup = async (workgroup) => {
      if (!workgroup || !workgroup.id) {
        companies.value = []
        return
      }

      loadingCompanies.value = true
      try {
        const resource = 'companies'
        const response = await axios.get(`/api/search/${resource}`, {
          params: { 
            query: '', 
            column: 'name',
            workgroup_id: workgroup.id 
          }
        })

        if (response.data) {
          companies.value = (response.data.collection || response.data || []).map(company => ({
            ...company,
            type: workgroup.type
          }))
        }
      } catch (error) {
        console.error('Error loading companies:', error)
        companies.value = []
      } finally {
        loadingCompanies.value = false
      }
    }

    const onWorkgroupChange = () => {
      form.value.company = null
      companies.value = []
      if (form.value.workgroup) {
        loadCompaniesByWorkgroup(form.value.workgroup)
      }
    }

    const onFileChange = (event) => {
      const input = event.target
      assignValidatedFile(input?.files?.[0] || null, (file) => {
        form.value.file = file
      }, {
        onError: (error) => alert(error),
        input,
      })
    }

    const handleSubmit = async () => {
      if (!form.value.folder || !form.value.workgroup || !form.value.company || !form.value.name || !form.value.file) {
        alert('Please fill in all required fields')
        return
      }

      uploading.value = true
      try {
        const formData = new FormData()
        formData.append('folder_id', form.value.folder.id)
        formData.append('name', form.value.name)
        formData.append('file', form.value.file)

        formData.append('company_id', form.value.company.id)

        const response = await axios.post('/upload-portal/api/documents', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data.success) {
          closeModal()
          loadDocuments()
        }
      } catch (error) {
        console.error('Error uploading document:', error)
        alert('Error uploading document. Please try again.')
      } finally {
        uploading.value = false
      }
    }

    const closeModal = () => {
      showModal.value = false
      form.value = {
        folder: null,
        workgroup: null,
        company: null,
        name: '',
        file: null
      }
      companies.value = []
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      })
    }

    const formatFileSize = (bytes) => {
      if (!bytes || bytes === 0) return 'N/A'
      const units = ['B', 'KB', 'MB', 'GB']
      let size = bytes
      let unitIndex = 0
      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
      }
      return `${size.toFixed(2)} ${units[unitIndex]}`
    }

    const formatMimeType = (mimeType) => {
      if (!mimeType) return 'N/A'
      const typeMap = {
        'application/pdf': 'PDF',
        'application/msword': 'DOC',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
        'application/vnd.ms-excel': 'XLS',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
        'image/jpeg': 'IMAGE',
        'image/jpg': 'IMAGE',
        'image/png': 'IMAGE',
        'image/gif': 'IMAGE',
        'text/plain': 'TXT',
        'application/zip': 'ZIP',
        'application/x-rar-compressed': 'RAR'
      }
      return typeMap[mimeType] || mimeType.split('/')[1]?.toUpperCase() || 'FILE'
    }

    const downloadDocument = async (document) => {
      try {
        const response = await axios.get(`/upload-portal/api/documents/${document.id}/download`)
        if (response.data.success) {
          window.open(response.data.url, '_blank')
        }
      } catch (error) {
        console.error('Error downloading document:', error)
        alert('Error downloading document. Please try again.')
      }
    }

    const applyFilters = () => {
      pagination.value.current_page = 1
      loadDocuments(1, pagination.value.per_page)
    }

    const clearFilters = () => {
      filters.value = {
        folder: null,
        company: null,
        name: '',
        dateFrom: '',
        dateTo: '',
      }
      pagination.value.current_page = 1
      loadDocuments(1, pagination.value.per_page)
    }

    const debounceFilter = () => {
      if (filterDebounce) {
        clearTimeout(filterDebounce)
      }
      filterDebounce = setTimeout(() => {
        applyFilters()
      }, 500)
    }

    const onPageChange = (page, perPage) => {
      loadDocuments(page, perPage)
    }

    const openEditModal = (document) => {
      editForm.value = {
        id: document.id,
        name: document.name,
        company: document.company ? {
          id: document.company.id,
          name: document.company.name || document.company.store_number + ' - ' + document.company.name
        } : null
      }
      showEditModal.value = true
    }

    const closeEditModal = () => {
      showEditModal.value = false
      editForm.value = {
        id: null,
        name: '',
        company: null
      }
    }

    const handleEdit = async () => {
      if (!editForm.value.name || !editForm.value.company) {
        alert('Please fill in all required fields')
        return
      }

      updating.value = true
      try {
        const response = await axios.put(`/upload-portal/api/documents/${editForm.value.id}`, {
          name: editForm.value.name,
          company_id: editForm.value.company.id
        })

        if (response.data.success) {
          closeEditModal()
          loadDocuments(pagination.value.current_page, pagination.value.per_page)
          alert('Document updated successfully')
        }
      } catch (error) {
        console.error('Error updating document:', error)
        const errorMessage = error.response?.data?.message || 'Error updating document. Please try again.'
        alert(errorMessage)
      } finally {
        updating.value = false
      }
    }

    const deleteDocument = async (document) => {
      if (!confirm(`Are you sure you want to delete "${document.name}"?`)) {
        return
      }

      deletingId.value = document.id
      try {
        const response = await axios.delete(`/upload-portal/api/documents/${document.id}`)

        if (response.data.success) {
          loadDocuments(pagination.value.current_page, pagination.value.per_page)
          alert('Document deleted successfully')
        }
      } catch (error) {
        console.error('Error deleting document:', error)
        const errorMessage = error.response?.data?.message || 'Error deleting document. Please try again.'
        alert(errorMessage)
      } finally {
        deletingId.value = null
      }
    }

    watch(() => showModal.value, (newValue) => {
      if (newValue && allWorkgroups.value.length === 0) {
        loadWorkgroups()
      }
    })

    onMounted(() => {
      loadDocuments()
      loadAllCompanies()
      loadFolders()
    })

    return {
      documents,
      loading,
      showModal,
      showEditModal,
      uploading,
      updating,
      deletingId,
      allWorkgroups,
      companies,
      allCompanies,
      allFolders,
      loadingCompanies,
      form,
      editForm,
      filters,
      pagination,
      onWorkgroupChange,
      onFileChange,
      handleSubmit,
      closeModal,
      formatDate,
      formatFileSize,
      formatMimeType,
      downloadDocument,
      applyFilters,
      clearFilters,
      debounceFilter,
      onPageChange,
      openEditModal,
      closeEditModal,
      handleEdit,
      deleteDocument,
      can,
      getIconForMimeType,
      getColorForMimeType,
      message
    }
  }
}
</script>
