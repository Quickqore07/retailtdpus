<template>
  <div>
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Invoices</h4>
    </div>
    <!-- Header / Actions Bar -->
    <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div class="w-full md:w-1/3">
          <Input
            v-model="search"
            placeholder="Search by invoice #, customer, remarks..."
            @input="debounceSearch"
          />
        </div>
        <Button
          v-if="can('upload-portal-invoice', 'add')"
          @click="openAddModal"
          variant="primary"
          size="sm"
          icon-left="plus"
          icon-size="md"
        >
          Add Invoice
        </Button>
      </div>
      <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 items-end">
          <DynamicDropdown
            v-model="selectedStatusFilter"
            label="Status"
            :custom-options="statusFilterOptions"
            display-name="name"
            placeholder="All statuses"
            :searchable="false"
          />
          <Input
            v-model="invoiceDateFrom"
            type="date"
            label="Invoice date from"
          />
          <Input
            v-model="invoiceDateTo"
            type="date"
            label="Invoice date to"
          />
          <Input
            v-model="dueDateFrom"
            type="date"
            label="Due date from"
          />
          <Input
            v-model="dueDateTo"
            type="date"
            label="Due date to"
          />
          <div class="flex flex-wrap gap-2 xl:col-span-1">
            <Button type="button" variant="primary" size="sm" @click="applyFilters">
              Apply filters
            </Button>
            <Button type="button" variant="outline-secondary" size="sm" @click="clearFilters">
              Clear
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <Spinner size="lg" text="Loading invoices..." />
    </div>

    <!-- Table -->
    <div
      v-else-if="invoices.length > 0"
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
    >
      <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 dark:border-gray-700">
            <tr>
              <Th align="left" custom-class="py-4">
                <button 
                  @click="handleSort('invoice_number')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Invoice #
                  <svg-icon 
                    v-if="sort.sort_by === 'invoice_number'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button 
                  @click="handleSort('customer_id')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Customer
                  <svg-icon 
                    v-if="sort.sort_by === 'customer_id'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button 
                  @click="handleSort('invoice_date')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Invoice Date
                  <svg-icon 
                    v-if="sort.sort_by === 'invoice_date'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button 
                  @click="handleSort('due_date')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Due Date
                  <svg-icon 
                    v-if="sort.sort_by === 'due_date'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button 
                  @click="handleSort('overdue_days')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Overdue Days
                  <svg-icon 
                    v-if="sort.sort_by === 'overdue_days'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="right" custom-class="py-4">
                <button 
                  @click="handleSort('amount')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Amount
                  <svg-icon 
                    v-if="sort.sort_by === 'amount'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="right" custom-class="py-4">
                <button 
                  @click="handleSort('balance_due')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Due Balance
                  <svg-icon 
                    v-if="sort.sort_by === 'balance_due'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button 
                  @click="handleSort('status')" 
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Status
                  <svg-icon 
                    v-if="sort.sort_by === 'status'" 
                    :name="sort.sort_direction === 'asc' ? 'chevron-up' : 'chevron-down'" 
                    size="xs" 
                  />
                </button>
              </Th>
              <Th align="right" custom-class="py-4">Actions</Th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="invoice in invoices"
              :key="invoice.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
              :class="{ 'opacity-50 pointer-events-none': deletingId === invoice.id }"
            >
              <Td custom-class="py-4">
                <div class="font-medium text-gray-900 dark:text-white">{{ invoice.invoice_number }}</div>
              </Td>
              <Td custom-class="py-4">{{ invoice.customer?.name || '-' }}</Td>
              <Td custom-class="py-4">{{ formatDate(invoice.invoice_date) }}</Td>
              <Td custom-class="py-4">{{ formatDate(invoice.due_date) }}</Td>
              <Td custom-class="py-4"> <span class="text-red-500" >{{ renderDueDays(invoice) > 0 ? renderDueDays(invoice) : '-' }}</span></Td>
              <Td align="right" custom-class="py-4">{{ formatCurrency(invoice.amount) }}</Td>
              <Td align="right" custom-class="py-4">{{ formatCurrency(invoice.balance_due) }}</Td>
              <Td custom-class="py-4">
                <span :class="statusClass(invoice.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize">
                  {{ invoice.status }}
                </span>
              </Td>
              <Td align="right" custom-class="py-4">
                <div class="flex items-center justify-end">
                  <IconMenuDropdown title="Actions">
                    <template #default="{ close }">
                      <button
                        v-if="can('upload-portal-invoice', 'view')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="openViewModal(invoice); close()"
                      >
                        <SvgIcon name="eye" size="sm" />
                        View
                      </button>
                      <button
                        v-if="can('upload-portal-invoice', 'mail-sent') && invoice.status == 'draft'"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="markAsSent(invoice);"
                      >
                        <Spinner v-if="markSentLoading" size="sm"/>
                        <SvgIcon name="mail" size="sm" />
                        Mark as Sent
                      </button>
                      <button
                        v-if="can('upload-portal-invoice', 'mail-sent')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                        role="menuitem"
                        @click="openSendEmailModal(invoice); close()"
                      >
                        <SvgIcon name="mail" size="sm" />
                        Send Invoice
                      </button>
                      <button
                        v-if="can('upload-portal-invoice', 'edit')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="openEditModal(invoice); close()"
                      >
                        <SvgIcon name="edit" size="sm" />
                        Edit
                      </button>
                      <button
                        v-if="can('upload-portal-invoice', 'delete')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        role="menuitem"
                        :disabled="deletingId === invoice.id"
                        @click="deleteInvoice(invoice); "
                      >
                        <Spinner v-if="deletingId === invoice.id" size="sm" />
                        <SvgIcon v-else name="trash" size="sm" />
                        {{ deletingId === invoice.id ? 'Deleting...' : 'Delete' }}
                      </button>
                    </template>
                  </IconMenuDropdown>
                </div>
              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-20 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl">
      <SvgIcon name="files" size="lg" class="text-gray-300 dark:text-gray-600 mx-auto mb-3" />
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No invoices yet</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">Create your first invoice to get started</p>
    </div>

    <!-- Pagination -->
    <div v-if="invoices.length > 0" class="mt-6">
      <Pagination
        :collection="pagination"
        :loading="loading"
        @page-change="onPageChange"
      />
    </div>

    <!-- Add / Edit Modal -->
    <Modal
      v-if="showModal"
      :model-value="showModal"
      size="6xl"
      :title="modalTitle"
      @update:model-value="closeModal"
    >
      <form @submit.prevent="handleSubmit" class="space-y-5">
        <!-- Header Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <DynamicDropdown
            v-model="selectedCustomer"
            label="Customer"
            :custom-options="customers"
            display-name="name"
            placeholder="Select customer"
            :required="true"
            :searchable="true"
            :disabled=" loadingCustomers || invoiceDisabled"
            :error="errors.customer_id"
            remove-null-option
            @change="onCustomerChange"
          />
          <Input
            v-model="form.invoice_number"
            label="Invoice #"
            placeholder="Invoice number"
            :required="true"
            :disabled="invoiceDisabled"
            :error="errors.invoice_number"
          />
          <Input
            v-model="form.invoice_date"
            label="Invoice Date"
            type="date"
            :required="true"
            :disabled=" invoiceDisabled"
            :error="errors.invoice_date"
            @change="onInvoiceDateChange"
          />
          <Input
            v-model="form.due_date"
            label="Due Date"
            type="date"
            :disabled=" invoiceDisabled"
            :error="errors.due_date"
            help-text="Auto-calculated from customer's credit term"
          />
          <Input
            :model-value="formattedAmount"
            label="Amount"
            type="text"
            :disabled="true"
            help-text="Sum of line item totals"
          />
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">Remarks</label>
            <textarea
              v-model="form.remarks"
              rows="2"
              :disabled="viewMode"
              placeholder="Optional remarks"
              class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 !focus:outline-none !focus:ring-0 disabled:opacity-60"
            ></textarea>
          </div>
        </div>

        <!-- Line Items -->
        <div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-0">Line Items</h6>
            <Button
              v-if="!viewMode"
              type="button"
              variant="outline-secondary"
              size="sm"
              icon-left="plus"
              :disabled="invoiceDisabled"
              @click="addItemRow"
            >
              Add row
            </Button>
          </div>

          <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto">
            <table class="w-full text-sm min-w-[860px]">
              <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                  <th class="text-left py-2 px-2 w-10 font-medium text-gray-700 dark:text-gray-300">#</th>
                  <th class="text-left py-2 px-2 font-medium text-gray-700 dark:text-gray-300 min-w-[220px]">Item Name</th>
                  <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[110px]">Qty</th>
                  <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[140px]">Unit Price</th>
                  <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[130px]">Discount</th>
                  <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[130px]">Tax Amount</th>
                  <th class="text-right py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[140px]">Total</th>
                  <th v-if="!viewMode" class="w-12"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in form.items"
                  :key="row._key"
                  class="border-b border-gray-100 dark:border-gray-800 last:border-0 align-top"
                >
                  <td class="py-2 px-2 text-gray-600 dark:text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-2 px-2">
                    <Input
                      v-model="row.item_name"
                      type="text"
                      placeholder="Item / service"
                      :disabled="viewMode"
                    />
                  </td>
                  <td class="py-2 px-2">
                    <Input
                      v-model="row.qty"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0"
                      :disabled="invoiceDisabled"
                      @input="recalcRow(row)"
                    />
                  </td>
                  <td class="py-2 px-2">
                    <Input
                      v-model="row.unit_price"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      :disabled="invoiceDisabled"
                      @input="recalcRow(row)"
                    />
                  </td>
                  <td class="py-2 px-2">
                    <Input
                      v-model="row.discount"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      :disabled="invoiceDisabled"
                      @input="recalcRow(row)"
                    />
                  </td>
                  <td class="py-2 px-2">
                    <Input
                      v-model="row.tax_amount"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      :disabled="invoiceDisabled"
                      @input="recalcRow(row)"
                    />
                  </td>
                  <td class="py-2 px-2 text-right font-medium text-gray-900 dark:text-white align-middle">
                    {{ formatCurrency(row.total) }}
                  </td>
                  <td v-if="!viewMode" class="py-2 px-2 text-right align-middle">
                    <Button
                      type="button"
                      variant="outline-danger"
                      size="sm"
                      :disabled="form.items.length <= 1 || invoiceDisabled"
                      title="Remove row"
                      icon-left="trash"
                      icon-only
                      @click="removeItemRow(idx)"
                    />
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 dark:bg-gray-900/40 border-t border-gray-200 dark:border-gray-700">
                <tr>
                  <td colspan="2" class="py-2 px-2 text-right text-xs text-gray-500 dark:text-gray-400">Subtotal</td>
                  <td colspan="2" class="py-2 px-2 text-right text-sm text-gray-700 dark:text-gray-200">{{ formatCurrency(subtotal) }}</td>
                  <td class="py-2 px-2 text-right text-sm text-gray-700 dark:text-gray-200">-{{ formatCurrency(discountTotal) }}</td>
                  <td class="py-2 px-2 text-right text-sm text-gray-700 dark:text-gray-200">+{{ formatCurrency(taxTotal) }}</td>
                  <td class="py-2 px-2 text-right font-semibold text-gray-900 dark:text-white">{{ formatCurrency(amountTotal) }}</td>
                  <td v-if="!viewMode"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 py-4 border-t border-gray-200 dark:border-gray-700 mt-5">
        <Button
          type="button"
          @click="closeModal"
          variant="outline-secondary"
          size="md"
          :disabled="saving"
        >
          {{ viewMode ? 'Close' : 'Cancel' }}
        </Button>
        <Button
          v-if="!viewMode"
          type="submit"
          @click="handleSubmit"
          :disabled="saving"
          variant="primary"
          size="md"
        >
          {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update Invoice' : 'Save Invoice') }}
        </Button>
      </div>
    </Modal>

    <!-- Invoice View Modal -->
    <SalesInvoiceViewModal
      v-model="showViewModal"
      :invoice-id="viewInvoiceId"
    />

    <SendArDocumentEmailModal
      v-model="showSendEmailModal"
      kind="invoice"
      :invoice-id="sendMailInvoiceId"
      @sent="loadInvoices(pagination.current_page)"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from '../../plugins/axios'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Spinner from '@/components/ui/spinner.vue'
import Pagination from '@/components/ui/pagination.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Modal from '@/components/common/Modal.vue'
import SalesInvoiceViewModal from './SalesInvoiceViewModal.vue'
import SendArDocumentEmailModal from './SendArDocumentEmailModal.vue'
import { useUser } from '../../composables/useUser'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

let itemKeySeq = 0
const nextItemKey = () => {
  itemKeySeq += 1
  return `inv-item-${itemKeySeq}`
}

const emptyItemRow = () => ({
  _key: nextItemKey(),
  id: null,
  item_name: '',
  qty: 1,
  unit_price: 0,
  discount: 0,
  tax_amount: 0,
  total: 0,
})

const emptyForm = () => ({
  id: null,
  customer_id: null,
  invoice_number: '',
  invoice_date: new Date().toISOString().slice(0, 10),
  due_date: '',
  remarks: '',
  status: 'draft',
  items: [emptyItemRow()],
})

const { can } = useUser()
const message = useMessage()
const invoiceDisabled = ref(false)

const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const invoices = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  has_prev: false,
  has_next: false,
})
const search = ref('')
const perPage = ref(25)

const sort = ref({
  sort_by: 'invoice_date',
  sort_direction: 'desc'
})

const statusFilterOptions = [
  { id: '', name: 'All statuses' },
  { id: 'draft', name: 'Draft' },
  { id: 'sent', name: 'Sent' },
  { id: 'paid', name: 'Paid' },
  { id: 'cancelled', name: 'Cancelled' },
]
const selectedStatusFilter = ref({ id: '', name: 'All statuses' })
const invoiceDateFrom = ref('')
const invoiceDateTo = ref('')
const dueDateFrom = ref('')
const dueDateTo = ref('')

const customers = ref([])
const loadingCustomers = ref(false)

const showModal = ref(false)
const isEditMode = ref(false)
const viewMode = ref(false)
const form = reactive(emptyForm())
const errors = ref({})
const selectedCustomer = ref(null)

const showViewModal = ref(false)
const viewInvoiceId = ref(null)
const showSendEmailModal = ref(false)
const sendMailInvoiceId = ref(null)
const markSentLoading = ref(false)
const modalTitle = computed(() => {
  if (viewMode.value) return 'View Invoice'
  return isEditMode.value ? 'Edit Invoice' : 'Add Invoice'
})

const toNumber = (v) => {
  const n = parseFloat(v)
  return Number.isFinite(n) ? n : 0
}

const computeLineTotal = (row) => {
  const qty = toNumber(row.qty)
  const price = toNumber(row.unit_price)
  const discount = toNumber(row.discount)
  const tax = toNumber(row.tax_amount)
  const total = Math.max(0, qty * price - discount) + tax
  return Math.round(total * 100) / 100
}

const recalcRow = (row) => {
  row.total = computeLineTotal(row)
}

const subtotal = computed(() =>
  form.items.reduce((sum, r) => sum + toNumber(r.qty) * toNumber(r.unit_price), 0)
)
const discountTotal = computed(() =>
  form.items.reduce((sum, r) => sum + toNumber(r.discount), 0)
)
const taxTotal = computed(() =>
  form.items.reduce((sum, r) => sum + toNumber(r.tax_amount), 0)
)
const amountTotal = computed(() =>
  form.items.reduce((sum, r) => sum + toNumber(r.total), 0)
)
const formattedAmount = computed(() => formatCurrency(amountTotal.value))

const statusClass = (status) => {
  switch ((status || '').toLowerCase()) {
    case 'paid':
      return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
    case 'sent':
      return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
    case 'cancelled':
      return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
    case 'draft':
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'
  }
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

const markAsSent = async (invoice) => {
  try {
    markSentLoading.value = true
    const response = await axios.put(`/upload-portal/api/sales-invoices/${invoice.id}/mark-as-sent`, { status: 'sent' })
    if (response.data.success) {
      message.success(response.data.message || 'Invoice marked as sent')
      loadInvoices(1)
    }
  }
  catch (error) {
    message.error(error.response?.data?.message || 'Failed to mark invoice as sent')
  }
  finally {
    markSentLoading.value = false
  }
}
const listFilterParams = () => ({
  status: selectedStatusFilter.value?.id ? selectedStatusFilter.value.id : undefined,
  date_from: invoiceDateFrom.value || undefined,
  date_to: invoiceDateTo.value || undefined,
  due_date_from: dueDateFrom.value || undefined,
  due_date_to: dueDateTo.value || undefined,
})

const applyFilters = () => {
  loadInvoices(1)
}

const clearFilters = () => {
  selectedStatusFilter.value = { id: '', name: 'All statuses' }
  invoiceDateFrom.value = ''
  invoiceDateTo.value = ''
  dueDateFrom.value = ''
  dueDateTo.value = ''
  loadInvoices(1)
}

const handleSort = (column) => {
  if (sort.value.sort_by === column) {
    sort.value.sort_direction = sort.value.sort_direction === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value.sort_by = column
    sort.value.sort_direction = 'asc'
  }
  pagination.value.current_page = 1
  loadInvoices(1,true)
}

const loadInvoices = async (page = 1,isSort = false) => {

  !isSort && (loading.value = true) 
  try {
    const response = await axios.get('/upload-portal/api/sales-invoices', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value || undefined,
        sort_by: sort.value.sort_by,
        sort_direction: sort.value.sort_direction,
        ...listFilterParams(),
      },
    })
    if (response.data.success) {
      invoices.value = response.data.invoices.data || []
      pagination.value = buildPagination(response.data.invoices)
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load invoices')
  } finally {
    loading.value = false
  }
}

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
        email: c.email,
        invoice_due: c.invoice_due,
        invoice_condition: c.invoice_condition,
      }))
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load customers')
  } finally {
    loadingCustomers.value = false
  }
}

let searchTimer = null
const debounceSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadInvoices(1), 400)
}

const onPageChange = (page) => loadInvoices(page)

const resetForm = () => {
  Object.assign(form, emptyForm())
  selectedCustomer.value = null
  errors.value = {}
}

const fetchNextInvoiceNumber = async () => {
  try {
    const response = await axios.get('/upload-portal/api/sales-invoices/next-number')
    if (response.data?.success) {
      form.invoice_number = response.data.invoice_number || ''
    }
  } catch (error) {
    // optional helper; ignore failure
  }
}

const computeDueDate = async () => {
  if (!form.customer_id || !form.invoice_date) return

  const billDayRaw = selectedCustomer.value?.invoice_due
  const billConditionRaw = selectedCustomer.value?.invoice_condition
  const billDay = Number.parseInt(String(billDayRaw ?? '').trim(), 10)
  const billCondition = String(billConditionRaw ?? '').trim().toLowerCase()
  if (!Number.isFinite(billDay)) return ''

  const base = new Date(`${form.invoice_date}T12:00:00`)
  if (Number.isNaN(base.getTime())) return ''

  const sameMonthDay = () => {
    const due = new Date(base)
    due.setDate(1)
    due.setMonth(due.getMonth() + 1)
    due.setDate(0)
    const lastDay = due.getDate()
    due.setDate(Math.max(1, Math.min(billDay, lastDay)))
    return due.toISOString().slice(0, 10)
  }

  if (billCondition === 'of the following month') {
    const due = new Date(base)
    due.setMonth(due.getMonth() + 1, 1)
    due.setMonth(due.getMonth() + 1, 0)
    const lastDay = due.getDate()
    due.setDate(Math.max(1, Math.min(billDay, lastDay)))
    form.due_date = due.toISOString().slice(0, 10)
  }

  if (billCondition === 'day(s) after the invoice date') {
    const due = new Date(base)
    due.setDate(due.getDate() + billDay)
    form.due_date = due.toISOString().slice(0, 10)
  }

  if (billCondition === 'day(s) after the end of the invoice month') {
    const monthEnd = new Date(base.getFullYear(), base.getMonth() + 1, 0, 12, 0, 0)
    monthEnd.setDate(monthEnd.getDate() + billDay)
    form.due_date = monthEnd.toISOString().slice(0, 10)
  }

  if (billCondition === 'of current month' || billCondition === '') {
    form.due_date = sameMonthDay()
  }
  return false
}

const onCustomerChange = (value) => {
  form.customer_id = value?.id || null
  computeDueDate()
}

const onInvoiceDateChange = () => {
  computeDueDate()
}

const openAddModal = async () => {
  resetForm()
  isEditMode.value = false
  viewMode.value = false
  showModal.value = true
  await loadCustomers()
  await fetchNextInvoiceNumber()
}

const openEditModal = async (invoice) => {
  resetForm()
  isEditMode.value = true
  viewMode.value = false
  showModal.value = true
  await loadCustomers()
  await hydrateForm(invoice.id)
}

const openViewModal = (invoice) => {
  viewInvoiceId.value = invoice.id
  showViewModal.value = true
}

const openSendEmailModal = (invoice) => {
  sendMailInvoiceId.value = invoice.id
  showSendEmailModal.value = true
}

const hydrateForm = async (id) => {
  try {
    const response = await axios.get(`/upload-portal/api/sales-invoices/${id}`)
    if (!response.data?.success) return
    const inv = response.data.invoice
    form.id = inv.id
    form.customer_id = inv.customer_id
    form.invoice_number = inv.invoice_number
    form.invoice_date = (inv.invoice_date || '').slice(0, 10)
    form.due_date = inv.due_date ? String(inv.due_date).slice(0, 10) : ''
    form.remarks = inv.remarks || ''
    form.status = inv.status || 'draft'
    invoiceDisabled.value = inv.balance_due != inv.amount
    form.items = (inv.items || []).map((row) => ({
      _key: nextItemKey(),
      id: row.id,
      item_name: row.item_name,
      qty: toNumber(row.qty),
      unit_price: toNumber(row.unit_price),
      discount: toNumber(row.discount),
      tax_amount: toNumber(row.tax_amount),
      total: toNumber(row.total),
    }))
    if (form.items.length === 0) form.items = [emptyItemRow()]

    const matched = customers.value.find((c) => Number(c.id) === Number(inv.customer_id))
    selectedCustomer.value = matched || (inv.customer ? { id: inv.customer.id, name: inv.customer.name } : null)
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load invoice')
  }
}

const closeModal = () => {
  if (saving.value) return
  showModal.value = false
  resetForm()
}

const addItemRow = () => {
  form.items.push(emptyItemRow())
}

const removeItemRow = (idx) => {
  if (form.items.length <= 1) return
  form.items.splice(idx, 1)
}

const buildPayload = () => {
  return {
    customer_id: form.customer_id,
    invoice_number: form.invoice_number?.trim(),
    invoice_date: form.invoice_date,
    due_date: form.due_date || null,
    remarks: form.remarks || null,
    status: form.status || 'draft',
    items: form.items.map((row) => ({
      item_name: row.item_name?.trim(),
      qty: toNumber(row.qty),
      unit_price: toNumber(row.unit_price),
      discount: toNumber(row.discount),
      tax_amount: toNumber(row.tax_amount),
      total: computeLineTotal(row),
    })),
  }
}

const validateClient = () => {
  errors.value = {}
  if (!form.customer_id) {
    errors.value.customer_id = 'Customer is required'
  }
  if (!form.invoice_number?.trim()) {
    errors.value.invoice_number = 'Invoice number is required'
  }
  if (!form.invoice_date) {
    errors.value.invoice_date = 'Invoice date is required'
  }
  for (let i = 0; i < form.items.length; i += 1) {
    const row = form.items[i]
    if (!row.item_name?.trim()) {
      message.error(`Line ${i + 1}: item name is required`)
      return false
    }
    if (toNumber(row.qty) <= 0) {
      message.error(`Line ${i + 1}: qty must be greater than 0`)
      return false
    }
    if (toNumber(row.unit_price) < 0) {
      message.error(`Line ${i + 1}: unit price cannot be negative`)
      return false
    }
  }
  if (amountTotal.value <= 0) {
    message.error('Invoice total must be greater than zero')
    return false
  }
  if (Object.keys(errors.value).length > 0) {
    message.error('Please fix the highlighted errors')
    return false
  }
  return true
}

const renderDueDays = (invoice) => {
  const due_date = invoice?.due_date
  if(!due_date || !invoice || invoice.status == 'paid') return 0
    const invoiceDate = new Date()
    const dueDate = new Date(due_date)
    const diffTime = invoiceDate - dueDate 
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays
}
const handleSubmit = async () => {
  if (saving.value || viewMode.value) return
  if (!validateClient()) return
  saving.value = true
  try {
    const payload = buildPayload()
    let response
    if (isEditMode.value) {
      response = await axios.put(`/upload-portal/api/sales-invoices/${form.id}`, payload)
    } else {
      response = await axios.post('/upload-portal/api/sales-invoices', payload)
    }
    if (response.data.success) {
      message.success(response.data.message || 'Invoice saved')
      showModal.value = false
      resetForm()
      loadInvoices(isEditMode.value ? pagination.value.current_page : 1)
    }
  } catch (error) {
    if (error.response?.status === 422 && error.response.data?.errors) {
      const apiErrors = error.response.data.errors
      Object.keys(apiErrors).forEach((k) => {
        errors.value[k] = Array.isArray(apiErrors[k]) ? apiErrors[k][0] : apiErrors[k]
      })
    }
    message.error(error.response?.data?.message || 'Failed to save invoice')
  } finally {
    saving.value = false
  }
}

const deleteInvoice = async (invoice) => {
  if (deletingId.value) return
  if (!window.confirm(`Delete invoice "${invoice.invoice_number}"?`)) return
  deletingId.value = invoice.id
  try {
    const response = await axios.delete(`/upload-portal/api/sales-invoices/${invoice.id}`)
    if (response.data.success) {
      message.success(response.data.message || 'Invoice deleted')
      const nextPage = invoices.value.length === 1 && pagination.value.current_page > 1
        ? pagination.value.current_page - 1
        : pagination.value.current_page
      loadInvoices(nextPage)
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete invoice')
  } finally {
    deletingId.value = null
  }
}

onMounted(() => {
  loadInvoices(1)
})
</script>
