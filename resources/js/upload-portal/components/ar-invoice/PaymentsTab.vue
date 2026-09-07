<template>
  <div>

    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Payments</h4>
    </div>
    <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div class="w-full md:w-1/3">
          <Input
            v-model="listSearch"
            placeholder="Search by customer name..."
            @input="debounceListSearch"
          />
        </div>
        <Button
          v-if="can('upload-portal-customer-payment', 'add')"
          @click="openAddModal"
          variant="primary"
          size="sm"
          icon-left="plus"
          icon-size="md"
        >
          Add payment
        </Button>
      </div>
    </div>

    <div v-if="!can('upload-portal-customer-payment', 'index')" class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl">
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">You do not have permission to view payments.</p>
    </div>

    <div v-else-if="loadingPayments" class="flex flex-col items-center justify-center py-20">
      <Spinner size="lg" text="Loading payments..." />
    </div>

    <div
      v-else-if="payments.length > 0"
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
    >
      <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 dark:border-gray-700">
            <tr>
              <Th align="left" custom-class="py-4">Date</Th>
              <Th align="left" custom-class="py-4">Customer</Th>
              <Th align="left" custom-class="py-4">Payment type</Th>
              <Th align="right" custom-class="py-4">Applied</Th>
              <Th align="right" custom-class="py-4">Received</Th>
              <Th align="right" custom-class="py-4">Credit on account</Th>
              <Th align="right" custom-class="py-4">Actions</Th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="p in payments"
              :key="p.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
              :class="{ 'opacity-50 pointer-events-none': deletingId === p.id }"
            >
              <Td custom-class="py-4">{{ formatDate(p.payment_date) }}</Td>
              <Td custom-class="py-4">{{ p.customer?.name || '-' }}</Td>
              <Td custom-class="py-4">{{ paymentTypeLabel(p.payment_type) }}</Td>
              <Td align="right" custom-class="py-4">{{ formatCurrency(p.total_amount) }}</Td>
              <Td align="right" custom-class="py-4">{{ formatCurrency(p.amount_received ?? p.total_amount) }}</Td>
              <Td align="right" custom-class="py-4">
                <span v-if="toNumber(p.unapplied_amount) > 0" class="text-green-600 dark:text-green-400">
                  {{ formatCurrency(p.unapplied_amount) }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </Td>
              <Td align="right" custom-class="py-4">
                <div class="flex items-center justify-end">
                  <IconMenuDropdown v-if="can('upload-portal-customer-payment', 'view') || can('upload-portal-customer-payment', 'edit') || can('upload-portal-customer-payment', 'delete')" title="Actions">
                    <template #default="{ close }">
                      <button
                        v-if="can('upload-portal-customer-payment', 'view')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="openViewModal(p.id); close()"
                      >
                        <SvgIcon name="eye" size="sm" />
                        View
                      </button>
                      <button
                        v-if="can('upload-portal-customer-payment', 'edit')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="openEditModal(p.id); close()"
                      >
                        <SvgIcon name="edit" size="sm" />
                        Edit
                      </button>
                      <button
                        v-if="can('upload-portal-customer-payment', 'delete')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        role="menuitem"
                        :disabled="deletingId === p.id"
                        @click="deletePayment(p); close()"
                      >
                        <Spinner v-if="deletingId === p.id" size="sm" />
                        <SvgIcon v-else name="trash" size="sm" />
                        {{ deletingId === p.id ? 'Deleting...' : 'Delete' }}
                      </button>
                    </template>
                  </IconMenuDropdown>
                  <span v-else class="text-sm text-gray-400">—</span>
                </div>
              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl">
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">No payments recorded yet.</p>
    </div>

    <div v-if="can('upload-portal-customer-payment', 'index') && payments.length > 0" class="mt-6">
      <Pagination
        :collection="pagination"
        :loading="loadingPayments"
        :limit="perPage"
        @page-change="onPaymentsPageChange"
      />
    </div>

    <!-- Add / Edit payment -->
    <Modal
      v-if="showAddModal"
      :model-value="showAddModal"
      size="6xl"
      :title="editingPaymentId ? 'Edit payment' : 'Add payment'"
      @update:model-value="(open) => { if (!open) cancelAddModal() }"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <DynamicDropdown
            v-model="selectedCustomer"
            label="Customer"
            :custom-options="customers"
            display-name="name"
            placeholder="Select customer"
            :searchable="true"
            :disabled="loadingCustomers || !!editingPaymentId"
            remove-null-option
            @change="onCustomerChange"
          />
          <Input
            v-model="paymentDate"
            label="Date"
            type="date"
          />
          <DynamicDropdown
            v-model="selectedPaymentType"
            label="Payment type"
            :custom-options="paymentTypeOptions"
            display-name="name"
            placeholder="Select payment type"
            remove-null-option
            :disabled="isCreditOnlyPayment"
          />
        </div>
        <div
          v-if="selectedCustomer && availableCreditBalance > 0"
          class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200"
        >
          Available customer credit: <span class="font-semibold">{{ formatCurrency(availableCreditBalance) }}</span>
        </div>
        <div
          v-if="mixedQuickqoreInvoices"
          class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200"
        >
          You cannot add a Quickqore-linked invoice together with an invoice that is not linked to Quickqore. Select invoices of only one type.
        </div>
        <div v-if="selectedCustomer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            v-model="amountReceived"
            label="Amount received"
            type="text"
            inputmode="decimal"
            placeholder="0.00"
            help-text="Cash, check, or card amount received from the customer"
          />
          <div>
            <Input
              v-model="creditApplied"
              label="Apply customer credit"
              type="text"
              inputmode="decimal"
              placeholder="0.00"
              :disabled="availableCreditBalance <= 0"
              help-text="Use existing credit from overpayments"
            />
            <button
              v-if="availableCreditBalance > 0"
              type="button"
              class="mt-1 text-xs text-primary-600 hover:underline dark:text-primary-400"
              @click="applyMaxCredit"
            >
              Use all available credit
            </button>
          </div>
        </div>
        <Textarea
          v-model="remarks"
          label="Remarks"
          placeholder="Optional notes about this payment"
          :rows="3"
        />
        <div v-if="!selectedCustomer" class="text-center py-10 border border-dashed border-gray-200 dark:border-gray-600 rounded-lg">
          <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">Select a customer to load open invoices.</p>
        </div>

        <div
          v-else-if="!canViewEligibleInvoices"
          class="text-center py-10 border border-dashed border-gray-200 dark:border-gray-600 rounded-lg"
        >
          <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">You do not have permission to load invoice balances.</p>
        </div>

        <div v-else-if="loadingInvoices" class="flex justify-center py-12">
          <Spinner size="md" text="Loading invoices..." />
        </div>

        <div
          v-else-if="dueInvoices.length > 0"
          class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto"
        >
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="text-center py-2 px-2 w-10"></th>
                <th class="text-left py-2 px-2 font-medium text-gray-700 dark:text-gray-300">Invoice #</th>
                <th class="text-left py-2 px-2 font-medium text-gray-700 dark:text-gray-300">Invoice date</th>
                <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300">Original amount</th>
                <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300">Due amount</th>
                <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Paid</th>
                <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Discount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="invoice in dueInvoices" :key="invoice.id">
                <td class="py-2 px-2 text-center">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                    :checked="invoice.amount_applied > 0"
                    @change="onToggleRow(invoice, $event.target.checked)"
                  />
                </td>
                <td class="py-2 px-2 font-medium text-gray-900 dark:text-white">
                  {{ invoice.invoice_number }}
                  <span
                    v-if="isQqLinkedInvoice(invoice)"
                    class="ml-1 inline-flex rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                  >QQ</span>
                </td>
                <td class="py-2 px-2">{{ formatDate(invoice.invoice_date) }}</td>
                <td class="py-2 px-2 text-right">{{ formatCurrency(originalAmount(invoice)) }}</td>
                <td class="py-2 px-2 text-right">{{ formatCurrency(dueBalance(invoice)) }}</td>
                <td class="py-2 px-2 text-right">
                  <Input
                    v-model="invoice.amount_applied"
                    type="text"
                    inputmode="decimal"
                    placeholder="0.00"
                  />
                </td>
                <td class="py-2 px-2 text-right">
                  <Input
                    v-model="invoice.discount"
                    type="text"
                    inputmode="decimal"
                    placeholder="0.00"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else
          class="text-center py-10 border border-dashed border-gray-200 dark:border-gray-600 rounded-lg"
        >
          <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">No open invoices for this customer.</p>
        </div>

        <div
          v-if="selectedCustomer && (totalAppliedToInvoices > 0 || toNumber(amountReceived) > 0 || toNumber(creditApplied) > 0)"
          class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/40"
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Applied to invoices</span>
              <div class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(totalAppliedToInvoices) }}</div>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Credit applied</span>
              <div class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(toNumber(creditApplied)) }}</div>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Amount received</span>
              <div class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(toNumber(amountReceived)) }}</div>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Credit to account</span>
              <div class="font-semibold" :class="creditToAccount > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'">
                {{ formatCurrency(creditToAccount) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 py-4 border-t border-gray-200 dark:border-gray-700 mt-5">
        <Button type="button" variant="outline-secondary" size="md" :disabled="saving" @click="cancelAddModal">
          Cancel
        </Button>
        <Button type="button" variant="primary" size="md" :disabled="saving || mixedQuickqoreInvoices" @click="submitPayment">
          {{ saving ? (editingPaymentId ? 'Updating...' : 'Saving...') : (editingPaymentId ? 'Update payment' : 'Save payment') }}
        </Button>
      </div>
    </Modal>

    <PaymentDetailModal
      v-if="showViewModal"
      v-model="showViewModal"
      :payment-id="viewPaymentId"
      @edit="onEditFromViewModal"
      @deleted="onPaymentDeletedFromViewModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '../../plugins/axios'
import Input from '@/components/ui/input.vue'
import Textarea from '@/components/ui/textarea.vue'
import Button from '@/components/ui/button.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Spinner from '@/components/ui/spinner.vue'
import Pagination from '@/components/ui/pagination.vue'
import Modal from '@/components/common/Modal.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import PaymentDetailModal from './PaymentDetailModal.vue'
import { useUser } from '../../composables/useUser'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

const { can } = useUser()
const message = useMessage()

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

const canViewEligibleInvoices = computed(
  () =>
    can('upload-portal-invoice', 'index')
    || can('upload-portal-customer-payment', 'add')
    || can('upload-portal-customer-payment', 'edit')
)

const customers = ref([])
const loadingCustomers = ref(false)
const selectedCustomer = ref(null)

const paymentDate = ref(new Date().toISOString().slice(0, 10))
const selectedPaymentType = ref(null)
const remarks = ref('')
const amountReceived = ref('')
const creditApplied = ref('')
const availableCreditBalance = ref(0)
const loadingCreditBalance = ref(false)

const dueInvoices = ref([])
const loadingInvoices = ref(false)


const payments = ref([])
const loadingPayments = ref(false)
const listSearch = ref('')
const perPage = ref(25)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  has_prev: false,
  has_next: false,
})

const showAddModal = ref(false)
const saving = ref(false)
const editingPaymentId = ref(null)

const showViewModal = ref(false)
const viewPaymentId = ref(null)
const deletingId = ref(null)

const toNumber = (v) => {
  const n = parseFloat(String(v).replace(/,/g, ''))
  return Number.isFinite(n) ? n : 0
}

const originalAmount = (invoice) => toNumber(invoice.amount)

const dueBalance = (invoice) => {
  if (invoice.balance_due !== undefined && invoice.balance_due !== null) {
    return toNumber(invoice.balance_due)
  }
  const paid = toNumber(invoice.amount_paid)
  return Math.max(0, roundMoney(originalAmount(invoice) - paid))
}

const roundMoney = (n) => Math.round(n * 100) / 100

const totalAppliedToInvoices = computed(() => {
  let total = 0
  for (const inv of dueInvoices.value) {
    total += toNumber(inv.amount_applied)
  }
  return roundMoney(total)
})

const creditToAccount = computed(() => {
  const received = toNumber(amountReceived.value)
  const credit = toNumber(creditApplied.value)
  const applied = totalAppliedToInvoices.value
  return roundMoney(Math.max(0, received - Math.max(0, applied - credit)))
})

const isCreditOnlyPayment = computed(() => {
  return toNumber(amountReceived.value) <= 0 && toNumber(creditApplied.value) > 0
})

const isQqLinkedInvoice = (invoice) => Number(invoice?.qq_id) > 0

const mixedQuickqoreInvoices = computed(() => {
  const selected = dueInvoices.value.filter(
    (inv) => toNumber(inv.amount_applied) > 0 || toNumber(inv.discount) > 0
  )
  if (selected.length < 2) return false

  const hasLinked = selected.some(isQqLinkedInvoice)
  const hasUnlinked = selected.some((inv) => !isQqLinkedInvoice(inv))

  return hasLinked && hasUnlinked
})




const onToggleRow = (invoice, checked) => {
  const due = dueBalance(invoice)
 
  if (checked) {
   invoice.amount_applied = due.toFixed(2)
  } else {
    invoice.amount_applied = 0
    invoice.discount = 0
  }
}

const resetInvoiceRows = () => {
  dueInvoices.value = []
}

const resetAddForm = () => {
  selectedCustomer.value = null
  paymentDate.value = new Date().toISOString().slice(0, 10)
  selectedPaymentType.value = null
  remarks.value = ''
  amountReceived.value = ''
  creditApplied.value = ''
  availableCreditBalance.value = 0
  resetInvoiceRows()
}

const loadCustomerCreditBalance = async (customerId) => {
  if (!customerId) {
    availableCreditBalance.value = 0
    return
  }
  loadingCreditBalance.value = true
  try {
    const params = {}
    if (editingPaymentId.value) {
      params.payment_id = editingPaymentId.value
    }
    const response = await axios.get(`/upload-portal/api/customer-payments/customer/${customerId}/credit-balance`, { params })
    if (response.data.success) {
      availableCreditBalance.value = toNumber(response.data.credit_balance)
    } else {
      availableCreditBalance.value = 0
    }
  } catch {
    availableCreditBalance.value = 0
  } finally {
    loadingCreditBalance.value = false
  }
}

const applyMaxCredit = () => {
  const maxUsable = roundMoney(Math.min(availableCreditBalance.value, totalAppliedToInvoices.value))
  creditApplied.value = maxUsable > 0 ? maxUsable.toFixed(2) : availableCreditBalance.value.toFixed(2)
}

const buildPagination = (meta) => ({
  current_page: meta.current_page,
  last_page: meta.last_page,
  from: meta.from,
  to: meta.to,
  total: meta.total,
  per_page: meta.per_page,
  has_prev: meta.current_page > 1,
  has_next: meta.current_page < meta.last_page,
})

const loadCustomers = async () => {
  if (customers.value.length > 0) return
  loadingCustomers.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-customers', {
      params: { per_page: 1000 },
    })
    if (response.data.success) {
      customers.value = (response.data.customers?.data || []).map((c) => ({
        id: c.id,
        name: c.name,
      }))
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load customers')
  } finally {
    loadingCustomers.value = false
  }
}

const loadDueInvoices = async (customerId, includeInvoiceIds = []) => {
  if (!customerId) {
    resetInvoiceRows()
    return
  }
  if (!canViewEligibleInvoices.value) {
    resetInvoiceRows()
    return
  }
  loadingInvoices.value = true
  try {
    const params = {
      customer_id: customerId,
      outstanding: 1,
      per_page: 500,
      page: 1,
    }
    if (includeInvoiceIds.length) {
      params.include_invoice_ids = includeInvoiceIds
    }
    const response = await axios.get('/upload-portal/api/sales-invoices', {
      params,
    })
    if (response.data.success) {
      dueInvoices.value = response.data.invoices?.data || []
    } else {
      dueInvoices.value = []
    }

  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load invoices')
    dueInvoices.value = []
  } finally {
    loadingInvoices.value = false
  }
}

const onCustomerChange = async (value) => {
  const id = value?.id ?? null
  amountReceived.value = ''
  creditApplied.value = ''
  if (id) {
    await Promise.all([loadDueInvoices(id), loadCustomerCreditBalance(id)])
  } else {
    availableCreditBalance.value = 0
    resetInvoiceRows()
  }
}

const loadPayments = async (page = 1) => {
  loadingPayments.value = true
  try {
    const response = await axios.get('/upload-portal/api/customer-payments', {
      params: {
        page,
        per_page: perPage.value,
        search: listSearch.value?.trim() || undefined,
      },
    })
    if (response.data.success) {
      payments.value = response.data.payments.data || []
      pagination.value = buildPagination(response.data.payments)
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load payments')
  } finally {
    loadingPayments.value = false
  }
}

let listSearchTimer = null
const debounceListSearch = () => {
  clearTimeout(listSearchTimer)
  listSearchTimer = setTimeout(() => loadPayments(1), 400)
}

const onPaymentsPageChange = (page, maybePerPage) => {
  if (maybePerPage && Number(maybePerPage) !== perPage.value) {
    perPage.value = Number(maybePerPage)
  }
  loadPayments(page)
}

const openAddModal = async () => {
  editingPaymentId.value = null
  resetAddForm()
  showAddModal.value = true
  await loadCustomers()
}

const openEditModal = async (id) => {
  editingPaymentId.value = id
  resetInvoiceRows()
  showAddModal.value = true
  await loadCustomers()
  try {
    const response = await axios.get(`/upload-portal/api/customer-payments/${id}`)
    if (!response.data.success) {
      message.error('Could not load payment')
      closeAddModal()
      return
    }
    const pay = response.data.payment
    const cid = pay.customer_id
    selectedCustomer.value =
      customers.value.find((c) => c.id === cid) || { id: cid, name: pay.customer?.name || 'Customer' }
    const pd = pay.payment_date
    paymentDate.value = typeof pd === 'string' ? pd.slice(0, 10) : pd
    selectedPaymentType.value = pay.payment_type === 'customer_credit'
      ? null
      : paymentTypeOptions.find((o) => o.id === pay.payment_type) || null
    remarks.value = pay.remarks || ''
    amountReceived.value = pay.amount_received != null ? String(pay.amount_received) : String(pay.total_amount ?? '')
    creditApplied.value = pay.credit_applied != null ? String(pay.credit_applied) : '0'

    const includeIds = (pay.items || []).map((i) => i.sales_invoice_id).filter(Boolean)

    await Promise.all([loadDueInvoices(cid, includeIds), loadCustomerCreditBalance(cid)])

    dueInvoices.value.forEach(inv => {
      const paid = (pay.items || []).find(i => i.sales_invoice_id === inv.id);
        if (paid) {
          inv.amount_applied = paid.amount_applied
          inv.discount = paid.discount
          inv.balance_due += parseFloat(paid.amount_applied) + parseFloat(paid.discount)
          inv.balance_due = roundMoney(inv.balance_due)
        }
      })
    dueInvoices.value.sort((a, b) => a.balance_due - b.balance_due)
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load payment')
    closeAddModal()
  }
}

const closeAddModal = () => {
  showAddModal.value = false
  editingPaymentId.value = null
  resetAddForm()
}

const cancelAddModal = () => {
  if (saving.value) return
  closeAddModal()
}

const buildItemsPayload = () => {
  const items = []
  for (const inv of dueInvoices.value) {
    const applied = toNumber(inv.amount_applied)
    const discount = toNumber(inv.discount)
    if (applied > 0 || discount > 0) {
      items.push({
        sales_invoice_id: inv.id,
        amount_applied: applied,
        discount,
      })
    }
  }
  return items
}

const submitPayment = async () => {
  if (saving.value) return
  if (!selectedCustomer.value?.id) {
    message.error('Please select a customer')
    return
  }
  if (!paymentDate.value) {
    message.error('Please select a payment date')
    return
  }

  const received = roundMoney(toNumber(amountReceived.value))
  const credit = roundMoney(toNumber(creditApplied.value))
  const items = buildItemsPayload()

  if (received <= 0 && credit <= 0) {
    message.error('Enter amount received or apply customer credit')
    return
  }
  if (received > 0 && !selectedPaymentType.value?.id) {
    message.error('Please select a payment type')
    return
  }
  if (items.length === 0 && received <= 0) {
    message.error('Enter at least one amount toward an invoice, or record a prepayment in amount received')
    return
  }
  if (totalAppliedToInvoices.value - (received + credit) > 0.009) {
    message.error('Amount received plus credit applied must cover the total applied to invoices')
    return
  }
  if (credit - availableCreditBalance.value > 0.009) {
    message.error(`Customer only has ${formatCurrency(availableCreditBalance.value)} in available credit`)
    return
  }
  if (mixedQuickqoreInvoices.value) {
    message.warning('You cannot add a Quickqore-linked invoice together with an invoice that is not linked to Quickqore.')
    return
  }

  for (const inv of dueInvoices.value) {
    const amt = roundMoney(toNumber(inv.amount_applied) + toNumber(inv.discount))
    if (amt <= 0) continue
    if (amt - dueBalance(inv) > 0.009) {
      message.error(`Amount for invoice ${inv.invoice_number} cannot exceed due balance`)
      return
    }
  }

  saving.value = true
  try {
    const payload = {
      customer_id: selectedCustomer.value.id,
      payment_date: paymentDate.value,
      payment_type: received > 0 ? selectedPaymentType.value.id : null,
      amount_received: received,
      credit_applied: credit,
      remarks: remarks.value?.trim() || null,
      items,
    }
    const response = editingPaymentId.value
      ? await axios.put(`/upload-portal/api/customer-payments/${editingPaymentId.value}`, payload)
      : await axios.post('/upload-portal/api/customer-payments', payload)
    if (response.data.success) {
      message.success(response.data.message || (editingPaymentId.value ? 'Payment updated' : 'Payment saved'))
      showAddModal.value = false
      editingPaymentId.value = null
      resetAddForm()
      loadPayments(pagination.value.current_page || 1)
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to save payment')
  } finally {
    saving.value = false
  }
}

const deletePayment = async (row) => {
  if (deletingId.value) return
  if (!window.confirm('Delete this payment?')) return
  deletingId.value = row.id
  try {
    const response = await axios.delete(`/upload-portal/api/customer-payments/${row.id}`)
    if (response.data.success) {
      message.success(response.data.message || 'Payment deleted')
      if (showViewModal.value && viewPaymentId.value === row.id) {
        closeViewModal()
      }
      const nextPage =
        payments.value.length === 1 && pagination.value.current_page > 1
          ? pagination.value.current_page - 1
          : pagination.value.current_page
      loadPayments(nextPage || 1)
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete payment')
  } finally {
    deletingId.value = null
  }
}

const onEditFromViewModal = async (id) => {
  closeViewModal()
  if (id) await openEditModal(id)
}

const onPaymentDeletedFromViewModal = () => {
  const nextPage =
    payments.value.length === 1 && pagination.value.current_page > 1
      ? pagination.value.current_page - 1
      : pagination.value.current_page
  loadPayments(nextPage || 1)
}

const openViewModal = (id) => {
  viewPaymentId.value = id
  showViewModal.value = true
}

const closeViewModal = () => {
  showViewModal.value = false
  viewPaymentId.value = null
}

watch(showViewModal, (open) => {
  if (!open) {
    viewPaymentId.value = null
  }
})

onMounted(() => {
  loadPayments(1)
})
</script>
