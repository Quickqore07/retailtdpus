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
              <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/payroll/employee-new-rate-request" />
              <Button
                v-if="model.status === 'pending' && access.includes('update')"
                icon-left="edit"
                icon-size="sm"
                variant="primary"
                size="sm"
                :to="`/payroll/employee-new-rate-request/${model.id}/edit`"
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
                icon-left="trash"
                icon-size="sm"
                variant="danger"
                size="sm"
                @click="deleteRateRequest"
                :loading="deleteLoading"
                :disabled="deleteLoading || approveLoading"
            >
                Delete
            </Button>
            </div>
          </div>
        </template>
  
        <div class="space-y-6">
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

  </template>
  
  <script setup>
  import { useRoute } from 'vue-router'
  import { useShowable } from '@/composables/useShowable'
  import Panel from '@/components/ui/panel.vue'
  import Button from '@/components/ui/button.vue'
  import Label from '@/components/ui/label.vue'
  import Spinner from '@/components/ui/spinner.vue'
  import { formatDate, formatDateTime } from '@/utils/date'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
  const router = useRouter()
  const route = useRoute()
  const resource = route.meta?.resource || 'payroll/employee-new-rate-request'
  const message = useMessage()
  // Use the useShowable composable
  const { model, show, setData, access, removeDB } = useShowable(resource, 'employee-new-rate-request')
  const approveLoading = ref(false)
  const deleteLoading = ref(false)
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

const deleteRateRequest = async () => {
  if (!model.value?.id) return
  deleteLoading.value = true
  try {
    await removeDB(resource, model.value.id)
  } finally {
    deleteLoading.value = false
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
          router.push('/payroll/employee-new-rate-request')
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
  