<template>
  <div v-if="show" class="other-daily-sales-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Daily Sale' : 'Create New Daily Sale' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input
            v-model="form.date"
            type="date"
            label="Date"
            :required="true"
            :error="errors.date ? errors.date[0] : null"
          />
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
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1 w-[32%]">
                      <Input v-model="form.sales" type="number" step="0.01" :error="errors.sales?.[0] || null" />
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium w-[18%]">Cash</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1 w-[32%]">
                      <Input v-model="form.cash" type="number" step="0.01" :error="errors.cash?.[0] || null" />
                    </td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Tax</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.tax" type="number" step="0.01" :error="errors.tax?.[0] || null" />
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Credit Card</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.credit_card" type="number" step="0.01" :error="errors.credit_card?.[0] || null" />
                    </td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Other</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.other" type="number" step="0.01" :error="errors.other?.[0] || null" />
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Account</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.account" type="number" step="0.01" :error="errors.account?.[0] || null" />
                    </td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Total</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                      {{ formatAmount(subTotal) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Check</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.check" type="number" step="0.01" :error="errors.check?.[0] || null" />
                    </td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Round Off</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.round_off" type="number" step="0.01" :error="errors.round_off?.[0] || null" />
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Coupon</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.coupon" type="number" step="0.01" :error="errors.coupon?.[0] || null" />
                    </td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Other</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input v-model="form.other_payment" type="number" step="0.01" :error="errors.other_payment?.[0] || null" />
                    </td>
                  </tr>
                  <tr class="bg-gray-50 dark:bg-gray-800/60">
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Total</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                      {{ formatAmount(finalSalesTotal) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Total</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                      {{ formatAmount(paymentMethodsTotal) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="order-3 border border-gray-200 dark:border-gray-700 rounded-md">
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h6 class="font-semibold !mb-0">Other Bank Deposits</h6>
                <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addBankDeposit">
                  Add Row
                </Button>
              </div>
              <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <Th class="text-left px-3 py-3 w-14">No</Th>
                    <Th class="text-left px-3 py-3 min-w-[240px]">Bank Name</Th>
                    <Th class="text-left px-3 py-3 min-w-[140px]">Amount</Th>
                    <Th class="text-left px-3 py-3 w-16">Actions</Th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, index) in form.bank_deposits"
                    :key="`bank-deposit-${index}`"
                    class="border-t border-gray-100 dark:border-gray-700"
                  >
                    <Td class="px-3 py-3">{{ index + 1 }}</Td>
                    <Td class="px-3 py-3">
                      <Input
                        v-model="item.bank_name"
                        type="text"
                        placeholder="Enter bank name"
                        :error="getChildError('bank_deposits', index, 'bank_name')"
                      />
                    </Td>
                    <Td class="px-3 py-3">
                      <Input
                        v-model="item.amount"
                        type="number"
                        step="0.01"
                        :error="getChildError('bank_deposits', index, 'amount')"
                      />
                    </Td>
                    <Td class="px-3 py-3">
                      <Button
                        type="button"
                        variant="outline-danger"
                        size="sm"
                        icon-left="trash"
                        @click="removeBankDeposit(index)"
                        :disabled="form.bank_deposits.length === 1"
                      />
                    </Td>
                  </tr>
                  <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                    <Td class="px-3 py-3 font-semibold" colspan="2">Total</Td>
                    <Td class="px-3 py-3 font-semibold">{{ formatAmount(bankDepositsTotal) }}</Td>
                    <Td class="px-3 py-3"></Td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="order-2 overflow-x-auto xl:col-start-2">
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/60 px-3 py-2 border border-gray-200 dark:border-gray-700">
              <h6 class="font-semibold !mb-0">Cash Reconciliation</h6>
              <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addCashReconciliation">
                Add Row
              </Button>
            </div>
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="bg-sky-100 dark:bg-sky-900/40">
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Cash Payments</th>
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Detail</th>
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left !min-w-[120px] !w-[120px] !max-w-[120px]">Amount</th>
                  <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left !w-[60px] !min-w-[60px] !max-w-[60px]"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in form.cash_reconciliations"
                  :key="`cash-recon-${index}`"
                >
                  <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                    <Input
                      v-model="item.cash_payment"
                      type="text"
                      :disabled="item.is_default"
                      :error="getChildError('cash_reconciliations', index, 'cash_payment')"
                    />
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                    <Input
                      v-model="item.detail"
                      type="text"
                      :disabled="item.is_default"
                      :error="getChildError('cash_reconciliations', index, 'detail')"
                    />
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                    <Input
                      v-model="item.amount"
                      type="number"
                      step="0.01"
                      :error="getChildError('cash_reconciliations', index, 'amount')"
                    />
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-2 py-1 text-center">
                    <Button
                      v-if="!item.is_default"
                      type="button"
                      variant="outline-danger"
                      size="xs"
                      icon-left="trash"
                      @click="removeCashReconciliation(index)"
                    />
                  </td>
                </tr>
                <tr>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold" colspan="2">
                    Total Payment
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                    {{ formatAmount(totalPayment) }}
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                </tr>
                <tr class="bg-pink-100 dark:bg-pink-900/30">
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold" colspan="2">
                    Cash Due
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                    {{ formatAmount(cashDue) }}
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                </tr>
              </tbody>
            </table>
          </div>
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
            {{ mode === 'edit' ? 'Update Daily Sale' : 'Create Daily Sale' }}
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
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/other-daily-sales'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'data-entry/other-daily-sales',
  'other-daily-sales'
)

const DEFAULT_CASH_RECONCILIATIONS = [
  { cash_payment: 'Lottery', detail: 'PA Lottery' },
  { cash_payment: 'Skill MAchine', detail: 'Skill Machine Pay' },
  { cash_payment: 'Employee Pay', detail: 'Mike' },
  { cash_payment: 'Employee Pay', detail: 'John' },
  { cash_payment: 'Employee Pay', detail: 'Patel' },
  { cash_payment: 'Repairs', detail: null },
  { cash_payment: 'Store Supplies', detail: null },
  { cash_payment: 'Cleaning', detail: null },
  { cash_payment: 'Snow Removal', detail: null },
  { cash_payment: 'Office Supplies', detail: null },
  { cash_payment: 'Grocessary', detail: null },
  { cash_payment: 'Other Expense', detail: null },
]

const createDefaultCashReconciliations = () =>
  DEFAULT_CASH_RECONCILIATIONS.map((row) => ({
    ...row,
    amount: 0,
    is_default: true,
  }))

const createEmptyCashReconciliation = () => ({
  cash_payment: null,
  detail: null,
  amount: 0,
  is_default: false,
})

const createEmptyBankDeposit = () => ({
  bank_name: null,
  amount: 0,
})

const defaultRowKey = (row) => `${row?.cash_payment || ''}|${row?.detail || ''}`

const mergeDefaultCashReconciliations = (rows = []) => {
  const matched = {}
  const custom = []

  rows.forEach((row) => {
    if (row?.is_default) {
      matched[defaultRowKey(row)] = row
    } else {
      custom.push({ ...row, is_default: false })
    }
  })

  const defaults = createDefaultCashReconciliations().map((defaultRow) => {
    const existing = matched[defaultRowKey(defaultRow)]
    if (!existing) return defaultRow
    return {
      ...defaultRow,
      id: existing.id,
      amount: existing.amount ?? 0,
    }
  })

  return [...defaults, ...custom]
}

const ensureFormShape = () => {
  if (!form.value) return

  ;[
    'sales',
    'tax',
    'other',
    'round_off',
    'cash',
    'credit_card',
    'account',
    'check',
    'coupon',
    'other_payment',
    'total_payment',
    'cash_due',
  ].forEach((field) => {
    if (form.value[field] === null || form.value[field] === undefined || form.value[field] === '') {
      form.value[field] = 0
    }
  })

  form.value.cash_reconciliations = mergeDefaultCashReconciliations(
    Array.isArray(form.value.cash_reconciliations) ? form.value.cash_reconciliations : []
  )

  if (!Array.isArray(form.value.bank_deposits) || form.value.bank_deposits.length === 0) {
    form.value.bank_deposits = [createEmptyBankDeposit()]
  }
}

watch(
  () => show.value,
  (visible) => {
    if (visible) {
      ensureFormShape()
    }
  },
  { immediate: true }
)

watch(
  () => form.value,
  () => ensureFormShape(),
  { deep: false }
)

const toNumber = (value) => Number(value || 0)

const subTotal = computed(() => {
  return toNumber(form.value?.sales) + toNumber(form.value?.tax) + toNumber(form.value?.other)
})

const finalSalesTotal = computed(() => subTotal.value + toNumber(form.value?.round_off))

const paymentMethodsTotal = computed(() => {
  return (
    toNumber(form.value?.cash) +
    toNumber(form.value?.credit_card) +
    toNumber(form.value?.account) +
    toNumber(form.value?.check) +
    toNumber(form.value?.coupon) +
    toNumber(form.value?.other_payment)
  )
})

const totalPayment = computed(() => {
  return (form.value?.cash_reconciliations || []).reduce(
    (sum, row) => sum + toNumber(row.amount),
    0
  )
})

const bankDepositsTotal = computed(() => {
  return (form.value?.bank_deposits || []).reduce(
    (sum, row) => sum + toNumber(row.amount),
    0
  )
})

const cashDue = computed(() => toNumber(finalSalesTotal.value) - totalPayment.value)

const formatAmount = (value) => {
  return toNumber(value).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const addCashReconciliation = () => {
  form.value.cash_reconciliations.push(createEmptyCashReconciliation())
}

const removeCashReconciliation = (index) => {
  const row = form.value.cash_reconciliations[index]
  if (!row || row.is_default) return
  form.value.cash_reconciliations.splice(index, 1)
}

const addBankDeposit = () => {
  form.value.bank_deposits.push(createEmptyBankDeposit())
}

const removeBankDeposit = (index) => {
  if (form.value.bank_deposits.length === 1) return
  form.value.bank_deposits.splice(index, 1)
}

const getChildError = (group, index, field) => {
  return errors.value?.[`${group}.${index}.${field}`]?.[0] || null
}

const handleSave = async () => {
  ensureFormShape()

  const payload = {
    ...form.value,
    total: subTotal.value,
    total_payment: totalPayment.value,
    cash_due: cashDue.value,
    cash_reconciliations: (form.value.cash_reconciliations || []).map((row) => ({
      cash_payment: row.cash_payment || null,
      detail: row.detail || null,
      amount: toNumber(row.amount),
      is_default: !!row.is_default,
    })),
    bank_deposits: (form.value.bank_deposits || [])
      .filter((row) => row.bank_name || toNumber(row.amount) !== 0)
      .map((row) => ({
        bank_name: row.bank_name || null,
        amount: toNumber(row.amount),
      })),
  }

  await save(payload)
}

defineExpose({ setData })
</script>
