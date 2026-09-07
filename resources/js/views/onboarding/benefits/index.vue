<template>
  <div class="benefits-index">
    <!-- Send Mail Modal -->
    <Modal
      v-model="sendMailModalVisible"
      size="5xl"
      :show-footer="false"
      @close="closeSendMailModal"
    >
      <template #header>
        <div class="flex items-center justify-between w-full gap-4">
          <h5 class="text-xl font-bold !mb-0">Send Benefit Mail</h5>
          <Button
            v-if="can('benefits', 'send-mail')"
            @click="openAddEmployeeModal"
            variant="outline-secondary"
            size="sm"
            icon-left="plus"
          >
            Add Employee
          </Button>
        </div>
      </template>
      <div class="space-y-4">
        <div class="relative">
          <Input
            v-model="searchQuery"
            label="Search Employee"
            type="text"
            placeholder="Search by name, ID, or email"
            icon-left="search"
            @input="handleSearch"
          />
          <div
            v-if="searchLoading"
            class="absolute right-3 top-[38px] transform -translate-y-1/2"
          >
            <Spinner size="sm" />
          </div>
        </div>

        <!-- Search Results -->
        <div
          v-if="searchResults.length > 0"
          class="border border-gray-200 dark:border-gray-700 rounded-lg max-h-[400px] overflow-auto"
        >
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Employee Name
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Employee ID
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Email
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Company
                </th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Action
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="employee in searchResults"
                :key="employee.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                  {{ employee.pos_name || employee.aliases?.[0]?.alias_name || 'N/A' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ employee.employee_id || employee.aliases?.[0]?.alias_employee_id || 'N/A' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ employee.email || 'No email' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex flex-wrap gap-2 max-w-[300px]  whitespace-normal break-words ">
                  {{ renderUniqueCompanies([...employee.employee_rates, ...employee.employee_rates_requests]) }} 
                </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                  <Button
                    @click="openConfirmSendModal(employee)"
                    :disabled="sendingMailId === employee.id"
                    variant="primary"
                    size="sm"
                    icon-left="mail"
                  >
                    Send
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div
          v-else-if="searchQuery && !searchLoading"
          class="text-center py-8 text-gray-500 dark:text-gray-400"
        >
          No employees found
        </div>
        <div
          v-else-if="!searchQuery"
          class="text-center py-8 text-gray-500 dark:text-gray-400"
        >
          Start typing to search for employees
        </div>
      </div>
    </Modal>

    <!-- Add Employee Modal -->
    <Modal
      v-model="addEmployeeModalVisible"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Submit"
      @close="closeAddEmployeeModal"
      @confirm="submitAddEmployee"
      :loading="addEmployeeLoading"
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0">Add Employee</h5>
      </template>
      <div class="space-y-4">
        <Input
          v-model="addEmployeeForm.employee_id"
          label="Employee ID"
          type="text"
          placeholder="Enter employee ID"
          icon-left="hash"
          :error="addEmployeeErrors.employee_id"
        />
        <Input
          v-model="addEmployeeForm.pos_name"
          label="Employee Name"
          type="text"
          placeholder="Enter employee name"
          icon-left="user"
          :error="addEmployeeErrors.pos_name"
        />
        <Input
          v-model="addEmployeeForm.email"
          label="Email"
          type="email"
          placeholder="Enter email address"
          icon-left="mail"
          :error="addEmployeeErrors.email"
        />
        <div class="flex flex-col gap-1.5">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Benefits State
            <span class="text-red-500">*</span>
          </label>
          <select
            v-model="addEmployeeForm.benefits_state"
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          >
            <option value="" disabled>Select state</option>
            <option
              v-for="state in SUPPORTED_BENEFIT_STATES"
              :key="state.id"
              :value="state.id"
            >
              {{ state.name }}
            </option>
          </select>
          <p v-if="addEmployeeErrors.benefits_state" class="text-sm text-red-600">{{ addEmployeeErrors.benefits_state }}</p>
        </div>
      </div>
    </Modal>

    <!-- Confirm Send Modal -->
    <Modal
      v-model="confirmSendModalVisible"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Send Mail"
      @close="closeConfirmSendModal"
      @confirm="confirmSendMail"
      :loading="sendMailLoading"
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0">Confirm Send Mail</h5>
      </template>
      <div v-if="selectedEmployee" class="space-y-4">
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <p class="font-medium text-gray-900 dark:text-white mb-2">
            {{ selectedEmployee.pos_name || selectedEmployee.aliases?.[0]?.alias_name }}
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            ID: {{ selectedEmployee.employee_id || selectedEmployee.aliases?.[0]?.alias_employee_id }}
          </p>
        </div>
        <Input
          v-model="selectedEmail"
          label="Email Address"
          type="email"
          placeholder="Enter email address"
          icon-left="mail"
          :error="emailError"
        />
        <div class="flex flex-col gap-1.5">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Benefits State
            <span class="text-red-500">*</span>
          </label>
          <select
            v-model="selectedBenefitsState"
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          >
            <option value="" disabled>Select state</option>
            <option
              v-for="state in SUPPORTED_BENEFIT_STATES"
              :key="state.id"
              :value="state.id"
            >
              {{ state.name }}
            </option>
          </select>
          <p v-if="benefitsStateError" class="text-sm text-red-600">{{ benefitsStateError }}</p>
        </div>
      </div>
    </Modal>

    <Filterable
      ref="filterableRef"
      title="Benefits Mail Tracking"
      url="onboarding/benefits"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
      :show-search="true"

    >
      <template #extra>
        <div class="flex items-center gap-2">
          <Button
            v-if="can('benefits', 'index')"
            icon-left="download"
            icon-size="sm"
            variant="secondary"
            size="sm"
            :disabled="isDownloading"
            @click="downloadExport"
          >
            {{ isDownloading ? 'Downloading...' : 'Export' }}
          </Button>
          <Button
            v-if="can('benefits', 'send-mail') && !loading"
            @click="openSendMailModal"
            icon-left="mail"
            icon-size="sm"
            variant="primary"
            size="sm"
          >
            Send Benefit Mail
          </Button>
        </div>
      </template>

      <template #heading>
        <tr>  
          <Th>No</Th>
          <Th>Employee ID</Th>
          <Th>Employee Name</Th>
          <Th>Email</Th>
          <Th>Company</Th>
          <Th>Mail Sent Date</Th>
          <Th>Status</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td color="secondary">
            {{ [item.employee_id, ...(item.aliases || []).map(a => a.alias_employee_id)].filter(Boolean).join(' / ') }}

          </Td>
          <Td weight="medium" color="primary">
            <router-link :to="`/employee/${item.id}`" target="_blank">
              {{ [item.pos_name, ...(item.aliases || []).map(a => a.alias_name)].filter(Boolean).join(' / ') }}
            </router-link>
          </Td>
          <Td color="secondary">
            {{ item.email || 'N/A' }}
          </Td>
          <Td color="secondary">
            <div class="flex flex-wrap gap-2 max-w-[300px]  whitespace-normal break-words ">
              {{ renderUniqueCompanies([...item.employee_rates, ...item.employee_rates_requests]) }} 
            </div>
          </Td>
          <Td color="secondary">
            {{ formatDate(item.benefit_mail_sent) }}
          </Td>
          <Td color="secondary">
            <div :class="benefitStatusClass(item)">
              {{ benefitStatusLabel(item) }}
              <small class="text-xs text-gray-500 dark:text-gray-400" v-if="item.benefit_submitted_at">
              (  {{ formatDate(item.benefit_submitted_at) }} )
              </small>
            </div>
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <Button
              v-if="can('employee', 'documents-show') && item.benefit_submitted_at && item.benefit_enrollment?.benefit_acknowledgment !== 'decline'"
                @click="downloadBenefitPDF(item)"
                :disabled="downloadingPdf === item.id"
                icon-left="download"
                icon-size="sm"
                variant="outline-secondary"
                size="sm"
                title="Download Benefit Enrollment PDF"
              >
                {{ downloadingPdf === item.id ? 'Downloading...' : 'Download' }}
              </Button>
              <IconMenuDropdown icon="more-vertical" icon-size="lg" title="More actions">
                <template #default="{ close }">
                  <button
                    v-if="can('benefits', 'send-mail')"
                    @click="openConfirmSendModal(item); close()"
                    class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                  >
                    <SvgIcon name="mail" size="sm" />
                    <span>Send Mail</span>
                  </button>
                  <router-link
                    :to="`/employee/${item.id}`"
                    target="_blank"
                    @click="close()"
                    class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                  >
                    <SvgIcon name="eye" size="sm" />
                    <span>View Employee</span>
                  </router-link>
                </template>
              </IconMenuDropdown>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate } from '@/utils/date'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import Button from '@/components/ui/button.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import { SUPPORTED_BENEFIT_STATES, resolveDefaultBenefitsState } from '@/utils/benefitStates'
const message = useMessage()
const route = useRoute()
const resource = route.meta?.resource || 'benefits'
const { can } = usePermission()
// Use the useIndexable composable
const { filterableRef } = useIndexable(resource, 'benefits')
const downloadingPdf = ref(null)
const isDownloading = ref(false)

// Send Mail Modal State
const sendMailModalVisible = ref(false)
const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const sendingMailId = ref(null)
let searchTimeout = null

// Add Employee Modal State
const addEmployeeModalVisible = ref(false)
const addEmployeeLoading = ref(false)
const addEmployeeForm = ref({
  employee_id: '',
  pos_name: '',
  email: '',
  benefits_state: '',
})
const addEmployeeErrors = ref({})

// Confirm Send Modal State
const confirmSendModalVisible = ref(false)
const selectedEmployee = ref(null)
const selectedEmail = ref('')
const selectedBenefitsState = ref('')
const sendMailLoading = ref(false)
const emailError = ref(null)
const benefitsStateError = ref(null)

// Sortable columns configuration
const sortableColumns = [
  { value: 'pos_name', label: 'Employee Name' },
  { value: 'employee_id', label: 'Employee ID' },
  { value: 'email', label: 'Email' },
  { value: 'benefit_mail_sent', label: 'Mail Sent Date' },
]

// Filter groups configuration
const filterGroups = [
  {
    title: 'Employee Information',
    filters: [
      {
        name: 'pos_name',
        title: 'Employee Name',
        type: 'string',
        placeholder: 'Enter employee name'
      },
      {
        name: 'employee_id',
        title: 'Employee ID',
        type: 'string',
        placeholder: 'Enter employee ID'
      },
      {
        name: 'email',
        title: 'Email',
        type: 'string',
        placeholder: 'Enter email'
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
        name: 'benefit_mail_sent',
        title: 'Mail Sent Date',
        type: 'datetime',
        placeholder: 'Select date'
      },
    ]
  }
]

// Open send mail modal
const openSendMailModal = () => {
  sendMailModalVisible.value = true
  searchQuery.value = ''
  searchResults.value = []
}

// Close send mail modal
const closeSendMailModal = () => {
  sendMailModalVisible.value = false
  searchQuery.value = ''
  searchResults.value = []
}


const renderUniqueCompanies = computed(() => {
  return (item) => {
    if (!item?.length) return 'N/A'

    const companies = item.map(rate => {
        const company = rate?.company
        if (!company) return null

        return company.name
      })
      .filter(Boolean)

    return [...new Set(companies)].join(', ')
  }
})

// Handle search with debounce
const handleSearch = () => {
  clearTimeout(searchTimeout)
  
  if (!searchQuery.value || searchQuery.value.trim().length < 2) {
    searchResults.value = []
    return
  }

  searchTimeout = setTimeout(async () => {
    searchLoading.value = true
    try {
      const response = await useRequest('get', 'onboarding/benefits/search',null, {
        params: {
          search: searchQuery.value
        }
      })
      searchResults.value = response?.data || []
    } catch (error) {
      message.error('Failed to search employees')
    } finally {
      searchLoading.value = false
    }
  }, 300)
}

const resetAddEmployeeForm = () => {
  addEmployeeForm.value = {
    employee_id: '',
    pos_name: '',
    email: '',
    benefits_state: '',
  }
  addEmployeeErrors.value = {}
}

const openAddEmployeeModal = () => {
  resetAddEmployeeForm()
  addEmployeeModalVisible.value = true
}

const closeAddEmployeeModal = () => {
  addEmployeeModalVisible.value = false
  resetAddEmployeeForm()
}

const submitAddEmployee = async () => {
  addEmployeeErrors.value = {}

  if (!addEmployeeForm.value.employee_id?.trim()) {
    addEmployeeErrors.value.employee_id = 'Employee ID is required'
  }
  if (!addEmployeeForm.value.pos_name?.trim()) {
    addEmployeeErrors.value.pos_name = 'Employee name is required'
  }
  if (!addEmployeeForm.value.email?.trim()) {
    addEmployeeErrors.value.email = 'Email is required'
  } else {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(addEmployeeForm.value.email)) {
      addEmployeeErrors.value.email = 'Please enter a valid email address'
    }
  }
  if (!addEmployeeForm.value.benefits_state) {
    addEmployeeErrors.value.benefits_state = 'Benefits state is required'
  }

  if (Object.keys(addEmployeeErrors.value).length > 0) {
    return
  }

  addEmployeeLoading.value = true

  try {
    const response = await useRequest('post', 'onboarding/benefits/employees', {
      employee_id: addEmployeeForm.value.employee_id.trim(),
      pos_name: addEmployeeForm.value.pos_name.trim(),
      email: addEmployeeForm.value.email.trim(),
      benefits_state: addEmployeeForm.value.benefits_state,
    })

    if (response?.success) {
      message.success('Employee created successfully')
      closeAddEmployeeModal()

      if (response.data) {
        searchResults.value = [response.data, ...searchResults.value.filter((item) => item.id !== response.data.id)]
        searchQuery.value = response.data.employee_id || response.data.pos_name || ''
      }

      if (filterableRef.value) {
        filterableRef.value.fetch()
      }
    } else {
      message.error(response?.message || 'Failed to create employee')
    }
  } catch (error) {
    const validationErrors = error?.response?.data?.errors
    if (validationErrors) {
      addEmployeeErrors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
      )
    }
    message.error(error?.response?.data?.message || 'Failed to create employee')
  } finally {
    addEmployeeLoading.value = false
  }
}

// Open confirm send modal
const openConfirmSendModal = (employee) => {
  selectedEmployee.value = employee
  selectedEmail.value = employee.email || ''
  selectedBenefitsState.value = resolveDefaultBenefitsState(employee)
  emailError.value = null
  benefitsStateError.value = null
  confirmSendModalVisible.value = true
}

// Close confirm send modal
const closeConfirmSendModal = () => {
  confirmSendModalVisible.value = false
  selectedEmployee.value = null
  selectedEmail.value = ''
  selectedBenefitsState.value = ''
  emailError.value = null
  benefitsStateError.value = null
}

// Confirm send mail
const confirmSendMail = async () => {
  if (!selectedEmployee.value) return

  emailError.value = null
  benefitsStateError.value = null

  if (!selectedEmail.value || !selectedEmail.value.trim()) {
    emailError.value = 'Email is required'
    return
  }

  // Basic email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(selectedEmail.value)) {
    emailError.value = 'Please enter a valid email address'
    return
  }

  if (!selectedBenefitsState.value) {
    benefitsStateError.value = 'Benefits state is required'
    return
  }

  sendMailLoading.value = true
  sendingMailId.value = selectedEmployee.value.id

  try {
    const response = await useRequest('post', 'onboarding/benefits/send', {
      employee_id: selectedEmployee.value.id,
      email: selectedEmail.value,
      benefits_state: selectedBenefitsState.value,
    })

    if (response?.success) {
      message.success('Benefit mail sent successfully')
      closeConfirmSendModal()
      closeSendMailModal()
      
      // Refresh the table
      if (filterableRef.value) {
        filterableRef.value.fetch()
      }
    } else {
      message.error(response?.message || 'Failed to send benefit mail')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to send benefit mail')
  } finally {
    sendMailLoading.value = false
    sendingMailId.value = null
  }
}

// Handle collection update from filterable component
const handleCollectionUpdate = (collection) => {
  // Handle collection updates if needed
}

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', 'onboarding/benefits-export', null, {
      params: currentParams,
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'benefits_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Benefits exported successfully')
  } catch (error) {
    message.error('Failed to export benefits')
  } finally {
    isDownloading.value = false
  }
}

const isBenefitDeclined = (item) => item?.benefit_enrollment?.benefit_acknowledgment === 'decline'

const benefitStatusLabel = (item) => {
  if (isBenefitDeclined(item)) return 'Declined'
  if (item.benefit_submitted_at) return 'Submitted'
  return 'Not Submitted'
}

const benefitStatusClass = (item) => {
  if (isBenefitDeclined(item)) return 'text-amber-500'
  if (item.benefit_submitted_at) return 'text-green-500'
  return 'text-red-500'
}

// Download benefit enrollment PDF
const downloadBenefitPDF = async (employee) => {
  downloadingPdf.value = employee.id
  try {
    const response = await useRequest('post', '/onboarding/benefits/download-current-year-benefit-enrollment-file', {
      employee_id: employee.id
    })

    if (response?.success && response?.url) {
      const link = document.createElement('a')
      link.href = response.url
      link.download = `Benefit_Enrollment_${employee.employee_id || employee.aliases?.[0]?.alias_employee_id}_2026-2027.pdf`
      link.target = '_blank'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      message.success('Downloading benefit enrollment PDF')
    } else {
      message.error(response?.message || 'Benefit enrollment PDF not found')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to download PDF')
  } finally {
    downloadingPdf.value = null
  }
}
</script>
