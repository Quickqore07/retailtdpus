<template>
  <div v-if="show" class="employee-hours-request-show space-y-3">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 justify-between">
            <h5 class="text-2xl font-bold !mb-0">Employee Hours Request Details</h5>
            <div
              class="text-sm text-gray-500 dark:text-gray-400 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-md"
              v-if="isNew"
            >
              New
            </div>
            <div
              class="text-sm text-gray-500 dark:text-gray-400 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-2 py-1 rounded-md"
              v-else
            >
              Changed
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/payroll/employee-hours-request"
            />
            <Button
              v-if="model.status === 'pending' && access.includes('update')"
              icon-left="check"
              icon-size="sm"
              variant="success"
              size="sm"
              @click="approveHoursRequest"
              :loading="approveLoading"
              :disabled="approveLoading"
            >
              Approve
            </Button>
            <Button
              v-if="model.status != 'approved' && access.includes('delete')"
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="sm"
              @click="deleteHoursRequest"
              :loading="deleteLoading"
              :disabled="deleteLoading"
            >
              Delete
            </Button>
          </div>
        </div>
      </template>

      <div class="space-y-6">
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Basic Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <Label
              label="Employee Name"
              :value="model.employee?.pos_name || model.employee_id || '-'"
            />
            <Label
              label="Company"
              :value="model.company?.name || model.company_id || '-'"
            />
            <Label label="Date">
              <div v-if="hasValueChanged(oldHours, model.date, oldHours?.date)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{ formatDate(oldHours.date) }}
                </div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">
                  {{ formatDate(model.date) }}
                </div>
              </div>
              <span v-else>{{ formatDate(model.date) }}</span>
            </Label>
            <Label label="Role">
              <div v-if="hasValueChanged(oldHours, model.role_id, oldHours?.role_id)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{
                    oldHours.role?.name
                      ? oldHours.role?.code + ' - ' + oldHours.role?.name
                      : oldHours.role_id || '-'
                  }}
                </div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">
                  {{
                    model.role?.name
                      ? model.role?.code + ' - ' + model.role?.name
                      : model.role_id || '-'
                  }}
                </div>
              </div>
              <span v-else>
                {{
                  model.role?.name
                    ? model.role?.code + ' - ' + model.role?.name
                    : '-'
                }}
              </span>
            </Label>
            <Label label="Pay Type">
              <div v-if="hasValueChanged(oldHours, model.pay_type, oldHours?.pay_type)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{ oldHours.pay_type || '-' }}
                </div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">
                  {{ model.pay_type || '-' }}
                </div>
              </div>
              <span v-else>{{ model.pay_type || '-' }}</span>
            </Label>
            <Label label="Total Hours">
              <div v-if="hasValueChanged(oldHours, model.total_hours, oldHours?.total_hours)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{ formatNumber(oldHours.total_hours) }}
                </div>
                <div :class="getNumericChangeClass(model.total_hours, oldHours.total_hours)">
                  {{ formatNumber(model.total_hours) }}
                </div>
              </div>
              <span v-else>{{ formatNumber(model.total_hours) }}</span>
            </Label>
            <Label label="Pay Rate">
              <div v-if="hasValueChanged(oldHours, model.pay_rate, oldHours?.pay_rate)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{ oldHours.pay_rate != null ? formatCurrency(oldHours.pay_rate) : '-' }}
                </div>
                <div :class="getNumericChangeClass(model.pay_rate, oldHours.pay_rate)">
                  {{ model.pay_rate != null ? formatCurrency(model.pay_rate) : '-' }}
                </div>
              </div>
              <span v-else>
                {{ model.pay_rate != null ? formatCurrency(model.pay_rate) : '-' }}
              </span>
            </Label>
            <Label label="Tips">
              <div v-if="hasValueChanged(oldHours, model.tips, oldHours?.tips)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{ oldHours.tips != null ? formatCurrency(oldHours.tips) : '-' }}
                </div>
                <div :class="getNumericChangeClass(model.tips, oldHours.tips)">
                  {{ model.tips != null ? formatCurrency(model.tips) : '-' }}
                </div>
              </div>
              <span v-else>
                {{ model.tips != null ? formatCurrency(model.tips) : '-' }}
              </span>
            </Label>
            <Label label="Tips Due">
              <div v-if="hasValueChanged(oldHours, model.tips_due, oldHours?.tips_due)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{ oldHours.tips_due != null ? formatCurrency(oldHours.tips_due) : '-' }}
                </div>
                <div :class="getNumericChangeClass(model.tips_due, oldHours.tips_due)">
                  {{ model.tips_due != null ? formatCurrency(model.tips_due) : '-' }}
                </div>
              </div>
              <span v-else>
                {{ model.tips_due != null ? formatCurrency(model.tips_due) : '-' }}
              </span>
            </Label>
            <Label label="Mileage">
              <div v-if="hasValueChanged(oldHours, model.mileage_excess, oldHours?.mileage_excess)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{
                    oldHours.mileage_excess != null
                      ? formatCurrency(oldHours.mileage_excess)
                      : '-'
                  }}
                </div>
                <div :class="getNumericChangeClass(model.mileage_excess, oldHours.mileage_excess)">
                  {{
                    model.mileage_excess != null
                      ? formatCurrency(model.mileage_excess)
                      : '-'
                  }}
                </div>
              </div>
              <span v-else>
                {{
                  model.mileage_excess != null
                    ? formatCurrency(model.mileage_excess)
                    : '-'
                }}
              </span>
            </Label>
            <Label label="Mileage Due">
              <div v-if="hasValueChanged(oldHours, model.mileage_due, oldHours?.mileage_due)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
                  {{
                    oldHours.mileage_due != null
                      ? formatCurrency(oldHours.mileage_due)
                      : '-'
                  }}
                </div>
                <div :class="getNumericChangeClass(model.mileage_due, oldHours.mileage_due)">
                  {{
                    model.mileage_due != null
                      ? formatCurrency(model.mileage_due)
                      : '-'
                  }}
                </div>
              </div>
              <span v-else>
                {{
                  model.mileage_due != null ? formatCurrency(model.mileage_due) : '-'
                }}
              </span>
            </Label>
            <Label label="HR Approved By" v-if="model.hr_approved_by">
              {{ model.hr_approved_by?.name }}
              ({{ formatDateTime(model.hr_approved_at) }})
            </Label>
            <Label label="DO Approved By" v-if="model.do_approved_by">
              {{ model.do_approved_by?.name }}
              ({{ formatDateTime(model.do_approved_at) }})
            </Label>
            <Label label="Admin Approved By" v-if="model.admin_approved_by">
              {{ model.admin_approved_by?.name }}
              ({{ formatDateTime(model.admin_approved_at) }})
            </Label>
          </div>
        </div>
      </div>
    </Panel>
  </div>

  <div
    v-else
    class="flex items-center justify-center min-h-[400px]"
  >
    <Spinner size="md" text="Loading employee hours request details..." centered />
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate, formatDateTime } from '@/utils/date'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { ref, computed } from 'vue'

const router = useRouter()
const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-hours-request'
const message = useMessage()
const { model, show, setData, access, removeDB } = useShowable(
  resource,
  'employee-hours-request'
)
const approveLoading = ref(false)
const deleteLoading = ref(false)

const oldHours = computed(() => model.value?.employee_hour || false)

const hasValueChanged = (oldData, newValue, oldValue) => {
  if (oldData) {
    return newValue !== oldValue
  }
  return false
}

const getNumericChangeClass = (newValue, oldValue) => {
  if (!oldValue && oldValue !== 0) {
    return 'text-orange-600 dark:text-orange-400 font-semibold'
  }
  const newNum = parseFloat(newValue)
  const oldNum = parseFloat(oldValue)
  if (isNaN(newNum) || isNaN(oldNum)) {
    return 'text-orange-600 dark:text-orange-400 font-semibold'
  }
  if (newNum > oldNum) {
    return 'text-green-600 dark:text-green-400 font-semibold'
  }
  if (newNum < oldNum) {
    return 'text-red-600 dark:text-red-400 font-semibold'
  }
  return ''
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}

const formatNumber = (value) => {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

const approveHoursRequest = async () => {
  approveLoading.value = true
  try {
    const response = await useRequest(
      'post',
      `${resource}/${model.value.id}/approve`
    )
    if (response.success) {
      message.success('Hours request approved successfully')
      router.push('/payroll/employee-hours-request')
    } else {
      message.error('Failed to approve hours request')
    }
  } catch (error) {
    console.error(error)
    message.error('Failed to approve hours request')
  } finally {
    approveLoading.value = false
  }
}

const deleteHoursRequest = async () => {
  deleteLoading.value = true
  const success = await removeDB(resource, model.value.id)
  if (success) {
    message.success('Hours request deleted successfully')
    router.push('/payroll/employee-hours-request')
  } else {
    message.error('Failed to delete hours request')
  }
  deleteLoading.value = false
}
const isNew = computed(() => 
  oldHours.value.total_hours === model.value.total_hours &&
  oldHours.value.pay_rate === model.value.pay_rate &&
  oldHours.value.tips === model.value.tips &&
  oldHours.value.tips_due === model.value.tips_due &&
  oldHours.value.mileage_excess === model.value.mileage_excess &&
  oldHours.value.mileage_due === model.value.mileage_due &&
  oldHours.value.role_id === model.value.role_id &&
  oldHours.value.company_id === model.value.company_id

)

defineExpose({ setData })
</script>

<style scoped>
@media (max-width: 640px) {
  .employee-hours-request-show {
    padding: 1rem;
  }
}
</style>
