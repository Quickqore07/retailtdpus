<template>
  <div v-if="show" class="daily-sales-form">
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
          <Input v-model="form.net_sales" type="number" step="0.01" min="0" label="Net Sales" :error="errors.net_sales?.[0] || null" />
          <Input v-model="form.beverage_tax" type="number" step="0.01" min="0" label="Beverage Tax" :error="errors.beverage_tax?.[0] || null" />
          <Input v-model="form.food_tax" type="number" step="0.01" min="0" label="Food Tax" :error="errors.food_tax?.[0] || null" />
          <Input :model-value="formatAmount(totals.total_sales)" label="Total Sales" disabled />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
          <h6 class="font-semibold mb-3">Cash Reconciliation</h6>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Input v-model="form.cash_received" type="number" step="0.01" min="0" label="Cash Received" :error="errors.cash_received?.[0] || null" />
            <Input v-model="form.partial_void" type="number" step="0.01" min="0" label="Partial Void" :error="errors.partial_void?.[0] || null" />
            <Input :model-value="formatAmount(totals.total_cash)" label="Total Cash" disabled />
            <div></div>

            <Input v-model="form.tips" type="number" step="0.01" min="0" label="Tips" :error="errors.tips?.[0] || null" />
            <Input v-model="form.mileage" type="number" step="0.01" min="0" label="Mileage" :error="errors.mileage?.[0] || null" />
            <Input :model-value="formatAmount(totals.total_tips_mileage)" label="Total Tips Mileage" disabled />
            <div></div>

            <Input v-model="form.dd_tips" type="number" step="0.01" min="0" label="DD Tips" :error="errors.dd_tips?.[0] || null" />
            <Input v-model="form.e_tips" type="number" step="0.01" min="0" label="E Tips" :error="errors.e_tips?.[0] || null" />
            <Input v-model="form.e_tips_payroll" type="number" step="0.01" min="0" label="E Tips Payroll" :error="errors.e_tips_payroll?.[0] || null" />
            <Input :model-value="formatAmount(totals.total_e_and_dd_tips)" label="Total E and DD Tips" disabled />

            <Input :model-value="formatAmount(totals.cash_payment)" label="Cash Payment" disabled />
            <Input :model-value="formatAmount(totals.other_payments_total)" label="Other Payments" disabled />
            <Input :model-value="formatAmount(totals.total_cash_payment)" label="Total Cash Payment" disabled />
            <div></div>
            <Input :model-value="formatAmount(totals.net_cash_due)" label="Net Cash Due" disabled />

            <Input v-model="form.cash_bag" type="number" step="0.01" min="0" label="Cash Bag" :error="errors.cash_bag?.[0] || null" />
            <Input :model-value="formatAmount(totals.short_over)" label="Short / Over" disabled />
          </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold !mb-0">Other Payments</h6>
            <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addOtherPayment">
              Add Row
            </Button>
          </div>

          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <Th class="text-left px-3 py-3 w-14">No</Th>
                <Th class="text-left px-3 py-3 min-w-[260px]">Expense</Th>
                <Th class="text-left px-3 py-3 min-w-[170px]">Amount</Th>
                <Th class="text-left px-3 py-3 w-16">Actions</Th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in form.other_payments"
                :key="`other-payment-${index}`"
                class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <Td class="px-3 py-3">{{ index + 1 }}</Td>
                <Td class="px-3 py-3">
                  <select
                    v-model="item.expense"
                    class="w-full px-3 py-1 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
                  >
                    <option :value="null">Select expense</option>
                    <option v-for="option in expenseOptions" :key="option" :value="option">{{ option }}</option>
                  </select>
                  <p v-if="getOtherPaymentError(index, 'expense')" class="text-xs text-red-600 dark:text-red-400 mt-1">
                    {{ getOtherPaymentError(index, 'expense') }}
                  </p>
                </Td>
                <Td class="px-3 py-3">
                  <Input
                    v-model="item.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    :error="getOtherPaymentError(index, 'amount')"
                  />
                </Td>
                <Td class="px-3 py-3">
                  <Button
                    type="button"
                    variant="outline-danger"
                    size="sm"
                    icon-left="trash"
                    @click="removeOtherPayment(index)"
                    :disabled="form.other_payments.length === 1"
                  />
                </Td>
              </tr>
              <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                <Td class="px-3 py-3" colspan="2">Total</Td>
                <Td class="px-3 py-3">{{ formatAmount(totals.other_payments_total) }}</Td>
                <Td class="px-3 py-3"></Td>
              </tr>
            </tbody>
          </table>
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

  <Modal
    v-model="showPartialVoidWarning"
    title="Partial Void Warning"
    size="sm"
    :show-footer="true"
    :show-cancel="true"
    :show-confirm="true"
    cancel-text="Cancel"
    confirm-text="Save"
    :loading="isSaving"
    @close="showPartialVoidWarning = false"
    @confirm="confirmPartialVoidSave"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Partial Void is <span class="font-semibold">${{ formatAmount(form.partial_void) }}</span>.
      Are you sure you want to save this daily sale?
    </p>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Modal from '@/components/common/Modal.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/daily-sales'
const expenseOptions = ['Small Maintenance', 'Office Expense', 'Food', 'Supplies', 'MISC']

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'data-entry/daily-sales',
  'daily-sales'
) 

const createEmptyOtherPayment = () => ({
  expense: null,
  amount: 0
})

const ensureFormShape = () => {
  if (!form.value) return

  const numericFields = [
    'net_sales',
    'beverage_tax',
    'food_tax',
    'cash_received',
    'partial_void',
    'tips',
    'mileage',
    'dd_tips',
    'e_tips',
    'e_tips_payroll',
    'cash_bag'
  ]

  numericFields.forEach((field) => {
    form.value[field] = Number(form.value[field] || 0)
  })

  if (!Array.isArray(form.value.other_payments) || !form.value.other_payments.length) {
    form.value.other_payments = [createEmptyOtherPayment()]
    return
  }

  form.value.other_payments = form.value.other_payments.map((item) => ({
    ...createEmptyOtherPayment(),
    ...item,
    amount: Number(item?.amount || 0)
  }))
}

watch(
  () => form.value,
  () => {
    ensureFormShape()
  },
  { immediate: true }
)

const toNumber = (value) => Number(value || 0)
const formatAmount = (value) => Number(value || 0).toFixed(2)

const totals = computed(() => {
  const totalSales = toNumber(form.value.net_sales) + toNumber(form.value.beverage_tax) + toNumber(form.value.food_tax)
  const totalCash = toNumber(form.value.cash_received) + toNumber(form.value.partial_void)
  const totalTipsMileage = toNumber(form.value.tips) + toNumber(form.value.mileage)
  const totalEAndDdTips = toNumber(form.value.dd_tips) + toNumber(form.value.e_tips) + toNumber(form.value.e_tips_payroll)
  const cashPayment = totalTipsMileage - totalEAndDdTips
  const otherPaymentsTotal = (form.value.other_payments || []).reduce((sum, row) => sum + toNumber(row.amount), 0)
  const totalCashPayment = cashPayment + otherPaymentsTotal
  const netCashDue = totalCash - totalCashPayment
  const shortOver = toNumber(form.value.cash_bag) - netCashDue

  return {
    total_sales: Number(totalSales.toFixed(2)),
    total_cash: Number(totalCash.toFixed(2)),
    total_tips_mileage: Number(totalTipsMileage.toFixed(2)),
    total_e_and_dd_tips: Number(totalEAndDdTips.toFixed(2)),
    cash_payment: Number(cashPayment.toFixed(2)),
    other_payments_total: Number(otherPaymentsTotal.toFixed(2)),
    total_cash_payment: Number(totalCashPayment.toFixed(2)),
    net_cash_due: Number(netCashDue.toFixed(2)),
    short_over: Number(shortOver.toFixed(2))
  }
})

const addOtherPayment = () => {
  form.value.other_payments.push(createEmptyOtherPayment())
}

const removeOtherPayment = (index) => {
  if (form.value.other_payments.length === 1) return
  form.value.other_payments.splice(index, 1)
}

const getOtherPaymentError = (index, field) => {
  return errors.value?.[`other_payments.${index}.${field}`]?.[0] || null
}

const showPartialVoidWarning = ref(false)

const buildPayload = () => ({
  date: form.value.date,
  net_sales: toNumber(form.value.net_sales),
  beverage_tax: toNumber(form.value.beverage_tax),
  food_tax: toNumber(form.value.food_tax),
  cash_received: toNumber(form.value.cash_received),
  partial_void: toNumber(form.value.partial_void),
  tips: toNumber(form.value.tips),
  mileage: toNumber(form.value.mileage),
  dd_tips: toNumber(form.value.dd_tips),
  e_tips: toNumber(form.value.e_tips),
  e_tips_payroll: toNumber(form.value.e_tips_payroll),
  cash_bag: toNumber(form.value.cash_bag),
  other_payments: (form.value.other_payments || [])
    .filter((row) => row.expense || toNumber(row.amount) > 0)
    .map((row) => ({
      expense: row.expense,
      amount: toNumber(row.amount)
    }))
})

const submitDailySale = async () => {
  await save(buildPayload())
}

const handleSave = async () => {
  if (mode.value === 'edit' && toNumber(form.value.partial_void) !== 0) {
    showPartialVoidWarning.value = true
    return
  }

  await submitDailySale()
}

const confirmPartialVoidSave = async () => {
  showPartialVoidWarning.value = false
  await submitDailySale()
}

defineExpose({
  setData
})
</script>
