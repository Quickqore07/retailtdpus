<template>
    <div v-if="show" class="employee-rate-request-show space-y-3">
      <!-- Main Information Panel -->
      <Panel :divider="true">
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 justify-between">
              <h5 class="text-2xl font-bold !mb-0">Employee Rate Request Details</h5>
              <div class="text-sm text-gray-500 dark:text-gray-400 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-md" v-if="!oldRate">
                New
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-2 py-1 rounded-md" v-else>
                Changed
              </div>
            </div>
            <div class="flex items-center  gap-2">
              <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/onboarding/employee-rate-request" />
              <Button
                v-if="model.status === 'pending' && access.includes('update')"
                icon-left="edit"
                icon-size="sm"
                variant="primary"
                size="sm"
                :to="`/onboarding/employee-rate-request/${model.id}/edit`"
              >
                Edit
              </Button>

              <Button
                v-if="model.status === 'pending' && access.includes('approve')"
                icon-left="check"
                icon-size="sm"
                variant="success"
                size="sm"
                @click="approveRateRequest"
                :loading="approveLoading"
                :disabled="approveLoading"
            >
                Approve
            </Button>
            <Button
                v-if="model.status === 'pending' && access.includes('approve')"
                icon-left="x"
                icon-size="sm"
                variant="danger"
                size="sm"
                @click="openRejectModal"
                :loading="rejectSubmitting"
                :disabled="rejectSubmitting || approveLoading"
            >
                Reject
            </Button>
            </div>
          </div>
        </template>
  
        <div class="space-y-6">
          <div
            v-if="model.employee_id && loadingEmployeeProfile"
            class="flex items-center gap-2 py-2 text-sm text-gray-600 dark:text-gray-400"
          >
            <Spinner size="sm" text="" />
            <span>Loading employee record…</span>
          </div>

          <div
            v-else-if="showOnboardingEmployeeDetails"
            class="space-y-6 pb-6 border-b border-gray-200 dark:border-gray-700"
          >
            <div>
              <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-1">
                Employee record
              </h6>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Onboarding employee — full record (names, contact, and rates) for reference while reviewing this rate request.
              </p>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Label label="Employee ID" :value="employeeDetails.employee_id" />
                <Label label="POS Name" :value="employeeDetails.pos_name" />
                <Label label="Hire Date" :value="formatDate(employeeDetails.hire_date)" />
                <Label label="SSN" :value="employeeDetails.ssn" />
                <Label label="Workgroup" :value="employeeDetails.workgroup?.name" />
                <Label label="Onboarding status" :value="employeeDetails.onboarding_status" />
                <Label label="Employee type" :value="employeeDetails.employee_type" />
                <div class="md:col-span-2 lg:col-span-4">
                  <Label label="Profile picture">
                    <img
                      v-if="employeeDetails.profile_picture_url"
                      :src="employeeDetails.profile_picture_url"
                      alt="Profile"
                      title="Open full size in new tab"
                      class="h-20 w-20 object-cover border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:opacity-90"
                      @click="openProfilePictureFullSize(employeeDetails.profile_picture_url)"
                    />
                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">—</span>
                  </Label>
                </div>
              </div>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
              <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Employee names</h6>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Label label="First name" :value="employeeDetails.first_name" />
                <Label label="Middle name" :value="employeeDetails.middle_name" />
                <Label label="Last name" :value="employeeDetails.last_name" />
                <Label label="Check name" :value="employeeDetails.check_name" />
              </div>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
              <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Employee aliases</h6>
              <div v-if="employeeDetails.aliases?.length" class="space-y-3">
                <div
                  v-for="(alias, index) in employeeDetails.aliases"
                  :key="'alias-' + index"
                  class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                  <Label label="Alias Employee ID" :value="alias.alias_employee_id" />
                  <Label label="Alias Name" :value="alias.alias_name" />
                </div>
              </div>
              <p v-else class="text-sm text-gray-500 dark:text-gray-400">No aliases</p>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
              <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Contact information</h6>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Label label="Phone" :value="employeeDetails.phone" />
                <Label label="Email" :value="employeeDetails.email" />
                <Label label="Date of birth" :value="formatDate(employeeDetails.dob)" />
                <Label
                  label="Termination date"
                  :value="employeeDetails.termination_date ? formatDate(employeeDetails.termination_date) : '—'"
                />
                <Label label="Street address" :value="employeeDetails.street" />
                <Label label="City" :value="employeeDetails.city" />
                <Label label="State" :value="employeeDetails.state" />
                <Label label="ZIP code" :value="employeeDetails.zip" />
                <Label label="Emergency contact name" :value="employeeDetails.emergency_contact_name" />
                <Label label="Emergency contact phone" :value="employeeDetails.emergency_contact_phone" />
                <Label
                  label="Emergency contact relationship"
                  :value="employeeDetails.emergency_contact_relationship"
                />
              </div>
            </div>
            <div
              v-if="onboardingEmployeeRates.length > 0"
              class="border-t border-gray-200 dark:border-gray-700 pt-4"
            >
              <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Employee rates on file</h6>
              <div class="space-y-3">
                <div
                  v-for="(rate, idx) in onboardingEmployeeRates"
                  :key="'ob-rate-' + idx"
                  class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
                >
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Label label="Company" :value="rate.company?.name" />
                    <Label label="Effective date" :value="formatDate(rate.effective_date)" />
                    <Label
                      label="Role"
                      :value="
                        rate.role?.name
                          ? (rate.role?.code ?? '') + ' - ' + rate.role.name
                          : (rate.role_id ?? '—')
                      "
                    />
                    <Label label="Rate" :value="formatCurrency(rate.rate)" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Basic Information -->
          <div>
            <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
              Basic Information
            </h6>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <Label label="Employee Name"  >
                    <a :href="`/employee/${model.employee_id}`" target="_blank">{{ model.employee?.pos_name || model.employee_id || '-' }}</a>
                </Label>
                <Label label="Company" :value="model.company?.name || model.company_id || '-'" />
                <Label label="Effective Date">
                    <div v-if="hasValueChanged(oldRate, model.effective_date, oldRate?.effective_date)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ formatDate(oldRate.effective_date) }}
                      </div>
                      <div class="text-orange-600 dark:text-orange-400 font-semibold">
                        {{ formatDate(model.effective_date) }}
                      </div>
                    </div>
                    <span v-else>
                      {{ formatDate(model.effective_date) }}
                    </span>
                </Label>
                <Label label="Till Date">
                    <div v-if="hasValueChanged(oldRate, model.till_date, oldRate?.till_date)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.till_date ? formatDate(oldRate.till_date) : '-' }}
                      </div>
                      <div class="text-orange-600 dark:text-orange-400 font-semibold">
                        {{ model.till_date ? formatDate(model.till_date) : '-' }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.till_date ? formatDate(model.till_date) : '-' }}
                    </span>
                </Label>
                <Label label="Role">
                    <div v-if="hasValueChanged(oldRate, model.role_id, oldRate?.role_id)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.role?.name ? oldRate.role?.code + ' - ' + oldRate.role?.name : oldRate.role_id || '-' }}
                      </div>
                      <div class="text-orange-600 dark:text-orange-400 font-semibold">
                        {{ model.role?.name ? model.role?.code + ' - ' + model.role?.name : model.role_id || '-' }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.role?.name ? model.role?.code + ' - ' + model.role?.name : model.role_id || '-' }}
                    </span>
                </Label>
                <Label label="Pay Type">
                    <div v-if="hasValueChanged(oldRate, model.pay_type, oldRate?.pay_type)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.pay_type }}
                      </div>
                      <div class="text-orange-600 dark:text-orange-400 font-semibold">
                        {{ model.pay_type }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.pay_type }}
                    </span>
                </Label>
                <Label label="Rate Type">
                    <div v-if="hasValueChanged(oldRate, model.rate_type, oldRate?.rate_type)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.rate_type }}
                      </div>
                      <div class="text-orange-600 dark:text-orange-400 font-semibold">
                        {{ model.rate_type }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.rate_type }}
                    </span>
                </Label>
                <Label label="Payroll Type" v-if="rateTypesWithPayrollType.includes(model.rate_type)">
                    <div v-if="hasValueChanged(oldRate, model.payroll_type, oldRate?.payroll_type)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.payroll_type }}
                      </div>
                      <div class="text-orange-600 dark:text-orange-400 font-semibold">
                        {{ model.payroll_type }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.payroll_type }}
                    </span>
                </Label>
                <Label 
                    v-if="model.rate_type === 'Payroll Slab' || model.rate_type === '1099 Slab' || model.rate_type === 'Payroll 1099' || model.rate_type === '1099 1099'" 
                    label="Slab First Hours"
                >
                    <div v-if="hasValueChanged(oldRate, model.slab_first_hours, oldRate?.slab_first_hours)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.slab_first_hours }}
                      </div>
                      <div :class="getNumericChangeClass(model.slab_first_hours, oldRate.slab_first_hours)">
                        {{ model.slab_first_hours }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.slab_first_hours }}
                    </span>
                </Label>
                <Label label="Rate">
                    <div v-if="hasValueChanged(oldRate, model.rate, oldRate?.rate)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ formatCurrency(oldRate.rate) }}
                      </div>
                      <div :class="getNumericChangeClass(model.rate, oldRate.rate)">
                        {{ formatCurrency(model.rate) }}
                      </div>
                    </div>
                    <span v-else>
                      {{ formatCurrency(model.rate) }}
                    </span>
                </Label>
                <Label 
                    v-if="model.rate_type === 'Payroll Slab' || model.rate_type === '1099 Slab' || model.rate_type === 'Payroll 1099' || model.rate_type === '1099 1099'" 
                    label="Slab Rest Rate"
                >
                    <div v-if="hasValueChanged(oldRate, model.slab_rest_rate, oldRate?.slab_rest_rate)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ formatCurrency(oldRate.slab_rest_rate) }}
                      </div>
                      <div :class="getNumericChangeClass(model.slab_rest_rate, oldRate.slab_rest_rate)">
                        {{ formatCurrency(model.slab_rest_rate) }}
                      </div>
                    </div>
                    <span v-else>
                      {{ formatCurrency(model.slab_rest_rate) }}
                    </span>
                </Label>
                <Label v-if="model.rate_type === 'Payroll 1099'"
                    label="Payroll Rate"
                >
                    <div v-if="hasValueChanged(oldRate, model.payroll_rate, oldRate?.payroll_rate)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ formatCurrency(oldRate.payroll_rate) }}
                      </div>
                      <div :class="getNumericChangeClass(model.payroll_rate, oldRate.payroll_rate)">
                        {{ formatCurrency(model.payroll_rate) }}
                      </div>
                    </div>
                    <span v-else>
                      {{ formatCurrency(model.payroll_rate) }}
                    </span>
                </Label>
                <Label v-if="model.rate_type === '1099 1099'" label="1099 Rate">
                    <div v-if="hasValueChanged(oldRate, model.ten99_rate, oldRate?.ten99_rate)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ formatCurrency(oldRate.ten99_rate) }}
                      </div>
                      <div :class="getNumericChangeClass(model.ten99_rate, oldRate.ten99_rate)">
                        {{ formatCurrency(model.ten99_rate) }}
                      </div>
                    </div>
                    <span v-else>
                      {{ formatCurrency(model.ten99_rate) }}
                    </span>
                </Label>
                <Label 
                    v-if="model.rate_type === 'Payroll Slab'" 
                    label="Payroll Hours"
                >
                    <div v-if="hasValueChanged(oldRate, model.payroll_hours, oldRate?.payroll_hours)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.payroll_hours ? ( oldRate.payroll_hours + ' ' + (oldRate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }}
                      </div>
                      <div :class="getNumericChangeClass(model.payroll_hours, oldRate.payroll_hours)">
                        {{ model.payroll_hours ? ( model.payroll_hours + ' ' + (model.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.payroll_hours ? ( model.payroll_hours + ' ' + (model.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }}
                    </span>
                </Label>
                <Label label="Check Payment" v-if="rateTypesWithCheckPayment.includes(model.rate_type)">
                    <div v-if="hasValueChanged(oldRate, model.check_payment_amount, oldRate?.check_payment_amount)">
                      <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                        {{ oldRate.check_payment_type === 'percentage' ? `${oldRate.check_payment_amount}%` : formatCurrency(oldRate.check_payment_amount) }}
                      </div>
                      <div :class="getNumericChangeClass(model.check_payment_amount, oldRate.check_payment_amount)">
                        {{ model.check_payment_type === 'percentage' ? `${model.check_payment_amount}%` : formatCurrency(model.check_payment_amount) }}
                      </div>
                    </div>
                    <span v-else>
                      {{ model.check_payment_type === 'percentage' ? `${model.check_payment_amount}%` : formatCurrency(model.check_payment_amount) }}
                    </span>
                </Label>
                <div
                  v-if="(model.rate_type === '1099 Regular' || model.rate_type === 'Payroll Regular') && model.rate"
                  class="md:col-span-2 lg:col-span-4"
                >
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    <span class="font-bold">Note:</span>
                    Overtime will be calculated at 1.5× the regular rate ({{ (Number(model.rate) || 0) * 1.5 }}).
                  </p>
                </div>
                <div
                  v-if="model.rate_type === 'Payroll Slab' && model.payroll_hours != null"
                  class="md:col-span-2 lg:col-span-4"
                >
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    <span class="font-bold">Note:</span>
                    Any hours worked beyond {{ model.payroll_hours }} will be paid as 1099.
                  </p>
                </div>
                <Label label="HR Approved By">
                    <div v-if="model.hr_approved_by">
                      {{ model.hr_approved_by?.name }} ({{ formatDateTime(model.hr_approved_at) }})
                    </div>
                </Label>
                <Label label="DO Approved By">
                    <div v-if="model.do_approved_by">
                      {{ model.do_approved_by?.name }} ({{ formatDateTime(model.do_approved_at) }})
                    </div>
                </Label>
                <Label label="Admin Approved By">
                    <div v-if="model.admin_approved_by">
                      {{ model.admin_approved_by?.name }} ({{ formatDateTime(model.admin_approved_at) }})
                    </div>
                </Label>
            </div>
          </div>
        </div>
      </Panel>
  
    </div>
  
    <!-- Loading State -->
    <div v-else class="flex items-center justify-center min-h-[400px]">
      <Spinner size="md" text="Loading employee rate request details..." centered />
    </div>

    <Modal
      v-model="rejectModalOpen"
      title="Reject rate request"
      size="lg"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Reject"
      :loading="rejectSubmitting"
      @confirm="submitReject"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
        Enter a reason. It will be included in the email sent to the user who last updated this employee record.
      </p>
      <Textarea
        v-model="rejectReason"
        label="Reason"
        :rows="4"
        placeholder="Explain why this request is being rejected…"
        required
      />
    </Modal>
  </template>
  
  <script setup>
  import { useRoute } from 'vue-router'
  import { useShowable } from '@/composables/useShowable'
  import Panel from '@/components/ui/panel.vue'
  import Button from '@/components/ui/button.vue'
  import Label from '@/components/ui/label.vue'
  import Spinner from '@/components/ui/spinner.vue'
  import Modal from '@/components/common/Modal.vue'
  import Textarea from '@/components/ui/textarea.vue'
  import { formatDate, formatDateTime } from '@/utils/date'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
  const router = useRouter()
  const route = useRoute()
  const resource = route.meta?.resource || 'onboarding/employee-rate-request'
  const message = useMessage()
  // Use the useShowable composable
  const { model, show, setData, access } = useShowable(resource, 'employee-rate-request')
  const approveLoading = ref(false)
  const rejectModalOpen = ref(false)
  const rejectReason = ref('')
  const rejectSubmitting = ref(false)
  // Utility functions for formatting
  const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '$0.00'
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount)
  }
  
// Rate types that display Payroll Type (must match form.vue)
const rateTypesWithPayrollType = ['Payroll Regular', 'Payroll Slab', 'Payroll 1099']

// Check payment visibility (must match form.vue)
const rateTypesWithCheckPayment = ['1099 Regular', '1099 Slab', 'Payroll Slab', 'Payroll 1099']

const employeeDetails = ref(null)
const loadingEmployeeProfile = ref(false)

const showOnboardingEmployeeDetails = computed(() => {
  const e = employeeDetails.value
  if (!e?.id || loadingEmployeeProfile.value) return false
  return (
    e.employee_type === 'New' ||
    e.employee_type === 'Rate Approval' || !e.first_name || !e.last_name || !e.check_name)
})

const onboardingEmployeeRates = computed(() => {
  const rates = employeeDetails.value?.employee_rates
  return Array.isArray(rates) ? rates : []
})

function openProfilePictureFullSize(url) {
  if (!url) return
  window.open(url, '_blank', 'noopener,noreferrer')
}

async function loadEmployeeDetails(employeeId) {
  if (!employeeId) {
    employeeDetails.value = null
    return
  }
  loadingEmployeeProfile.value = true
  try {
    const { data } = await axios.get(`/api/employee/${employeeId}`)
    employeeDetails.value = data?.model ?? null
  } catch {
    employeeDetails.value = null
  } finally {
    loadingEmployeeProfile.value = false
  }
}

watch(
  () => model.value?.employee_id,
  (id) => {
    loadEmployeeDetails(id)
  },
  { immediate: true }
)

// Get old rate data
const oldRate = computed(() => model.value.employee_rate || false)

// Helper function to check if value has changed
const hasValueChanged = (oldRate, newValue, oldValue) => {
  if(oldRate) {
    return newValue !== oldValue
  }
  return false
}

// Helper function to get color class for numeric value changes
const getNumericChangeClass = (newValue, oldValue) => {
  if (!oldValue && oldValue !== 0) {
    return ''
  }
  
  const newNum = parseFloat(newValue)
  const oldNum = parseFloat(oldValue)
  
  if (isNaN(newNum) || isNaN(oldNum)) {
    return 'text-orange-600 dark:text-orange-400 font-semibold'
  }
  
  if (newNum > oldNum) {
    return 'text-green-600 dark:text-green-400 font-semibold'
  } else if (newNum < oldNum) {
    return 'text-red-600 dark:text-red-400 font-semibold'
  }
  
  return ''
}

const openRejectModal = () => {
  rejectReason.value = ''
  rejectModalOpen.value = true
}

const submitReject = async () => {
  const reason = (rejectReason.value || '').trim()
  if (!reason) {
    message.error('Please enter a rejection reason.')
    return
  }
  if (!model.value?.id) return
  rejectSubmitting.value = true
  try {
    const response = await useRequest('post', `${resource}/${model.value.id}/reject`, { reason })
    if (response.success) {
      message.success('Rate request rejected.')
      rejectModalOpen.value = false
      router.push('/onboarding/employee-rate-request')
    } else {
      message.error(response.message || 'Failed to reject rate request')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to reject rate request')
  } finally {
    rejectSubmitting.value = false
  }
}

// Expose setData for useShowable route guards
defineExpose({
  setData
})

  const approveRateRequest = async () => {
    approveLoading.value = true
    try {
        const response = await useRequest('post', `${resource}/${model.value.id}/approve`)
        if(response.success) {
          message.success('Rate request approved successfully')
          router.push('/onboarding/employee-rate-request')
        } else {
          message.error('Failed to approve rate request')
        }

    } catch (error) {
      console.error(error)
      message.error('Failed to approve rate request')
    } finally {
      approveLoading.value = false
    }
  }
  </script>
  
  <style scoped>
  @media (max-width: 640px) {
    .employee-rate-request-show {
      padding: 1rem;
    }
  }
  </style>
  