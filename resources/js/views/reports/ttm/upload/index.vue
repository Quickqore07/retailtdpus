<template>
  <div class="ttm-upload-index">
    <Filterable
      ref="filterableRef"
      title="TTM Upload"
      url="data-entry/ttm-uploads"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
    >
      <template #extra>
        <Button
          v-if="access.includes('upload')"
          icon-left="upload"
          icon-size="sm"
          variant="secondary"
          size="sm"
          @click="showUploadModal = true"
        >
          Import File
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Year</Th>
          <Th>Month</Th>
          <Th>Stores</Th>
          <Th>Rows</Th>
          <Th>Last Upload</Th>
          <Th>Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td>{{ index + 1 }}</Td>
          <Td>{{ item.year }}</Td>
          <Td>{{ formatMonthLabel(item.month) }}</Td>
          <Td>{{ item.store_count }}</Td>
          <Td>{{ item.row_count }}</Td>
          <Td>{{ formatDate(item.created_at) }}</Td>
          <Td>
            <Button
              v-if="access.includes('delete')"
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="sm"
              @click="handleDeletePeriod(item)"
            >
              Delete period
            </Button>
          </Td>
        </tr>
      </template>
    </Filterable>

    <FileUploadModal
      v-model="showUploadModal"
      title="Import TTM Sheet"
      size="lg"
      :upload-url="`/data-entry/ttm-uploads/upload`"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
      @upload-success="handleUploadSuccess"
      @upload-error="handleUploadError"
      :allowMultiple="true"
    />

    <MessageModal v-model="showMessagesModal" title="Import messages" :messages="messages" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import MessageModal from '@/components/common/MessageModal.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { useRequest } from '@/services/api'

const resource = 'data-entry/ttm-uploads'
const { filterableRef, setData, access } = useIndexable(resource, 'ttm-upload')
const message = useMessage()

const showUploadModal = ref(false)
const showMessagesModal = ref(false)
const messages = ref([])

const uploadYear = ref(new Date().getFullYear())
const uploadMonth = ref(new Date().getMonth() + 1)

const monthNames = {
  1: 'January',
  2: 'February',
  3: 'March',
  4: 'April',
  5: 'May',
  6: 'June',
  7: 'July',
  8: 'August',
  9: 'September',
  10: 'October',
  11: 'November',
  12: 'December'
}

const uploadYearOptions = computed(() => {
  const y = new Date().getFullYear()
  const list = []
  for (let i = 0; i <= 6; i++) {
    list.push(y - i)
  }
  return list
})

const uploadAdditionalData = computed(() => ({
  year: String(uploadYear.value),
  month: String(uploadMonth.value)
}))

const uploadInstructions = [
  'Required columns: Store Number, Label, Amount',
  'Choose the year and month above; that period is cleared and replaced by this file.',
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE
]

const sortableColumns = [
  { value: 'year', label: 'Year' },
  { value: 'month', label: 'Month' },
  { value: 'row_count', label: 'Rows' },
  { value: 'store_count', label: 'Stores' },
  { value: 'created_at', label: 'Last Upload' }
]

const filterGroups = [
  {
    title: 'TTM Upload Filters',
    filters: [
      {
        name: 'year',
        title: 'Year',
        type: 'text',
        placeholder: 'e.g. 2026'
      },
      {
        name: 'month',
        title: 'Month',
        type: 'text',
        placeholder: 'e.g. 05'
      }
    ]
  }
]

const formatMonthLabel = (month) => {
  const n = parseInt(String(month), 10)
  if (n >= 1 && n <= 12) {
    return monthNames[n]
  }
  return month
}

const handleUploadSuccess = (response) => {
  message.success(response.message || 'File uploaded successfully')
  messages.value = response.messages || []
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
  if (messages.value.length > 0) {
    showMessagesModal.value = true
  }
}

const handleUploadError = (error) => {
  message.error(error.response?.data?.message || 'Upload failed. Please try again.')
}

const handleDeletePeriod = async (item) => {
  const label = `${item.year} ${formatMonthLabel(item.month)}`
  if (!confirm(`Delete all TTM data for ${label}? This cannot be undone.`)) {
    return
  }

  try {
    const res = await useRequest('post', '/data-entry/ttm-uploads/delete-period', {
      year: String(item.year),
      month: String(parseInt(String(item.month), 10))
    })
    message.success(res.message || 'Deleted successfully')
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete period')
  }
}

onMounted(() => {})

defineExpose({
  setData
})
</script>
