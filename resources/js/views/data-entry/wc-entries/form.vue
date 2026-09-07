<template>
  <div v-if="show" class="wc-entry-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit WC Entry' : 'Create New WC Entry' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Year
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model.number="form.year"
              class="w-full min-h-[36px] px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
            >
              <option v-for="year in yearOptions" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
            <div v-if="errors.year?.[0]" class="text-xs text-red-600">{{ errors.year[0] }}</div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Select Period
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="selectedPeriod"
              class="w-full min-h-[36px] px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
            >
              <option v-for="period in periodOptions" :key="period.value" :value="period.value">
                {{ period.label }}
              </option>
            </select>
            <div v-if="errors.eow?.[0]" class="text-xs text-red-600">{{ errors.eow[0] }}</div>
          </div>

          <DynamicDropdown
            label="Company"
            v-model="form.company"
            resource="companies"
            display-name="name"
            :required="true"
            :error="errors.company_id ? errors.company_id[0] : null"
            placeholder="Select company"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Input
            v-model="form.driver_pay"
            type="number"
            step="0.01"
            min="0"
            label="Driver Pay"
            :required="true"
            :error="errors.driver_pay?.[0] || null"
          />
          <Input
            v-model="form.non_driver_pay"
            type="number"
            step="0.01"
            min="0"
            label="Non Driver Pay"
            :required="true"
            :error="errors.non_driver_pay?.[0] || null"
          />
          <Input :model-value="formatAmount(totalPay)" label="Total Pay" disabled />
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
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
            {{ mode === 'edit' ? 'Update WC Entry' : 'Create WC Entry' }}
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
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useAuthStore } from '@/stores/auth'
const authStore = useAuthStore()
const route = useRoute()
const resource = route.meta?.resource || 'data-entry/wc-entries'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'data-entry/wc-entries',
  'wc-entry'
)

const selectedPeriod = ref('')

const ensureFormShape = () => {
  if (!form.value) return
  form.value.year = Number(form.value.year || new Date().getFullYear())
  form.value.driver_pay = Number(form.value.driver_pay || 0)
  form.value.non_driver_pay = Number(form.value.non_driver_pay || 0)
}


const syncSelectedPeriodFromEow = () => {
  if (!form.value?.eow) return
  const found = periodOptions.value.find((option) => option.value === form.value.eow)
  if (found) {
    selectedPeriod.value = found.value
  }
}
const isDC = computed(() => {
    return authStore.isDC || false
})


watch(
  () => form.value,
  () => {
    ensureFormShape()
    syncSelectedPeriodFromEow()
  },
  { immediate: true }
)

watch(selectedPeriod, (value) => {
  if (!value || !form.value) return
  form.value.eow = value
})

watch(
  () => form.value?.year,
  (newValue, oldValue) => {
    if (!newValue || newValue === oldValue) return
    onYearChanged()
  }
)

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let year = currentYear + 1; year >= 2000; year--) {
    years.push(year)
  }
  return years
})

const periodOptions = computed(() => generateWeekOptions(Number(form.value.year || new Date().getFullYear())))

const totalPay = computed(() => Number(form.value.driver_pay || 0) + Number(form.value.non_driver_pay || 0))

const onYearChanged = () => {
  const option = periodOptions.value[0]
  if (option) {
    selectedPeriod.value = option.value
    form.value.eow = option.value
  }
}

const generateWeekOptions = (year) => {
  const options = []
  let startDate = new Date(Date.UTC(year, 0, 1))
  const dayOfWeek = startDate.getUTCDay()
  let daysToMonday = 0;
  if(isDC.value){
    daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek
  }else{
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

    options.push({
      label: `${formatShortDate(startDate)} - ${formatShortDate(endDate)}`,
      value: endDate.toISOString().slice(0, 10)
    })

    startDate.setUTCDate(startDate.getUTCDate() + 14)
    periodNumber++
  }

  return options
}


const formatShortDate = (date) => {
  const mm = String(date.getUTCMonth() + 1).padStart(2, '0')
  const dd = String(date.getUTCDate()).padStart(2, '0')
  return `${mm}/${dd}`
}

const formatAmount = (value) => Number(value || 0).toFixed(2)

const handleSave = async () => {
  const payload = {
    year: Number(form.value.year),
    eow: form.value.eow,
    company_id: form.value.company?.id || form.value.company_id,
    driver_pay: Number(form.value.driver_pay || 0),
    non_driver_pay: Number(form.value.non_driver_pay || 0),
    total_pay: Number(totalPay.value.toFixed(2))
  }

  await save(payload)
}

defineExpose({
  setData
})
</script>
