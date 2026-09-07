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
                      {{ formatAmount(openingBalance) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 w-[20%]"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 w-[20%]"></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Bank deposit</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                      <Input
                        v-model="form.bank_deposits"
                        type="number"
                        step="0.01"
                        :error="errors.bank_deposits?.[0] || null"
                      />
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-medium">Cash due</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right">
                      {{ formatAmount(cashDue) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-semibold">Closing balance</td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-right font-semibold">
                      {{ formatAmount(closingBalance) }}
                    </td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
                    <td class="border border-gray-200 dark:border-gray-700 px-3 py-2"></td>
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
                    <select
                      v-model="item.cash_payment"
                      class="w-full px-2 py-1.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
                    >
                      <option :value="null">Select payment</option>
                      <option
                        v-for="option in cashPaymentOptions"
                        :key="option"
                        :value="option"
                      >
                        {{ option }}
                      </option>
                    </select>
                    <p
                      v-if="getChildError('cash_reconciliations', index, 'cash_payment')"
                      class="text-xs text-red-600 dark:text-red-400 mt-1"
                    >
                      {{ getChildError('cash_reconciliations', index, 'cash_payment') }}
                    </p>
                  </td>
                  <td class="border border-gray-200 dark:border-gray-700 px-2 py-1">
                    <Input
                      v-model="item.detail"
                      type="text"
                      placeholder="Detail"
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
                      type="button"
                      variant="outline-danger"
                      size="xs"
                      icon-left="trash"
                      :disabled="form.cash_reconciliations.length <= 1"
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
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import { useRequest } from '@/services/api'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/daily-sales'

const { form, errors, isSaving, show, mode, save, cancel, setData: baseSetData, access } = useFormable(
  resource,
  'data-entry/daily-sales',
  'daily-sales'
)

const cashPaymentOptions = [
  'Lottery',
  'Skill MAchine',
  'Employee Pay',
  'Repairs',
  'Store Supplies',
  'Cleaning',
  'Snow Removal',
  'Office Supplies',
  'Grocessary',
  'Other Expense',
]

const openingBalance = ref(0)

const createEmptyCashReconciliation = () => ({
  cash_payment: null,
  detail: null,
  amount: 0,
  is_default: false,
})

const padCashReconciliations = (rows = [], min = 5) => {
  const next = [...rows]
  while (next.length < min) {
    next.push(createEmptyCashReconciliation())
  }
  return next
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
    'bank_deposits',
  ].forEach((field) => {
    if (form.value[field] === null || form.value[field] === undefined || form.value[field] === '') {
      form.value[field] = 0
    }
  })

  form.value.cash_reconciliations = padCashReconciliations(
    Array.isArray(form.value.cash_reconciliations) ? form.value.cash_reconciliations : []
  )

  if (form.value.opening_balance !== undefined && form.value.opening_balance !== null) {
    openingBalance.value = toNumber(form.value.opening_balance)
  }
}

const setData = (res) => {
  baseSetData(res)
  ensureFormShape()
}

const fetchOpeningBalance = async () => {
  if (!form.value?.date) return

  try {
    const res = await useRequest('get', 'data-entry/daily-sales/opening-balance', null, {
      params: {
        date: form.value.date,
        ignore_id: mode.value === 'edit' ? route.params.id : undefined,
      },
    })
    openingBalance.value = toNumber(res?.data?.opening_balance ?? res?.opening_balance)
    form.value.opening_balance = openingBalance.value
  } catch (error) {
    console.error('Failed to load opening balance', error)
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

watch(
  () => form.value?.date,
  (date, previous) => {
    if (!show.value || !date || date === previous) return
    fetchOpeningBalance()
  }
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

const cashDue = computed(() => toNumber(form.value?.cash) - totalPayment.value)

const closingBalance = computed(() => {
  return openingBalance.value - toNumber(form.value?.bank_deposits) + cashDue.value
})

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
  if (form.value.cash_reconciliations.length <= 1) return
  form.value.cash_reconciliations.splice(index, 1)
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
    bank_deposits: toNumber(form.value.bank_deposits),
    cash_reconciliations: (form.value.cash_reconciliations || [])
      .filter((row) => row.cash_payment || row.detail || toNumber(row.amount) !== 0)
      .map((row) => ({
        cash_payment: row.cash_payment || null,
        detail: row.detail || null,
        amount: toNumber(row.amount),
        is_default: false,
      })),
  }

  await save(payload)
}

defineExpose({ setData })
</script>
