<template>
  <div class="employee-hours-index">
    <Filterable
      ref="filterableRef"
      title="Employee Hours"
      :url="url"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
      @update:data="handleDataUpdate"
      :total-values="[{ label: 'Total Hours', value: totalHours }]"
    >
      <template #extra>
        <div class="flex items-center gap-2">
          <Button size="sm"  icon-size="sm" icon-left="bell" class="relative" variant="secondary" @click="showMissingDatesModal = true">
            <span class="absolute top-[-10px] right-[-2px] bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">{{ missingDates?.length }}</span>
          </Button>
          <Button 
            v-if="access.includes('delete') && selectedIds.length > 0 &&!params.id"
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
            icon-left="download" 
            icon-size="sm" 
            variant="secondary" 
            size="sm" 
            @click="downloadEmployeeHoursData"
            :disabled="isDownloading"
          >
            {{ isDownloading ? 'Downloading...' : 'Export Hours' }}
          </Button>
          <Button 
            v-if="access.includes('create') && !params.id"
            icon-left="upload" 
            icon-size="sm" 
            variant="secondary" 
            size="sm" 
            @click="showUploadModal = true"
          >
            Upload File
          </Button>
          <!-- <Button 
            v-if="access.includes('create')"
            icon-left="plus" 
            icon-size="sm" 
            variant="primary" 
            size="sm" 
            to="/payroll/employee-hours/create"
          >
            New Entry
          </Button> -->
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
          <Th>Employee ID</Th>
          <Th>Employee</Th>
          <Th>Company</Th>
          <Th>SSN</Th>
          <Th>Role</Th>
          <Th>Total Hours</Th>
          <Th>Pay Rate</Th>
          <Th>Rate Type</Th>
          <Th>Tips</Th>
          <Th>Mileage Excess</Th>
          <Th>Incentive</Th>
          <Th>Bonus</Th>
          <Th>Tips Due</Th>
          <Th>Mileage Due</Th>
          <Th>Created At</Th>
          <Th>Updated At</Th>
          <!-- <Th>Earnings</Th> -->
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
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td weight="medium" color="primary">
            {{ formatDate(item.date) }}
          </Td>
          <Td color="secondary">
            {{ item.employee?.employee_id || 'N/A' }}
          </Td>
          <Td color="secondary">
            <a :href="`/employee/${item.employee?.id}`" target="_blank">{{ item.employee?.pos_name || 'N/A' }}</a>
          </Td>
          <Td color="secondary">
            {{ item.company?.name  }}
          </Td>
          <Td color="secondary">  
            {{ item.ssn || 'N/A' }}
          </Td>
          <Td color="secondary">
            {{ item.role?.name ? item.role?.code + ' - ' + item.role?.name : 'N/A' }}
          </Td>
          <Td color="secondary">
            {{ formatNumber(item.total_hours) }} hrs
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.pay_rate) }}
          </Td>
          <Td color="secondary">
            {{calculateRateType(item) }}
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.tips) }}
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.mileage_excess) }}
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.incentive) }}
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.bonus) }}
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.tips_due) }}
          </Td>
          <Td color="secondary">
            ${{ formatNumber(item.mileage_due) }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.updated_at) }}
          </Td>
          <!-- <Td weight="medium" color="primary">
            ${{ formatNumber(calculateEarnings(item)) }}
          </Td> -->
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/payroll/employee-hours/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/payroll/employee-hours/${item.id}/edit`"
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
      title="Upload Employee Hours"
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

    <!-- Upload Result Modal -->
    <UploadResultModal
      v-model="showResultModal"
      title="Upload Results"
      :result="uploadResult"
      @close="handleResultModalClose"
    />
    <Modal v-model="showMissingDatesModal" title="Missing Dates" :messages="missingDates" >
      <div class="mb-3 flex justify-end">
        <Button size="sm" variant="secondary" @click="showAllDeleted">
          All Deleted
        </Button>
      </div>
      <div class="flex flex-wrap gap-2">
        <div
          v-for="date in missingDates"
          :key="date.date"
          class="flex items-center gap-2 bg-gray-100 p-2 rounded-md hover:bg-gray-200 cursor-pointer"
          @click="showCompanies(date)"
        >
          {{ formatDate(date.date) }} ({{ date.companies.length }})
        </div>
        <div v-if="!missingDates?.length" class="text-sm text-gray-500">
          No missing dates
        </div>
      </div>
    </Modal>

    <Modal v-model="showCompaniesModal" title="Missing Companies" >
      <div class="flex flex-col gap-2 max-h-[500px] overflow-y-auto">
        <div
          v-for="company in selectedCompanies"
          :key="company.company_id"
          class="flex items-center justify-between gap-2 bg-gray-100 p-2 rounded-md"
        >
          <span>{{ company.name }}</span>
          <button
            type="button"
            class="text-red-600 hover:text-red-900 transition-colors"
            title="Delete"
            @click="deleteMissingCompany(company)"
          >
            <SvgIcon name="trash" size="lg" />
          </button>
        </div>
        <div v-if="!selectedCompanies?.length" class="text-sm text-gray-500">
          No missing companies
        </div>
      </div>
    </Modal>

    <Modal v-model="showDeletedModal" title="Deleted Missing Companies">
      <div class="flex flex-col gap-2 max-h-[500px] overflow-y-auto">
        <div
          v-for="item in deletedMissingItems"
          :key="item.id"
          class="flex items-center justify-between gap-2 bg-gray-100 p-2 rounded-md"
        >
          <div>
            <div class="font-medium">{{ item.company }}</div>
            <div class="text-sm text-gray-500">{{ formatDate(item.date) }}</div>
          </div>
        </div>
        <div v-if="!deletedMissingItems?.length" class="text-sm text-gray-500">
          No deleted items
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import Button from '@/components/ui/button.vue'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import UploadResultModal from '@/components/common/UploadResultModal.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'
import { useMessage } from '@/composables/useMessage'
import {useRequest}  from '@/services/api'
import Modal from '@/components/common/Modal.vue'
const route = useRoute()
const resource = 'payroll/employee-hours'

// Use the useIndexable composable
const params = route.params
const { filterableRef, setData, removeDB, removeMultipleDB, access } = useIndexable(params.id ? 'payroll/employee-hours?employee_id=' + params.id : resource, 'employee-hour')
const url = params.id ? `/payroll/employee-hours?employee_id=${params.id}` : resource
// Export functionality
const message = useMessage()
const isDownloading = ref(false)
const missingDates = ref([])
const showMissingDatesModal = ref(false)
const selectedCompanies = ref([])
const selectedMissingDate = ref(null)
const showCompaniesModal = ref(false)
const showDeletedModal = ref(false)
const deletedMissingItems = ref([])
// Multi-select state
const selectedIds = ref([])
const collectionData = ref([])

const showCompanies = (dateItem) => {
  selectedMissingDate.value = dateItem.date
  selectedCompanies.value = dateItem.companies || []
  showCompaniesModal.value = true
}

const deleteMissingCompany = async (company) => {
  if (!selectedMissingDate.value || !company?.company_id) {
    return
  }
  if (!confirm(`Remove ${company.name} from missing list for ${formatDate(selectedMissingDate.value)}?`)) {
    return
  }

  try {
    await useRequest('post', '/payroll/employee-hours/missing/delete', {
      date: selectedMissingDate.value,
      company_id: company.company_id,
    })

    selectedCompanies.value = selectedCompanies.value.filter(
      (item) => item.company_id !== company.company_id
    )

    const dateIndex = missingDates.value.findIndex(
      (item) => item.date === selectedMissingDate.value
    )
    if (dateIndex > -1) {
      missingDates.value[dateIndex].companies = selectedCompanies.value
      if (selectedCompanies.value.length === 0) {
        missingDates.value.splice(dateIndex, 1)
        showCompaniesModal.value = false
      }
    }

    message.success('Missing company deleted successfully')
  } catch (error) {
    console.error('Delete missing company error:', error)
    message.error('Failed to delete missing company')
  }
}

const showAllDeleted = async () => {
  try {
    const response = await useRequest('get', '/payroll/employee-hours/missing/deleted')
    deletedMissingItems.value = response.collection || []
    showDeletedModal.value = true
  } catch (error) {
    console.error('Fetch deleted missing companies error:', error)
    message.error('Failed to load deleted missing companies')
  }
}
const templates =[
  {
    name: 'employee-hours-template.xlsx',
    label: 'Employee Hours Template'
  }
]

// Upload Modal State
const showUploadModal = ref(false)
const showResultModal = ref(false)
const uploadResult = ref({
  message: '',
  uploaded: false,
  files_processed: 0,
  rows_processed: 0,
  missing_companies: [],
  new_employees: [],
  existing_employees: []
})

const totalHours = ref(0)
const handleDataUpdate = (response) => {
  totalHours.value = formatNumber(response.totalHours || 0)
  missingDates.value = response.missingDates || []
}
// Computed property to check if all items are selected
const isAllSelected = computed(() => {
  return collectionData.value.length > 0 && 
         selectedIds.value.length === collectionData.value.length
})

// Upload instructions
const uploadInstructions = [
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
  'Ensure your file includes required columns: Store, SSN, Pay Date, Employee Id, Name, Pay Type, Total Hours, Tips, Mileage Excess, Incentive, Bonus, Home Store, Employee Role, Role Code, Pay Rate',
  'After upload you will be asked to confirm the dates found in the sheet before import'
]

// Sortable columns configuration
const sortableColumns = [
  { value: 'date', label: 'Date' },
  { value: 'company.name', label: 'Company' },
  { value: 'employee_name', label: 'Employee Name' },
  { value: 'total_hours', label: 'Total Hours' },
  { value: 'pay_rate', label: 'Pay Rate' },
  { value: 'created_at', label: 'Created At' },
  { value: 'updated_at', label: 'Updated At' },
]

// Filter groups configuration
const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      ...(params.id ? [] : [{
        name: 'employee_id',
        title: 'Employee',
        type: 'lookup_only',
        placeholder: 'Enter employee',
        resource: 'employees',
        column: 'pos_name',
      }]),
      {
        name: 'date',
        title: 'Date',
        type: 'datetime',
        placeholder: 'Select date'
      },
      {
        name: 'total_hours',
        title: 'Employee hours',
        type: 'numeric', 
        placeholder: 'Enter employee hours'
      },
      {
        name: 'tips',
        title: 'Tips',
        type: 'numeric', 
        placeholder: 'Enter tips'
      },
      {
        name: 'mileage_excess',
        title: 'Mileage Excess',
        type: 'numeric', 
        placeholder: 'Enter mileage excess'
      },
      {
        name: 'incentive',
        title: 'Incentive',
        type: 'numeric', 
        placeholder: 'Enter incentive'
      },
      {
        name: 'bonus',
        title: 'Bonus',
        type: 'numeric', 
        placeholder: 'Enter bonus'
      },
      {
        name: 'tips_due',
        title: 'Tips Due',
        type: 'numeric', 
        placeholder: 'Enter tips due'
      },
      {
        name: 'mileage_due',
        title: 'Mileage Due',
        type: 'numeric', 
        placeholder: 'Enter mileage due'
      },
    ]
  },
  {
    title: 'Relationships',
    filters: [
      {
        name: 'role_id',
        title: 'Role',
        type: 'lookup_only',
        resource: 'employee-roles',
        column: 'name',
        placeholder: 'Select role'
      },
      {
        name: 'company_id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company'
      },

    ]
  },
  {
    title: 'Dates',
    filters: [
      {
        name: 'created_at',
        title: 'Created At',
        type: 'datetime',
        placeholder: 'Select date'
      },
      {
        name: 'updated_at',
        title: 'Updated At',
        type: 'datetime',
        placeholder: 'Select date'
      }
    ]
  }
]

// Format number helper
const formatNumber = (value) => {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

const calculateRateType = (item) => {
  const itemDate = new Date(item.date)
  let employeeRate = null

  item.employee?.employee_rates?.forEach(rate => {
    const effectiveDate = new Date(rate.effective_date)

    if (
      effectiveDate <= itemDate &&
      item.company_id === rate.company_id
    ) {
      employeeRate = rate;
    }
  })

  return employeeRate?.rate_type || 'N/A'
}


// Handle collection update from filterable component
const handleCollectionUpdate = (collection) => {
  collectionData.value = collection?.data || []
  // Clear selections when collection changes
  selectedIds.value = []
}

// Multi-select functions
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
    selectedIds.value = collectionData.value.map(item => item.id)
  }
}

// Handle single delete action
const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
    // Remove from selected if it was selected
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    }
  }
}

// Handle multiple delete action
const handleMultiDelete = async () => {
  if (selectedIds.value.length === 0) {
    return
  }
  
  const success = await removeMultipleDB(resource, selectedIds.value)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
    selectedIds.value = []
  }
}

// Upload handlers
const handleUploadSuccess = (response) => {
  // Store the upload result
  uploadResult.value = {
    message: response.message || 'Upload completed successfully',
    uploaded: response.uploaded || true,
    rows_processed: response.rows_processed || 0,
    missing_companies: response.missing_companies || [],
    new_employees: response.new_employees || [],
    existing_employees: response.existing_employees || []
  }
  
  // Show the result modal
  showResultModal.value = true
  
  // Refresh the list after successful upload
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const handleUploadError = (error) => {
  console.error('Upload error:', error)
}

const handleResultModalClose = () => {
  showResultModal.value = false
}

// Download employee hours data with filters
const downloadEmployeeHoursData = async () => {
  try {

    if(!confirm('Are you sure you want to download the employee hours data?')){
      return
    }
    isDownloading.value = true
    
    // Get current filters from the filterable component
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    if(params.id){
      currentParams.employee_id = params.id
    }
    const response = await useRequest('get', '/payroll/employee-hours-export',null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))

    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'employee_hours_export.xlsx')

    document.body.appendChild(link)
    link.click()

    link.remove()
    window.URL.revokeObjectURL(url)
    
    message.success('Employee hours exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export employee hours')
  } finally {
    isDownloading.value = false
  }
}

// Expose setData for useIndexable route guards
defineExpose({
  setData
})
</script>

