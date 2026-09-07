<template>
    <div class="ideal-costs-index">
      <Filterable
        ref="filterableRef"
        title="Driver"
        url="data-entry/driver"
        :sortable="sortableColumns"
        :filter-groups="filterGroups"
        @update:collection="handleCollectionUpdate"
        :show-search="true"
      >
        <template #extra>
          <!-- <Button
            v-if="access.includes('index')"
            icon-left="download"
            icon-size="sm"
            variant="secondary"
            size="sm"
            @click="downloadExport"
            :disabled="isDownloading"
          >
            {{ isDownloading ? 'Downloading...' : 'Export' }}
          </Button> -->
          <Button 
              v-if="access.includes('create')"
              icon-left="upload" 
              icon-size="sm" 
              variant="secondary" 
              size="sm" 
              @click="showUploadModal = true"
            >
              Upload File
            </Button>
          <Button
            v-if="access.includes('delete') && selectedIds.length > 0"
            icon-left="trash"
            icon-size="sm"
            variant="danger"
            size="sm"
            :disabled="selectedIds.length === 0"
            @click="handleDeleteMultiple"
          >
            Delete Selected ({{ selectedIds.length }})
          </Button>
        </template>
  
        <template #heading>
          <tr>
            <Th>
              <input
                type="checkbox"
                :checked="allChecked"
                :indeterminate.prop="isIndeterminate"
                @change="toggleAllSelection"
              />
            </Th>
            <Th>No</Th>
            <Th>Date</Th>
            <Th>Company</Th>
            <Th>Driver ID</Th>
            <Th>Driver Name</Th>
            <Th>Time In</Th>
            <Th>Time Out</Th>
            <Th>Cash Tips</Th>
            <Th>Mileage</Th>
            <Th>Created At</Th>
            <Th align="right">Actions</Th>
          </tr>
        </template>
  
        <template #default="{ item, index }">
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
            <Td color="default">
              <input
                type="checkbox"
                :checked="selectedIds.includes(item.id)"
                @change="toggleSelection(item.id)"
              />
            </Td>
            <Td color="default">{{ index + 1 }}</Td>
            <Td color="secondary">{{ formatDate(item.date) }}</Td>
            <Td color="secondary">{{ item.company?.name || 'N/A' }}</Td>
            <Td color="secondary">
                <a v-if="item.employee_id" :href="`/employee/${item.employee_id}`" target="_blank">{{ item.driver_id || 'N/A' }}</a>
                <span v-else>{{ item.driver_id || 'N/A' }}</span>
            </Td>
            <Td color="secondary">{{ item.driver_name || 'N/A' }}</Td>
            <Td color="secondary">{{ item.time_in || 'N/A' }}</Td>
            <Td color="secondary">{{ item.time_out || 'N/A' }}</Td>
            <Td color="secondary">{{ item.cash_tips || 'N/A' }}</Td>
            <Td color="secondary">{{ item.mileage || 'N/A' }}</Td>
            <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
            <Td align="right" weight="medium">
              <div class="flex items-center justify-end gap-2">
                <!-- <router-link
                  v-if="access.includes('show')"
                  :to="`/data-entry/ideal-costs/${item.id}`"
                  class="text-blue-600 hover:text-blue-900 transition-colors"
                  title="View"
                >
                  <SvgIcon name="eye" size="lg" />
                </router-link>
                <router-link
                  v-if="access.includes('update')"
                  :to="`/data-entry/ideal-costs/${item.id}/edit`"
                  class="text-indigo-600 hover:text-indigo-900 transition-colors"
                  title="Edit"
                >
                  <SvgIcon name="edit" size="lg" />
                </router-link> -->
                <button
                  v-if="access.includes('delete')"
                  @click="handleDelete(item.id)"
                  class="text-red-600 hover:text-red-900 transition-colors"
                  title="Delete"
                >
                  <SvgIcon name="trash" size="lg" />
                </button>
              </div>
            </Td>
          </tr>
        </template>
      </Filterable>
          <!-- Upload Modal -->
        <FileUploadModal
            v-model="showUploadModal"
            title="Upload Driver" 
            size="lg"
            :upload-url="`/${resource}/upload`"
            :instructions="uploadInstructions"
            format-text="CSV, XLSX, XLS"
            :templates="templates"
            :allow-download-template="access.includes('create')"
            @upload-success="handleUploadSuccess"
            @upload-error="handleUploadError"
      />
      <MessageModal v-model="showMissingMessagesModal" title="Messages" :messages="missingMessages" />
    </div>
  </template>
  
  <script setup>
  import Filterable from '@/components/filterable/filterable.vue'
  import Button from '@/components/ui/button.vue'
  import Td from '@/components/ui/td.vue'
  import Th from '@/components/ui/th.vue'
  import SvgIcon from '@/components/SvgIcon.vue'
  import { useIndexable } from '@/composables/useIndexable'
  import { formatDate } from '@/utils/date'
  import FileUploadModal from '@/components/common/FileUploadModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
  import MessageModal from '@/components/common/MessageModal.vue'
  import { computed, ref } from 'vue'
  
  const resource = 'data-entry/driver'
  const { filterableRef, setData, removeDB, removeMultipleDB, access } = useIndexable(resource, 'driver')
  const showUploadModal = ref(false)
  const selectedIds = ref([])
  

  const showMissingMessagesModal = ref(false)
  const missingMessages = ref([])
  const sortableColumns = [
      { value: 'created_at', label: 'Created At' },
    { value: 'date', label: 'Date' },
    { value: 'company.name', label: 'Company' },
    { value: 'driver_name', label: 'Driver Name' },
    { value: 'time_in', label: 'Time In' },
    { value: 'time_out', label: 'Time Out' },
    { value: 'cash_tips', label: 'Cash Tips' },
    { value: 'mileage', label: 'Mileage' },
  ]
  // Upload instructions
  const uploadInstructions = [
    'Supported formats: CSV, Excel (.xlsx, .xls)',
    UPLOAD_MAX_SIZE_NOTE,
  ]
  const templates = [{
    name: 'driver-template.xlsx',
    label: 'Driver Template'
  }]
  // Upload handlers
  const handleUploadSuccess = (response) => {
    // Store the upload result
    missingMessages.value = response.messages || []
    
    // // Show the result modal
    if(response.messages && response.messages.length > 0){
      showMissingMessagesModal.value = true
  
    }
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  
  }
  
  const handleUploadError = (error) => {
    console.error('Upload error:', error)
  }
  
  
  const filterGroups = [
    {
      title: 'Basic Information',
      filters: [
        {
          name: 'date',
          title: 'Date',
          type: 'datetime',
          placeholder: 'Select date'
        },
        {
          name: 'company_id',
          title: 'Company',
          type: 'lookup_only',
          resource: 'companies',
          column: 'name',
          placeholder: 'Select company'
        },
        {
          name: 'driver_id',
          title: 'Driver ID',
          type: 'string',
          placeholder: 'Enter driver ID'
        },
        {
          name: 'driver_name',
          title: 'Driver Name',
          type: 'string',
          placeholder: 'Enter driver name'
        }
      ]
    }
  ]
  
  const handleCollectionUpdate = (collection) => {
    const idsInPage = (collection?.data || []).map(item => item.id)
    selectedIds.value = selectedIds.value.filter(id => idsInPage.includes(id))
    
  }

  const allChecked = computed(() => {
    const data = filterableRef.value?.collection?.data || []
    return data.length > 0 && data.every(item => selectedIds.value.includes(item.id))
  })

  const isIndeterminate = computed(() => {
    const data = filterableRef.value?.collection?.data || []
    const selectedCount = data.filter(item => selectedIds.value.includes(item.id)).length
    return selectedCount > 0 && selectedCount < data.length
  })

  const toggleSelection = (id) => {
    if (selectedIds.value.includes(id)) {
      selectedIds.value = selectedIds.value.filter(itemId => itemId !== id)
      return
    }
    selectedIds.value.push(id)
  }

  const toggleAllSelection = (event) => {
    const checked = event.target.checked
    const data = filterableRef.value?.collection?.data || []
    selectedIds.value = checked ? data.map(item => item.id) : []
  }

  const handleDelete = async (id) => {
    const success = await removeDB(resource, id)
    if (success) {
      selectedIds.value = selectedIds.value.filter(itemId => itemId !== id)
      filterableRef.value?.fetch()
    }
  }

  const handleDeleteMultiple = async () => {
    const success = await removeMultipleDB(resource, selectedIds.value)
    if (success) {
      selectedIds.value = []
      filterableRef.value?.fetch()
    }
  }
  
  
  
  defineExpose({
    setData
  })
  </script>
  