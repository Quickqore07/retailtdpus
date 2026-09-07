<template>
  <div v-if="show">
    <!-- Form Panel -->
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Employee Inactive Period' : 'Create New Employee Inactive Period' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <!-- Basic Information Section -->
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Basic Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Employee -->
            <DynamicDropdown 
              label='Employee'
              v-model="form.employee" 
              :resource="`employees?${form.company?.id ? `company_id=${form.company?.id}` : ''}`" 
              display-name="pos_name" 
              placeholder="Select employee" 
              :required="true" 
              :error="errors.employee_id ? errors.employee_id[0] : null" 
              icon-left="user" 
            />

            <!-- Company -->
            <div>
              <InputLabel :required="true">Company</InputLabel>
              <DynamicDropdown 
                v-model="form.company" 
                :resource="`companies?${form.employee?.id ? `employee_id=${form.employee?.id}` : ''}`" 
                display-name="name"
                placeholder="Select company" 
                :required="true"
                :error="errors.company_id ? errors.company_id[0] : null"
              />
            </div>
          </div>
        </div>

        <!-- Date Range Section -->
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            Inactive Period
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- From Date -->
            <Input 
              v-model="form.from_date" 
              label="From Date" 
              type="date"
              placeholder="Select from date" 
              :required="true"
              :error="errors.from_date ? errors.from_date[0] : null" 
              icon-left="calendar" 
            />

            <!-- To Date -->
            <Input 
              v-model="form.to_date" 
              label="To Date" 
              type="date"
              placeholder="Select to date" 
              :required="true"
              :error="errors.to_date ? errors.to_date[0] : null" 
              icon-left="calendar" 
            />
          </div>

          <!-- Duration Display -->
          <div v-if="form.from_date && form.to_date" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="flex items-center gap-2">
              <SvgIcon name="info" size="md" class="text-blue-600 dark:text-blue-400" />
              <p class="text-sm text-blue-800 dark:text-blue-200 !mb-0">
                Duration: <strong>{{ calculateDuration() }} days</strong>
              </p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button 
            variant="primary" 
            size="md" 
            type="submit" 
            :loading="isSaving"
            v-if="mode === 'create' ? access.includes('create') : access.includes('update')"
          >
            {{ mode === 'edit' ? 'Update Entry' : 'Create Entry' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import InputLabel from '@/components/ui/inputLabel.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import SvgIcon from '@/components/SvgIcon.vue'

const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-inactive'

// Use the useFormable composable
const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource, 
  'payroll/employee-inactive', 
  'employee-inactive'
)

// Calculate duration in days
const calculateDuration = () => {
  if (!form.value.from_date || !form.value.to_date) return 0
  const from = new Date(form.value.from_date)
  const to = new Date(form.value.to_date)
  const diffTime = Math.abs(to - from)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
}

const handleSave = async () => {
  try {
    const obj = {
      ...form.value,
      company_id: form.value.company?.id,
      employee_id: form.value.employee?.id,
    }
    
    // Remove nested objects
    delete obj.employee
    delete obj.company

    await save(obj)
  } catch (error) {
    console.error(error)
  }
}


// Expose setData for useFormable route guards
defineExpose({
  setData
})
</script>

<style scoped>
textarea:focus,
select:focus,
input:focus {
  outline: none !important;
  outline-offset: 0 !important;
}
</style>
