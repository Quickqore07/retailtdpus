<template>
  <Modal
    :model-value="modelValue"
    title="Company Payroll Data"
    size="5xl"
    :show-footer="false"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="space-y-4">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="text-sm text-gray-600 dark:text-gray-300">
          <span class="font-medium">EOW:</span> {{ selectedRecord?.eow ? formatDate(selectedRecord.eow) : '-' }}
        </div>
        <Input
          v-model="search"
          type="text"
          placeholder="Filter company data..."
          class="md:max-w-sm"
        />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto max-h-[60vh]">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10">
            <tr>
              <Th>No</Th>
              <Th>Company</Th>
              <Th>Total Earnings</Th>
              <Th>-Total Tips</Th>
              <Th>-Total Mileage</Th>
              <Th>-Total Bonus</Th>
              <Th>+ Payroll Taxes</Th>
              <Th>= CTC</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <Td colspan="8" class="text-center py-6">Loading company data...</Td>
            </tr>
            <tr v-else-if="filteredRows.length === 0">
              <Td colspan="8" class="text-center py-6">No company data found.</Td>
            </tr>
            <tr
              v-for="(row, index) in filteredRows"
              :key="`${row.company_id}-${index}`"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td>{{ index + 1 }}</Td>
              <Td>{{ row.company?.name || '-' }}</Td>
              <Td>{{ formatCurrency(row.total_earnings) }}</Td>
              <Td>{{ formatCurrency(row.total_tips) }}</Td>
              <Td>{{ formatCurrency(row.total_mileage) }}</Td>
              <Td>{{ formatCurrency(row.total_bonus) }}</Td>
              <Td>{{ formatCurrency(row.er_withholdings) }}</Td>
              <Td>{{ formatCurrency(row.ctc) }}</Td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  rows: {
    type: Array,
    default: () => []
  },
  selectedRecord: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const search = ref('')

watch(() => props.modelValue, (value) => {
  if (!value) {
    search.value = ''
  }
})

const filteredRows = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  if (!keyword) {
    return props.rows
  }

  return props.rows.filter((row) => {
    const companyName = String(row.company?.name || '').toLowerCase()
    const storeNumber = String(row.company?.store_number || '').toLowerCase()
    const stateId = String(row.company?.state_id || '').toLowerCase()
    const totalEarnings = String(row.total_earnings || '').toLowerCase()
    const totalTips = String(row.total_tips || '').toLowerCase()
    const totalMileage = String(row.total_mileage || '').toLowerCase()
    const erWithholdings = String(row.er_withholdings || '').toLowerCase()
    const ctc = String(row.ctc || '').toLowerCase()

    return [
      companyName,
      storeNumber,
      stateId,
      totalEarnings,
      totalTips,
      totalMileage,
      erWithholdings,
      ctc
    ].some((value) => value.includes(keyword))
  })
})

</script>
