<template>
  <Modal
    v-model="isOpen"
    :title="title"
    size="lg"
    @close="handleClose"
  >
    <div class="space-y-4">
      <!-- EOW Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
          End of Week (EOW)
          <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.eow"
          class="form-select"
        >
          <option value="">Select EOW</option>
          <option v-for="period in availablePeriods" :key="period.value" :value="period.endDate">
            {{ period.label }}
          </option>
        </select>
        <p v-if="errors.eow" class="mt-1 text-sm text-red-600">{{ errors.eow }}</p>
      </div>

      <!-- Employee Selection -->
      <div>
        <DynamicDropdown
          v-model="selectedEmployee"
          label="Employee"
          :required="true"
          resource="employees"
          display-name="pos_name"
          placeholder="Select Employee"
          :disabled="loadingEmployees"
          :error="errors.employee_id"
          @change="onEmployeeChange"
        />
      </div>

      <!-- Company Selection -->
      <div>
        <DynamicDropdown
          v-model="selectedCompany"
          label="Company"
          :required="true"
          resource="companies"
          :params="{ employee_id: selectedEmployee?.id }"
          display-name="name"
            placeholder="Select Company"
          :disabled="!selectedEmployee"
          :error="errors.company_id"
          @change="onCompanyChange"
        />
      </div>

      <!-- Role Selection -->
      <div>
        <DynamicDropdown
          v-model="selectedRole"
          label="Role"
          :required="true"
          :custom-options="roles"
          display-name="name"
          placeholder="Select Role"
          :disabled="!selectedCompany || loadingRoles"
          :error="errors.role_id"
        />
      </div>

      <!-- Amount Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
          Amount ($)
          <span class="text-red-500">*</span>
        </label>
        <input
          v-model.number="form.amount"
          type="number"
          step="0.01"
          min="0"
          class="form-input"
          placeholder="Enter amount"
          @input="validateForm"
        />
        <p v-if="errors.amount" class="mt-1 text-sm text-red-600">{{ errors.amount }}</p>
      </div>

      <!-- Unique Constraint Warning -->
      <div v-if="duplicateWarning" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <div class="flex">
          <div class="flex-shrink-0">
            <SvgIcon name="alert-triangle" size="md" class="text-yellow-600 dark:text-yellow-400" />
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
              This combination of Employee, Company, Role, and EOW must be unique.
            </p>
          </div>
        </div>
      </div>
    </div>

      <div class="flex justify-end gap-3 mt-3">
        <Button
          variant="secondary"
          size="md"
          @click="handleClose"
        >
          {{ isViewMode ? 'Close' : 'Cancel' }}
        </Button>
        <Button
          v-if="!isViewMode"
          variant="primary"
          size="md"
          :loading="saving"
          :disabled="!isFormValid"
          @click="handleSave"
        >
          {{ isEditMode ? 'Update' : 'Save' }}
        </Button>
      </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Add MWA Entry'
  },
  mwaEntry: {
    type: Object,
    default: null
  },
  isEditMode: {
    type: Boolean,
    default: false
  },
  isViewMode: {
    type: Boolean,
    default: false
  },
  defaultEow: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'saved'])

const authStore = useAuthStore()
const message = useMessage()
const resource = 'payroll/mwa'

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const form = ref({
  employee_id: '',
  company_id: '',
  role_id: '',
  eow: '',
  amount: 0
})

const errors = ref({})
const companies = ref([])
const roles = ref([])
const selectedEmployee = ref(null)
const selectedCompany = ref(null)
const selectedRole = ref(null)
const loadingEmployees = ref(false)
const loadingCompanies = ref(false)
const loadingRoles = ref(false)
const saving = ref(false)
const duplicateWarning = ref(false)

const isDC = computed(() => {
  return authStore.isDC || false
})

const availablePeriods = computed(() => {
  const currentYear = new Date().getFullYear()
  const year = currentYear
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
      endDate: endDate.toISOString().split('T')[0]
    })

    startDate.setUTCDate(startDate.getUTCDate() + 14)
    periodNumber++
  }

  return periods
})

const isFormValid = computed(() => {
  return form.value.employee_id &&
         form.value.company_id &&
         form.value.role_id &&
         form.value.eow &&
         form.value.amount >= 0
})

const loadCompanies = async (employeeId) => {
  if (!employeeId) {
    companies.value = []
    return
  }

  try {
    loadingCompanies.value = true
    const response = await useRequest('get', `/${resource}/employees/${employeeId}/companies`)
    companies.value = response.data || []
  } catch (error) {
    console.error(error)
    message.error('Failed to load companies')
  } finally {
    loadingCompanies.value = false
  }
}

const loadRoles = async (employeeId, companyId) => {
  if (!employeeId || !companyId) {
    roles.value = []
    return
  }

  try {
    loadingRoles.value = true
    const response = await useRequest('get', `/${resource}/employees/${employeeId}/companies/${companyId}/roles`)
    roles.value = response.data || []
  } catch (error) {
    console.error(error)
    message.error('Failed to load roles')
  } finally {
    loadingRoles.value = false
  }
}

const onEmployeeChange = (employee) => {
  form.value.employee_id = employee?.id || ''
  form.value.company_id = ''
  form.value.role_id = ''
  selectedCompany.value = null
  selectedRole.value = null
  companies.value = []
  roles.value = []
  errors.value = {}
  
  if (form.value.employee_id) {
    loadCompanies(form.value.employee_id)
  }
  
}

const onCompanyChange = (company) => {
  form.value.company_id = company?.id || ''
  form.value.role_id = ''
  selectedRole.value = null
  roles.value = []
  errors.value = {}
  
  if (form.value.employee_id && form.value.company_id) {
    loadRoles(form.value.employee_id, form.value.company_id)
  }
  
}

watch(selectedRole, (newRole) => {
  form.value.role_id = newRole?.id || ''
})

const validateForm = () => {
  errors.value = {}

  if (!form.value.eow) {
    errors.value.eow = 'EOW is required'
  }
  if (!form.value.employee_id) {
    errors.value.employee_id = 'Employee is required'
  }
  if (!form.value.company_id) {
    errors.value.company_id = 'Company is required'
  }
  if (!form.value.role_id) {
    errors.value.role_id = 'Role is required'
  }
  if (form.value.amount === '' || form.value.amount < 0) {
    errors.value.amount = 'Amount must be 0 or greater'
  }

  return Object.keys(errors.value).length === 0
}

const handleSave = async () => {
  if (!validateForm()) {
    return
  }

  try {
    saving.value = true
    
    const method = props.isEditMode ? 'put' : 'post'
    const url = props.isEditMode 
      ? `/${resource}/${props.mwaEntry.id}` 
      : `/${resource}`

    const response = await useRequest(method, url, form.value)

    if (response.saved) {
      message.success(response.message || 'MWA entry saved successfully')
      emit('saved')
      handleClose()
    }
  } catch (error) {
    console.error(error)
    if (error.response?.status === 422) {
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      }
      message.error(error.response?.data?.message || 'Validation error')
    } else {
      message.error(error.response?.data?.message || 'Failed to save MWA entry')
    }
  } finally {
    saving.value = false
  }
}

const handleClose = () => {
  form.value = {
    employee_id: '',
    company_id: '',
    role_id: '',
    eow: '',
    amount: 0
  }
  selectedEmployee.value = null
  selectedCompany.value = null
  selectedRole.value = null
  errors.value = {}
  duplicateWarning.value = false
  companies.value = []
  roles.value = []
  isOpen.value = false
}

watch(() => props.modelValue, async (newVal) => {
  if (newVal) {
    if ((props.isEditMode || props.isViewMode) && props.mwaEntry) {
      form.value = {
        employee_id: props.mwaEntry.employee_id,
        company_id: props.mwaEntry.company_id,
        role_id: props.mwaEntry.role_id,
        eow: props.mwaEntry.eow,
        amount: props.mwaEntry.amount
      }
      
      await loadCompanies(props.mwaEntry.employee_id)
      await loadRoles(props.mwaEntry.employee_id, props.mwaEntry.company_id)
      
      selectedEmployee.value = props.mwaEntry.employee || null
      selectedCompany.value = props.mwaEntry.company || null
      selectedRole.value = props.mwaEntry.role || null
    } else {
      form.value.eow = props.defaultEow || ''
    }
  }
})
</script>
