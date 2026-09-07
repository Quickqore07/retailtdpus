<template>
  <div v-if="show">
    <!-- Form Panel -->
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Employee Hours' : 'Create New Employee Hours Entry' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <!-- Basic Information Section -->
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            Basic Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Date -->
            <Input 
              v-model="form.date" 
              label="Date" 
              type="date"
              placeholder="Select date" 
              :required="true"
              :error="errors.date ? errors.date[0] : null" 
              icon-left="calendar" 
            />
            <DynamicDropdown 
              label='Employee'
              v-model="form.employee" 
              resource="employees" 
              display-name="pos_name" 
              placeholder="Select employee" 
              :required="true" 
              :error="errors.employee_id ? errors.employee_id[0] : null" 
              icon-left="user" />

            <div>
              <InputLabel :required="true">Company</InputLabel>
              <DynamicDropdown 
                v-model="form.company" 
                resource="companies" 
                display-name="name"
                placeholder="Select company" 
                :required="true"
                :error="errors.company_id ? errors.company_id[0] : null"
              />
            </div>

            <!-- Role -->
            <div>
              <InputLabel :required="true">Role</InputLabel>
              <DynamicDropdown 
                v-model="form.role" 
                resource="employee-roles" 
                display-name="name"
                placeholder="Select role" 
                :required="true"
                :error="errors.role_id ? errors.role_id[0] : null"
              />
            </div>

            <!-- Pay Type -->
            <DynamicDropdown
                label="Pay Type"
                v-model="form.pay_type" 
                :custom-options="payTypes" 
                display-name="name"
                placeholder="Select pay type" 
                :required="true"
                :searchable="false"
                :error="errors.pay_type ? errors.pay_type[0] : null"
                />

            <!-- Total Hours -->
            <Input 
              v-model="form.total_hours" 
              label="Total Hours" 
              type="number"
              step="0.01"
              placeholder="Enter total hours" 
              :required="true"
              :error="errors.total_hours ? errors.total_hours[0] : null" 
              icon-left="clock" 
            />

            <!-- Pay Rate -->
            <Input 
              v-model="form.pay_rate" 
              label="Pay Rate" 
              type="number"
              step="0.01"
              placeholder="Enter pay rate" 
              :disabled="true"
              :required="true"
              :error="errors.pay_rate ? errors.pay_rate[0] : null" 
              icon-left="dollar" 
            />
          </div>
        </div>

        <!-- Additional Compensation Section -->
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            Additional Compensation
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tips -->
            <Input 
              v-model="form.tips" 
              label="Tips" 
              type="number"
              step="0.01"
              placeholder="Enter tips" 
              :error="errors.tips ? errors.tips[0] : null" 
              icon-left="dollar" 
            />

            <Input 
              v-model="form.tips_due" 
              label="Tips Due" 
              type="number"
              step="0.01"
              placeholder="Enter tips due" 
              :error="errors.tips_due ? errors.tips_due[0] : null" 
              icon-left="dollar" 
            />

            <!-- Mileage Excess -->
            <Input 
              v-model="form.mileage_excess" 
              label="Mileage Excess" 
              type="number"
              step="0.01"
              placeholder="Enter mileage excess" 
              :error="errors.mileage_excess ? errors.mileage_excess[0] : null" 
              icon-left="dollar" 
            />

            <Input 
              v-model="form.mileage_due" 
              label="Mileage Due" 
              type="number"
              step="0.01"
              placeholder="Enter mileage due" 
              :error="errors.mileage_due ? errors.mileage_due[0] : null" 
              icon-left="dollar" 
            />

           
            <!-- Incentive -->
            <Input 
              v-model="form.incentive" 
              label="Incentive" 
              type="number"
              step="0.01"
              placeholder="Enter incentive" 
              :error="errors.incentive ? errors.incentive[0] : null" 
              icon-left="dollar" 
            />

            <!-- Bonus -->
            <Input 
              v-model="form.bonus" 
              label="Bonus" 
              type="number"
              step="0.01"
              placeholder="Enter bonus" 
              :error="errors.bonus ? errors.bonus[0] : null" 
              icon-left="dollar" 
            />
          </div>
        </div>

        <!-- Additional Information Section -->
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            Additional Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          

            <!-- SSN (auto-filled) -->
            <Input 
              v-model="form.ssn" 
              label="SSN" 
              placeholder="XXX-XX-XXXX or 9 digits (auto-filled from employee)" 
              icon-left="shield" 
              :required="true"
              :disabled="true"
              :error="errors.ssn ? errors.ssn[0] : null"
            />

            <!-- Pay ID -->
            <Input 
              v-model="form.pay_id" 
              label="Pay ID" 
              type="number"
              placeholder="Enter pay ID" 
              icon-left="hash" 
            />

            
            <!-- Dev ID -->
            <Input 
              v-model="form.dev_id" 
              label="Dev ID" 
              type="number"
              placeholder="Enter dev ID" 
              icon-left="hash" 
            />

            <!-- Home Store -->
            <Input 
              v-model="form.home_store" 
              label="Home Store" 
              type="number"
              placeholder="Enter home store" 
              icon-left="building" 
            />
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
import { isSSNValid, formatSSN } from '@/utils/ssn'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import InputLabel from '@/components/ui/inputLabel.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRouter } from 'vue-router'
const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-hours'
const router = useRouter()
// Use the useFormable composable
const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource, 
  'payroll/employee-hours', 
  'employee-hour'
)

watch(() => form.value, (newVal) => {
  if (!newVal) return

  // Skip if already transformed (pay_type is object from dropdown)
  if (typeof newVal.pay_type === 'object' && newVal.pay_type?.id) {
    if (newVal.reviewed) router.push(`/payroll/employee-hours/${newVal.id}`)
    return
  }

  // When there's a pending request, show requested data (like employee form does for rates)
  const req = newVal.employee_hours_request
  const hasPending = req?.status === 'pending'
  const payTypeVal = hasPending ? req.pay_type : newVal.pay_type
  const payTypeObj = payTypes.find(type => type.id === (payTypeVal?.id ?? payTypeVal)) ?? payTypes[0]

  if (hasPending && req) {
    form.value = {
      ...newVal,
      id: newVal.id,
      employee_hours_request_id: req.id,
      date: req.date,
      employee: req.employee ?? newVal.employee,
      company: req.company ?? newVal.company,
      role: req.role ?? newVal.role,
      pay_type: payTypeObj,
      total_hours: req.total_hours,
      pay_rate: req.pay_rate,
      tips: req.tips,
      tips_due: req.tips_due,
      mileage_excess: req.mileage_excess,
      mileage_due: req.mileage_due,
      incentive: req.incentive,
      bonus: req.bonus,
      ssn: req.ssn ?? newVal.ssn,
      pay_id: req.pay_id,
      dev_id: req.dev_id,
      home_store: req.home_store,
    }
  } else {
    form.value.pay_type = payTypeObj
  }

  if (form.value.reviewed) {
    router.push(`/payroll/employee-hours/${form.value.id}`)
  }
})

watch(() => form.value.employee, (newVal) => {
  if (!newVal) return
  form.value.ssn = newVal.ssn
})


const payTypes = [
  {
    id: 'HR',
    name: 'HR'
  },
  {
    id: 'WK',
    name: 'WK'
  }
]


const handleSave = async () => {
  if (form.value.ssn && !isSSNValid(form.value.ssn)) {
    errors.value.ssn = ['Please enter a valid 9-digit SSN (e.g. XXX-XX-XXXX or 9 digits).']
    return
  }
  if (errors.value.ssn) delete errors.value.ssn
  try {
    const obj = {
      ...form.value,
      company_id: form.value.company?.id,
      role_id: form.value.role?.id,
      pay_type: form.value.pay_type?.id,
      employee_id: form.value.employee?.id,
      employee_hours_request_id: form.value.employee_hours_request_id ?? null,
    }
    if (obj.ssn && isSSNValid(obj.ssn)) obj.ssn = formatSSN(obj.ssn)

    // Remove nested objects
    delete obj.employee
    delete obj.company
    delete obj.role
    delete obj.employee_hours_request

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

