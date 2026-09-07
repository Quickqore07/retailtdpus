<template>
    <div class="weekly-network-ar-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Weekly Network AR Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Store-wise PJ ledger totals for the selected week (payment, deposit, fees, balance)
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Year
                    </label>
                    <select
                        v-model.number="filters.selectedYear"
                        @change="onYearChange"
                        class="filterable-select w-full form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Week
                    </label>
                    <select
                        v-model="filters.selectedPeriod"
                        class="filterable-select w-full form-select"
                        @change="applyFilters"
                    >
                        <option value="">Select weekly period</option>
                        <option
                            v-for="period in availablePeriods"
                            :key="period.value"
                            :value="period.value"
                        >
                            {{ period.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Workgroups
                </label>
                <div class="rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 max-h-44 overflow-y-auto space-y-2">
                    <label
                        v-for="workgroup in workgroups"
                        :key="workgroup.id"
                        class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                    >
                        <input
                            type="checkbox"
                            :value="workgroup.id"
                            v-model="filters.workgroup_ids"
                            @change="onWorkgroupChange"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <span>{{ workgroup.name }}</span>
                    </label>
                    <p v-if="!workgroups.length" class="text-sm text-gray-500 dark:text-gray-400">
                        No workgroups available
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Button
                    icon-left="refresh"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    @click="applyFilters"
                    :loading="loading"
                >
                    Load Report
                </Button>
                <Button
                    icon-left="edit"
                    icon-size="sm"
                    variant="secondary"
                    size="sm"
                    @click="updateAllFees"
                    :loading="updateLoading"
                    :disabled="reportData.length === 0 || !filters.selectedPeriod"
                >
                    Update
                </Button>
                <Button
                    icon-left="upload"
                    icon-size="sm"
                    variant="secondary"
                    size="sm"
                    @click="exportReport"
                    :loading="exportLoading"
                    :disabled="reportData.length === 0"
                >
                    Export to Excel
                </Button>
            </div>
        </Panel>

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th rowspan="2" class="md:sticky md:left-0 bg-gray-100 dark:bg-gray-800 !z-[102] align-bottom">
                                Sr
                            </Th>
                            <Th rowspan="2" class="md:sticky md:left-10 bg-gray-100 dark:bg-gray-800 !z-[102] align-bottom min-w-[5rem]">
                                <div
                                    class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                    @click="handleSort('store_number')"
                                >
                                    <span>Store No.</span>
                                    <SvgIcon
                                        v-if="getSortDirection('store_number')"
                                        :name="getSortDirection('store_number') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th
                                v-for="ledger in ledgers"
                                :key="ledger.key"
                                :colspan="ledger.code == '1001.52' ? 5 : 4"
                                class="!text-center border-l border-gray-200 dark:border-gray-600"
                            >
                                {{ ledger.label }}
                            </Th>
                            <Th colspan="4" class="!text-center border-l border-gray-200 dark:border-gray-600">
                                Total
                            </Th>
                        </tr>
                        <tr>
                            <template v-for="ledger in [...ledgers, { key: 'total', label: 'Total' }]" :key="ledger.key + '-sub'">
                                <template v-for="metric in amountMetrics" :key="ledger.key + '-' + metric.key">
                                    <Th
                                         v-if="metric.key !== 'ddc_doordash' || (ledger.code == '1001.52')"
                                        :class="[
                                            'text-center',
                                            metric.key === 'payment' ? 'border-l border-gray-200 dark:border-gray-600' : ''
                                        ]"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                            @click="handleSort(sortKey(ledger.key, metric.key))"
                                        >
                                            <span>{{ metric.label }}</span>
                                            <SvgIcon
                                                v-if="getSortDirection(sortKey(ledger.key, metric.key))"
                                                :name="getSortDirection(sortKey(ledger.key, metric.key)) === 'asc' ? 'chevron-up' : 'chevron-down'"
                                                size="sm"
                                                class="text-blue-600 dark:text-blue-400"
                                            />
                                        </div>
                                    </Th>
                            </template>

                            </template>
                        </tr>
                    </thead>
                    <tbody
                        v-if="reportData.length > 0"
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="(row, index) in sortedReportData"
                            :key="row.company_id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800">{{ index + 1 }}</Td>
                            <Td class="md:sticky md:left-10 z-10 bg-white dark:bg-gray-800 font-medium">
                                {{ row.company_name }}
                            </Td>
                            <template v-for="ledger in ledgers" :key="row.company_id + '-' + ledger.key">
                                <Td align="right" class="border-l border-gray-100 dark:border-gray-700">
                                    {{ formatNumber(row.ledgers[ledger.key]?.payment ?? 0) }}
                                </Td>
                                <Td align="right">
                                    {{ formatNumber(row.ledgers[ledger.key]?.deposit ?? 0) }}
                                </Td>
                                <Td align="right" class="!p-1">
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="row.ledgers[ledger.key].fees"
                                        @input="recalculateBalance(row.ledgers[ledger.key])"
                                        class="w-20 px-1.5 py-0.5 text-right text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </Td>
                                <Td v-if="ledger.code == '1001.52'" align="right" class="!p-1">
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="row.ledgers[ledger.key].ddc_doordash"
                                        @input="recalculateBalance(row.ledgers[ledger.key])"
                                        class="w-20 px-1.5 py-0.5 text-right text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                    />

                                </Td>
                                <Td align="right" weight="medium">
                                    {{ formatNumber(row.ledgers[ledger.key]?.payment - row.ledgers[ledger.key]?.fees - row.ledgers[ledger.key]?.deposit - (row.ledgers[ledger.key]?.ddc_doordash ?? 0) ?? 0) }}
                                </Td>
                            </template>

                                <Td align="right" class="border-l border-gray-100 dark:border-gray-700">
                                    {{ formatNumber(companyTotals[row.company_id]?.payment ?? 0) }}
                                </Td>
                                <Td align="right">
                                    {{ formatNumber(companyTotals[row.company_id]?.deposit ?? 0) }}
                                </Td>
                                <Td align="right" class="!p-1">
                                    {{ formatNumber(companyTotals[row.company_id]?.fees ?? 0) }}
                                </Td>
                                <Td align="right" weight="medium">
                                    {{ formatNumber(companyTotals[row.company_id]?.balance ?? 0) }}
                                </Td>
                            
                        </tr>
                    </tbody>
                    <tbody v-else class="bg-white dark:bg-gray-800">
                        <tr>
                            <td :colspan="Math.max(6 + ledgers.length * 4, 2)" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No AR Data Found"
                                    message="Select a year and week, then load the report."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        v-if="reportData.length > 0"
                        class="bg-gray-100 dark:bg-gray-800 font-semibold"
                    >
                        <tr>
                            <Td colspan="2" class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="primary">
                                TOTALS
                            </Td>
                            <template v-for="ledger in ledgers" :key="'total-' + ledger.key">
                                <Td align="right" class="border-l border-gray-200 dark:border-gray-600" weight="bold">
                                    {{ formatNumber(columnTotals[ledger.key]?.payment ?? 0) }}
                                </Td>
                                <Td align="right" weight="bold">
                                    {{ formatNumber(columnTotals[ledger.key]?.deposit ?? 0) }}
                                </Td>
                                <Td align="right" weight="bold">
                                    {{ formatNumber(columnTotals[ledger.key]?.fees ?? 0) }}
                                </Td>
                                <Td align="right" weight="bold" color="primary">
                                    {{ formatNumber(columnTotals[ledger.key]?.balance ?? 0) }}
                                </Td>
                            </template>
                            <Td align="right" weight="bold" class="border-l border-gray-200 dark:border-gray-600" color="primary">
                                {{ formatNumber(columnTotals.total.payment ?? 0) }}
                            </Td>
                            <Td align="right" weight="bold" color="primary">
                                {{ formatNumber(columnTotals.total.deposit ?? 0) }}
                            </Td>
                            <Td align="right" weight="bold" color="primary">
                                {{ formatNumber(columnTotals.total.fees ?? 0) }}
                            </Td>
                            <Td align="right" weight="bold" color="primary">
                                {{ formatNumber(columnTotals.total.balance ?? 0) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div
                v-if="periodLabel"
                class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700"
            >
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ reportData.length }}</span> stores
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Period: {{ periodLabel }}
                </div>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'

const message = useMessage()

const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedPeriod: '',
    workgroup_ids: []
})
const workgroups = ref([])
const selectedPeriodDetails = ref(null)
const reportData = ref([])
const ledgers = ref([])
const period = ref(null)
const loading = ref(false)
const exportLoading = ref(false)
const updateLoading = ref(false)
const originalFees = ref({})
const sortField = ref(null)
const sortDirection = ref('asc')

const amountMetrics = [
    { key: 'payment', label: 'Due' },
    { key: 'deposit', label: 'Deposit' },
    { key: 'fees', label: 'Fees' },
    { key: 'ddc_doordash', label: 'DDC Doordash' },
    { key: 'balance', label: 'Balance' },
]

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const periods = []

    let startDate = new Date(Date.UTC(year, 0, 1))
    const dayOfWeek = startDate.getUTCDay()
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

    let periodNumber = 1
    const currentDate = new Date()
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 6)

        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }

        const formatPeriodDate = (date) => {
            const month = String(date.getUTCMonth() + 1).padStart(2, '0')
            const day = String(date.getUTCDate()).padStart(2, '0')
            const dateYear = date.getUTCFullYear()
            return `${month}-${day}-${dateYear}`
        }

        const objectPeriod = {
            label: `${formatPeriodDate(startDate)} To ${formatPeriodDate(endDate)}`,
            value: `${startDate.toISOString()} to ${endDate.toISOString()}`,
            startDate: new Date(startDate),
            endDate: new Date(endDate)
        }
        if (currentDate >= startDate && currentDate <= endDate) {
            filters.value.selectedPeriod = objectPeriod.value
        }
        periods.push(objectPeriod)

        startDate.setUTCDate(startDate.getUTCDate() + 7)
        periodNumber++
    }
    
    return periods
})

const periodLabel = computed(() => {
    if (!selectedPeriodDetails.value) return ''
    const format = (date) => {
        const month = String(date.getUTCMonth() + 1).padStart(2, '0')
        const day = String(date.getUTCDate()).padStart(2, '0')
        return `${month}-${day}-${date.getUTCFullYear()}`
    }
    return `${format(selectedPeriodDetails.value.startDate)} to ${format(selectedPeriodDetails.value.endDate)}`
})

const formatApiDate = (date) => {
    if (!date) return ''
    return new Date(date).toISOString().slice(0, 10)
}

const sortKey = (ledgerKey, metric) => `${ledgerKey}.${metric}`

const getSortValue = (row, field) => {
    if (field === 'store_number') {
        const storeNumber = parseInt(row.store_number, 10)
        return Number.isNaN(storeNumber) ? 0 : storeNumber
    }

    const [ledgerKey, metric] = field.split('.')
    const cell = row.ledgers?.[ledgerKey] ?? {}
    return parseFloat(cell[metric] ?? 0)
}

const sortedReportData = computed(() => {
    const data = [...reportData.value]
    if (!sortField.value) {
        return data
    }

    return data.sort((a, b) => {
        const aVal = getSortValue(a, sortField.value)
        const bVal = getSortValue(b, sortField.value)
        return sortDirection.value === 'asc' ? aVal - bVal : bVal - aVal
    })
})

const handleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDirection.value = 'asc'
    }
}

const getSortDirection = (field) => {
    if (sortField.value !== field) {
        return null
    }
    return sortDirection.value
}

const companyTotals = computed(() => {
    const totals = {}
    reportData.value.forEach((row) => {
        totals[row.company_id] = { payment: 0, deposit: 0, fees: 0, balance: 0 }
        ledgers.value.forEach((ledger) => {
            totals[row.company_id].payment += parseFloat(row.ledgers?.[ledger.key]?.payment ?? 0)
            totals[row.company_id].deposit += parseFloat(row.ledgers?.[ledger.key]?.deposit ?? 0)
            totals[row.company_id].fees += parseFloat(row.ledgers?.[ledger.key]?.fees ?? 0)
            totals[row.company_id].balance += (parseFloat(row.ledgers?.[ledger.key]?.payment ?? 0) - parseFloat(row.ledgers?.[ledger.key]?.fees ?? 0) - parseFloat(row.ledgers?.[ledger.key]?.deposit ?? 0))
        })
    })
    return totals
})
const columnTotals = computed(() => {
    const totals = { total: { payment: 0, deposit: 0, fees: 0, balance: 0 } }
    ledgers.value.forEach((ledger) => {
        totals[ledger.key] = { payment: 0, deposit: 0, fees: 0, balance: 0 }
    })

    reportData.value.forEach((row) => {
        ledgers.value.forEach((ledger) => {
            const cell = row.ledgers?.[ledger.key] ?? {}
            totals[ledger.key].payment += parseFloat(cell.payment ?? 0)
            totals[ledger.key].deposit += parseFloat(cell.deposit ?? 0)
            totals[ledger.key].fees += parseFloat(cell.fees ?? 0)
            totals[ledger.key].balance += (parseFloat(cell.payment ?? 0) - parseFloat(cell.fees ?? 0) - parseFloat(cell.deposit ?? 0))
        })
    })

    Object.keys(totals).forEach((key) => {
        totals[key].payment = Math.round(totals[key].payment * 100) / 100
        totals[key].deposit = Math.round(totals[key].deposit * 100) / 100
        totals[key].fees = Math.round(totals[key].fees * 100) / 100
        totals[key].balance = Math.round(totals[key].balance * 100) / 100
        totals.total.payment += totals[key].payment
        totals.total.deposit += totals[key].deposit
        totals.total.fees += totals[key].fees
        totals.total.balance += totals[key].balance
    })

    return totals
})

const loadWorkgroups = async () => {
    const response = await useRequest('get', '/search/workgroups?query=&column=name')
    const collection = response?.collection ?? []
    workgroups.value = collection
    filters.value.workgroup_ids = collection.map((item) => item.id)
}

const onWorkgroupChange = () => {
    if (filters.value.selectedPeriod && selectedPeriodDetails.value) {
        applyFilters()
    }
}

const onYearChange = () => {
    filters.value.selectedPeriod = ''
    selectedPeriodDetails.value = null
    reportData.value = []
}

onMounted(async () => {
    await loadWorkgroups()
    applyFilters()
})

watch(() => filters.value.selectedPeriod, (value) => {
    selectedPeriodDetails.value = availablePeriods.value.find((p) => p.value === value) || null
})

const applyFilters = async () => {
    if (!filters.value.selectedPeriod || !selectedPeriodDetails.value) {
        message.error('Please select a weekly period')
        return
    }

    try {
        loading.value = true
        const response = await useRequest('post', '/ar/weekly-network-ar-report', {
            year: filters.value.selectedYear,
            start_date: formatApiDate(selectedPeriodDetails.value.startDate),
            end_date: formatApiDate(selectedPeriodDetails.value.endDate),
            workgroup_ids: filters.value.workgroup_ids
        })
        reportData.value = response?.data ?? []
        ledgers.value = response?.ledgers ?? []
        period.value = response?.period ?? null
        snapshotOriginalFees()
    } catch (error) {
        console.error('Error loading weekly network AR report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const feeSnapshotKey = (companyId, ledgerKey) => `${companyId}_${ledgerKey}`

const snapshotOriginalFees = () => {
    const snapshot = {}
    reportData.value.forEach((row) => {
        ledgers.value.forEach((ledger) => {
            const cell = row.ledgers?.[ledger.key]
            if (cell) {
                snapshot[feeSnapshotKey(row.company_id, ledger.key)] = parseFloat(cell.fees ?? 0)
                snapshot[feeSnapshotKey(row.company_id, `${ledger.key}_ddc_doordash`)] = parseFloat(cell.ddc_doordash ?? 0)
            }
        })
    })
    originalFees.value = snapshot
}

const recalculateBalance = (ledgerCell) => {
    if (!ledgerCell) return
    const payment = parseFloat(ledgerCell.payment ?? 0)
    const deposit = parseFloat(ledgerCell.deposit ?? 0)
    const fees = parseFloat(ledgerCell.fees ?? 0)
    const ddc_doordash = parseFloat(ledgerCell.ddc_doordash ?? 0)
    ledgerCell.balance = Math.round((payment + fees + ddc_doordash - deposit ) * 100) / 100
}

const getChangedFeeItems = () => {
    const items = []

    reportData.value.forEach((row) => {
        ledgers.value.forEach((ledger) => {
            const cell = row.ledgers?.[ledger.key]
            if (!cell) return

            const fees = parseFloat(cell.fees ?? 0)
            const ddc_doordash = parseFloat(cell.ddc_doordash ?? 0)
            if (Number.isNaN(fees)) {
                throw new Error(`Invalid fee amount for ${row.company_name}`)
            }

            const key = feeSnapshotKey(row.company_id, ledger.key)
            const original = originalFees.value[key] ?? 0
            const key_ddc_doordash = feeSnapshotKey(row.company_id, `${ledger.key}_ddc_doordash`)
            const original_ddc_doordash = originalFees.value[key_ddc_doordash] ?? 0
            if (Math.round(fees * 100) === Math.round(original * 100) && Math.round(ddc_doordash * 100) === Math.round(original_ddc_doordash * 100)) {
                return
            }

            const ledgerCode = cell.ledger_code ?? ledger.code
            if (!ledgerCode) return

            items.push({
                company_id: row.company_id,
                ledger_code: ledgerCode,
                fees,
                ddc_doordash,
            })
        })
    })

    return items
}

const applyUpdatedRows = (rows) => {
    if (!rows?.length) return

    const rowMap = Object.fromEntries(reportData.value.map((row) => [row.company_id, row]))
    rows.forEach((updatedRow) => {
        const row = rowMap[updatedRow.company_id]
        if (!row || !updatedRow.ledgers) return
        Object.keys(updatedRow.ledgers).forEach((ledgerKey) => {
            row.ledgers[ledgerKey] = {
                ...row.ledgers[ledgerKey],
                ...updatedRow.ledgers[ledgerKey],
            }
        })
    })
}

const updateAllFees = async () => {
    if (!selectedPeriodDetails.value) {
        message.error('Please select a weekly period')
        return
    }

    let items
    try {
        items = getChangedFeeItems()
    } catch (error) {
        message.error(error.message || 'Please enter valid fee amounts')
        return
    }

    if (!items.length) {
        message.warning('No fee changes to save')
        return
    }

    try {
        updateLoading.value = true
        const response = await useRequest('post', '/ar/weekly-network-ar-report/update-fees', {
            date: formatApiDate(selectedPeriodDetails.value.endDate),
            start_date: formatApiDate(selectedPeriodDetails.value.startDate),
            end_date: formatApiDate(selectedPeriodDetails.value.endDate),
            items,
        })

        // applyUpdatedRows(response?.data)
        // snapshotOriginalFees()
        applyFilters()
        message.success(response?.message || 'Fees updated successfully')
    } catch (error) {
        console.error('Error updating fees:', error)
        message.error(error.response?.data?.message || 'Failed to update fees')
    } finally {
        updateLoading.value = false
    }
}

const exportReport = async () => {
    if (!reportData.value.length) {
        message.error('No data to export')
        return
    }

    try {
        exportLoading.value = true
        await import('xlsx').then((XLSX) => {

            
            const headerRow1 = ['Sr', 'Store No.']
            const headerRow2 = ['', '']
            ledgers.value.forEach((ledger) => {
                headerRow1.push(ledger.label, '', '', '')
                headerRow2.push('Due', 'Deposit', 'Fees', 'Balance')
            })
            headerRow1.push('Total')
            headerRow2.push('Due', 'Deposit', 'Fees', 'Balance')
            
            const rows = [
                ['Weekly Network AR Report'],
                ['Report Period:', `${formatApiDate(selectedPeriodDetails.value?.startDate)} to ${formatApiDate(selectedPeriodDetails.value?.endDate)}`],
                ['Report Year:', filters.value.selectedYear],
                ['Report Generated:', new Date().toLocaleString()],
                [''],
            ]
            rows.push(headerRow1)
            rows.push(headerRow2)

            sortedReportData.value.forEach((row, index) => {
                const dataRow = [index + 1, row.company_name]
                ledgers.value.forEach((ledger) => {
                    const cell = row.ledgers?.[ledger.key] ?? {}
                    dataRow.push(
                        cell.payment ?? 0,
                        cell.deposit ?? 0,
                        cell.fees ?? 0,
                        (cell.payment - cell.fees - cell.deposit) ?? 0
                    )
                })
                dataRow.push(companyTotals.value[row.company_id]?.payment ?? 0, companyTotals.value[row.company_id]?.deposit ?? 0, companyTotals.value[row.company_id]?.fees ?? 0, companyTotals.value[row.company_id]?.balance ?? 0)
                rows.push(dataRow)
            })

            const totalRow = ['TOTALS', '']
            ledgers.value.forEach((ledger) => {
                const t = columnTotals.value[ledger.key] ?? {}
                totalRow.push(t.payment ?? 0, t.deposit ?? 0, t.fees ?? 0, (t.payment - t.fees - t.deposit) ?? 0)
            })
            totalRow.push(columnTotals.value.total.payment ?? 0, columnTotals.value.total.deposit ?? 0, columnTotals.value.total.fees ?? 0, columnTotals.value.total.balance ?? 0)
            rows.push(totalRow)

            const ws = XLSX.utils.aoa_to_sheet(rows)
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Weekly Network AR')
            const filename = `Weekly_Network_AR_Report_${filters.value.selectedYear}_${formatApiDate(selectedPeriodDetails.value?.endDate)}_${Date.now()}.xlsx`
            XLSX.writeFile(wb, filename)
            message.success('Report exported successfully')
        })
    } catch (error) {
        console.error('Export error:', error)
        message.error('Failed to export report')
    } finally {
        exportLoading.value = false
    }
}
</script>
