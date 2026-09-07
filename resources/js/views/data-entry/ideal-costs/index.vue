<template>
  <div class="ideal-costs-index">
    <Filterable
      ref="filterableRef"
      title="Ideal Costs"
      url="data-entry/ideal-costs"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
    >
      <template #extra>
        <Button
          v-if="access.includes('index')"
          icon-left="download"
          icon-size="sm"
          variant="secondary"
          size="sm"
          @click="downloadExport"
          :disabled="isDownloading"
        >
          {{ isDownloading ? 'Downloading...' : 'Export' }}
        </Button>
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
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/data-entry/ideal-costs/create"
        >
          New Ideal Cost
        </Button>
       
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Companies</Th>
          <Th>Total Cost</Th>
          <Th>Total Mileage</Th>
          <Th>Total Delivery</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td weight="medium" color="primary">{{ item.items_count || 0 }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(totalCost(item)) }}</Td>
          <Td weight="medium" color="primary">{{ formatAmount(totalMileage(item)) }}</Td>
          <Td weight="medium" color="primary">{{ formatAmount(totalDelivery(item)) }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
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
              </router-link>
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
      title="Upload Ideal Costs"
      size="lg"
      :upload-url="`/${resource}/upload`"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
      :import-type-options="importTypeOptions"
      import-type-label="Import Type"
      import-type-placeholder="Select import type"
      :import-type-required="true"
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
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import MessageModal from '@/components/common/MessageModal.vue'
import { useRequest } from '@/services/api'
import { ref } from 'vue'

const resource = 'data-entry/ideal-costs'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'ideal-cost')
const message = useMessage()
const showUploadModal = ref(false)
const isDownloading = ref(false)

// Dynamic import type options: add more here for future use (e.g. Ideal Cost, Delivery, etc.)
const importTypeOptions = [
  { value: 'ideal-cost', label: 'Ideal Cost' },
  { value: 'delivery', label: 'Delivery' }
]
const showMissingMessagesModal = ref(false)
const missingMessages = ref([])
const sortableColumns = [
  { value: 'date', label: 'Date' },
  { value: 'total_cost', label: 'Total Cost' },
  { value: 'total_mileage', label: 'Total Mileage' },
  { value: 'total_delivery', label: 'Total Delivery' },
  { value: 'created_at', label: 'Created At' }
]
// Upload instructions
const uploadInstructions = [
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
]
const templates = [{
  name: 'ideal-cost-template.xlsx',
  label: 'Ideal Cost Template'
},{
  name: 'delivery-template.xlsx',
  label: 'Delivery Template'
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
        name: 'total_cost',
        title: 'Total Cost',
        type: 'text',
        placeholder: 'Enter total cost'
      }
    ]
  }
]

const totalCost = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.ideal_cost || 0), 0)
}
const totalMileage = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.mileage || 0), 0)
}
const totalDelivery = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.delivery || 0), 0)
}

const handleCollectionUpdate = (collection) => {
  collection.data.forEach(item => {
    item.items_count = new Set(item.items.map(row => row.company_id)).size
  })
}

const formatAmount = (value) => {
  const n = Number(value || 0)
  return n.toFixed(2)
}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/data-entry/ideal-costs-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'ideal_costs_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Ideal costs exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export ideal costs')
  } finally {
    isDownloading.value = false
  }
}

defineExpose({
  setData
})
</script>
