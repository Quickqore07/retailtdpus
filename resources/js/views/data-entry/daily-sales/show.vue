<template>
  <div v-if="show" class="daily-sales-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Daily Sale Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/daily-sales" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/data-entry/daily-sales/${model.id}/edit`"
            />
            <Button
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              v-if="access.includes('delete')"
              @click="handleDelete"
            />
          </div>
        </div>
      </template>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 mb-8">
        <Label label="Date" :value="formatDate(model.date)" />
        <Label label="Net Sales" :value="`$${formatAmount(model.net_sales)}`" />
        <Label label="Beverage Tax" :value="`$${formatAmount(model.beverage_tax)}`" />
        <Label label="Food Tax" :value="`$${formatAmount(model.food_tax)}`" />
        <Label label="Total Sales" :value="`$${formatAmount(model.total_sales)}`" />
      </div>
      
      <h5 class="text-xl font-bold">Cash Reconciliation</h5>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <Label label="Cash Received" :value="`$${formatAmount(model.cash_received)}`" />
        <Label label="Partial Void" :value="`$${formatAmount(model.partial_void)}`" />
        <Label label="Total Cash" :value="`$${formatAmount(model.total_cash)}`" />

      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <Label label="Tips" :value="`$${formatAmount(model.tips)}`" />
        <Label label="Mileage" :value="`$${formatAmount(model.mileage)}`" />
        <Label label="Total Tips Mileage" :value="`$${formatAmount(model.total_tips_mileage)}`" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <Label label="DD Tips" :value="`$${formatAmount(model.dd_tips)}`" />
        <Label label="E Tips" :value="`$${formatAmount(model.e_tips)}`" />
        <Label label="E Tips Payroll" :value="`$${formatAmount(model.e_tips_payroll)}`" />
        <Label label="Total E and DD Tips" :value="`$${formatAmount(model.total_e_and_dd_tips)}`" />
      </div>
        
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <Label label="Cash Payment" :value="`$${formatAmount(model.cash_payment)}`" />
        <Label label="Other Payments Total" :value="`$${formatAmount(model.other_payments_total)}`" />
        <Label label="Total Cash Payment" :value="`$${formatAmount(model.total_cash_payment)}`" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <Label label="Net Cash Due" :value="`$${formatAmount(model.net_cash_due)}`" />
        <Label label="Cash Bag" :value="`$${formatAmount(model.cash_bag)}`" />
        <Label label="Short / Over" :value="`$${formatAmount(model.short_over)}`" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
        <table class="text-sm w-full">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th class="text-left px-4 py-3">No</Th>
              <Th class="text-left px-4 py-3">Expense</Th>
              <Th class="text-right px-4 py-3">Amount</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!model.other_payments?.length">
              <Td colspan="3" class="px-4 py-6 text-center text-gray-500">No other payments found.</Td>
            </tr>
            <tr
              v-for="(row, index) in model.other_payments"
              :key="row.id || index"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td class="px-4 py-3">{{ index + 1 }}</Td>
              <Td class="px-4 py-3">{{ row.expense || 'N/A' }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(row.amount) }}</Td>
            </tr>
            <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
              <Td class="px-4 py-3" colspan="2">Total</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(model.other_payments_total) }}</Td>
            </tr>
          </tbody>
        </table>
      </div>

      <h5 class="text-xl font-bold !mt-8">Bank Deposits</h5>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-6">
        <Label label="Total Deposits" :value="String(bankDeposits.length)" />
        <Label label="Total Deposits Amount" :value="`$${formatAmount(bankDepositTotal)}`" />
        <Label label="Remaining" :value="`$${formatAmount(remainingAmount)}`" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto mb-8">
        <table class="text-sm w-full">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th custom-class="text-left px-4 py-3">No</Th>
              <Th custom-class="text-left px-4 py-3">Date</Th>
              <Th custom-class="text-right px-4 py-3">Amount</Th>
              <Th custom-class="text-left !px-6 py-3 min-w-[100px]">Note</Th>
              <Th custom-class="text-left px-4 py-3">Show</Th>
              <!-- <Th custom-class="text-left px-4 py-3">Created By / Time</Th>
              <Th custom-class="text-left px-4 py-3">Updated By / Time</Th> -->
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
              <Td class="px-4 py-3 text-right">${{ formatAmount(row.amount) }}</Td>
              <Td class="!px-6 py-3">{{ row.notes || '-' }}</Td>
              <Td class="px-4 py-3">
                <Button size="xs" variant="outline-primary" icon-left="eye" @click="showBankDeposit(model.id)" />
              </Td>
              <!-- <Td class="px-4 py-3">
                <div>{{ row.created_by?.name || row.createdBy?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ formatDateTime(row.created_at) }}</div>
              </Td>
              <Td class="px-4 py-3">
                <div>{{ row.updated_by?.name || row.updatedBy?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ formatDateTime(row.updated_at) }}</div>
              </Td> -->
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="shortages.length">
        <h5 class="text-xl font-bold">Shortages</h5>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-6">
          <Label label="Total Shortages" :value="String(shortages.length)" />
          <Label label="Total Shortages Amount" :value="`$${formatAmount(shortageTotal)}`" />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
          <table class="text-sm w-full">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <Th class="text-left px-4 py-3">No</Th>
                <Th class="text-left px-4 py-3">Date</Th>
                <Th class="text-right px-4 py-3">Amount</Th>
                <Th class="text-left px-4 py-3">Note</Th>
                <Th class="text-left px-4 py-3">Show</Th>
                <!-- <Th class="text-left px-4 py-3">Created By / Time</Th>
                <Th class="text-left px-4 py-3">Updated By / Time</Th> -->
              </tr>
            </thead>
            <tbody>
              <tr v-if="!shortages.length">
                <Td colspan="6" class="px-4 py-6 text-center text-gray-500">No shortages found.</Td>
              </tr>
              <tr
                v-for="(row, index) in shortages"
                :key="row.id || index"
                class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <Td class="px-4 py-3">{{ index + 1 }}</Td>
                <Td class="px-4 py-3">{{ formatDate(row.date) }}</Td>
                <Td class="px-4 py-3 text-right">${{ formatAmount(row.amount) }}</Td>
                <Td class="px-4 py-3">{{ row.notes || '-' }}</Td>
                <Td class="px-4 py-3">
                  <Button size="xs" variant="outline-primary" icon-left="eye" @click="showShortage(model.id)" />
                </Td>
                <!-- <Td class="px-4 py-3">
                  <div>{{ row.createdBy?.name || '-' }}</div>
                  <div class="text-xs text-gray-500">{{ formatDateTime(row.created_at) }}</div>
                </Td>
                <Td class="px-4 py-3">
                  <div>{{ row.updatedBy?.name || '-' }}</div>
                  <div class="text-xs text-gray-500">{{ formatDateTime(row.updated_at) }}</div>
                </Td> -->
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading daily sale details..." centered />
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
import { formatDate, formatDateTime } from '@/utils/date'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/daily-sales'

const { model, show, setData, removeDB, access } = useShowable(resource, 'daily-sales')

const bankDeposits = computed(() => model.value?.bank_deposits || [])
const shortages = computed(() => model.value?.shortages || [])
const bankDepositTotal = computed(() => bankDeposits.value.reduce((acc, curr) => acc + curr.amount, 0) || 0)
const shortageTotal = computed(() => shortages.value.reduce((acc, curr) => acc + curr.amount, 0) || 0)
const remainingAmount = computed(() => model.value?.cash_bag - bankDepositTotal.value - shortageTotal.value || 0)

const formatAmount = (value) => Number(value || 0).toFixed(2)
  
const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

const showBankDeposit = (id) => {
  window.open(`/data-entry/bank-deposits/${id}`, '_blank')
}

const showShortage = (id) => {
  window.open(`/data-entry/shortages/${id}`, '_blank')
}

defineExpose({
  setData
})
</script>
