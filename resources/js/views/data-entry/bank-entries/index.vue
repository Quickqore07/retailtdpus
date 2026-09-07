<template>
  <div class="bank-entries-index">
    <Filterable
      ref="filterableRef"
      title="Bank Entries"
      url="data-entry/bank-entries"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
    >
      <template #extra>
        <div class="flex items-center gap-2">
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
            v-if="access.includes('delete') && selectedIds.length > 0"
            icon-left="trash"
            icon-size="sm"
            variant="danger"
            size="sm"
            @click="handleMultiDelete"
          >
            Delete Selected ({{ selectedIds.length }})
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
            to="/data-entry/bank-entries/create"
          >
            New Bank Entry
          </Button>
        </div>
      </template>

      <template #heading>
        <tr>
          <Th v-if="access.includes('delete')">
            <input
              type="checkbox"
              :checked="isAllSelected"
              @change="toggleSelectAll"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
            />
          </Th>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Company</Th>
          <Th>Bank</Th>
          <Th>Total Amount</Th>
          <!-- <Th>Created By</Th>
          <Th>Updated By</Th> -->
          <Th>Created At</Th>
          <Th>Updated At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td v-if="access.includes('delete')" color="default">
            <input
              type="checkbox"
              :checked="selectedIds.includes(item.id)"
              @change="toggleSelect(item.id)"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
            />
          </Td>
          <Td color="default">{{ index + 1 }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td weight="medium" color="primary">{{ item.company?.name }}</Td>
          <Td color="secondary">{{ item.bank_ledger?.name || '-' }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.total_amount) }}</Td>
          <!-- <Td color="secondary">{{ item.created_by?.name }}</Td>
          <Td color="secondary">{{ item.updated_by?.name }}</Td> -->
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td color="secondary">{{ formatDate(item.updated_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/data-entry/bank-entries/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/data-entry/bank-entries/${item.id}/edit`"
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

    <FileUploadModal
      v-model="showUploadModal"
      title="Upload Bank Entries"
      size="lg"
      :upload-url="`/${resource}/upload`"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
      :templates="templates"
      :allow-download-template="access.includes('create')"
      allow-multiple
      @upload-success="handleUploadSuccess"
      @upload-error="handleUploadError"
    />

    <MessageModal v-model="showMissingMessagesModal" title="Messages" :messages="missingMessages" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import MessageModal from '@/components/common/MessageModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { useRequest } from '@/services/api'

const resource = 'data-entry/bank-entries'
const message = useMessage()
const showUploadModal = ref(false)
const isDownloading = ref(false)
const selectedIds = ref([])
const collectionData = ref([])
const missingMessages = ref([])
const showMissingMessagesModal = ref(false)
const uploadInstructions = [
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
  'Columns (order does not matter): Date, Account number, Cr amount / Db amount / Amount, Description',
  'Account number must match a ledger account number for the current company',
  'After upload you will be asked to confirm the dates found in the sheet before import'
]
const templates = [{
  name: 'bank-entries-template.xlsx',
  label: 'Bank Entries Template'
}]
const { filterableRef, setData, removeDB, removeMultipleDB, access } = useIndexable(resource, 'bank-entry')

const sortableColumns = [
  { value: 'bank_entries.date', label: 'Date' },
  { value: 'company.store_number', label: 'Company' },
  { value: 'bank_entries.total_amount', label: 'Total Amount' },
  { value: 'bank_entries.created_at', label: 'Created At' },
  { value: 'bank_entries.updated_at', label: 'Updated At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'company_id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company'
      },
      {
        name: 'date',
        title: 'Date',
        type: 'datetime',
        placeholder: 'Select date'
      },
      {
        name: 'total_amount',
        title: 'Total Amount',
        type: 'text',
        placeholder: 'Enter amount'
      },
      {
        name: 'bank',
        title: 'Bank',
        type: 'lookup_only',
        resource: 'banks',
        column: 'name',
        placeholder: 'Select bank'
      }
    ]
  }
]

const formatAmount = (value) => Number(value || 0).toFixed(2)

const isAllSelected = computed(() =>
  collectionData.value.length > 0 && selectedIds.value.length === collectionData.value.length
)

const handleCollectionUpdate = (collection) => {
  collectionData.value = collection?.data || []
  selectedIds.value = []
}

const toggleSelect = (id) => {
  const index = selectedIds.value.indexOf(id)
  if (index > -1) {
    selectedIds.value.splice(index, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = collectionData.value.map((item) => item.id)
  }
}

const handleMultiDelete = async () => {
  if (selectedIds.value.length === 0) return
  const success = await removeMultipleDB(resource, selectedIds.value)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
    selectedIds.value = []
  }
}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
    const index = selectedIds.value.indexOf(id)
    if (index > -1) selectedIds.value.splice(index, 1)
  }
}

const handleUploadSuccess = (response) => {
  message.success(response.message || 'File uploaded successfully')
  missingMessages.value = response.messages || response.missing || []
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
  if (missingMessages.value.length > 0) {
    showMissingMessagesModal.value = true
  }
}

const handleUploadError = (error) => {
  const msg = error.response?.data?.message || 'Upload failed. Please try again.'
  message.error(msg)
}

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/data-entry/bank-entries-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'bank_entries_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Bank entries exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export bank entries')
  } finally {
    isDownloading.value = false
  }
}

defineExpose({
  setData
})
</script>
