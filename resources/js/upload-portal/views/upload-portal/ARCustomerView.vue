<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <Button
          type="button"
          variant="outline-secondary"
          size="sm"
          icon-left="chevron-left"
          icon-size="sm"
          class="mb-3"
          @click="goBack"
        >
          Back to AR Invoices
        </Button>
        <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">
          {{ customer?.name || 'Customer' }}
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
          Customer profile, invoices, and account statement
        </p>
      </div>
    </div>

    <div v-if="loadingCustomer" class="flex flex-col items-center justify-center py-20">
      <Spinner size="lg" text="Loading customer..." />
    </div>

    <div v-else-if="customerLoadError" class="text-center py-16 text-red-600 dark:text-red-400">
      {{ customerLoadError }}
    </div>

    <template v-else-if="customer">
      <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-3 mb-4">
        <div class="flex flex-wrap items-center gap-2">
          <Button
            v-for="tab in tabs"
            :key="tab.value"
            :variant="activeTab === tab.value ? 'primary' : 'outline-secondary'"
            size="sm"
            type="button"
            @click="activeTab = tab.value"
          >
            {{ tab.label }}
          </Button>
        </div>
      </div>

      <!-- General -->
      <div v-show="activeTab === 'general'" class="space-y-4 mb-6">
        <section class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            Basic information
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div v-for="row in basicRows(customer)" :key="row.label">
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ row.label }}</div>
              <div class="text-gray-900 dark:text-white break-words">{{ row.value }}</div>
            </div>
          </div>
        </section>

        <section class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            Address
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div v-for="row in addressRows(customer)" :key="row.label">
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ row.label }}</div>
              <div class="text-gray-900 dark:text-white break-words">{{ row.value }}</div>
            </div>
          </div>
        </section>

        <section class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            Financial details
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-3 text-sm">
            <div v-for="row in financialRows(customer)" :key="row.label">
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ row.label }}</div>
              <div class="text-gray-900 dark:text-white break-words">{{ row.value }}</div>
            </div>
          </div>
        </section>

        <section class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            Credit terms
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div v-for="row in creditRows(customer)" :key="row.label">
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ row.label }}</div>
              <div class="text-gray-900 dark:text-white break-words">{{ row.value }}</div>
            </div>
          </div>
        </section>

        <section class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            Account balances
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div>
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Outstanding balance</div>
              <div class="text-gray-900 dark:text-white">{{ customer.outstanding_balance > 0 ? formatCurrency(customer.outstanding_balance) : '—' }}</div>
            </div>
            <div>
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Available credit</div>
              <div class="text-green-600 dark:text-green-400 font-medium">
                {{ customer.credit_balance > 0 ? formatCurrency(customer.credit_balance) : '—' }}
              </div>
            </div>
          </div>
        </section>

        <section class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            Email notes
          </h5>
          <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap break-words">
            {{ customer.email_notes?.trim() ? customer.email_notes : '—' }}
          </p>
          <p v-if="customer.email_notes?.trim()" class="text-xs text-gray-500 dark:text-gray-400 !mb-0 mt-2">
            Included in invoice and statement emails for this customer.
          </p>
        </section>
      </div>

      <!-- Invoices -->
      <div v-show="activeTab === 'invoices'" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
          <h5 class="text-lg font-semibold text-gray-900 dark:text-white !mb-0">
            Invoices
          </h5>
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-1">Balance</span>
            <Button
              v-for="opt in invoiceFilterOptions"
              :key="opt.value"
              type="button"
              size="sm"
              :variant="invoiceFilter === opt.value ? 'primary' : 'outline-secondary'"
              @click="setInvoiceFilter(opt.value)"
            >
              {{ opt.label }}
            </Button>
          </div>
        </div>

        <div v-if="invoicesDenied" class="text-sm text-amber-700 dark:text-amber-300 py-4">
          You do not have permission to view this customer's invoices.
        </div>

        <div v-else-if="loadingInvoices" class="flex justify-center py-12">
          <Spinner size="md" text="Loading invoices..." />
        </div>

        <div v-else-if="customerInvoices.length === 0" class="text-center py-12 text-gray-600 dark:text-gray-400 text-sm">
          No invoices match this filter.
        </div>

        <div v-else class="overflow-x-auto -mx-1">
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b-2 border-gray-900 dark:border-gray-100">
                <Th align="center" custom-class="py-3 font-semibold">Invoice #</Th>
                <Th align="center" custom-class="py-3 font-semibold">Invoice date</Th>
                <Th align="center" custom-class="py-3 font-semibold">Due date</Th>
                <Th align="center" custom-class="py-3 font-semibold">Amount</Th>
                <Th align="center" custom-class="py-3 font-semibold">Balance due</Th>
                <Th align="center" custom-class="py-3 font-semibold">Status</Th>
                <Th align="center" custom-class="py-3 font-semibold">Actions</Th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="inv in customerInvoices"
                :key="inv.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
              >
                <Td align="center" custom-class="py-3 border-r border-gray-200 dark:border-gray-700">
                  <span class="font-medium text-gray-900 dark:text-white">{{ inv.invoice_number }}</span>
                </Td>
                <Td align="center" custom-class="py-3 border-r border-gray-200 dark:border-gray-700">{{ formatDate(inv.invoice_date) }}</Td>
                <Td align="center" custom-class="py-3 border-r border-gray-200 dark:border-gray-700">{{ formatDate(inv.due_date) }}</Td>
                <Td align="right" custom-class="py-3 border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(inv.amount) }}</Td>
                <Td align="right" custom-class="py-3 border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(inv.balance_due) }}</Td>
                <Td align="center" custom-class="py-3 border-r border-gray-200 dark:border-gray-700">
                  <span :class="statusClass(inv.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize">
                    {{ inv.status }}
                  </span>
                </Td>
                <Td align="center" custom-class="py-3">
                  <Button
                    v-if="can('upload-portal-invoice', 'view')"
                    type="button"
                    variant="outline-secondary"
                    size="sm"
                    @click="openInvoiceView(inv.id)"
                  >
                    View
                  </Button>
                  <span v-else class="text-xs text-gray-400">—</span>
                </Td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!invoicesDenied && customerInvoices.length > 0 && invoicePagination.last_page > 1" class="mt-6">
          <Pagination
            :collection="invoicePagination"
            :loading="loadingInvoices"
            @page-change="loadCustomerInvoices"
          />
        </div>
      </div>

      <CustomerAccountStatementTab
        v-show="activeTab === 'statement'"
        ref="statementTabRef"
        :customer-id="customerId"
        :customer-name="customer?.name || ''"
      />

      <SalesInvoiceViewModal
        v-model="showInvoiceModal"
        :invoice-id="viewInvoiceId"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '../../plugins/axios'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Pagination from '@/components/ui/pagination.vue'
import SalesInvoiceViewModal from '../../components/ar-invoice/SalesInvoiceViewModal.vue'
import CustomerAccountStatementTab from '../../components/ar-invoice/CustomerAccountStatementTab.vue'
import { useUser } from '../../composables/useUser'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

const disp = (v) => {
  if (v === null || v === undefined || v === '') return '—'
  return String(v)
}

const row = (label, raw) => ({ label, value: disp(raw) })

const basicRows = (c) => [
  row('Name', c.name),
  row('Email', c.email),
  row('Mobile', c.mobile),
  row('Fax', c.fax),
  row('Primary contact', c.primary_person_name),
]

const addressRows = (c) => [
  row('Address line 1', c.address_line_1),
  row('Address line 2', c.address_line_2),
  row('Address line 3', c.address_line_3),
  row('City', c.city),
  row('State', c.state),
  row('Country', c.country),
  row('Zip code', c.zip_code),
]

const financialRows = (c) => [
  row('Credit card number', c.credit_card_number),
  row('Card expiry', c.card_expiry),
  row('CVV', c.cvv),
  row('Name on card', c.name_on_card),
  row('zip code', c.financial_zip_code),
]

const creditRows = (c) => [
  row('Invoice due (days)', c.invoice_due != null ? String(c.invoice_due) : null),
  row('Condition', c.invoice_condition),
]

const tabs = [
  { value: 'general', label: 'General' },
  { value: 'invoices', label: 'Invoices' },
  { value: 'statement', label: 'Statement' },
]

const invoiceFilterOptions = [
  { value: 'all', label: 'All' },
  { value: 'outstanding', label: 'Outstanding' },
  { value: 'paid', label: 'Paid' },
]

const route = useRoute()
const router = useRouter()
const { can } = useUser()
const message = useMessage()

const customerId = computed(() => route.params.customerId)

const activeTab = ref('general')
const loadingCustomer = ref(true)
const customer = ref(null)
const customerLoadError = ref('')

const loadingInvoices = ref(false)
const invoicesDenied = ref(false)
const customerInvoices = ref([])
const invoiceFilter = ref('all')
const invoicePagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  has_prev: false,
  has_next: false,
})
const invoicePerPage = 25

const showInvoiceModal = ref(false)
const viewInvoiceId = ref(null)
const statementTabRef = ref(null)

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

const goBack = () => {
  router.push({ name: 'upload-portal-ar-customers' })
}

const loadCustomer = async () => {
  if (!customerId.value) return
  loadingCustomer.value = true
  customerLoadError.value = ''
  customer.value = null
  try {
    const response = await axios.get(`/upload-portal/api/ar-customers/${customerId.value}`)
    if (response.data.success) {
      customer.value = response.data.customer
    } else {
      customerLoadError.value = response.data.message || 'Customer not found'
    }
  } catch (error) {
    customerLoadError.value = error.response?.data?.message || 'Failed to load customer'
    if (error.response?.status !== 403) {
      message.error(customerLoadError.value)
    }
  } finally {
    loadingCustomer.value = false
  }
}

const loadCustomerInvoices = async (page = 1) => {
  if (!customerId.value || !customer.value) return
  loadingInvoices.value = true
  invoicesDenied.value = false
  try {
    const params = {
      page,
      per_page: invoicePerPage,
      customer_id: customerId.value,
      customer_ar: true,
    }
    if (invoiceFilter.value === 'outstanding') {
      params.outstanding = true
    } else if (invoiceFilter.value === 'paid') {
      params.status = 'paid'
    }

    const response = await axios.get('/upload-portal/api/sales-invoices', { params })
    if (response.data.success) {
      customerInvoices.value = response.data.invoices.data || []
      invoicePagination.value = buildPagination(response.data.invoices)
    }
  } catch (error) {
    customerInvoices.value = []
    if (error.response?.status === 403) {
      invoicesDenied.value = true
    } else {
      message.error(error.response?.data?.message || 'Failed to load invoices')
    }
  } finally {
    loadingInvoices.value = false
  }
}

const setInvoiceFilter = (value) => {
  if (invoiceFilter.value === value) return
  invoiceFilter.value = value
  loadCustomerInvoices(1)
}

const openInvoiceView = (id) => {
  viewInvoiceId.value = id
  showInvoiceModal.value = true
}

const resetStatementUi = () => {
  statementTabRef.value?.reset()
}

const reloadAll = async () => {
  activeTab.value = 'general'
  await loadCustomer()
  customerInvoices.value = []
  invoicePagination.value = {
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    has_prev: false,
    has_next: false,
  }
  resetStatementUi()
}

watch(customerId, () => {
  reloadAll()
})

watch(activeTab, (tab) => {
  if (tab === 'statement') {
    statementTabRef.value?.loadStatement()
  }
  if (tab === 'invoices' && customer.value) {
    loadCustomerInvoices(1)
  }
})

onMounted(() => {
  reloadAll()
})
</script>
