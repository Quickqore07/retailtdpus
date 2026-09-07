<template>
  <div v-if="show" class="employee-hours-show space-y-3">
    <!-- Main Information Panel -->
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <h5 class="text-2xl font-bold !mb-0">Employee Hours Details</h5>
            <div
              v-if="hasApprovers"
              class="flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm font-medium"
            >
              <SvgIcon name="check-circle" size="sm" class="text-green-600 dark:text-green-400" />
              <span>Approved</span>
            </div>
            <div
              v-if="!hasApprovers && request && request.status === 'pending'"
              class="flex items-center gap-2 px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-sm font-medium cursor-pointer hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
              @click="showPendingModal = true"
            >
              <SvgIcon name="clock" size="sm" class="text-red-600 dark:text-red-400" />
              <span>Pending Approval</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/payroll/employee-hours" />
            <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update') && !model.reviewed"
              :to="`/payroll/employee-hours/${model.id}/edit`" />
            <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete"
              v-if="access.includes('delete') && !model.reviewed" />
          </div>
        </div>
      </template>

      <div class="space-y-6">
        <!-- Basic Information -->
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Basic Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Label label="Date" :value="formatDate(model.date)" />
            <Label label="Employee ID" :value="model.employee?.employee_id" />
            <Label label="Employee Name" :value="model.employee_name || model.employee?.pos_name" />
            <Label label="Company" :value="model.company?.name" />
            <Label label="Role" :value="model.role?.name" />

          </div>
        </div>

        <!-- Hours and Rate -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Hours and Rate
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Pay Type" :value="model.pay_type" />
            <Label label="Total Hours">
              <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                {{ formatNumber(model.total_hours) }} hrs
              </span>
            </Label>
            <Label label="Pay Rate">
              <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                {{ formatCurrency(model.pay_rate) }}
              </span>
            </Label>
          </div>
        </div>

        <!-- Approved By (from request) -->
        <div
          v-if="request && hasApprovers"
          class="border-t border-gray-200 dark:border-gray-700 pt-3"
        >
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Approved By
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Label
              v-if="request.hrApprovedBy || request.hr_approved_by"
              label="HR Approved By"
              :value="`${(request.hrApprovedBy || request.hr_approved_by)?.name || '-'} (${formatDate(request.hr_approved_at)})`"
            />
            <Label
              v-if="request.doApprovedBy || request.do_approved_by"
              label="DO Approved By"
              :value="`${(request.doApprovedBy || request.do_approved_by)?.name || '-'} (${formatDate(request.do_approved_at)})`"
            />
            <Label
              v-if="request.adminApprovedBy || request.admin_approved_by"
              label="Admin Approved By"
              :value="`${(request.adminApprovedBy || request.admin_approved_by)?.name || '-'} (${formatDate(request.admin_approved_at)})`"
            />
          </div>
        </div>

        <!-- Additional Compensation -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Additional Compensation
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Tips" :value="formatCurrency(model.tips)" />
            <Label label="Tips Due" :value="formatCurrency(model.tips_due)" />
            <Label label="Mileage Excess" :value="formatCurrency(model.mileage_excess)" />
            <Label label="Mileage Due" :value="formatCurrency(model.mileage_due)" />
            <Label label="Incentive" :value="formatCurrency(model.incentive)" />
            <Label label="Bonus" :value="formatCurrency(model.bonus)" />
          </div>
        </div>

        <!-- Additional Information -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Additional Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="SSN" :value="model.ssn || '-'" />
            <Label label="Pay ID" :value="model.pay_id || '-'" />
            <Label label="Dev ID" :value="model.dev_id || '-'" />
            <Label label="Home Store" :value="model.home_store || '-'" />
            <Label label="Created At" :value="formatDate(model.created_at)" />
            <Label label="Updated At" :value="formatDate(model.updated_at)" />
          </div>
        </div>
      </div>
    </Panel>

    <!-- Pending Approval Modal -->
    <Modal
      v-model="showPendingModal"
      title="Pending Approval - Requested Data"
      size="xl"
      :show-footer="false"
      @close="showPendingModal = false"
    >
      <div v-if="request" class="space-y-6">
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Basic Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Label label="Date">
              <div v-if="hasValueChanged(oldHours, request.date, oldHours?.date)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatDate(oldHours.date) }}</div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">{{ formatDate(request.date) }}</div>
              </div>
              <span v-else>{{ formatDate(request.date) }}</span>
            </Label>
            <Label label="Employee">
              <div v-if="hasValueChanged(oldHours, (request.employee?.pos_name || request.employee_name), (oldHours.employee_name || oldHours.employee?.pos_name))">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ oldHours.employee_name || oldHours.employee?.pos_name || '-' }}</div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">{{ (request.employee?.pos_name || request.employee_name) || '-' }}</div>
              </div>
              <span v-else>{{ (request.employee?.pos_name || request.employee_name) || '-' }}</span>
            </Label>
            <Label label="Company">
              <div v-if="hasValueChanged(oldHours, request.company?.name, oldHours.company?.name)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ oldHours.company?.name || '-' }}</div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">{{ request.company?.name || '-' }}</div>
              </div>
              <span v-else>{{ request.company?.name || '-' }}</span>
            </Label>
            <Label label="Role">
              <div v-if="hasValueChanged(oldHours, request.role_id, oldHours.role_id)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ oldHours.role?.name || '-' }}</div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">{{ request.role?.name || '-' }}</div>
              </div>
              <span v-else>{{ request.role?.name || '-' }}</span>
            </Label>
          </div>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Hours and Rate
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Pay Type">
              <div v-if="hasValueChanged(oldHours, request.pay_type, oldHours.pay_type)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ oldHours.pay_type || '-' }}</div>
                <div class="text-orange-600 dark:text-orange-400 font-semibold">{{ request.pay_type || '-' }}</div>
              </div>
              <span v-else>{{ request.pay_type || '-' }}</span>
            </Label>
            <Label label="Total Hours">
              <div v-if="hasValueChanged(oldHours, request.total_hours, oldHours.total_hours)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatNumber(oldHours.total_hours) }}</div>
                <div :class="getNumericChangeClass(request.total_hours, oldHours.total_hours)">{{ formatNumber(request.total_hours) }}</div>
              </div>
              <span v-else>{{ formatNumber(request.total_hours) }}</span>
            </Label>
            <Label label="Pay Rate">
              <div v-if="hasValueChanged(oldHours, request.pay_rate, oldHours.pay_rate)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.pay_rate) }}</div>
                <div :class="getNumericChangeClass(request.pay_rate, oldHours.pay_rate)">{{ formatCurrency(request.pay_rate) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.pay_rate) }}</span>
            </Label>
          </div>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Additional Compensation
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Tips">
              <div v-if="hasValueChanged(oldHours, request.tips, oldHours.tips)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.tips) }}</div>
                <div :class="getNumericChangeClass(request.tips, oldHours.tips)">{{ formatCurrency(request.tips) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.tips) }}</span>
            </Label>
            <Label label="Tips Due">
              <div v-if="hasValueChanged(oldHours, request.tips_due, oldHours.tips_due)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.tips_due) }}</div>
                <div :class="getNumericChangeClass(request.tips_due, oldHours.tips_due)">{{ formatCurrency(request.tips_due) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.tips_due) }}</span>
            </Label>
            <Label label="Mileage Excess">
              <div v-if="hasValueChanged(oldHours, request.mileage_excess, oldHours.mileage_excess)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.mileage_excess) }}</div>
                <div :class="getNumericChangeClass(request.mileage_excess, oldHours.mileage_excess)">{{ formatCurrency(request.mileage_excess) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.mileage_excess) }}</span>
            </Label>
            <Label label="Mileage Due">
              <div v-if="hasValueChanged(oldHours, request.mileage_due, oldHours.mileage_due)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.mileage_due) }}</div>
                <div :class="getNumericChangeClass(request.mileage_due, oldHours.mileage_due)">{{ formatCurrency(request.mileage_due) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.mileage_due) }}</span>
            </Label>
            <Label label="Incentive">
              <div v-if="hasValueChanged(oldHours, request.incentive, oldHours.incentive)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.incentive) }}</div>
                <div :class="getNumericChangeClass(request.incentive, oldHours.incentive)">{{ formatCurrency(request.incentive) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.incentive) }}</span>
            </Label>
            <Label label="Bonus">
              <div v-if="hasValueChanged(oldHours, request.bonus, oldHours.bonus)">
                <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">{{ formatCurrency(oldHours.bonus) }}</div>
                <div :class="getNumericChangeClass(request.bonus, oldHours.bonus)">{{ formatCurrency(request.bonus) }}</div>
              </div>
              <span v-else>{{ formatCurrency(request.bonus) }}</span>
            </Label>
          </div>
        </div>
        <div v-if="request.notes" class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <Label label="Notes" :value="request.notes" />
        </div>
      </div>
    </Modal>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading employee hours details..." centered />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import Modal from '@/components/common/Modal.vue'
import { formatDateTime as formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-hours'
const showPendingModal = ref(false)

// Use the useShowable composable
const { model, show, setData, removeDB, access } = useShowable(resource, 'employee-hour')

// Approved hours request (from approved EmployeeHoursRequest)
const request = computed(() => model.value?.employeeHoursRequest || model.value?.employee_hours_request)

// For modal: model = current/original values, request = requested values
const oldHours = computed(() => model.value || null)

const hasValueChanged = (oldData, newValue, oldValue) => {
  if (oldData && (newValue !== undefined || oldValue !== undefined)) {
    return String(newValue ?? '') !== String(oldValue ?? '')
  }
  return false
}

const getNumericChangeClass = (newValue, oldValue) => {
  if (!oldValue && oldValue !== 0) return 'text-orange-600 dark:text-orange-400 font-semibold'
  const newNum = parseFloat(newValue)
  const oldNum = parseFloat(oldValue)
  if (isNaN(newNum) || isNaN(oldNum)) return 'text-orange-600 dark:text-orange-400 font-semibold'
  if (newNum > oldNum) return 'text-green-600 dark:text-green-400 font-semibold'
  if (newNum < oldNum) return 'text-red-600 dark:text-red-400 font-semibold'
  return ''
}

const hasApprovers = computed(() => {
  const req = request.value
  return req && (req.hrApprovedBy || req.hr_approved_by || req.doApprovedBy || req.do_approved_by || req.adminApprovedBy || req.admin_approved_by)
})

// Handle delete action
const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

// Utility functions for formatting
const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatNumber = (value) => {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

// Expose setData for useShowable route guards
defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .employee-hours-show {
    padding: 1rem;
  }
}
</style>
