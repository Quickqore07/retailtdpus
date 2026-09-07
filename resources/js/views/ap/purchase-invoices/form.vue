<template>
  <div v-if="show" class="purchase-invoice-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Purchase Invoice' : 'Create Purchase Invoice' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <WorkgroupCompanySelect
          v-model:workgroup="form.workgroup"
          v-model:company="form.company"
          :workgroup-error="errors.workgroup_id?.[0] || null"
          :company-error="errors.company_id?.[0] || null"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="flex items-end gap-2">
            <div class="flex-1 min-w-0">
              <DynamicDropdown
                v-model="form.vendor"
                label="Vendor"
                resource="vendors"
                display-name="name"
                placeholder="Select vendor"
                :required="true"
                :error="errors.vendor_id?.[0] || null"
              />
            </div>
            <Button
              v-if="canCreateVendor"
              type="button"
              variant="outline-secondary"
              size="sm"
              icon-left="plus"
              icon-only
              title="Add vendor"
              class="shrink-0 h-9"
              @click="showVendorModal = true"
            />
          </div>

          <Input
            v-model="form.invoice_no"
            label="Invoice #"
            placeholder="Enter invoice number"
            :required="true"
            :error="errors.invoice_no?.[0] || null"
          />

          <Input
            v-model="form.invoice_date"
            type="date"
            label="Invoice Date"
            :required="true"
            :error="errors.invoice_date?.[0] || null"
          />

          <Input
            v-model="form.due_date"
            type="date"
            label="Due Date"
            :error="errors.due_date?.[0] || null"
          />

          <div class="flex items-end gap-2">
            <div class="flex-1 min-w-0">
              <DynamicDropdown
                v-model="form.expense"
                label="Expense Type"
                resource="expense-types"
                display-name="name"
                placeholder="Select expense type"
                :required="true"
                :searchable="false"
                :error="errors.expense_id?.[0] || null"
              />
            </div>
            <Button
              v-if="canCreateExpenseType"
              type="button"
              variant="outline-secondary"
              size="sm"
              icon-left="plus"
              icon-only
              title="Add expense type"
              class="shrink-0 h-9"  
              @click="showExpenseTypeModal = true"
            />
          </div>

          <Input
            v-model="form.amount"
            type="number"
            step="0.01"
            min="0"
            :label="amountLabels.amount"
            placeholder="0.00"
            :required="amountLabels.amountRequired"
            :error="errors.amount?.[0] || null"
          />

          <Input
            v-if="amountLabels.showOtherAmount"
            v-model="form.other_amount"
            type="number"
            step="0.01"
            min="0"
            :label="amountLabels.otherAmount"
            placeholder="0.00"
            :error="errors.other_amount?.[0] || null"
          />

          <Input
            :model-value="totalAmountDisplay"
            type="text"
            label="Total Amount"
            placeholder="0.00"
            :disabled="true"
          />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Remarks
            </label>
            <textarea
              v-model="form.remarks"
              rows="4"
              class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
              placeholder="Additional notes about this invoice"
            />
            <p v-if="errors.remarks?.[0]" class="text-xs text-red-600 dark:text-red-400">
              {{ errors.remarks[0] }}
            </p>
          </div>

          <DocumentFileField
            v-model="invoiceDocument"
            label="Invoice Document"
            placeholder="Upload purchase invoice document"
            :error="errors.document?.[0] || null"
          />
        </div>

        <div v-if="mode === 'edit' && form.document?.file_name" class="text-sm text-gray-500 dark:text-gray-400">
          Current document: {{ form.document.file_name }}
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
            {{ mode === 'edit' ? 'Update Purchase Invoice' : 'Create Purchase Invoice' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>

  <VendorCreateModal
    v-model="showVendorModal"
    @created="onVendorCreated"
  />

  <ExpenseTypeCreateModal
    v-model="showExpenseTypeModal"
    @created="onExpenseTypeCreated"
  />
</template>

<script setup>
import { onMounted, ref, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import WorkgroupCompanySelect from '@/components/common/WorkgroupCompanySelect.vue'
import DocumentFileField from '@/components/common/DocumentFileField.vue'
import VendorCreateModal from '@/views/ap/purchase-invoices/components/VendorCreateModal.vue'
import ExpenseTypeCreateModal from '@/views/ap/purchase-invoices/components/ExpenseTypeCreateModal.vue'
import { useRequest } from '@/services/api'
import { useMessage, useLoadingBar } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import { useAuthStore } from '@/stores/auth'
import { calculateTotalAmount, getExpenseAmountLabels } from '@/utils/purchaseInvoiceExpense'
import { addDaysToDateString } from '@/utils/date'
import { validateDocumentFileSize } from '@/utils/documentUpload'

const route = useRoute()
const router = useRouter()
const message = useMessage()
const loadingBar = useLoadingBar()
const { getAllowedActions, can } = usePermission()
const authStore = useAuthStore()

const showVendorModal = ref(false)
const showExpenseTypeModal = ref(false)
const canCreateVendor = computed(() => can('vendor', 'create'))
const canCreateExpenseType = computed(() => can('expense-type', 'create'))

const resource = route.meta?.resource || 'ap/purchase-invoices'
const mode = route.meta?.mode
const access = ref([])
const show = ref(false)
const isSaving = ref(false)
const errors = ref({})
const invoiceDocument = ref(null)
const options = ref({ statuses: [] })

const form = ref({
  workgroup_id: null,
  company_id: null,
  vendor_id: null,
  workgroup: null,
  company: null,
  vendor: null,
  invoice_date: '',
  invoice_no: '',
  due_date: '',
  expense_id: null,
  expense: null,
  amount: '',
  other_amount: 0,
  total_amount: 0,
  remarks: '',
  status: 'draft',
  document: null,
})

const amountLabels = computed(() => getExpenseAmountLabels(form.value.expense))

const totalAmountDisplay = computed(() => {
  const total = calculateTotalAmount(
    form.value.amount,
    amountLabels.value.showOtherAmount ? form.value.other_amount : 0
  )
  return total.toFixed(2)
})

const canAccess = () => {
  access.value = getAllowedActions('purchase-invoice')
  if (!access.value.includes('create') && mode === 'create') {
    router.push('/')
  }
  if (!access.value.includes('update') && mode === 'edit') {
    router.push('/')
  }
}

watch(() => authStore.user?.role?.name, canAccess)

watch(() => form.value.workgroup, (workgroup) => {
  form.value.workgroup_id = workgroup?.id || null
})

watch(() => form.value.company, (company) => {
  form.value.company_id = company?.id || null
})

watch(() => form.value.vendor, (vendor) => {
  form.value.vendor_id = vendor?.id || null
  applyVendorCreditDays()
})

watch(() => form.value.invoice_date, () => {
  applyVendorCreditDays()
})

watch(() => form.value.expense, (expense) => {
  form.value.expense_id = expense?.id || null
  if (!getExpenseAmountLabels(expense).showOtherAmount) {
    form.value.other_amount = 0
  }
})

const formatIsoDate = (value) => {
  if (!value) return ''
  return value.includes('T') ? value.split('T')[0] : value.split(' ')[0]
}

const applyVendorCreditDays = () => {
  const creditDays = form.value.vendor?.credit_days
  if (creditDays === null || creditDays === undefined || creditDays === '') {
    return
  }

  if (!form.value.invoice_date) {
    return
  }

  form.value.due_date = addDaysToDateString(form.value.invoice_date, creditDays)
}

const loadForm = async () => {
  const url = mode === 'edit'
    ? `/${resource}/${route.params.id}/edit`
    : `/${resource}/create`

  const response = await useRequest('get', url)
  options.value = response.options || { statuses: [] }

  if (mode === 'edit') {
    const item = response.form || {}
    form.value = {
      ...form.value,
      ...item,
      invoice_date: formatIsoDate(item.invoice_date),
      due_date: formatIsoDate(item.due_date),
      workgroup: item.workgroup || null,
      company: item.company || null,
      vendor: item.vendor || null,
      expense: item.expense || null,
      other_amount: item.other_amount ?? 0,
    }
  } else {
    form.value = {
      ...form.value,
      ...(response.form || {}),
    }
  }

  show.value = true
  loadingBar.finish()
}

const handleSave = async () => {
  isSaving.value = true
  errors.value = {}

  if (invoiceDocument.value) {
    const sizeError = validateDocumentFileSize(invoiceDocument.value)
    if (sizeError) {
      errors.value = { document: [sizeError] }
      message.error(sizeError)
      isSaving.value = false
      return
    }
  }

  try {
    const payload = new FormData()

    const fields = [
      'workgroup_id',
      'company_id',
      'vendor_id',
      'invoice_date',
      'invoice_no',
      'due_date',
      'expense_id',
      'amount',
      'other_amount',
      'remarks',
      'status',
    ]

    const otherAmount = amountLabels.value.showOtherAmount
      ? (form.value.other_amount || 0)
      : 0

    fields.forEach((key) => {
      const value = key === 'other_amount' ? otherAmount : form.value[key]
      if (value !== null && value !== '') {
        payload.append(key, value)
      }
    })

    if (invoiceDocument.value) {
      payload.append('document', invoiceDocument.value)
    }

    const url = mode === 'edit'
      ? `/${resource}/${route.params.id}`
      : `/${resource}`

    if (mode === 'edit') {
      payload.append('_method', 'PUT')
    }

    const response = await useRequest('post', url, payload, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    if (response.saved) {
      message.success(response.message || 'Purchase invoice saved successfully')
      router.push(`/ap/purchase-invoices/${response.id}`)
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    message.error(error.response?.data?.message || 'Failed to save purchase invoice')
  } finally {
    isSaving.value = false
  }
}

const cancel = () => {
  if (mode === 'edit') {
    router.push(`/ap/purchase-invoices/${route.params.id}`)
    return
  }

  router.push('/ap/purchase-invoices')
}

const onVendorCreated = (vendor) => {
  form.value.vendor = vendor
  applyVendorCreditDays()
}

const onExpenseTypeCreated = (expense) => {
  form.value.expense = expense
  if (!getExpenseAmountLabels(expense).showOtherAmount) {
    form.value.other_amount = 0
  }
}

onMounted(async () => {
  canAccess()
  try {
    await loadForm()
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load purchase invoice form')
    loadingBar.finish()
    router.push('/ap/purchase-invoices')
  }
})
</script>
