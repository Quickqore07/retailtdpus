<template>
  <div>
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">AR Aging Report</h4>
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        Outstanding balances grouped by aging buckets as of a selected date.
      </p>
    </div>

    <div
      v-if="!canViewReport"
      class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        You do not have permission to view the AR aging report.
      </p>
    </div>

    <template v-else>
      <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 flex-1">
            <Input
              v-model="asOfDate"
              type="date"
              label="As of date"
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
        <Spinner size="lg" text="Loading AR aging report..." />
      </div>

      <div
        v-else-if="rows.length > 0"
        class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
      >
        <div class="px-4 pt-4 pb-2 text-xs text-gray-500 dark:text-gray-400">
          As of {{ formatDate(asOfDate) }} &middot; {{ rows.length }} customer{{ rows.length === 1 ? '' : 's' }}
        </div>
        <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/30">
              <tr>
                <Th align="left" custom-class="py-3 px-3">Customer</Th>
                <Th align="right" custom-class="py-3 px-3">Current – 30</Th>
                <Th align="right" custom-class="py-3 px-3">31 – 60</Th>
                <Th align="right" custom-class="py-3 px-3">61 – 90</Th>
                <Th align="right" custom-class="py-3 px-3">&gt; 90 days</Th>
                <Th align="right" custom-class="py-3 px-3">Total due</Th>
                <Th align="center" custom-class="py-3 px-3">Actions</Th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="row in rows"
                :key="row.customer_id"
                class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
              >
                <Td custom-class="!py-1 px-3">
                  <div class="font-medium text-gray-900 dark:text-white">{{ row.customer_name }}</div>
                  <!-- <div v-if="row.email || row.mobile" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ row.email || '' }}{{ row.email && row.mobile ? ' · ' : '' }}{{ row.mobile || '' }}
                  </div> -->
                </Td>
                <Td align="right" custom-class="!py-1 px-3 tabular-nums">{{ formatBucket(row.current) }}</Td>
                <Td align="right" custom-class="!py-1 px-3 tabular-nums">{{ formatBucket(row.days_31_60) }}</Td>
                <Td align="right" custom-class="!py-1 px-3 tabular-nums">{{ formatBucket(row.days_61_90) }}</Td>
                <Td align="right" custom-class="!py-1 px-3 tabular-nums" :class="row.days_over_90 > 0 ? 'text-red-600 dark:text-red-400 font-medium' : ''">
                  {{ formatBucket(row.days_over_90) }}
                </Td>
                <Td align="right" custom-class="!py-1 px-3 tabular-nums font-semibold">{{ formatNumber(row.total) }}</Td>
                <Td align="center" custom-class="!py-1 px-3">
                  <div
                    v-if="canViewCustomerProfile"
                    class="cursor-pointer text-primary hover:text-primary-hover"
                    @click="goToCustomerView(row.customer_id)"
                  >
                    View
                  </div>
                  <span v-else class="text-xs text-gray-400">—</span>
                </Td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40">
              <tr class="font-semibold text-gray-900 dark:text-white">
                <Td custom-class="py-3 px-3">Totals</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ formatBucket(totals.current) }}</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ formatBucket(totals.days_31_60) }}</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ formatBucket(totals.days_61_90) }}</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ formatBucket(totals.days_over_90) }}</Td>
                <Td align="right" custom-class="py-3 px-3 tabular-nums">{{ formatNumber(totals.total) }}</Td>
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
          No outstanding balances for the selected criteria.
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
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
import { formatDate } from '@/utils/date'
import { formatNumber } from '@/utils/number'

const router = useRouter()
const { can } = useUser()
const message = useMessage()

const canViewReport = computed(() => can('upload-portal-ar-aging-report', 'index'))
const canViewCustomerProfile = computed(
  () => can('upload-portal-customer', 'view') || can('upload-portal-customer', 'index')
)

const todayIso = () => new Date().toISOString().slice(0, 10)

const asOfDate = ref(todayIso())
const search = ref('')
const includeZero = ref(false)

const loading = ref(false)
const rows = ref([])
const totals = reactive({
  current: 0,
  days_31_60: 0,
  days_61_90: 0,
  days_over_90: 0,
  total: 0,
})

const formatBucket = (value) => {
  const num = Number(value || 0)
  if (Math.abs(num) < 0.005) return '-'
  return formatNumber(num)
}

const goToCustomerView = (customerId) => {
  window.open(`/upload-portal/ar-invoice/customers/${customerId}`, '_blank')
}

const resetTotals = () => {
  totals.current = 0
  totals.days_31_60 = 0
  totals.days_61_90 = 0
  totals.days_over_90 = 0
  totals.total = 0
}

const loadReport = async () => {
  if (!canViewReport.value) return
  if (loading.value) return
  loading.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-aging-report', {
      params: {
        as_of_date: asOfDate.value || undefined,
        search: search.value?.trim() || undefined,
        include_zero: includeZero.value ? 1 : 0,
      },
    })
    if (response.data.success) {
      rows.value = response.data.rows || []
      const t = response.data.totals || {}
      totals.current = Number(t.current || 0)
      totals.days_31_60 = Number(t.days_31_60 || 0)
      totals.days_61_90 = Number(t.days_61_90 || 0)
      totals.days_over_90 = Number(t.days_over_90 || 0)
      totals.total = Number(t.total || 0)
    } else {
      rows.value = []
      resetTotals()
    }
  } catch (error) {
    rows.value = []
    resetTotals()
    message.error(error.response?.data?.message || 'Failed to load AR aging report')
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

  const header = [
    'Customer',
    'Email',
    'Mobile',
    'Current - 30',
    '31 - 60',
    '61 - 90',
    '> 90 days',
    'Total Due',
  ]
  const data = [header]
  rows.value.forEach((r) => {
    data.push([
      r.customer_name || '',
      r.email || '',
      r.mobile || '',
      round2(r.current),
      round2(r.days_31_60),
      round2(r.days_61_90),
      round2(r.days_over_90),
      round2(r.total),
    ])
  })
  data.push([
    'Totals',
    '',
    '',
    round2(totals.current),
    round2(totals.days_31_60),
    round2(totals.days_61_90),
    round2(totals.days_over_90),
    round2(totals.total),
  ])

  const ws = XLSX.utils.aoa_to_sheet(data)

  const moneyCols = ['D', 'E', 'F', 'G', 'H']
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
    { wch: 16 },
    { wch: 16 },
    { wch: 16 },
    { wch: 16 },
    { wch: 18 },
  ]

  ws['!freeze'] = { xSplit: '0', ySplit: '1' }

  const wb = XLSX.utils.book_new()
  const sheetName = 'AR Aging'
  XLSX.utils.book_append_sheet(wb, ws, sheetName)

  const safeDate = (asOfDate.value || todayIso()).replace(/-/g, '')
  const filename = `ar_aging_report_as_of_${safeDate}.xlsx`
  XLSX.writeFile(wb, filename)
}

watch(canViewReport, () => {
  loadReport()
})
onMounted(() => {
  loadReport()
})
</script>
