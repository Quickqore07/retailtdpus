<template>
  <Modal
    :model-value="modelValue"
    size="5xl"
    :show-header="false"
    body-class="!p-0"
    content-class="invoice-modal-content"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="no-print flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 rounded-t-lg">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 !mb-0">Invoice Preview</h3>
      <div class="flex items-center gap-2">
        <Button
          variant="outline-secondary"
          size="sm"
          icon-left="download"
          :disabled="loading || !invoice || downloading"
          :loading="downloading"
          @click="downloadInvoicePdf"
        >
          Download PDF
        </Button>
        <Button
          variant="outline-secondary"
          size="sm"
          @click="$emit('update:modelValue', false)"
        >
          Close
        </Button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 flex justify-center">
      <Spinner size="lg" text="Loading invoice..." />
    </div>

    <!-- Empty -->
    <div v-else-if="!invoice" class="py-20 text-center text-sm text-gray-500 dark:text-gray-400">
      Unable to load invoice details.
    </div>

    <!-- Invoice Body -->
    <div v-else class="invoice-sheet bg-white text-gray-900 px-10 py-10">
      <!-- Header -->
      <div class="flex items-start justify-between gap-8 pb-6 border-b-2 border-gray-900">
        <div class="text-left shrink-0">
          <p class="text-2xl font-bold text-gray-900 !mb-1">{{ invoice.company_name || 'N/A' }}</p>
          <p class="text-sm text-gray-700 !mb-0 max-w-xs leading-relaxed">
            {{ invoice.company_address || 'N/A' }}
          </p>
        </div>
        <div class="text-right">
          <h1 class="text-3xl font-bold tracking-wider text-gray-900 !mb-1">INVOICE</h1>
          <p class="text-sm text-gray-600 !mb-0.5">
            <span class="font-medium">No:</span> {{ invoice.invoice_number || '-' }}
          </p>
          <span
            :class="statusClass(invoice.status)"
            class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold capitalize no-print"
          >
            {{ invoice.status || 'draft' }}
          </span>
        </div>
      </div>

      <!-- Meta: Bill To + Dates -->
      <div class="flex justify-between mt-6">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 !mb-2">Bill To</p>
          <p class="text-base font-semibold text-gray-900 !mb-1">{{ customer?.name || '-' }}</p>
          <p v-if="customer?.primary_person_name" class="text-sm text-gray-700 !mb-0.5">
            Attn: {{ customer.primary_person_name }}
          </p>
          <p v-if="customer?.address_line_1" class="text-sm text-gray-700 !mb-0.5">{{ customer.address_line_1 }}</p>
          <p v-if="customer?.address_line_2" class="text-sm t ext-gray-700 !mb-0.5">{{ customer.address_line_2 }}</p>
          <p v-if="customer?.address_line_3" class="text-sm text-gray-700 !mb-0.5">{{ customer.address_line_3 }}</p>
            <p v-if="cityStateZip" class="text-sm text-gray-700 !mb-0.5">{{ cityStateZip }}</p>
          <p v-if="customer?.country" class="text-sm text-gray-700 !mb-0.5">{{ customer.country }}</p>
          <p v-if="customer?.email" class="text-sm text-gray-700 !mb-0.5 mt-2">
            <span class="font-medium">Email:</span> {{ customer.email }}
          </p>
          <p v-if="customer?.mobile" class="text-sm text-gray-700 !mb-0">
            <span class="font-medium">Phone:</span> {{ customer.mobile }}
          </p>
        </div>

        <div class="md:text-right">
          <div class="inline-block text-left md:text-right">
            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
              <span class="text-gray-500">Invoice Date</span>
              <span class="text-gray-900 font-medium">{{ formatDate(invoice.invoice_date) }}</span>

              <span class="text-gray-500">Due Date</span>
              <span class="text-gray-900 font-medium">{{ formatDate(invoice.due_date) }}</span>

              <span class="text-gray-500">Balance Due</span>
              <span class="text-gray-900 font-semibold">{{ formatMoney(balanceDue) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="mt-8">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              <th class="text-left px-3 py-2.5 w-10 font-semibold">#</th>
              <th class="text-left px-3 py-2.5 font-semibold">Description</th>
              <th class="text-right px-3 py-2.5 w-20 font-semibold">Qty</th>
              <th class="text-right px-3 py-2.5 w-28 font-semibold">Unit Price</th>
              <th class="text-right px-3 py-2.5 w-24 font-semibold">Discount</th>
              <th class="text-right px-3 py-2.5 w-24 font-semibold">Tax</th>
              <th class="text-right px-3 py-2.5 w-28 font-semibold">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in items"
              :key="item.id || idx"
              class="border-b border-gray-200 dark:border-gray-700"
            >
              <td class="px-3 py-2.5 text-gray-700 align-top">{{ idx + 1 }}</td>
              <td class="px-3 py-2.5 text-gray-900 align-top">{{ item.item_name || '-' }}</td>
              <td class="px-3 py-2.5 text-right text-gray-700 align-top">{{ formatQty(item.qty) }}</td>
              <td class="px-3 py-2.5 text-right text-gray-700 align-top">{{ formatMoney(item.unit_price) }}</td>
              <td class="px-3 py-2.5 text-right text-gray-700 align-top">{{ formatMoney(item.discount) }}</td>
              <td class="px-3 py-2.5 text-right text-gray-700 align-top">{{ formatMoney(item.tax_amount) }}</td>
              <td class="px-3 py-2.5 text-right text-gray-900 font-medium align-top">{{ formatMoney(item.total) }}</td>
            </tr>
            <tr v-if="items.length === 0">
              <td colspan="7" class="px-3 py-6 text-center text-gray-500">No line items.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="flex justify-end mt-6">
        <div class="w-full max-w-xs">
          <div class="flex items-center justify-between py-1.5 text-sm text-gray-700">
            <span>Subtotal</span>
            <span>{{ formatMoney(invoice.subtotal ?? computedSubtotal) }}</span>
          </div>
          <div class="flex items-center justify-between py-1.5 text-sm text-gray-700">
            <span>Discount</span>
            <span>-{{ formatMoney(invoice.discount_total ?? computedDiscount) }}</span>
          </div>
          <div class="flex items-center justify-between py-1.5 text-sm text-gray-700 border-b border-gray-300">
            <span>Tax</span>
            <span>+{{ formatMoney(invoice.tax_total ?? computedTax) }}</span>
          </div>
          <div class="flex items-center justify-between py-2 text-base font-bold text-gray-900">
            <span>Total</span>
            <span>{{ formatMoney(invoice.amount ?? computedTotal) }}</span>
          </div>
          <div class="no-print">
            <template v-for="(payment, pIdx) in invoice.payment_summary" :key="`${payment.id}-${pIdx}`">
              <button
                v-if="canViewPayment"
                type="button"
                class="flex w-full items-center justify-between gap-2 py-1.5 text-sm text-gray-700 text-left rounded -mx-1 px-1 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800/60 cursor-pointer"
                @click="openPaymentDetail(payment.id)"
              >
                <span class="underline-offset-2 hover:underline">Paid ({{ payment.payment_type }}) on {{ formatDate(payment.payment_date) }}</span>
                <span class="shrink-0 font-medium tabular-nums">{{ formatMoney(payment.amount_applied) }}</span>
              </button>
              <div
                v-else
                class="flex items-center justify-between py-1.5 text-sm text-gray-700"
              >
                <span>Paid ({{ payment.payment_type }}) on {{ formatDate(payment.payment_date) }}</span>
                <span>{{ formatMoney(payment.amount_applied + (payment.discount ?? 0)) }}</span>
              </div>
            </template>
            <div class="bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-gray-100 flex items-center justify-between px-3 py-2 mt-2 rounded-sm">
              <span class="text-xs uppercase tracking-wider">Amount Due</span>
              <span class="text-base font-bold">{{ formatMoney(balanceDue) }}</span>
            </div>
          </div>
        </div>
      </div> 

      <!-- Notes -->
      <div v-if="invoice.remarks" class="mt-8 pt-4 border-t border-gray-200">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 !mb-1">Notes</p>
        <p class="text-sm text-gray-700 whitespace-pre-wrap !mb-0">{{ invoice.remarks }}</p>
      </div>

      <!-- Footer -->
      <!-- <div class="mt-10 pt-4 border-t border-gray-200 flex items-end justify-end gap-6">
        <div class="text-right">
          <p class="text-sm font-semibold text-gray-900 !mb-0.5">Thank you for your business!</p>
          <p class="text-xs text-gray-500 !mb-0">Please make payment by the due date.</p>
        </div>
      </div> -->

    </div>
  </Modal>

  <PaymentDetailModal
    v-if="showPaymentDetail"
    v-model="showPaymentDetail"
    :payment-id="paymentDetailId"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from '../../plugins/axios'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import PaymentDetailModal from './PaymentDetailModal.vue'
import { useUser } from '../../composables/useUser'
import { formatDate } from '@/utils/date'

const { can } = useUser()
const canViewPayment = computed(() => can('upload-portal-customer-payment', 'view'))

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  invoiceId: { type: [Number, String], default: null },
})

defineEmits(['update:modelValue'])

const loading = ref(false)
const downloading = ref(false)
const invoice = ref(null)

const showPaymentDetail = ref(false)
const paymentDetailId = ref(null)

const openPaymentDetail = (id) => {
  if (!id || !canViewPayment.value) return
  paymentDetailId.value = id
  showPaymentDetail.value = true
}

const customer = computed(() => invoice.value?.customer || null)
const items = computed(() => invoice.value?.items || [])

const cityStateZip = computed(() => {
  if (!customer.value) return ''
  const parts = [customer.value.city, customer.value.state].filter(Boolean).join(', ')
  return [parts, customer.value.zip_code].filter(Boolean).join(' ')
})


const toNumber = (v) => {
  const n = parseFloat(v)
  return Number.isFinite(n) ? n : 0
}

const computedSubtotal = computed(() =>
  items.value.reduce((sum, r) => sum + toNumber(r.qty) * toNumber(r.unit_price), 0)
)
const computedDiscount = computed(() =>
  items.value.reduce((sum, r) => sum + toNumber(r.discount), 0)
)
const computedTax = computed(() =>
  items.value.reduce((sum, r) => sum + toNumber(r.tax_amount), 0)
)
const computedTotal = computed(() =>
  items.value.reduce((sum, r) => sum + toNumber(r.total), 0)
)

const balanceDue = computed(() => {
  const inv = invoice.value
  if (!inv) return 0
  if (inv.balance_due !== undefined && inv.balance_due !== null) {
    return toNumber(inv.balance_due)
  }
  return toNumber(inv.amount ?? computedTotal.value)
})


const formatMoney = (v) => {
  const num = toNumber(v)
  return num.toLocaleString('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const formatQty = (v) => {
  const num = toNumber(v)
  return num % 1 === 0 ? num.toString() : num.toFixed(2)
}

const statusClass = (status) => {
  switch ((status || '').toLowerCase()) {
    case 'paid':
      return 'bg-green-100 text-green-700 border border-green-300'
    case 'sent':
      return 'bg-blue-100 text-blue-700 border border-blue-300'
    case 'cancelled':
      return 'bg-red-100 text-red-700 border border-red-300'
    case 'draft':
    default:
      return 'bg-gray-100 text-gray-700 border border-gray-300'
  }
}

const loadInvoice = async () => {
  if (!props.invoiceId) {
    invoice.value = null
    return
  }
  loading.value = true
  try {
    const { data } = await axios.get(`/upload-portal/api/sales-invoices/${props.invoiceId}`)
    if (data?.success) {
      invoice.value = data.invoice || null
    } else {
      invoice.value = null
    }
  } catch (error) {
    invoice.value = null
    console.error('Error loading invoice:', error)
  } finally {
    loading.value = false
  }
}

const downloadInvoicePdf = async () => {
  if (!props.invoiceId || !invoice.value) return
  downloading.value = true
  try {
    const res = await axios.get(`/upload-portal/api/sales-invoices/${props.invoiceId}/pdf`, {
      responseType: 'blob',
      headers: { Accept: 'application/pdf' },
    })
    const contentType = (res.headers['content-type'] || '').toLowerCase()
    if (contentType.includes('application/json')) {
      const text = await res.data.text()
      let message = 'Could not download PDF.'
      try {
        const parsed = JSON.parse(text)
        message = parsed.message || message
      } catch {
        /* ignore */
      }
      console.error(message)
      return
    }
    const blob = res.data instanceof Blob ? res.data : new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    const num = invoice.value.invoice_number || props.invoiceId
    a.download = `invoice-${String(num).replace(/[^A-Za-z0-9._-]+/g, '-')}.pdf`
    a.rel = 'noopener'
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    const res = error.response
    const ctype = (res?.headers?.['content-type'] || '').toLowerCase()
    if (res?.data instanceof Blob && ctype.includes('application/json')) {
      try {
        const text = await res.data.text()
        const parsed = JSON.parse(text)
        console.error(parsed.message || 'Could not download PDF.')
      } catch {
        console.error('Could not download PDF.')
      }
    } else {
      console.error('Error downloading invoice PDF:', error)
    }
  } finally {
    downloading.value = false
  }
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      loadInvoice()
    } else {
      invoice.value = null
      showPaymentDetail.value = false
      paymentDetailId.value = null
    }
  }
)

watch(showPaymentDetail, (open) => {
  if (!open) {
    paymentDetailId.value = null
  }
})
</script>

<style scoped>
.invoice-sheet {
  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
  color: #111827;
}

</style>
