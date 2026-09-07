<template>
  <div v-if="show" class="employee-hours-request-form space-y-6">
    <Panel :divider="true">
      <template #header>
        <h5 class="font-bold !mb-0">New Employee Hours Request</h5>
      </template>

      <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Select date, employee, company, and role. Existing payroll hours and any pending request
        are shown for reference; enter total hours, tips, tips due, and mileage fields for approval.
      </p>

      <div
        v-if="lookupLoading"
        class="text-sm text-gray-500 dark:text-gray-400 mb-4"
      >
        Loading existing data…  
      </div>

      <div
        v-if="oldEmployeeHours"
        class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4"
      >
        <h6 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">
          Current payroll hours (reference)
        </h6>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
          <div><span class="text-gray-500">Total hours</span> {{ formatNumber(oldEmployeeHours.total_hours) }}</div>
          <div><span class="text-gray-500">Tips</span> ${{ formatNumber(oldEmployeeHours.tips) }}</div>
          <div><span class="text-gray-500">Tips due</span> ${{ formatNumber(oldEmployeeHours.tips_due) }}</div>
          <div><span class="text-gray-500">Mileage excess</span> ${{ formatNumber(oldEmployeeHours.mileage_excess) }}</div>
          <div><span class="text-gray-500">Mileage due</span> ${{ formatNumber(oldEmployeeHours.mileage_due) }}</div>
        </div>
      </div>

      <div
        v-if="pendingRequest"
        class="mb-6 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4"
      >
        <h6 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-1">
          Pending request (will be rejected if you submit a new one)
        </h6>
        <p class="text-xs text-amber-800 dark:text-amber-200 mb-3">
           values below are prefilled in your new request; adjust as needed.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
          <div><span class="text-gray-500">Total hours</span> {{ formatNumber(pendingRequest.total_hours) }}</div>
          <div><span class="text-gray-500">Tips</span> ${{ formatNumber(pendingRequest.tips) }}</div>
          <div><span class="text-gray-500">Tips due</span> ${{ formatNumber(pendingRequest.tips_due) }}</div>
          <div><span class="text-gray-500">Mileage excess</span> ${{ formatNumber(pendingRequest.mileage_excess) }}</div>
          <div><span class="text-gray-500">Mileage due</span> ${{ formatNumber(pendingRequest.mileage_due) }}</div>
        </div>
      </div>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div>
          <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            New request values
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
              label="Employee"
              v-model="form.employee"
              resource="employees"
              display-name="pos_name"
              placeholder="Select employee"
              :required="true"
              :error="errors.employee_id ? errors.employee_id[0] : null"
              icon-left="user"
            />

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

            <div>
              <InputLabel :required="true">Role</InputLabel>
             
              <DynamicDropdown
                :key="`role-dd-${form.employee?.id ?? ''}-${form.company?.id ?? ''}`"
                v-model="form.role"
                :custom-options="roleOptions"
                display-name="name"
                placeholder="Select role"
                :required="true"
                :searchable="true"
                :remove-null-option="true"
                :error="errors.role_id ? errors.role_id[0] : null"
              />

              <p
                v-if="form.employee?.id && form.company?.id && !rolesLoading && roleOptions.length === 0"
                class="text-xs text-amber-700 dark:text-amber-300 mb-1.5"
              >
                No employee rates for this employee at this company. Add a rate first, then request hours.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
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
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <Button variant="outline-secondary" size="md" type="button" @click="cancel">
            Cancel
          </Button>
          <Button
            v-if="access.includes('create')"
            variant="primary"
            size="md"
            type="submit"
            :loading="isSaving"
          >
            Submit request
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import { isSSNValid, formatSSN } from '@/utils/ssn'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import InputLabel from '@/components/ui/inputLabel.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'

const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-hours-request'

const { form, errors, isSaving, show, save, cancel, setData, access } = useFormable(
  resource,
  'payroll/employee-hours-request',
  'employee-hours-request'
)

const oldEmployeeHours = ref(null)
const pendingRequest = ref(null)
const lookupLoading = ref(false)
const roleOptions = ref([])
const rolesLoading = ref(false)
let lookupDebounce = null

const payTypes = [
  { id: 'HR', name: 'HR' },
  { id: 'WK', name: 'WK' },
]

const formatNumber = (value) => {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

const applyPrefillFromLookup = (lookupRes) => {
  const pr = pendingRequest.value
  const eh = oldEmployeeHours.value
  const src = pr || eh
  if (src) {
    const payTypeId = pr ? pr.pay_type : eh.pay_type
    form.value.pay_type = payTypes.find((t) => t.id === (payTypeId?.id ?? payTypeId)) ?? payTypes[0]
    form.value.total_hours = src.total_hours
    form.value.pay_rate = src.pay_rate
    form.value.tips = src.tips ?? 0
    form.value.tips_due = src.tips_due ?? 0
    form.value.mileage_excess = src.mileage_excess ?? 0
    form.value.mileage_due = src.mileage_due ?? 0
    form.value.pay_id = src.pay_id ?? form.value.pay_id
    form.value.dev_id = src.dev_id ?? form.value.dev_id
    form.value.home_store = src.home_store ?? form.value.home_store
    if (eh && !pr) {
      form.value.ssn = eh.ssn || form.value.ssn
    }
    if (pr) {
      form.value.ssn = pr.ssn || form.value.ssn
    }
    return
  }

  if (lookupRes?.suggested_pay_rate != null) {
    form.value.pay_rate = lookupRes.suggested_pay_rate
  }
}

const loadRolesForSelection = async () => {
  const empId = form.value?.employee?.id
  const compId = form.value?.company?.id
  if (!empId || !compId) {
    roleOptions.value = []
    return
  }
  rolesLoading.value = true
  try {
    const res = await useRequest('get', `${resource}/roles-for-selection`, undefined, {
      params: { employee_id: empId, company_id: compId },
    })
    roleOptions.value = res?.roles ?? []
    const ids = new Set(roleOptions.value.map((r) => r.id))
    if (form.value.role?.id && !ids.has(form.value.role.id)) {
      form.value.role = null
    }
  } catch {
    roleOptions.value = []
  } finally {
    rolesLoading.value = false
  }
}

const runLookup = async () => {
  const f = form.value
  if (!f?.date || !f?.employee?.id || !f?.company?.id || !f?.role?.id) {
    oldEmployeeHours.value = null
    pendingRequest.value = null
    return
  }

  lookupLoading.value = true
  try {
    const res = await useRequest('get', `${resource}/lookup`, undefined, {
      params: {
        date: f.date,
        employee_id: f.employee.id,
        company_id: f.company.id,
        role_id: f.role.id,
        pay_type: f.pay_type?.id ?? f.pay_type ?? 'HR',
      },
    })
    oldEmployeeHours.value = res?.employee_hours ?? null
    pendingRequest.value = res?.pending_request ?? null
    applyPrefillFromLookup(res)
  } catch {
    oldEmployeeHours.value = null
    pendingRequest.value = null
  } finally {
    lookupLoading.value = false
  }
}

const scheduleLookup = () => {
  clearTimeout(lookupDebounce)
  lookupDebounce = setTimeout(runLookup, 400)
}

watch(
  () => [
    form.value?.date,
    form.value?.employee?.id,
    form.value?.company?.id,
    form.value?.role?.id,
    form.value?.pay_type?.id,
  ],
  () => scheduleLookup(),
  { deep: true }
)

watch(
  () => [form.value?.employee?.id, form.value?.company?.id],
  async ([employeeId, companyId], prev) => {
    if (
      Array.isArray(prev) &&
      (prev[0] !== employeeId || prev[1] !== companyId) &&
      (prev[0] != null || prev[1] != null)
    ) {
      form.value.role = null
      oldEmployeeHours.value = null
      pendingRequest.value = null
      form.value.total_hours = 0
      form.value.tips = 0
      form.value.tips_due = 0
      form.value.mileage_excess = 0
      form.value.mileage_due = 0
      form.value.pay_rate = 0
    }
    await loadRolesForSelection()
  }
)

watch(
  () => form.value?.employee,
  (newVal) => {
    if (!newVal) return
    form.value.ssn = newVal.ssn
  },
  { deep: true }
)

watch(
  () => form.value?.pay_type,
  (v) => {
    if (!form.value) return
    if (typeof v === 'object' && v?.id) return
    form.value.pay_type = payTypes.find((t) => t.id === (v?.id ?? v)) ?? payTypes[0]
  },
  { immediate: true }
)

const handleSave = async () => {
  if (form.value.ssn && !isSSNValid(form.value.ssn)) {
    errors.value.ssn = ['Please enter a valid 9-digit SSN (e.g. XXX-XX-XXXX or 9 digits).']
    return
  }
  if (errors.value.ssn) delete errors.value.ssn
  try {
    const obj = {
      ...form.value,
      incentive: 0,
      bonus: 0,
      company_id: form.value.company?.id,
      role_id: form.value.role?.id,
      pay_type: form.value.pay_type?.id,
      employee_id: form.value.employee?.id,
    }
    if (obj.ssn && isSSNValid(obj.ssn)) obj.ssn = formatSSN(obj.ssn)
    delete obj.employee
    delete obj.company
    delete obj.role
    await save(obj)
  } catch (error) {
    console.error(error)
  }
}

defineExpose({ setData })
</script>
