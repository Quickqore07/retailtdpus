<template>
  <div v-if="show" class="employee-inactive-show">
    <!-- Header Panel -->
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <div>
            <h5 class="font-bold !mb-1">Employee Inactive Period Details</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              View employee inactive period information
            </p>
          </div>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/payroll/employee-inactive" />
            <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
              :to="`/payroll/employee-inactive/${model.id}/edit`" />
            <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete"
              v-if="access.includes('delete')" />
          </div>
        </div>
      </template>

      <!-- Employee Information -->
      <div class="space-y-6">
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Employee Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Label label="Employee ID" :value="model.employee?.employee_id || 'N/A'" />
            <Label label="Employee Name" :value="model.employee?.pos_name || 'N/A'" />
            <Label 
              label="Company" 
              :value="model.company?.name || 'N/A'" 
            />
          </div>
        </div>

        <!-- Inactive Period Information -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Inactive Period
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Label 
              label="From Date" 
              :value="formatDate(model.from_date)" 
              icon="calendar"
            />
            <Label 
              label="To Date" 
              :value="formatDate(model.to_date)" 
              icon="calendar"
            />
            <Label 
              label="Duration" 
              :value="calculateDuration(model.from_date, model.to_date)" 
              icon="clock"
            />
          </div>
        </div>

        <!-- Metadata -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Record Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Label 
              label="Created At" 
              :value="formatDate(model.created_at)" 
            />
            <Label 
              label="Updated At" 
              :value="formatDate(model.updated_at)" 
            />
          </div>
        </div>
      </div>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading details..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate } from '@/utils/date'
import Label from '@/components/ui/label.vue'

const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-inactive'

// Use the useShowable composable
const { model, show, setData, access, removeDB } = useShowable(
  resource,
  'employee-inactive'
)

// Calculate duration in days
const calculateDuration = (fromDate, toDate) => {
  if (!fromDate || !toDate) return 'N/A'
  const from = new Date(fromDate)
  const to = new Date(toDate)
  const diffTime = Math.abs(to - from)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return `${diffDays} days`
}

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}
// Expose setData for useShowable route guards
defineExpose({
  setData
})
</script>
