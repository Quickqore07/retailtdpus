<template>
  <div>
    
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Customers</h4>
    </div>
    <!-- Header / Actions Bar -->
    <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div class="w-full md:w-1/3">
          <Input
            v-model="search"
            placeholder="Search by name, email, mobile..."
            @input="debounceSearch"
          />
        </div>
        <div class="flex flex-wrap items-center gap-2 md:justify-end">
          <input
            ref="importFileInput"
            type="file"
            accept=".csv,text/csv"
            class="sr-only"
            @change="onImportFileSelected"
          />
          <Button
            v-if="canExportCustomers"
            type="button"
            variant="outline-secondary"
            size="sm"
            icon-left="download"
            icon-size="md"
            :loading="exporting"
            :disabled="exporting || importing"
            @click="exportCustomersCsv"
          >
            Export CSV
          </Button>
          <Button
            v-if="canImportCustomers"
            type="button"
            variant="outline-secondary"
            size="sm"
            icon-left="file-spreadsheet"
            icon-size="md"
            :loading="importing"
            :disabled="exporting || importing"
            @click="triggerImportPicker"
          >
            Import CSV
          </Button>
          <Button
            v-if="can('upload-portal-customer', 'add')"
            @click="openAddModal"
            variant="primary"
            size="sm"
            icon-left="plus"
            icon-size="md"
          >
            Add Customer
          </Button>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <Spinner size="lg" text="Loading customers..." />
    </div>

    <!-- Table -->
    <div
      v-else-if="customers.length > 0"
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
    >
      <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 dark:border-gray-700">
            <tr>
              <Th align="left" custom-class="py-4">Name</Th>
              <Th align="left" custom-class="py-4">Email</Th>
              <Th align="left" custom-class="py-4">Mobile</Th>
              <Th align="left" custom-class="py-4">Primary Person</Th>
              <Th align="left" custom-class="py-4">City</Th>
              <Th align="left" custom-class="py-4">Invoice Due</Th>
              <Th align="right" custom-class="py-4">Outstanding Balance</Th>
              <Th align="right" custom-class="py-4">Credit Balance</Th>
              <Th align="right" custom-class="py-4">Actions</Th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="customer in customers"
              :key="customer.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
              :class="{ 'opacity-50 pointer-events-none': deletingId === customer.id }"
            >
              <Td custom-class="py-4">
                <div class="font-medium text-gray-900 dark:text-white">{{ customer.name }}</div>
              </Td>
              <Td custom-class="py-4">{{ customer.email || '-' }}</Td>
              <Td custom-class="py-4">{{ customer.mobile || '-' }}</Td>
              <Td custom-class="py-4">{{ customer.primary_person_name || '-' }}</Td>
              <Td custom-class="py-4">{{ customer.city || '-' }}</Td>
              <Td custom-class="py-4">{{ customer.invoice_due && customer.invoice_due > 0 ? customer.invoice_due + ' days' : '-' }} {{ customer.invoice_condition || '-' }}</Td>
              <Td align="right" custom-class="py-4 tabular-nums">{{ customer.outstanding_balance > 0 ? formatCurrency(customer.outstanding_balance) : '-' }}</Td>
              <Td align="right" custom-class="py-4 tabular-nums">
                <span v-if="customer.credit_balance > 0" class="text-green-600 dark:text-green-400">
                  {{ formatCurrency(customer.credit_balance) }}
                </span>
                <span v-else>-</span>
              </Td>
              <Td align="right" custom-class="py-4">
                <div class="flex items-center justify-end">
                  <IconMenuDropdown title="Actions">
                    <template #default="{ close }">
                      <button
                        v-if="canViewCustomerProfile"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="goToCustomerView(customer); close()"
                      >
                        <SvgIcon name="eye" size="sm" />
                        View
                      </button>
                      <button
                        v-if="can('upload-portal-customer', 'edit')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="openEditModal(customer); close()"
                      >
                        <SvgIcon name="edit" size="sm" />
                        Edit
                      </button>
                      <button
                        v-if="can('upload-portal-customer', 'delete')"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        role="menuitem"
                        :disabled="deletingId === customer.id"
                        @click="deleteCustomer(customer); close()"
                      >
                        <Spinner v-if="deletingId === customer.id" size="sm" />
                        <SvgIcon v-else name="trash" size="sm" />
                        {{ deletingId === customer.id ? 'Deleting...' : 'Delete' }}
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
      <SvgIcon name="user" size="lg" class="text-gray-300 dark:text-gray-600 mx-auto mb-3" />
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No customers yet</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">Add your first customer to get started</p>
    </div>

    <!-- Pagination -->
    <div v-if="customers.length > 0" class="mt-6">
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
      size="5xl"
      :title="isEditMode ? 'Edit Customer' : 'Add Customer'"
      @update:model-value="closeModal"
    >
      <form @submit.prevent="handleSubmit">
        <!-- Basic Info -->
        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">Basic Information</h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Input
              v-model="form.name"
              label="Name"
              placeholder="Enter customer name"
              :required="true"
              :error="errors.name"
            />
            <Input
              v-model="form.email"
              label="Email"
              type="email"
              placeholder="Enter email"
              :error="errors.email"
            />
            <Input
              v-model="form.mobile"
              label="Mobile"
              placeholder="Enter mobile number"
              :error="errors.mobile"
            />
            <Input
              v-model="form.fax"
              label="Fax"
              placeholder="Enter fax number"
              :error="errors.fax"
            />
            <Input
              v-model="form.primary_person_name"
              label="Primary Person Name"
              placeholder="Enter primary contact"
              :error="errors.primary_person_name"
            />
          </div>
        </div>

        <!-- Address -->
        <div class="mt-5">
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">Address</h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Input
              v-model="form.address_line_1"
              label="Address Line 1"
              placeholder="Street address"
              :error="errors.address_line_1"
            />
            <Input
              v-model="form.address_line_2"
              label="Address Line 2"
              placeholder="Apartment, suite, etc."
              :error="errors.address_line_2"
            />
            <Input
              v-model="form.address_line_3"
              label="Address Line 3"
              placeholder="Address line 3"
              :error="errors.address_line_3"
            />
            <Input
              v-model="form.city"
              label="City"
              placeholder="City"
              :error="errors.city"
            />
            <Input
              v-model="form.state"
              label="State"
              placeholder="State"
              :error="errors.state"
            />
            <!-- <Input
              v-model="form.country"
              label="Country"
              placeholder="Country"
              :error="errors.country"
            /> -->
            <Input
              v-model="form.zip_code"
              label="Zip Code"
              placeholder="Zip / Postal code"
              :error="errors.zip_code"
            />
          </div>
        </div>

        <!-- Financial Details -->
        <div class="mt-5">
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">Financial Details</h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- <Input
              v-model="form.bank_name"
              label="Bank Name"
              placeholder="Bank name"
              :error="errors.bank_name"
            />
            <Input
              v-model="form.account_number"
              label="Account Number"
              placeholder="Account number"
              :error="errors.account_number"
            />
            <Input
              v-model="form.routing_number"
              label="Routing Number"
              placeholder="Routing number"
              :error="errors.routing_number"
            /> -->
            <Input
              v-model="form.credit_card_number"
              label="Credit Card Number"
              placeholder="Credit card number"
              :error="errors.credit_card_number"
            />
            <Input
              v-model="form.card_expiry"
              label="Expiry"
              placeholder="MM/YY"
              :error="errors.card_expiry"
            />
            <Input
              v-model="form.name_on_card"
              label="Name on Card"
              placeholder="Name on card"
              :error="errors.name_on_card"
            />
            <Input
              v-model="form.cvv"
              label="CVV"
              placeholder="CVV"
              :error="errors.cvv"
            />
            <Input
              v-model="form.financial_zip_code"
              label="Zip Code"
              placeholder="Zip / Postal code"
              :error="errors.financial_zip_code"
            />
            <!-- <Input
              v-model="form.card_type"
              label="Card Type"
              placeholder="Card type like Visa, Mastercard, etc."
              :error="errors.card_type"
            /> -->
          </div>
        </div>

        <!-- Due Date Settings -->
        <div class="mt-5">
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">Credit Terms</h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Input
              v-model="form.invoice_due"
              label="Days"
              type="number"
              :min="0"
              :max="366"
              placeholder="0"
              :error="errors.invoice_due"
            />
            <DynamicDropdown
              v-model="selectedInvoiceCondition"
              label="Condition"
              :custom-options="invoiceConditionOptions"
              display-name="name"
              placeholder="Select condition"
              :error="errors.invoice_condition"
              @change="onInvoiceConditionChange"
            />
          </div>
        </div>

        <!-- Email notes -->
        <div class="mt-5">
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">Email</h6>
          <Textarea
            v-model="form.email_notes"
            label="Email notes"
            placeholder="Notes included when sending invoices or statements to this customer"
            :rows="4"
            :error="errors.email_notes"
            help-text="Appended to invoice and statement emails. You can still edit the message before sending."
          />
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
          Cancel
        </Button>
        <Button
          type="submit"
          @click="handleSubmit"
          :disabled="saving"
          variant="primary"
          size="md"
        >
          {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update Customer' : 'Save Customer') }}
        </Button>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
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
import Textarea from '@/components/ui/textarea.vue'
import { useUser } from '../../composables/useUser'
import { useMessage } from '@/composables/useMessage'
import { formatCurrency } from '@/utils/number'
import { rejectOversizedFile } from '@/utils/documentUpload'

const emptyForm = () => ({
  id: null,
  name: '',
  email: '',
  mobile: '',
  fax: '',
  primary_person_name: '',
  email_notes: '',
  address_line_1: '',
  address_line_2: '',
  address_line_3: '',
  city: '',
  state: '',
  country: '',
  zip_code: '',
  bank_name: '',
  account_number: '',
  routing_number: '',
  credit_card_number: '',
  card_expiry: '',
  name_on_card: '',
  financial_zip_code: '',
  invoice_due: 0,
  invoice_condition: '',
})

const router = useRouter()
const { can } = useUser()
const message = useMessage()

const canViewCustomerProfile = computed(
  () => can('upload-portal-customer', 'view') || can('upload-portal-customer', 'index')
)

const canExportCustomers = computed(() => can('upload-portal-customer', 'index'))

const canImportCustomers = computed(
  () => can('upload-portal-customer', 'add') || can('upload-portal-customer', 'edit')
)

const goToCustomerView = (customer) => {
  router.push({
    name: 'upload-portal-ar-customer-view',
    params: { customerId: String(customer.id) },
  })
}

const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const exporting = ref(false)
const importing = ref(false)
const importFileInput = ref(null)
const customers = ref([])
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

const invoiceConditions = ref([])
const invoiceConditionOptions = computed(() =>
  invoiceConditions.value.map((c) => ({ id: c, name: c }))
)

const showModal = ref(false)
const isEditMode = ref(false)
const form = reactive(emptyForm())
const errors = ref({})
const selectedInvoiceCondition = ref(null)

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

const loadCustomers = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-customers', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value || undefined,
      },
    })
    if (response.data.success) {
      customers.value = response.data.customers.data || []
      pagination.value = buildPagination(response.data.customers)
      invoiceConditions.value = response.data.invoice_conditions || []
    }
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to load customers'
    message.error(msg)
  } finally {
    loading.value = false
  }
}

let searchTimer = null
const debounceSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadCustomers(1), 400)
}

const onPageChange = (page) => {
  loadCustomers(page)
}

const exportCustomersCsv = async () => {
  if (exporting.value) return
  exporting.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-customers/export', {
      params: {
        search: search.value.trim() || undefined,
      },
      responseType: 'blob',
    })
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    const cd = response.headers['content-disposition']
    let filename = 'upload-portal-customers.csv'
    if (cd && cd.includes('filename=')) {
      const part = cd.split('filename=')[1]?.trim()
      if (part) {
        filename = part.replace(/^["']|["']$/g, '')
      }
    }
    a.setAttribute('download', filename)
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
    message.success('Customers exported')
  } catch (error) {
    const data = error.response?.data
    if (data instanceof Blob) {
      try {
        const text = await data.text()
        const parsed = JSON.parse(text)
        message.error(parsed.message || 'Export failed')
      } catch {
        message.error('Export failed')
      }
    } else {
      message.error(data?.message || 'Export failed')
    }
  } finally {
    exporting.value = false
  }
}

const triggerImportPicker = () => {
  if (importing.value) return
  importFileInput.value?.click()
}

const onImportFileSelected = async (event) => {
  const input = event.target
  const file = input.files?.[0]
  input.value = ''
  if (!file || importing.value) return
  if (rejectOversizedFile(file, (error) => message.error(error))) {
    return
  }
  importing.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    const response = await axios.post('/upload-portal/api/ar-customers/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (response.data.success) {
      const errCount = response.data.errors?.length || 0
      let msg = response.data.message || 'Import completed'
      if (errCount > 0) {
        msg += ` (${errCount} row(s) skipped.)`
        const samples = response.data.errors.slice(0, 5).map((e) => `Row ${e.row}: ${e.message}`).join(' ')
        message.warning(`${msg} ${samples}`)
      } else {
        message.success(msg)
      }
      loadCustomers(pagination.value.current_page)
    }
  } catch (error) {
    const msg = error.response?.data?.message || 'Import failed'
    message.error(msg)
  } finally {
    importing.value = false
  }
}

const resetForm = () => {
  Object.assign(form, emptyForm())
  selectedInvoiceCondition.value = null
  errors.value = {}
}

const openAddModal = () => {
  resetForm()
  isEditMode.value = false
  showModal.value = true
}

const openEditModal = (customer) => {
  resetForm()
  isEditMode.value = true
  Object.keys(form).forEach((key) => {
    if (customer[key] !== undefined && customer[key] !== null) {
      form[key] = customer[key]
    }
  })
  form.id = customer.id
  if (customer.invoice_condition) {
    selectedInvoiceCondition.value = {
      id: customer.invoice_condition,
      name: customer.invoice_condition,
    }
  }
  showModal.value = true
}

const closeModal = () => {
  if (saving.value) return
  showModal.value = false
  resetForm()
}

const onInvoiceConditionChange = (value) => {
  form.invoice_condition = value?.id || ''
}

const buildPayload = () => {
  const payload = { ...form }
  delete payload.id
  payload.invoice_due = payload.invoice_due === '' || payload.invoice_due === null
    ? 0
    : Number(payload.invoice_due)
  return payload
}

const handleSubmit = async () => {
  if (saving.value) return
  errors.value = {}
  saving.value = true
  try {
    const payload = buildPayload()
    let response
    if (isEditMode.value) {
      response = await axios.put(`/upload-portal/api/ar-customers/${form.id}`, payload)
    } else {
      response = await axios.post('/upload-portal/api/ar-customers', payload)
    }
    if (response.data.success) {
      message.success(response.data.message || 'Customer saved')
      showModal.value = false
      resetForm()
      loadCustomers(isEditMode.value ? pagination.value.current_page : 1)
    }
  } catch (error) {
    if (error.response?.status === 422 && error.response.data?.errors) {
      const apiErrors = error.response.data.errors
      Object.keys(apiErrors).forEach((k) => {
        errors.value[k] = Array.isArray(apiErrors[k]) ? apiErrors[k][0] : apiErrors[k]
      })
    }
    const msg = error.response?.data?.message || 'Failed to save customer'
    message.error(msg)
  } finally {
    saving.value = false
  }
}

const deleteCustomer = async (customer) => {
  if (deletingId.value) return
  if (!window.confirm(`Delete customer "${customer.name}"?`)) return
  deletingId.value = customer.id
  try {
    const response = await axios.delete(`/upload-portal/api/ar-customers/${customer.id}`)
    if (response.data.success) {
      message.success(response.data.message || 'Customer deleted')
      const nextPage = customers.value.length === 1 && pagination.value.current_page > 1
        ? pagination.value.current_page - 1
        : pagination.value.current_page
      loadCustomers(nextPage)
    }
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to delete customer'
    message.error(msg)
  } finally {
    deletingId.value = null
  }
}

onMounted(() => {
  loadCustomers(1)
})
</script>
