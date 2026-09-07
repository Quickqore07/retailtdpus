<template>
    <div class="weekly-network-ar-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Balance Due Network Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Store-wise balance due for the selected week
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
                        @change="onPeriodChange"
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        End Date
                    </label>
                    <input
                        type="date"
                        v-model="filters.endDate"
                        @change="onDateChange"
                        class="w-full form-input"
                    />
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
                                class="!text-center border-l border-gray-200 dark:border-gray-600"
                            >
                                {{ ledger.label }}
                            </Th>
                            <Th class=" md:sticky md:right-0 bg-gray-100 dark:bg-gray-800 !z-[102] align-bottom !text-center border-l border-gray-200 dark:border-gray-600">
                                Total
                            </Th>
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
                                    {{ formatNumber(row.ledgers[ledger.key]?.balance ?? 0) }}
                                </Td>
                            </template>
                            <Td align="right" class="md:sticky md:right-0 bg-gray-100 dark:bg-gray-800 align-bottom border-l border-gray-100 dark:border-gray-700">
                                {{ formatNumber(companyBalanceTotals[row.company_id]?.balance ?? 0) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody v-else class="bg-white dark:bg-gray-800">
                        <tr>
                            <td :colspan="ledgers.length + 3" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No AR Data Found"
                                    message="Select a year and either a weekly period or date range, then load the report."
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
                                <Td align="right" weight="bold" color="primary">
                                    {{ formatNumber(columnTotals[ledger.key]?.balance ?? 0) ?? 0 }}
                                </Td>
                            </template>
                            <Td align="right" weight="bold" color="primary" class="md:sticky md:right-0 bg-gray-100 dark:bg-gray-800">
                                {{ formatNumber(columnTotals?.total ?? 0) }}
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
    endDate: '',
    workgroup_ids: []
})
const workgroups = ref([])
const selectedPeriodDetails = ref(null)
const reportData = ref([])
const ledgers = ref([])
const period = ref(null)
const loading = ref(false)
const exportLoading = ref(false)
const sortField = ref(null)
const sortDirection = ref('asc')


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
    if (filters.value.endDate) {
        return `${filters.value.endDate}`
    }
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


const getSortValue = (row, field) => {
    if (field === 'store_number') {
        const storeNumber = parseInt(row.store_number, 10)
        return Number.isNaN(storeNumber) ? 0 : storeNumber
    }

    return parseFloat(row.balance ?? 0)
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

const columnTotals = computed(() => {
    const totals = { total: 0 }
    ledgers.value.forEach((ledger) => {
        totals[ledger.key] = { balance: 0 }
    })

    reportData.value.forEach((row) => {
        ledgers.value.forEach((ledger) => {
            totals[ledger.key].balance += parseFloat(row.ledgers?.[ledger.key]?.balance ?? 0)
            totals.total += parseFloat(row.ledgers?.[ledger.key]?.balance ?? 0)
        })
    })
    return totals
})
const companyBalanceTotals = computed(() => {
    const totals = {}
    reportData.value.forEach((row) => {
        totals[row.company_id] = { balance: 0 }
        ledgers.value.forEach((ledger) => {
            totals[row.company_id].balance += parseFloat(row.ledgers?.[ledger.key]?.balance ?? 0)
        })
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
    const hasPeriod = filters.value.selectedPeriod && selectedPeriodDetails.value
    const hasDateRange =  filters.value.endDate
    
    if (hasPeriod || hasDateRange) {
        applyFilters()
    }
}

const onYearChange = () => {
    filters.value.selectedPeriod = ''
    selectedPeriodDetails.value = null
    reportData.value = []
}

const onPeriodChange = () => {
    if (filters.value.selectedPeriod) {
        filters.value.endDate = ''
    }
}

const onDateChange = () => {
    if (filters.value.endDate) {
        filters.value.selectedPeriod = ''
        selectedPeriodDetails.value = null
        applyFilters()
    }
}

onMounted(async () => {
    await loadWorkgroups()
    applyFilters()
})

watch(() => filters.value.selectedPeriod, (value) => {
    selectedPeriodDetails.value = availablePeriods.value.find((p) => p.value === value) || null
})

const applyFilters = async () => {
    const hasPeriod = filters.value.selectedPeriod && selectedPeriodDetails.value
    const hasDateRange =  filters.value.endDate

    if (!hasPeriod && !hasDateRange) {
        message.error('Please select either a weekly period or a date range')
        return
    }

    try {
        loading.value = true
        const requestData = {
            year: filters.value.selectedYear,
            workgroup_ids: filters.value.workgroup_ids
        }

        if (hasPeriod) {
            requestData.end_date = formatApiDate(selectedPeriodDetails.value.endDate)
        } else if (hasDateRange) {
            requestData.end_date = filters.value.endDate
        }

        const response = await useRequest('post', '/ar/balance-due-network-report', requestData)
        reportData.value = response?.data ?? []
        ledgers.value = response?.ledgers ?? []
        period.value = response?.period ?? null
    } catch (error) {
        console.error('Error loading balance due network report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
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
            ledgers.value.forEach((ledger) => {
                headerRow1.push(ledger.label)
            })
            headerRow1.push('Total')
            
            let reportPeriod = ''
            if (filters.value.endDate) {
                reportPeriod = `${filters.value.endDate}`
            } else if (selectedPeriodDetails.value) {
                reportPeriod = `${formatApiDate(selectedPeriodDetails.value.startDate)} to ${formatApiDate(selectedPeriodDetails.value.endDate)}`
            }

            const rows = [
                ['Balance Due Network Report'],
                ['Report Period:', reportPeriod],
                ['Report Year:', filters.value.selectedYear],
                ['Report Generated:', new Date().toLocaleString()],
                [''],
            ]
            rows.push(headerRow1)

            sortedReportData.value.forEach((row, index) => {
                const dataRow = [index + 1, row.company_name]
                ledgers.value.forEach((ledger) => {
                    const cell = row.ledgers?.[ledger.key] ?? {}
                    dataRow.push(
                        parseFloat(cell.balance ?? 0)
                    )
                })
                dataRow.push(parseFloat(companyBalanceTotals.value[row.company_id]?.balance ?? 0))
                rows.push(dataRow)
            })

            const totalRow = ['TOTALS','']
            ledgers.value.forEach((ledger) => {
                const t = columnTotals.value[ledger.key] ?? {}
                totalRow.push(parseFloat(t.balance ?? 0))
            })
            totalRow.push(parseFloat(columnTotals.value.total ?? 0))
            rows.push(totalRow)

            const ws = XLSX.utils.aoa_to_sheet(rows)
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Balance Due Network')
            
            let dateStr = ''
            if (filters.value.endDate) {
                dateStr = filters.value.endDate
            } else if (selectedPeriodDetails.value?.endDate) {
                dateStr = formatApiDate(selectedPeriodDetails.value.endDate)
            }
            
            const filename = `Balance_Due_Network_Report_${filters.value.selectedYear}_${dateStr}_${Date.now()}.xlsx`
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
