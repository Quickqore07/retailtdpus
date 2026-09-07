<template>
  <div class="pj-payments-index">
    <Filterable
      ref="filterableRef"
      title="PJ Payments"
      url="ar/pj-payments"
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
            to="/ar/pj-payments/create"
          >
            New PJ Payment
          </Button>
        </div>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Company</Th>
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
          <Td color="default">{{ index + 1 }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td weight="medium" color="primary">
            {{ item.company?.name }}
          </Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.total_amount) }}</Td>
            <!-- <Td color="secondary">{{ item.created_by?.name }}</Td>
            <Td color="secondary">{{ item.updated_by?.name }}</Td> -->
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td color="secondary">{{ formatDate(item.updated_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/ar/pj-payments/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/ar/pj-payments/${item.id}/edit`"
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
      title="Upload PJ Payments"
      size="lg"
      :allow-multiple="true"
      :upload-url="`/${resource}/upload`"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
      @upload-success="handleUploadSuccess"
      @upload-error="handleUploadError"
      :templates="templates"
      :allow-download-template="access.includes('create')"
    />

    <MessageModal v-model="showMissingMessagesModal" title="Messages" :messages="missingMessages" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import MessageModal from '@/components/common/MessageModal.vue'
import { useRequest } from '@/services/api'

const resource = 'ar/pj-payments'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'pj-payment')
const message = useMessage()
const showUploadModal = ref(false)
const isDownloading = ref(false)
const missingMessages = ref([])
const showMissingMessagesModal = ref(false)
const uploadInstructions = [
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
  'Upload data using the PJ payments template format'
]
const templates = [{
  name: 'pj-cash-template.xlsx',
  label: 'PJ Cash Template'
},
{
  name: 'daily-sales-template.xlsx',
  label: 'Daily Sales Template'
}
]
const sortableColumns = [
  { value: 'pj_payments.date', label: 'Date' },
  { value: 'company.store_number', label: 'Company' },
  { value: 'pj_payments.total_amount', label: 'Total Amount' },
  { value: 'pj_payments.created_at', label: 'Created At' },
  { value: 'pj_payments.updated_at', label: 'Updated At' }  
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
      }
    ]
  }
]

const handleCollectionUpdate = () => {}

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

const handleUploadSuccess = (response) => {
  message.success('File uploaded successfully')
  missingMessages.value = response.missing
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
  if (missingMessages.value.length > 0) {
    showMissingMessagesModal.value = true
  }
}

const handleUploadError = (error) => {
  console.error('Upload error:', error)
}

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/ar/pj-payments-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'pj_payments_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('PJ payments exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export PJ payments')
  } finally {
    isDownloading.value = false
  }
}

defineExpose({
  setData
})
</script>
