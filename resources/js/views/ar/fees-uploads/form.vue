<template>
  <div v-if="show" class="fees-upload-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Fees Upload' : 'Create New Fees Upload' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
              Select Weekly Period
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
            <div v-if="errors.date?.[0]" class="text-xs text-red-600">{{ errors.date[0] }}</div>
          </div>

          <DynamicDropdown
            v-model="form.company"
            label="Company"
            resource="companies"
            display-name="name"
            :required="true"
            :error="errors.company ? errors.company[0] : null"
            placeholder="Select company"
          />

          <Input
            v-model="form.ddd_cash"
            type="number"
            step="0.01"
            min="0"
            label="DDD Cash"
            :error="errors.ddd_cash?.[0] || null"
          />

          <Input
            v-model="form.ez_cater"
            type="number"
            step="0.01"
            min="0"
            label="EZ Cater"
            :error="errors.ez_cater?.[0] || null"
          />

          <Input
            v-model="form.meal_deal"
            type="number"
            step="0.01"
            min="0"
            label="Meal Deal"
            :error="errors.meal_deal?.[0] || null"
          />

          <Input
            v-model="form.visa"
            type="number"
            step="0.01"
            min="0"
            label="Visa"
            :error="errors.visa?.[0] || null"
          />

          <Input
            v-model="form.amex"
            type="number"
            step="0.01"
            min="0"
            label="Amex"
            :error="errors.amex?.[0] || null"
          />
          <Input
            v-model="form.doordash"
            type="number"
            step="0.01"
            min="0"
            label="Doordash"
            :error="errors.doordash?.[0] || null"
          />
          <Input
            v-model="form.ddc_doordash"
            type="number"
            step="0.01"
            min="0"
            label="DDC Doordash"
            :error="errors.ddc_doordash?.[0] || null"
          />
          <Input
            v-model="form.uber"
            type="number"
            step="0.01"
            min="0"
            label="Uber"
            :error="errors.uber?.[0] || null"
          />
          <Input
            v-model="form.grubhub"
            type="number"
            step="0.01"
            min="0"
            label="Grubhub"
            :error="errors.grubhub?.[0] || null"
          />

          <Input
            :model-value="formatAmount(totals.total_amount)"
            label="Total Amount"
            disabled
          />
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
            {{ mode === 'edit' ? 'Update Fees Upload' : 'Create Fees Upload' }}
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
const route = useRoute()
const resource = route.meta?.resource || 'ar/fees-uploads'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'ar/fees-uploads',
  'fees-upload'
)

const selectedPeriod = ref('')

const ensureFormShape = () => {
  if (!form.value) return

  const numericFields = [
    'ddd_cash',
    'ez_cater',
    'meal_deal',
    'visa',
    'amex',
    'doordash',
    'ddc_doordash',
    'uber',
    'grubhub'
  ]

  numericFields.forEach((field) => {
    form.value[field] = Number(form.value[field] || 0)
  })

  form.value.year = Number(
    form.value.year || new Date(form.value.date || Date.now()).getFullYear()
  )
}

const syncSelectedPeriodFromDate = () => {
  if (!form.value?.date) return
  const dateOnly = String(form.value.date).slice(0, 10)
  const found = periodOptions.value.find((option) => option.value === dateOnly)
  if (found) {
    selectedPeriod.value = found.value
  }
}

watch(
  () => form.value,
  () => {
    ensureFormShape()
    syncSelectedPeriodFromDate()
  },
  { immediate: true }
)

watch(selectedPeriod, (value) => {
  if (!value || !form.value) return
  form.value.date = value
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

const periodOptions = computed(() =>
  generatePeriodOptions(Number(form.value?.year || new Date().getFullYear()))
)

const onYearChanged = () => {
  const option = periodOptions.value[0]
  if (option) {
    selectedPeriod.value = option.value
    form.value.date = option.value
  }
}

const generatePeriodOptions = (year) => {
  const options = []
  let startDate = new Date(Date.UTC(year, 0, 1))
  const dayOfWeek = startDate.getUTCDay()
  const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

  let periodNumber = 1
  const currentDate = new Date()
  while (startDate.getUTCFullYear() === year || periodNumber === 1) {
    const endDate = new Date(startDate)
    endDate.setUTCDate(endDate.getUTCDate() + 6)

    if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
      break
    }
    if(currentDate >= startDate && currentDate <= endDate){
      selectedPeriod.value = endDate.toISOString().slice(0, 10)
    }


    options.push({
      label: `${formatShortDate(startDate)} - ${formatShortDate(endDate)}`,
      value: endDate.toISOString().slice(0, 10)
    })

    startDate.setUTCDate(startDate.getUTCDate() + 7)
    periodNumber++
  }

  return options
}

const formatShortDate = (date) => {
  const mm = String(date.getUTCMonth() + 1).padStart(2, '0')
  const dd = String(date.getUTCDate()).padStart(2, '0')
  return `${mm}/${dd}`
}

const toNumber = (value) => Number(value || 0)
const formatAmount = (value) => Number(value || 0).toFixed(2)

const totals = computed(() => {
  const totalAmount = 
    toNumber(form.value.ddd_cash) +
    toNumber(form.value.ez_cater) +
    toNumber(form.value.meal_deal) +
    toNumber(form.value.visa) +
    toNumber(form.value.amex) +
    toNumber(form.value.doordash) +
    toNumber(form.value.ddc_doordash) +
    toNumber(form.value.uber) +
    toNumber(form.value.grubhub)

  return {
    total_amount: Number(totalAmount.toFixed(2))
  }
})

const handleSave = async () => {
  const payload = {
    company_id: form.value.company?.id,
    date: form.value.date,
    ddd_cash: toNumber(form.value.ddd_cash),
    ez_cater: toNumber(form.value.ez_cater),
    meal_deal: toNumber(form.value.meal_deal),
    visa: toNumber(form.value.visa),
    amex: toNumber(form.value.amex),
    doordash: toNumber(form.value.doordash),
    ddc_doordash: toNumber(form.value.ddc_doordash),
    uber: toNumber(form.value.uber),
    grubhub: toNumber(form.value.grubhub)
  }

  await save(payload)
}

defineExpose({
  setData
})
</script>
