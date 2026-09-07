<template>
  <div class="payroll-journal-index">
    <Filterable
      ref="filterableRef"
      title="Payroll Journal"
      url="data-entry/payroll-journals"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
    >
      <template #extra>
        <Button
          v-if="access.includes('create')"
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
          <Th>EOW</Th>
          <Th>Total Earnings</Th>
          <Th>- Total Tips</Th>
          <Th>- Total Mileage</Th>
          <Th>- Total Bonus</Th>
          <Th>+ Payroll Taxes</Th>
          <Th>= CTC</Th>
          <Th>Created At</Th>
          <Th>Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td>{{ index + 1 }}</Td>
          <Td>{{ formatDate(item.eow) }}</Td>
          <Td>{{ formatCurrency(item.total_earnings) }}</Td>
          <Td>{{ formatCurrency(item.total_tips) }}</Td>
          <Td>{{ formatCurrency(item.total_mileage) }}</Td>
          <Td>{{ formatCurrency(item.total_bonus) }}</Td>
          <Td>{{ formatCurrency(item.er_withholdings) }}</Td>
          <Td>{{ formatCurrency(item.ctc) }}</Td>
          <Td>{{ formatDate(item.created_at) }}</Td>
          <Td>
            <Button
              v-if="access.includes('index')"
              icon-left="eye"
              icon-size="sm"
              variant="primary"
              size="sm"
              @click="handleView(item)"
            >
              View
            </Button>
          </Td>
        </tr>
      </template>
    </Filterable>

    <FileUploadModal
      v-model="showUploadModal"
      title="Import Payroll Journal"
      size="lg"
      :upload-url="`/${resource}/upload`"
      :additional-data="uploadAdditionalData"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
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
              Select Bi-weekly Period
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="selectedPeriod"
              class="w-full px-3 py-2 text-sm rounded-md border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50 focus:border-primary"
            >
              <option value="">Select bi-weekly period</option>
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

    <MessageModal v-model="showMessagesModal" title="Messages" :messages="messages" />
    <CompanyDataModal
      v-model="showCompanyDataModal"
      :loading="isCompanyDataLoading"
      :rows="companyRows"
      :selected-record="selectedRecord"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import MessageModal from '@/components/common/MessageModal.vue'
import CompanyDataModal from '@/views/data-entry/payroll-journals/company-data-modal.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { useAuthStore } from '@/stores/auth'
import { useRequest } from '@/services/api'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import { formatCurrency } from '@/utils/number'

const authStore = useAuthStore()
const resource = 'data-entry/payroll-journals'
const { filterableRef, setData, access } = useIndexable(resource, 'payroll-journal')
const message = useMessage()
const showUploadModal = ref(false)
const showMessagesModal = ref(false)
const showCompanyDataModal = ref(false)
const isCompanyDataLoading = ref(false)
const messages = ref([])
const companyRows = ref([])
const selectedRecord = ref(null)
const selectedYear = ref(new Date().getFullYear())
const selectedPeriod = ref('')
const selectedPeriodDetails = ref(null)

const uploadInstructions = [
  'Column A: row labels (optional). Column B: company store number.',
  'Columns C onward match the payroll journal import order (benefits, tips, wages, adjustments, etc.).',
  'Select the EOW (bi-weekly end date) before importing.',
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
]

const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'eow', label: 'EOW' },
  { value: 'total_earnings', label: 'Total Earnings' },
  { value: 'total_tips', label: 'Total Tips' },
  { value: 'total_mileage', label: 'Total Mileage' },
  { value: 'total_bonus', label: 'Total Bonus' },
  { value: 'er_withholdings', label: 'ER Withholdings' },
  { value: 'ctc', label: 'CTC' },
]

const filterGroups = [
  {
    title: 'Payroll Journal Filters',
    filters: [
      {
        name: 'created_at',
        title: 'Created At',
        type: 'datetime',
        placeholder: 'Select Created At'
      },
      {
        name: 'eow',
        title: 'End of Week',
        type: 'date',
        placeholder: 'Select EOW'
      },
      {
        name: 'total_earnings',
        title: 'Total Earnings',
        type: 'text',
        placeholder: 'Enter Total Earnings'
      },
      {
        name: 'total_tips',
        title: 'Total Tips',
        type: 'text',
        placeholder: 'Enter Total Tips'
      },
      {
        name: 'total_mileage',
        title: 'Total Mileage',
        type: 'text',
        placeholder: 'Enter Total Mileage'
      },
      {
        name: 'total_bonus',
        title: 'Total Bonus',
        type: 'text',
        placeholder: 'Enter Total Bonus'
      },
      {
        name: 'er_withholdings',
        title: 'ER Withholdings',
        type: 'text',
        placeholder: 'Enter ER Withholdings'
      },
      {
        name: 'ctc',
        title: 'CTC',
        type: 'text',
        placeholder: 'Enter CTC'
      }
    ]
  }
]

const isDC = computed(() => {
  return authStore.isDC || false
})

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
  let daysToMonday = 0
  if (isDC.value) {
    daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek
  } else {
    daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7
  }
  startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

  let periodNumber = 1
  while (startDate.getUTCFullYear() === year || periodNumber === 1) {
    const endDate = new Date(startDate)
    endDate.setUTCDate(endDate.getUTCDate() + 13)

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

    startDate.setUTCDate(startDate.getUTCDate() + 14)
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

const handleUploadSuccess = (response) => {
  message.success(response.message || 'File uploaded successfully')
  messages.value = response.messages || []
  selectedPeriod.value = ''
  selectedPeriodDetails.value = null
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

const handleView = async (item) => {
  if (!item?.eow || !item?.created_at) {
    message.error('Unable to load company data.')
    return
  }

  selectedRecord.value = item
  showCompanyDataModal.value = true
  isCompanyDataLoading.value = true

  try {
    const response = await useRequest('post', `/${resource}/company-data`, {
      eow: item.eow,
      created_at: item.created_at
    })
    companyRows.value = response.data || []
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load company data')
    companyRows.value = []
  } finally {
    isCompanyDataLoading.value = false
  }
}

defineExpose({
  setData
})
</script>
