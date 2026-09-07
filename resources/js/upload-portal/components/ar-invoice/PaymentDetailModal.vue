<template>
  <Modal
    :model-value="modelValue"
    size="3xl"
    title="Payment details"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="flex justify-center py-16">
      <Spinner size="md" text="Loading..." />
    </div>
    <div v-else-if="loadError" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
      Unable to load payment details.
    </div>
    <div v-else-if="payment" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><span class="text-gray-500 dark:text-gray-400">Customer:</span> {{ payment.customer?.name || '—' }}</div>
        <div><span class="text-gray-500 dark:text-gray-400">Date:</span> {{ formatDate(payment.payment_date) }}</div>
        <div><span class="text-gray-500 dark:text-gray-400">Type:</span> {{ paymentTypeLabel(payment.payment_type) }}</div>
        <div><span class="text-gray-500 dark:text-gray-400">Applied to invoices:</span> {{ formatCurrency(payment.total_amount) }}</div>
        <div><span class="text-gray-500 dark:text-gray-400">Amount received:</span> {{ formatCurrency(payment.amount_received ?? payment.total_amount) }}</div>
        <div v-if="toNumber(payment.credit_applied) > 0">
          <span class="text-gray-500 dark:text-gray-400">Credit applied:</span> {{ formatCurrency(payment.credit_applied) }}
        </div>
        <div v-if="toNumber(payment.unapplied_amount) > 0">
          <span class="text-gray-500 dark:text-gray-400">Credit on account:</span>
          <span class="text-green-600 dark:text-green-400 font-medium">{{ formatCurrency(payment.unapplied_amount) }}</span>
        </div>
      </div>
      <div v-if="payment.remarks" class="text-sm">
        <span class="text-gray-500 dark:text-gray-400">Remarks:</span>
        <p class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap !mb-0">{{ payment.remarks }}</p>
      </div>
      <div v-if="(payment.credit_items || payment.creditItems || []).length" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto">
        <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
          Customer credit used
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="text-left py-2 px-2">Source payment date</th>
              <th class="text-right py-2 px-2">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="line in payment.credit_items || payment.creditItems || []" :key="line.id">
              <td class="py-2 px-2">{{ formatDate(sourcePaymentDate(line)) }}</td>
              <td class="py-2 px-2 text-right">{{ formatCurrency(line.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="text-left py-2 px-2">Invoice #</th>
              <th class="text-left py-2 px-2">Invoice Date</th>
              <th class="text-right py-2 px-2">Paid</th>
              <th class="text-right py-2 px-2">Discount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="line in payment.items || []" :key="line.id">
              <td class="py-2 px-2">{{ invoiceNumber(line) }}</td>
              <td class="py-2 px-2">{{ formatDate(line.sales_invoice?.invoice_date) }}</td>
              <td class="py-2 px-2 text-right">{{ formatCurrency(line.amount_applied) }}</td>
              <td class="py-2 px-2 text-right">{{ formatCurrency(line.discount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Spinner from '@/components/ui/spinner.vue'
import axios from '../../plugins/axios'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  paymentId: { type: [Number, String], default: null },
})

defineEmits(['update:modelValue', 'edit', 'deleted'])

const message = useMessage()

const loading = ref(false)
const loadError = ref(false)
const payment = ref(null)

const paymentTypeOptions = [
  { id: 'cash', name: 'Cash' },
  { id: 'check', name: 'Check' },
  { id: 'credit_debit', name: 'Credit/Debit' },
  { id: 'echeck_ach', name: 'E-Check ACH' },
]

const paymentTypeLabel = (id) => {
  if (id === 'customer_credit') return 'Customer credit'
  return paymentTypeOptions.find((o) => o.id === id)?.name || id
}

const toNumber = (v) => {
  const n = parseFloat(String(v).replace(/,/g, ''))
  return Number.isFinite(n) ? n : 0
}

const invoiceNumber = (line) =>
  line.sales_invoice?.invoice_number ?? line.salesInvoice?.invoice_number ?? '—'

const sourcePaymentDate = (line) =>
  line.source_payment?.payment_date ?? line.sourcePayment?.payment_date ?? null

const loadPayment = async () => {
  if (!props.paymentId) {
    payment.value = null
    return
  }
  loading.value = true
  loadError.value = false
  try {
    const { data } = await axios.get(`/upload-portal/api/customer-payments/${props.paymentId}`)
    if (data?.success) {
      payment.value = data.payment ?? null
      if (!payment.value) loadError.value = true
    } else {
      payment.value = null
      loadError.value = true
    }
  } catch (error) {
    payment.value = null
    loadError.value = true
    message.error(error.response?.data?.message || 'Failed to load payment')
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.modelValue, props.paymentId],
  ([open, id]) => {
    if (open && id) {
      loadPayment()
    } else if (!open) {
      payment.value = null
      loadError.value = false
    }
  },
  { immediate: true }
)
</script>
