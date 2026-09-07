<template>
  <div v-if="show" class="purchase-invoice-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Purchase Invoice Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/ap/purchase-invoices" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update') && model.status === 'draft'"
              :to="`/ap/purchase-invoices/${model.id}/edit`"
            />
            <Button
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              @click="handleDelete"
              v-if="access.includes('delete') && model.status === 'draft'"
            />
          </div>
        </div>
      </template>

      <div class="space-y-6">
        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            Invoice Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Invoice #" :value="model.invoice_no" />
            <Label label="Status">
              <span :class="statusClass(model.status)">{{ formatStatus(model.status) }}</span>
            </Label>
            <Label label="Expense Type" :value="formatExpenseType(model.expense)" />
            <Label :label="amountLabels.amount" :value="formatCurrency(model.amount)" />
            <Label
              v-if="amountLabels.showOtherAmount"
              :label="amountLabels.otherAmount"
              :value="formatCurrency(model.other_amount)"
            />
            <Label label="Total Amount" :value="formatCurrency(model.total_amount ?? model.amount)" />
            <Label label="Paid Amount" :value="formatCurrency(model.paid_amount ?? 0)" />
            <Label
              v-if="model.status === 'approved'"
              label="Due Amount"
              :value="formatCurrency(model.due_amount ?? model.total_amount ?? model.amount)"
            />
            <Label label="Invoice Date" :value="formatDate(model.invoice_date)" />
            <Label label="Due Date" :value="formatDate(model.due_date)" />
            <Label label="Workgroup" :value="model.workgroup?.name || '-'" />
            <Label label="Company" :value="model.company?.name || '-'" />
            <Label label="Vendor" :value="model.vendor?.name || '-'" />
            <Label label="Remarks" :value="model.remarks || '-'" />
          </div>
        </div>

        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            Document
          </h6>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-700 dark:text-gray-300">
              {{ model.document?.file_name || 'No document uploaded' }}
            </span>
            <Button
              v-if="model.document?.file_path"
              type="button"
              variant="outline-primary"
              size="sm"
              @click="viewDocument"
            >
              View Document
            </Button>
          </div>
        </div>

        <div v-if="model.status === 'approved' || model.status === 'paid'">
          <div class="flex items-center justify-between gap-3 mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-0">
              Payments
            </h6>
            <Button
              v-if="canAddPayment"
              icon-left="dollar"
              icon-size="sm"
              variant="primary"
              size="sm"
              @click="openPaymentModal"
            >
              Add Payment
            </Button>
          </div>

          <div v-if="payments.length" class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                  <th class="py-2 pr-4 font-medium">Date</th>
                  <th class="py-2 pr-4 font-medium">Payment Type</th>
                  <th class="py-2 pr-4 font-medium">Check #</th>
                  <th class="py-2 pr-4 font-medium">Remarks</th>
                  <th class="py-2 pr-4 font-medium text-right">Amount</th>
                  <th class="py-2 pr-4 font-medium">Recorded By</th>
                  <th v-if="canManagePayments" class="py-2 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="payment in payments"
                  :key="payment.id"
                  class="border-b border-gray-100 dark:border-gray-800"
                >
                  <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ formatDate(payment.payment_date) }}</td>
                  <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ formatPaymentMethod(payment.payment_method) }}</td>
                  <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ payment.check_number || '-' }}</td>
                  <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ payment.remarks || '-' }}</td>
                  <td class="py-2 pr-4 text-right font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(payment.amount) }}</td>
                  <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ payment.created_by?.name || '-' }}</td>
                  <td v-if="canManagePayments" class="py-2 text-right">
                    <div class="inline-flex items-center gap-2">
                      <Button
                        icon-left="edit"
                        icon-size="sm"
                        variant="outline-primary"
                        size="xs"
                        type="button"
                        @click="openEditPaymentDateModal(payment)"
                      >
                        Edit Date
                      </Button>
                      <Button
                        icon-left="trash"
                        icon-size="sm"
                        variant="danger"
                        size="xs"
                        type="button"
                        :loading="deletingPaymentId === payment.id"
                        @click="confirmDeletePayment(payment)"
                      >
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">
            No payments recorded yet.
          </p>
        </div>

        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            Audit
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Created By" :value="model.created_by?.name || '-'" />
            <Label label="Updated By" :value="model.updated_by?.name || '-'" />
            <Label label="Created At" :value="formatDateTime(model.created_at)" />
            <Label label="Updated At" :value="formatDateTime(model.updated_at)" />
          </div>
        </div>
      </div>
    </Panel>

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
      <div class="space-y-4">
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3 text-sm">
          <div class="flex justify-between gap-4">
            <span class="text-gray-600 dark:text-gray-400">Total Amount</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(model.total_amount ?? model.amount) }}</span>
          </div>
          <div class="flex justify-between gap-4 mt-1">
            <span class="text-gray-600 dark:text-gray-400">Already Paid</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(model.paid_amount ?? 0) }}</span>
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
      v-model="editPaymentDateModalOpen"
      title="Edit Payment Date"
      size="sm"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Save Date"
      :loading="editPaymentDateSubmitting"
      @confirm="submitPaymentDate"
    >
      <div class="space-y-4">
        <div v-if="editingPayment" class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3 text-sm space-y-1">
          <div class="flex justify-between gap-4">
            <span class="text-gray-600 dark:text-gray-400">Payment Type</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatPaymentMethod(editingPayment.payment_method) }}</span>
          </div>
          <div class="flex justify-between gap-4">
            <span class="text-gray-600 dark:text-gray-400">Amount</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(editingPayment.amount) }}</span>
          </div>
        </div>
        <Input
          v-model="editPaymentDate"
          label="Payment Date"
          type="date"
          :required="true"
        />
      </div>
    </Modal>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading purchase invoice details..." centered />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import { formatDateTime as formatDateTime, formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { formatExpenseType, getExpenseAmountLabels } from '@/utils/purchaseInvoiceExpense'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'

const route = useRoute()
const message = useMessage()
const resource = route.meta?.resource || 'ap/purchase-invoices'

const { model, show, setData, removeDB, access } = useShowable(resource, 'purchase-invoice')

const paymentModalOpen = ref(false)
const paymentAmount = ref('')
const paymentMethod = ref('')
const paymentCheckNumber = ref('')
const paymentDate = ref('')
const paymentRemarks = ref('')
const paymentSubmitting = ref(false)

const editPaymentDateModalOpen = ref(false)
const editPaymentDate = ref('')
const editingPayment = ref(null)
const editPaymentDateSubmitting = ref(false)
const deletingPaymentId = ref(null)

const paymentMethodOptions = [
  { value: 'ach', label: 'ACH' },
  { value: 'cash', label: 'Cash' },
  { value: 'check', label: 'Check' },
  { value: 'other', label: 'Other' },
]

const payments = computed(() => model.value?.payments || [])

const paymentDueAmount = computed(() => {
  if (!model.value) return 0
  if (model.value.due_amount != null) return Number(model.value.due_amount)
  return Number(model.value.total_amount ?? model.value.amount ?? 0) - Number(model.value.paid_amount ?? 0)
})

const canManagePayments = computed(() => {
  if (!model.value) return false
  if (!['approved', 'paid'].includes(model.value.status)) return false
  return access.value.includes('payment') || access.value.includes('paid')
})

const canAddPayment = computed(() => {
  if (!canManagePayments.value || model.value.status !== 'approved') return false
  return paymentDueAmount.value > 0
})

const amountLabels = computed(() => getExpenseAmountLabels(model.value?.expense))

const statusClass = (status) => {
  const classes = {
    draft: 'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    approved: 'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    paid: 'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    cancelled: 'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
  }

  return classes[status] || classes.draft
}

const formatStatus = (status) => {
  if (!status) return '-'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatPaymentMethod = (method) => {
  const labels = {
    ach: 'ACH',
    cash: 'Cash',
    check: 'Check',
    other: 'Other',
  }

  return labels[method] || '-'
}

const normalizePaymentDate = (value) => {
  if (!value) return new Date().toISOString().slice(0, 10)
  if (typeof value === 'string') return value.slice(0, 10)
  return new Date(value).toISOString().slice(0, 10)
}

const openPaymentModal = () => {
  paymentAmount.value = ''
  paymentMethod.value = ''
  paymentCheckNumber.value = ''
  paymentDate.value = new Date().toISOString().slice(0, 10)
  paymentRemarks.value = ''
  paymentModalOpen.value = true
}

const openEditPaymentDateModal = (payment) => {
  editingPayment.value = payment
  editPaymentDate.value = normalizePaymentDate(payment.payment_date)
  editPaymentDateModalOpen.value = true
}

const reloadModel = async () => {
  const id = model.value?.id
  if (!id) return

  const response = await useRequest('get', `${resource}/${id}`)
  if (response?.model) {
    setData(response)
  }
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

  const id = model.value?.id
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
      paymentAmount.value = ''
      paymentMethod.value = ''
      paymentCheckNumber.value = ''
      paymentDate.value = ''
      paymentRemarks.value = ''
      await reloadModel()
    } else {
      message.error(response.message || 'Failed to record payment')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to record payment')
  } finally {
    paymentSubmitting.value = false
  }
}

const submitPaymentDate = async () => {
  const invoiceId = model.value?.id
  const paymentId = editingPayment.value?.id
  const date = (editPaymentDate.value || '').trim()

  if (!invoiceId || !paymentId) return

  if (!date) {
    message.error('Please select a payment date.')
    return
  }

  editPaymentDateSubmitting.value = true
  try {
    const response = await useRequest('put', `${resource}/${invoiceId}/payments/${paymentId}`, {
      payment_date: date,
    })
    if (response.success) {
      message.success(response.message || 'Payment date updated successfully')
      editPaymentDateModalOpen.value = false
      editingPayment.value = null
      editPaymentDate.value = ''
      await reloadModel()
    } else {
      message.error(response.message || 'Failed to update payment date')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to update payment date')
  } finally {
    editPaymentDateSubmitting.value = false
  }
}

const confirmDeletePayment = async (payment) => {
  const invoiceId = model.value?.id
  if (!invoiceId || !payment?.id) return

  const confirmed = window.confirm(
    `Delete payment of ${formatCurrency(payment.amount)} dated ${formatDate(payment.payment_date)}?`
  )
  if (!confirmed) return

  deletingPaymentId.value = payment.id
  try {
    const response = await useRequest('delete', `${resource}/${invoiceId}/payments/${payment.id}`)
    if (response.success) {
      message.success(response.message || 'Payment deleted successfully')
      await reloadModel()
    } else {
      message.error(response.message || 'Failed to delete payment')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete payment')
  } finally {
    deletingPaymentId.value = null
  }
}

const viewDocument = async () => {
  const id = model.value?.document?.id
  if (!id) return

  try {
    const response = await useRequest('post', '/view-document', { id })
    if (response.url) {
      window.open(response.url, '_blank', 'noopener,noreferrer')
    } else {
      message.error('Failed to view document')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to view document')
  }
}

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

defineExpose({ setData })
</script>
