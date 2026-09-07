<template>
  <div v-if="show" class="other-daily-sales-show space-y-6">
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

      <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Label label="Date" :value="formatDate(model.date)" />
          <Label label="Company" :value="model.company?.name || '-'" />
        </div>

        <div class="flex flex-col gap-4 xl:grid xl:grid-cols-2 xl:items-start">
          <div class="contents xl:flex xl:flex-col xl:gap-4 xl:col-start-1">
            <div class="order-1 overflow-x-auto">
              <table class="w-full text-sm border-collapse">
                <thead>
                  <tr class="bg-gray-50 dark:bg-gray-800/60">
                    <th colspan="4" class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-center font-semibold">
                      Daily Sales
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium w-[18%]">Sales</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right w-[32%]">{{ formatAmount(model.sales) }}</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium w-[18%]">Cash</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right w-[32%]">{{ formatAmount(model.cash) }}</td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Tax</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.tax) }}</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Credit Card</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.credit_card) }}</td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Other</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.other) }}</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Account</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.account) }}</td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Total</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">{{ formatAmount(subTotal) }}</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Check</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.check) }}</td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Round Off</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.round_off) }}</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Coupon</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.coupon) }}</td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Other</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(model.other_payment) }}</td>
                  </tr>
                  <tr class="bg-gray-50 dark:bg-gray-800/60">
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Total</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">{{ formatAmount(finalSalesTotal) }}</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Total</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">{{ formatAmount(paymentMethodsTotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="order-3 overflow-x-auto">
              <table class="w-full text-sm border-collapse">
                <thead>
                  <tr class="bg-violet-100 dark:bg-violet-900/40">
                    <th colspan="4" class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left font-semibold">
                      Cash balance
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium w-[30%]">OP balance</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right w-[30%]">
                      {{ formatAmount(model.opening_balance ?? openingBalance) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 w-[20%]"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 w-[20%]"></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Bank deposit</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">
                      {{ formatAmount(model.bank_deposits) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Cash due</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">
                      {{ formatAmount(model.cash_due ?? cashDue) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Closing balance</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                      {{ formatAmount(model.closing_balance ?? closingBalance) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="order-2 overflow-x-auto xl:col-start-2">
            <div class="bg-gray-50 dark:bg-gray-800/60 px-3 py-2 border border-gray-200 dark:border-gray-700">
              <h6 class="font-semibold !mb-0">Cash Reconciliation</h6>
            </div>
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="bg-sky-100 dark:bg-sky-900/40">
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Cash Payments</th>
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Detail</th>
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right min-w-[160px]">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!cashReconciliations.length">
                  <td colspan="3" class="border border-gray-200 dark:border-gray-700 px-3 py-6 text-center text-gray-500">
                    No cash reconciliations found.
                  </td>
                </tr>
                <tr
                  v-for="(row, index) in cashReconciliations"
                  :key="row.id || index"
                >
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">{{ row.cash_payment || '-' }}</td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">{{ row.detail || '-' }}</td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">{{ formatAmount(row.amount) }}</td>
                </tr>
                <tr>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold" colspan="2">
                    Total Payment
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                    {{ formatAmount(model.total_payment ?? totalPayment) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading..." centered />
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
import { formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/daily-sales'
const { model, show, access, removeDB, setData } = useShowable(resource, 'daily-sales')

const toNumber = (value) => Number(value || 0)

const formatAmount = (value) => {
  return toNumber(value).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const cashReconciliations = computed(() => model.value?.cash_reconciliations || [])

const subTotal = computed(() => {
  return toNumber(model.value?.sales) + toNumber(model.value?.tax) + toNumber(model.value?.other)
})

const finalSalesTotal = computed(() => subTotal.value + toNumber(model.value?.round_off))

const paymentMethodsTotal = computed(() => {
  return (
    toNumber(model.value?.cash) +
    toNumber(model.value?.credit_card) +
    toNumber(model.value?.account) +
    toNumber(model.value?.check) +
    toNumber(model.value?.coupon) +
    toNumber(model.value?.other_payment)
  )
})

const totalPayment = computed(() => {
  return cashReconciliations.value.reduce((sum, row) => sum + toNumber(row.amount), 0)
})

const openingBalance = computed(() => toNumber(model.value?.opening_balance))

const cashDue = computed(() => toNumber(model.value?.cash) - totalPayment.value)

const closingBalance = computed(() => {
  return openingBalance.value - toNumber(model.value?.bank_deposits) + cashDue.value
})

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

defineExpose({ setData })
</script>
