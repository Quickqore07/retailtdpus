<template>
  <div>
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Customer Balance Report</h4>
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        View current outstanding balances for all customers.
      </p>
    </div>

    <div
      v-if="!canViewReport"
      class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        You do not have permission to view the customer balance report.
      </p>
    </div>

    <template v-else>
      <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="flex flex-col gap-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <Input
              v-model="fromDate"
              label="From Date"
              type="date"
              placeholder="Select from date"
            />
            <Input
              v-model="toDate"
              label="To Date"
              type="date"
              placeholder="Select to date"
            />
            <Input
              v-model="search"
              label="Search customer"
              placeholder="Name, email, mobile..."
              @input="debounceLoad"
            />
            <div class="flex items-end">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input
                  type="checkbox"
                  v-model="includeZero"
                  class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                  @change="loadReport"
                />
                Show customers with zero balance
              </label>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button
              type="button"
              variant="primary"
              size="sm"
              icon-left="refresh"
              icon-size="sm"
              :disabled="loading"
              @click="loadReport"
            >
              {{ loading ? 'Loading…' : 'Run report' }}
            </Button>
            <Button
              type="button"
              variant="outline-secondary"
              size="sm"
              icon-left="download"
              icon-size="sm"
              :disabled="loading || rows.length === 0"
              @click="exportToExcel"
            >
              Export Excel
            </Button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <Spinner size="lg" text="Loading customer balance report..." />
      </div>

      <div
        v-else-if="rows.length > 0"
        class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
      >
        <div class="px-4 pt-4 pb-2 text-xs text-gray-500 dark:text-gray-400">
          {{ rows.length }} customer{{ rows.length === 1 ? '' : 's' }}
        </div>
        <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/30">
              <tr>
                <Th align="left" custom-class="py-3 px-3 w-8"></Th>
                <Th align="left" custom-class="py-3 px-3">Customer</Th>
                <Th align="right" custom-class="py-3 px-3">Invoice Count</Th>
                <Th align="right" custom-class="py-3 px-3">Balance Due</Th>
                <Th align="center" custom-class="py-3 px-3">Actions</Th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <template v-for="row in rows" :key="row.customer_id">
                <tr
                  class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150 cursor-pointer"
                  @click="toggleRow(row.customer_id)"
                >
                  <Td custom-class="!py-1 px-3">
                    <button
                      type="button"
                      class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                      @click.stop="toggleRow(row.customer_id)"
                    >
                      <svg
                        class="w-3 h-3 transition-transform duration-200"
                        :class="{ 'rotate-90': expandedRows.has(row.customer_id) }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </Td>
                  <Td custom-class="!py-1 px-3">
                    <div class="font-medium text-gray-900 dark:text-white">{{ row.customer_name }}</div>
                    <!-- <div v-if="row.email || row.mobile" class="text-xs text-gray-500 dark:text-gray-400">
                      {{ row.email || '' }}{{ row.email && row.mobile ? ' · ' : '' }}{{ row.mobile || '' }}
                    </div> -->
                  </Td>
                  <Td align="right" custom-class="!py-1 px-3 tabular-nums">
                    {{ row.invoices?.length || 0 }}
                  </Td>
                  <Td 
                    align="right" 
                    custom-class="!py-1 px-3 tabular-nums font-semibold"
                    :class="row.due_balance > 0 ? 'text-orange-600 dark:text-orange-400' : ''"
                  >
                    {{ formatCurrency(row.due_balance) }}
                  </Td>
                  <Td align="center" custom-class="!py-1 px-3">
                    <div
                      v-if="canViewCustomerProfile"
                      type="button"
                      class="text-primary hover:text-primary-hover cursor-pointer"
                      @click="goToCustomerView(row.customer_id)"
                    > 
                      View
                    </div>
                    <span v-else class="text-xs text-gray-400 cursor-not-allowed">—</span>
                  </Td>
                </tr>
                
                <tr
                  v-if="expandedRows.has(row.customer_id)"
                  class="bg-gray-50/50 dark:bg-gray-900/20"
                >
                  <td colspan="5" class="px-3 py-3">
                    <div v-if="row.invoices && row.invoices.length > 0" class="ml-8">
                      <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Due Invoices ({{ row.invoices.length }})
                      </div>
                      <div class="overflow-x-auto">
                        <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded">
                          <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                              <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Invoice #</th>
                              <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Invoice Date</th>
                              <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Due Date</th>
                              <th class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Amount</th>
                              <th class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Paid</th>
                              <th class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Balance</th>
                              <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300">Due Days</th>
                            </tr>
                          </thead>
                          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <tr v-for="invoice in row.invoices" :key="invoice.invoice_number">
                              <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ invoice.invoice_number }}</td>
                              <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ formatDate(invoice.invoice_date) }}</td>
                              <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ formatDate(invoice.due_date) }}</td>
                              <td class="px-3 py-2 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ formatCurrency(invoice.amount) }}</td>
                              <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ formatCurrency(invoice.applied_amount) }}</td>
                              <td class="px-3 py-2 text-right tabular-nums font-semibold text-orange-600 dark:text-orange-400">{{ formatCurrency(invoice.balance) }}</td>
                              <td class="px-3 py-2 text-center">
                                <span
                                  class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded"
                                  :class="getDueDaysClass(calculateDueDays(invoice.due_date))"
                                >
                                  {{ formatDueDays(calculateDueDays(invoice.due_date)) }}
                                </span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <div v-else class="ml-8 text-xs text-gray-500 dark:text-gray-400">
                      No due invoices found.
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
            <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40">
              <tr class="font-semibold text-gray-900 dark:text-white">
                <Td custom-class="py-3 px-3"></Td>
                <Td custom-class="py-3 px-3">Totals</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ totalInvoiceCount }}</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ formatCurrency(totalBalance) }}</Td>
                <Td custom-class="py-3 px-3"></Td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div
        v-else
        class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
      >
        <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
          No customer balances found for the selected criteria.
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import * as XLSX from 'xlsx'
import axios from '../../../../plugins/axios'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Spinner from '@/components/ui/spinner.vue'
import { useUser } from '../../../../composables/useUser'
import { useMessage } from '@/composables/useMessage'
import { formatCurrency } from '@/utils/number'

const router = useRouter()
const { can } = useUser()
const message = useMessage()

const canViewReport = computed(() => can('upload-portal-ar-customer-balance-report', 'index'))
const canViewCustomerProfile = computed(
  () => can('upload-portal-customer', 'view') || can('upload-portal-customer', 'index')
)

const fromDate = ref('')
const toDate = ref('')
const search = ref('')
const includeZero = ref(false)

const loading = ref(false)
const rows = ref([])
const expandedRows = ref(new Set())

const totalInvoiceCount = computed(() => {
  return rows.value.reduce((sum, row) => sum + (row.invoices?.length || 0), 0)
})

const totalBalance = computed(() => {
  return rows.value.reduce((sum, row) => sum + (Number(row.due_balance) || 0), 0)
})

const toggleRow = (customerId) => {
  if (expandedRows.value.has(customerId)) {
    expandedRows.value.delete(customerId)
  } else {
    expandedRows.value.add(customerId)
  }
}

const calculateDueDays = (dueDate) => {
  if (!dueDate) return 0
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const due = new Date(dueDate)
  due.setHours(0, 0, 0, 0)
  const diffTime = today - due
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
}

const formatDueDays = (days) => {
  if (days < 0) {
    return `${Math.abs(days)} days left`
  } else if (days === 0) {
    return 'Due today'
  } else {
    return `${days} days overdue`
  }
}

const getDueDaysClass = (days) => {
  if (days < 0) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  } else if (days === 0) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  } else if (days <= 30) {
    return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
  } else {
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const goToCustomerView = (customerId) => {
  window.open(`/upload-portal/ar-invoice/customers/${customerId}`, '_blank')
}

const loadReport = async () => {
  if (!canViewReport.value) return
  if (loading.value) return
  loading.value = true
  expandedRows.value.clear()
  try {
    const params = {
      search: search.value?.trim() || undefined,
      include_zero: includeZero.value ? 1 : 0,
    }
    if (fromDate.value) {
      params.from_date = fromDate.value
    }
    if (toDate.value) {
      params.to_date = toDate.value
    }
    const response = await axios.get('/upload-portal/api/ar-customer-balance-report', {
      params,
    })
    if (response.data.success) {
      rows.value = response.data.data || []
    } else {
      rows.value = []
    }
  } catch (error) {
    rows.value = []
    message.error(error.response?.data?.message || 'Failed to load customer balance report')
  } finally {
    loading.value = false
  }
}

let searchTimer = null
const debounceLoad = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadReport(), 400)
}

const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100

const exportToExcel = () => {
  if (rows.value.length === 0) return

  const data = [
    ['Customer Balance Report'],
  ]
  if(fromDate.value) {
    data.push(['From Date:', formatDate(fromDate.value)])
  }
  if(toDate.value) {
    data.push(['To Date:', formatDate(toDate.value)])
  }
  
  
  data.push([
    'Customer',
    'Mobile',
    'Invoice #',
    'Invoice Date',
    'Due Date',
    'Amount',
    'Paid',
    'Balance',
    'Due Days',
  ])

  rows.value.forEach((r) => {
    if (r.invoices && r.invoices.length > 0) {
      let customerTotalAmount = 0
      let customerTotalPaid = 0
      let customerTotalBalance = 0

      r.invoices.forEach((invoice, idx) => {
        const dueDays = calculateDueDays(invoice.due_date)
        const amount = round2(invoice.amount)
        const paid = round2(invoice.applied_amount)
        const balance = round2(invoice.balance)

        customerTotalAmount += amount
        customerTotalPaid += paid
        customerTotalBalance += balance

        data.push([
          idx === 0 ? (r.customer_name || '') : '',
          idx === 0 ? (r.mobile || '') : '',
          invoice.invoice_number || '',
          formatDate(invoice.invoice_date) || '',
          formatDate(invoice.due_date) || '',
          amount,
          paid,
          balance,
          dueDays,
        ])
      })

      data.push([
        `${r.customer_name} - Subtotal`,
        '',
        '',
        '',
        '',
        round2(customerTotalAmount),
        round2(customerTotalPaid),
        round2(customerTotalBalance),
        '',
      ])

      data.push(['', '', '', '', '', '', '', '', ''])
    } else {
      data.push([
        r.customer_name || '',
        r.mobile || '',
        '',
        '',
        '',
        0,
        0,
        round2(r.due_balance),
        '',
      ])
      data.push(['', '', '', '', '', '', '', '', ''])
    }
  })

  data.push([
    'Grand Total',
    '',
    '',
    '',
    '',
    '',
    '',
    round2(totalBalance.value),
    '',
  ])

  const ws = XLSX.utils.aoa_to_sheet(data)

  const moneyCols = ['G', 'H', 'I']
  const lastRow = data.length
  for (let r = 2; r <= lastRow; r++) {
    moneyCols.forEach((col) => {
      const ref = `${col}${r}`
      if (ws[ref]) {
        ws[ref].t = 'n'
        ws[ref].z = '#,##0.00'
      }
    })
  }

  ws['!cols'] = [
    { wch: 36 },
    { wch: 28 },
    { wch: 16 },
    { wch: 18 },
    { wch: 14 },
    { wch: 14 },
    { wch: 14 },
    { wch: 14 },
    { wch: 14 },
    { wch: 12 },
  ]

  ws['!freeze'] = { xSplit: '0', ySplit: '1' }

  const wb = XLSX.utils.book_new()
  const sheetName = 'Customer Balance'
  XLSX.utils.book_append_sheet(wb, ws, sheetName)

  const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '')
  const filename = `customer_balance_report_${timestamp}.xlsx`
  XLSX.writeFile(wb, filename)
}

watch(canViewReport, () => {
  loadReport()
})
onMounted(() => {
  loadReport()
})
</script>
