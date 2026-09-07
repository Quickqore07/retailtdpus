<template>
  <div class="fees-upload-index">
    <Filterable
      ref="filterableRef"
      title="Fees Upload"
      url="ar/fees-uploads"
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
            :disabled="isDownloadingExport"
          >
            {{ isDownloadingExport ? 'Downloading...' : 'Export' }}
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
            to="/ar/fees-uploads/create"
          >
            New Fees Upload
          </Button>
        </div>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Company</Th>
          <Th>DDD Cash</Th>
          <Th>EZ Cater</Th>
          <Th>Meal Deal</Th>
          <Th>Visa</Th>
          <Th>Amex</Th>
          <Th>Doordash</Th>
          <Th>DDC Doordash</Th>
          <Th>Uber</Th>
          <Th>Grubhub</Th>
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
          <Td weight="medium" color="secondary">${{ formatAmount(item.ddd_cash) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.ez_cater) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.meal_deal) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.visa) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.amex) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.doordash) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.ddc_doordash) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.uber) }}</Td>
          <Td weight="medium" color="secondary">${{ formatAmount(item.grubhub) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.total_amount) }}</Td>
          <!-- <Td color="secondary">{{ item.created_by?.name }}</Td>
          <Td color="secondary">{{ item.updated_by?.name }}</Td> -->
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td color="secondary">{{ formatDate(item.updated_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/ar/fees-uploads/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/ar/fees-uploads/${item.id}/edit`"
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
      title="Upload Fees"
      size="lg"
      :allow-multiple="true"
      :upload-url="`/${resource}/upload`"
      :additional-data="uploadAdditionalData"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
      :templates="templates"
      :allow-download-template="access.includes('create')"
      @upload-success="handleUploadSuccess"
      @upload-error="handleUploadError"
    >
      <template #extra>
        <div class="space-y-3">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Select Year
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model.number="selectedYear"
              class="w-full px-3 py-2 text-sm rounded-md border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50 focus:border-primary"
            >
              <option v-for="year in availableYears" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Select Weekly Period
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="selectedPeriod"
              class="w-full px-3 py-2 text-sm rounded-md border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50 focus:border-primary"
            >
              <option value="">Select weekly period</option>
              <option
                v-for="period in availablePeriods"
                :key="period.value"
                :value="period.value"
              >
                {{ period.label }}
              </option>
            </select>
          </div>
        </div>
      </template>
    </FileUploadModal>

    <MessageModal v-model="showMissingMessagesModal" title="Messages" :messages="missingMessages" />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
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
const resource = 'ar/fees-uploads'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'fees-upload')
const message = useMessage()
const showUploadModal = ref(false)
const selectedYear = ref(new Date().getFullYear())
const selectedPeriod = ref('')
const selectedPeriodDetails = ref(null)
const missingMessages = ref([])
const showMissingMessagesModal = ref(false)
const isDownloadingExport = ref(false)
const uploadInstructions = [
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
  'Download the template file below to see the required format',
  'Select the weekly period (EOW date) before uploading',
  'Required columns: Date, Company (Store Number), DDD Cash, EZ Cater, Meal Deal, Visa, Amex, Doordash, DDC Doordash, Uber, Grubhub'
]
const templates = [{
  name: 'fees-upload-template.xlsx',
  label: 'Fees Upload Template'
}]

const sortableColumns = [
  { value: 'fees_uploads.date', label: 'Date' },
  { value: 'company.store_number', label: 'Company' },
  { value: 'fees_uploads.total_amount', label: 'Total Amount' },
  { value: 'fees_uploads.created_at', label: 'Created At' },
  { value: 'fees_uploads.updated_at', label: 'Updated At' }
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

const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let i = 0; i <= 5; i++) {
    years.push(currentYear - i)
  }
  return years
})

const availablePeriods = computed(() => {
  const year = selectedYear.value
  const periods = []

  let startDate = new Date(Date.UTC(year, 0, 1))
  const dayOfWeek = startDate.getUTCDay()
  const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

  let periodNumber = 1
  while (startDate.getUTCFullYear() === year || periodNumber === 1) {
    const endDate = new Date(startDate)
    endDate.setUTCDate(endDate.getUTCDate() + 6)

    if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
      break
    }

    const formatPeriodDate = (date) => {
      const month = String(date.getUTCMonth() + 1).padStart(2, '0')
      const day = String(date.getUTCDate()).padStart(2, '0')
      const dateYear = date.getUTCFullYear()
      return `${month}-${day}-${dateYear}`
    }

    periods.push({
      label: `${formatPeriodDate(startDate)} To ${formatPeriodDate(endDate)}`,
      value: `${startDate.toISOString()} to ${endDate.toISOString()}`,
      startDate: new Date(startDate),
      endDate: new Date(endDate)
    })

    startDate.setUTCDate(startDate.getUTCDate() + 7)
    periodNumber++
  }

  return periods
})

const formatApiDate = (date) => {
  if (!date) return ''
  return new Date(date).toISOString().slice(0, 10)
}

const uploadAdditionalData = computed(() => ({
  date: formatApiDate(selectedPeriodDetails.value?.endDate)
}))

watch(selectedYear, () => {
  selectedPeriod.value = ''
  selectedPeriodDetails.value = null
})

watch(selectedPeriod, (value) => {
  selectedPeriodDetails.value = availablePeriods.value.find((period) => period.value === value) || null
})

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
  selectedPeriod.value = ''
  selectedPeriodDetails.value = null
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
    isDownloadingExport.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/ar/fees-uploads-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'fees_uploads_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Fees uploads exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export fees uploads')
  } finally {
    isDownloadingExport.value = false
  }
}


defineExpose({
  setData
})
</script>
