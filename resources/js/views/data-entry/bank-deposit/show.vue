<template>
  <div v-if="show" class="bank-deposit-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Sales Bank Deposits</h5>
          <div class="flex items-center gap-2">
              <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/bank-deposits" />
              <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')" :to="`/data-entry/bank-deposits/${model.daily_sale?.id}/edit`" />
        </div>
        </div>
      </template>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-6">
        <Label label="Daily Sale Date" :value="formatDate(model?.daily_sale?.date)" />
        <Label label="Company" :value="model?.daily_sale?.company?.name || '-'" />
        <Label label="Cash due" :value="formatNumber(model?.daily_sale?.net_cash_due)" />
        <Label label="Cash Bag"  :value="`$${formatNumber(model?.daily_sale?.cash_bag)}`" value-custom-class="cursor-pointer !text-blue-500 !dark:text-blue-400" @click="handleCashBagDoubleClick" />
        <Label label="Total Deposits" :value="String(bankDeposits.length)" />

        <Label label="Total Deposits Amount" :value="`$${formatNumber(bankDeposits.reduce((acc, curr) => acc + curr.amount, 0))}`" />
        <Label label="Total Shortages" :value="`$${formatNumber(model?.daily_sale?.shortage_total)}`" />
        <Label label="Remaining" :value="`$${formatNumber(model?.daily_sale?.remaining_amount)}`" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
        <table class="text-sm w-full">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th custom-class="text-left px-4 py-3">No</Th>
              <Th custom-class="text-left px-4 py-3">Date</Th>
              <Th custom-class="text-right px-4 py-3">Amount</Th>
              <Th custom-class="text-left !px-6 py-3 min-w-[100px]">Note</Th>
              <Th custom-class="text-left px-4 py-3">Created By / Time</Th>
              <Th custom-class="text-left px-4 py-3">Updated By / Time</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!bankDeposits.length">
              <Td colspan="6" class="px-4 py-6 !text-center text-gray-500">No bank deposits found.</Td>
            </tr>
            <tr
              v-for="(row, index) in bankDeposits"
              :key="row.id || index"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td class="px-4 py-3">{{ index + 1 }}</Td>
              <Td class="px-4 py-3">{{ formatDate(row.date) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatNumber(row.amount) }}</Td>
              <Td class="!px-6 py-3">{{ row.notes || '-' }}</Td>
              <Td class="px-4 py-3">
                <div>{{ row.created_by?.name || row.createdBy?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ formatDateTime(row.created_at) }}</div>
              </Td>
              <Td class="px-4 py-3">
                <div>{{ row.updated_by?.name || row.updatedBy?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ formatDateTime(row.updated_at) }}</div>
              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[300px]">
    <Spinner size="md" text="Loading bank deposits..." centered />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatNumber } from '@/utils/number'
import { formatDate, formatDateTime } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/bank-deposits'
const { model, show, setData, access } = useShowable(resource, 'bank-deposit')

const bankDeposits = computed(() => model.value?.bank_deposits || [])

const handleCashBagDoubleClick = () => {
  window.open(`/data-entry/daily-sales/${model.value?.daily_sale?.id}`, '_blank')
}

defineExpose({
  setData
})
</script>
