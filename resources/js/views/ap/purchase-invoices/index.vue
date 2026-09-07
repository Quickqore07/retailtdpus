<template>
  <div class="purchase-invoices-index">
    <nav
      class="mb-4 -mx-4 sm:mx-0 overflow-x-auto"
      role="tablist"
      aria-label="Purchase invoice status"
    >
      <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700 min-w-max sm:min-w-0 px-4 sm:px-0">
        <button
          v-for="tab in statusTabs"
          :key="tab.id"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.id"
          class="px-1 pb-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
          :class="activeTab === tab.id
            ? 'text-primary border-primary dark:text-emerald-400 dark:border-emerald-400'
            : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }} 
          <span
            v-if="tab.id !== 'paid'"
            class="ml-1.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums"
            :class="activeTab === tab.id
              ? 'bg-primary/10 text-primary dark:bg-emerald-400/15 dark:text-emerald-400'
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
          >
            {{ statusCounts[tab.id] ?? 0 }}
          </span>
        </button>
      </div>
    </nav>

    <p
      v-if="access.includes('create') && activeTab === 'draft'"
      class="mb-4 px-4 sm:px-0 text-xs text-gray-500 dark:text-gray-400"
    >
      <span class="font-medium text-gray-600 dark:text-gray-300">Note:</span>
      {{ documentUploadNote }}
    </p>

    <Filterable
      ref="filterableRef"
      title="Purchase Invoices"
      url="ap/purchase-invoices"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      :extra-params="extraParams"
      :show-search="true"
      @update:data="handleDataUpdate"
    >
      <template #extra>
        <Button
          v-if="access.includes('create') && activeTab === 'draft'"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/ap/purchase-invoices/create"
        >
          New Purchase Invoice
        </Button>
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Expense Type</Th>
          <Th>Invoice #</Th>
          <Th>Vendor</Th>
          <Th>Company</Th>
          <Th>Invoice Date</Th>
          <Th>Due Date</Th>
          <Th>Total Amount</Th>
          <Th v-if="activeTab === 'approved'">Paid</Th>
          <Th v-if="activeTab === 'approved'">Due</Th>
          <Th v-if="activeTab === 'paid'">Check #</Th>
          <Th>Created At</Th>
          <!-- <Th>Updated At</Th> -->
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td color="secondary">{{ formatExpenseType(item.expense) }}</Td>
          <Td weight="medium" color="primary">{{ item.invoice_no }}</Td>
          <Td color="secondary">{{ item.vendor?.name || '-' }}</Td>
          <Td color="secondary">{{ item.company?.name || '-' }}</Td>
          <Td color="secondary">{{ formatDate(item.invoice_date) }}</Td>
          <Td color="secondary">{{ formatDate(item.due_date) }}</Td>
          <Td weight="medium" color="primary">{{ formatCurrency(item.total_amount ?? item.amount) }}</Td>
          <Td v-if="activeTab === 'approved'" color="secondary">{{ formatCurrency(item.paid_amount ?? 0) }}</Td>
          <Td v-if="activeTab === 'approved'" weight="medium" color="primary">{{ formatCurrency(item.due_amount ?? item.total_amount ?? item.amount) }}</Td>
          <Td v-if="activeTab === 'paid'" color="secondary">{{ formatCheckNumbers(item) }}</Td>
          <Td weight="medium" color="secondary">{{ formatDate(item.created_at) }}</Td>
          <!-- <Td weight="medium" color="secondary">{{ formatDate(item.updated_at) }}</Td> -->
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <Button
                v-if="access.includes('approve') && item.status === 'draft'"
                icon-left="check"
                icon-size="sm"
                variant="success"
                size="sm"
                @click="handleApproveModal(item)"
                :loading="actionLoadingId === approveTargetItem?.id && actionType === 'approve'"
                :disabled="actionLoadingId !== null"
              >
                Approve
              </Button>
              <Button
                v-if="canAddPayment(item)"
                icon-left="dollar"
                icon-size="sm"
                variant="success"
                size="sm"
                @click="openPaymentModal(item)"
              >
                Payment
              </Button>
              <Button
                v-if="access.includes('cancel') && (item.status === 'draft' || item.status === 'approved')"
                icon-left="x"
                icon-size="sm"
                variant="danger"
                size="sm"
                customClass="!px-2 !py-1"
                @click="handleCancelModal(item)"
                :loading="actionLoadingId === item.id && actionType === 'cancel'"
                :disabled="actionLoadingId !== null"
              >
                Cancel
              </Button>
              <Button
                v-if="access.includes('update') && item.status === 'cancelled'"
                icon-left="edit"
                icon-size="sm"
                variant="secondary"
                size="sm"
                @click="moveToDraft(item.id)"
                :loading="actionLoadingId === item.id && actionType === 'draft'"
                :disabled="actionLoadingId !== null"
              >
                Move to Draft
              </Button>
              <IconMenuDropdown title="Actions">
                <template #default="{ close }">
                  <router-link
                    v-if="access.includes('show')"
                    :to="`/ap/purchase-invoices/${item.id}`"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="close()"
                  >
                    <SvgIcon name="eye" size="sm" />
                    View
                  </router-link>
                  <router-link
                    v-if="access.includes('update') && item.status === 'draft'"
                    :to="`/ap/purchase-invoices/${item.id}/edit`"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="close()"
                  >
                    <SvgIcon name="edit" size="sm" />
                    Edit
                  </router-link>
                  <button
                    v-if="item.document?.file_path"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="viewInvoice(item.document.id); close()"
                  >
                    <SvgIcon name="file-text" size="sm" />
                    View Invoice
                  </button>
                  <button
                    v-if="access.includes('delete') && item.status === 'draft'"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                    role="menuitem"
                    @click="handleDelete(item.id); close()"
                  >
                    <SvgIcon name="trash" size="sm" />
                    Delete
                  </button>
                </template>
              </IconMenuDropdown>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>

    <Modal
      v-model="paymentModalOpen"
      title="Add Payment"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Save Payment"
      :loading="paymentSubmitting"
      @confirm="submitPayment"
    >
      <div v-if="paymentTargetItem" class="space-y-4">
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3 text-sm">
          <div class="flex justify-between gap-4">
            <span class="text-gray-600 dark:text-gray-400">Total Amount</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(paymentTargetItem.total_amount ?? paymentTargetItem.amount) }}</span>
          </div>
          <div class="flex justify-between gap-4 mt-1">
            <span class="text-gray-600 dark:text-gray-400">Already Paid</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(paymentTargetItem.paid_amount ?? 0) }}</span>
          </div>
          <div class="flex justify-between gap-4 mt-1">
            <span class="text-gray-600 dark:text-gray-400">Due Amount</span>
            <span class="font-semibold text-primary dark:text-emerald-400">{{ formatCurrency(paymentDueAmount) }}</span>
          </div>
        </div>
        <Input
          v-model="paymentAmount"
          label="Payment Amount"
          type="number"
          placeholder="Enter payment amount"
          :required="true"
        />
        <div class="flex flex-col gap-1.5">
          <label for="payment-method" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Payment Type
            <span class="text-red-500">*</span>
          </label>
          <select
            id="payment-method"
            v-model="paymentMethod"
            required
            class="w-full px-3 py-1.5 text-sm rounded-md border transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50 focus:border-primary"
          >
            <option value="" disabled>Select payment type</option>
            <option
              v-for="option in paymentMethodOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>
        <Input
          v-if="paymentMethod === 'check'"
          v-model="paymentCheckNumber"
          label="Check Number"
          placeholder="Enter check number"
          :required="true"
        />
        <Input
          v-model="paymentDate"
          label="Payment Date"
          type="date"
        />
        <div class="flex flex-col gap-1.5">
          <label for="payment-remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Remarks
          </label>
          <textarea
            id="payment-remarks"
            v-model="paymentRemarks"
            rows="3"
            class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
            placeholder="Enter payment remarks"
          />
        </div>
      </div>
    </Modal>

    <Modal
      v-model="approveModalOpen"
      title="Approve Purchase Invoice"
      size="3xl"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Approve"
      :loading="approveSubmitting"
      @confirm="submitApprove"
    >
    <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        Are you sure you want to approve this purchase invoice?
      </p>
      <Button
        icon-left="eye"
        icon-size="sm"
        variant="outline-secondary"
        size="sm"
        @click="viewReport"
      >
        View Report
      </Button>
    </div>
      <purchase-invoice-details :invoice="approveTargetItem" />
    </Modal>

    <Modal
      v-model="cancelModalOpen"
      title="Cancel Purchase Invoice"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Keep Invoice"
      confirm-text="Yes, Cancel"
      :loading="cancelSubmitting"
      @confirm="submitCancel"
    >
      <div class="space-y-3">
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
          <p class="text-sm font-medium text-red-700 dark:text-red-300">
            This action will mark the purchase invoice as cancelled.
          </p>
          <p class="mt-1 text-sm text-red-600 dark:text-red-200">
            You should only continue if you are sure this invoice should no longer be processed.
          </p>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Are you sure you want to cancel
          <span class="font-semibold text-gray-900 dark:text-gray-100">
            {{ cancelTargetItem?.invoice_no || 'this purchase invoice' }}
          </span>
          ?
        </p>
      </div>
    </Modal>

  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { formatExpenseType } from '@/utils/purchaseInvoiceExpense'
import { DOCUMENT_UPLOAD_NOTE } from '@/utils/documentUpload'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import purchaseInvoiceDetails from './purchase-invoice-details.vue'

const route = useRoute()
const resource = route.meta?.resource || 'ap/purchase-invoices'
const message = useMessage()
const documentUploadNote = DOCUMENT_UPLOAD_NOTE

const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'purchase-invoice')

const statusTabs = [
  { id: 'draft', label: 'Draft' },
  { id: 'approved', label: 'Approved' },
  { id: 'paid', label: 'Paid' },
  { id: 'cancelled', label: 'Cancelled' },
]

const activeTab = ref('draft')
const extraParams = computed(() => ({ status: activeTab.value }))
const statusCounts = ref({
  draft: 0,
  approved: 0,
  paid: 0,
  cancelled: 0,
})

const handleDataUpdate = (response) => {
  const counts = response?.status_counts ?? response?.data?.status_counts
  if (counts) {
    statusCounts.value = { ...statusCounts.value, ...counts }
  }
}

const actionLoadingId = ref(null)
const actionType = ref(null)
const paymentModalOpen = ref(false)
const paymentTargetItem = ref(null)
const paymentAmount = ref('')
const paymentMethod = ref('')
const paymentCheckNumber = ref('')
const paymentDate = ref('')
const paymentRemarks = ref('')
const paymentSubmitting = ref(false)

const paymentMethodOptions = [
  { value: 'ach', label: 'ACH' },
  { value: 'cash', label: 'Cash' },
  { value: 'check', label: 'Check' },
  { value: 'other', label: 'Other' },
]

const paymentDueAmount = computed(() => {
  const item = paymentTargetItem.value
  if (!item) return 0
  if (item.due_amount != null) return Number(item.due_amount)
  return Number(item.total_amount ?? item.amount ?? 0) - Number(item.paid_amount ?? 0)
})

const canAddPayment = (item) => {
  if (!item || item.status !== 'approved') return false
  if (!access.value.includes('payment')) return false
  const due = item.due_amount != null
    ? Number(item.due_amount)
    : Number(item.total_amount ?? item.amount ?? 0) - Number(item.paid_amount ?? 0)
  return due > 0
}

const formatCheckNumbers = (item) => {
  
  const numbers = (item.payments || [])
    .map((payment) => payment.payment_method === 'check' ? payment.check_number : (payment.payment_method).toUpperCase())
    .filter(Boolean)
  if (numbers.length) return numbers.join(', ')
  return item.check_number || '-'
}

const approveModalOpen = ref(false)
const approveTargetItem = ref(null)
const approveSubmitting = ref(false)
const cancelModalOpen = ref(false)
const cancelTargetItem = ref(null)
const cancelSubmitting = ref(false)

const viewReport = () => {
  const expense = approveTargetItem.value?.expense
  const expenseId = expense?.id

  if (!expenseId) {
    message.error('No report available for this expense type')
    return
  }

  const reportPath = expense.name === 'Repair' ? 'bill-wise' : 'monthly'
  window.open(`/reports/ap/${reportPath}?expense_id=${expenseId}`, '_blank')
}

const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'vendor_name', label: 'Vendor' },
  { value: 'company_name', label: 'Company' },
  // { value: 'updated_at', label: 'Updated At' },
  { value: 'invoice_date', label: 'Invoice Date' },
  { value: 'invoice_no', label: 'Invoice #' },
  { value: 'due_date', label: 'Due Date' },
  { value: 'amount', label: 'Amount' },
  { value: 'total_amount', label: 'Total Amount' },
]

const filterGroups = [
  {
    title: 'Invoice Information',
    filters: [
      { name: 'expense_id', title: 'Expense Type', type: 'lookup_only', placeholder: 'Select expense type', resource: 'expense-types', column: 'name' },
      { name: 'invoice_no', title: 'Invoice #', type: 'string', placeholder: 'Enter invoice number' },
      { name: 'amount', title: 'Amount', type: 'numeric', placeholder: 'Enter amount' },
      { name: 'total_amount', title: 'Total Amount', type: 'numeric', placeholder: 'Enter total amount' },
      { name: 'vendor_id', title: 'Vendor', type: 'lookup_only', placeholder: 'Select vendor', resource: 'vendors', column: 'name' },
      { name: 'company_id', title: 'Company', type: 'lookup_only', placeholder: 'Select company', resource: 'companies', column: 'name' },
    ]
  },
  {
    title: 'Dates',
    filters: [
      { name: 'invoice_date', title: 'Invoice Date', type: 'date', placeholder: 'Select date' },
      { name: 'due_date', title: 'Due Date', type: 'date', placeholder: 'Select date' },
      { name: 'created_at', title: 'Created At', type: 'datetime', placeholder: 'Select date' },
      // { name: 'updated_at', title: 'Updated At', type: 'datetime', placeholder: 'Select date' },
    ]
  }
]

const refreshList = () => {
  filterableRef.value?.fetch()
}

const handleApproveModal = (item) => {
  approveTargetItem.value = item
  approveModalOpen.value = true
}

const handleCancelModal = (item) => {
  cancelTargetItem.value = item
  cancelModalOpen.value = true
}

const submitApprove = async () => {
  if (actionLoadingId.value !== null) return
  actionLoadingId.value = approveTargetItem.value.id
  actionType.value = 'approve'
  approveSubmitting.value = true
  try {
    const response = await useRequest('post', `${resource}/${approveTargetItem.value.id}/approve`)
    if (response.success) {
      message.success('Purchase invoice approved successfully')
      approveModalOpen.value = false
      approveTargetItem.value = null
      refreshList()
    } else {
      message.error(response.message || 'Failed to approve purchase invoice')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to approve purchase invoice')
  } finally {
    actionLoadingId.value = null
    actionType.value = null
    approveSubmitting.value = false
  }
}

const openPaymentModal = (item) => {
  paymentTargetItem.value = item
  paymentAmount.value = ''
  paymentMethod.value = ''
  paymentCheckNumber.value = ''
  paymentDate.value = new Date().toISOString().slice(0, 10)
  paymentRemarks.value = ''
  paymentModalOpen.value = true
}

const submitPayment = async () => {
  const amount = Number(paymentAmount.value)
  const method = paymentMethod.value
  const checkNumber = (paymentCheckNumber.value || '').trim()
  const remarks = (paymentRemarks.value || '').trim()
  const due = paymentDueAmount.value

  if (!amount || amount <= 0) {
    message.error('Please enter a valid payment amount.')
    return
  }

  if (amount > due) {
    message.error(`Payment amount cannot exceed the due amount of ${formatCurrency(due)}.`)
    return
  }

  if (!method) {
    message.error('Please select a payment method.')
    return
  }

  if (method === 'check' && !checkNumber) {
    message.error('Please enter a check number.')
    return
  }

  const id = paymentTargetItem.value?.id
  if (!id) return

  paymentSubmitting.value = true
  try {
    const response = await useRequest('post', `${resource}/${id}/payments`, {
      amount,
      payment_method: method,
      check_number: method === 'check' ? checkNumber : undefined,
      payment_date: paymentDate.value || undefined,
      remarks: remarks || undefined,
    })
    if (response.success) {
      message.success(response.message || 'Payment recorded successfully')
      paymentModalOpen.value = false
      paymentTargetItem.value = null
      paymentAmount.value = ''
      paymentMethod.value = ''
      paymentCheckNumber.value = ''
      paymentDate.value = ''
      paymentRemarks.value = ''
      refreshList()
    } else {
      message.error(response.message || 'Failed to record payment')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to record payment')
  } finally {
    paymentSubmitting.value = false
  }
}

const submitCancel = async () => {
  const id = cancelTargetItem.value?.id
  if (!id || actionLoadingId.value !== null) return
  actionLoadingId.value = id
  actionType.value = 'cancel'
  cancelSubmitting.value = true
  try {
    const response = await useRequest('post', `${resource}/${id}/cancel`)
    if (response.success) {
      message.success('Purchase invoice cancelled successfully')
      cancelModalOpen.value = false
      cancelTargetItem.value = null
      refreshList()
    } else {
      message.error(response.message || 'Failed to cancel purchase invoice')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to cancel purchase invoice')
  } finally {
    actionLoadingId.value = null
    actionType.value = null
    cancelSubmitting.value = false
  }
}

const moveToDraft = async (id) => {
  if (actionLoadingId.value !== null) return
  actionLoadingId.value = id
  actionType.value = 'draft'
  try {
    const response = await useRequest('post', `${resource}/${id}/draft`)
    if (response.success) {
      message.success('Purchase invoice moved to draft successfully')
      refreshList()
    } else {
      message.error(response.message || 'Failed to move purchase invoice to draft')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to move purchase invoice to draft')
  } finally {
    actionLoadingId.value = null
    actionType.value = null
  }
}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const viewInvoice = async (id) => {
  try {
    const response = await useRequest('post', '/view-document', { id })
    if (response.url) {
      window.open(response.url, '_blank', 'noopener,noreferrer')
    } else {
      message.error('Failed to view invoice')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to view invoice')
  }
}

defineExpose({ setData })
</script>
