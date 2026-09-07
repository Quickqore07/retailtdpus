<template>
    <div class="ttm-monthly-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    TTM Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Store-wise finance for a selected period. Period filters are year and month or quarter only.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onFilterChange"
                        class="form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Quarter <span class="text-xs text-gray-500">(or select month)</span>
                    </label>
                    <select
                        v-model="filters.selectedQuarter"
                        @change="onQuarterChange"
                        class="form-select"
                    >
                        <option :value="null">Select Quarter</option>
                        <option v-for="quarter in quarterOptions" :key="quarter.value" :value="quarter.value">
                            {{ quarter.label }}
                        </option>
                    </select>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Month
                    </label>
                    <select
                        v-model="filters.selectedMonth"
                        @change="onMonthChange"
                        class="form-select"
                    >
                        <option :value="null">Select Month</option>
                        <option v-for="(monthName, monthNum) in monthOptions" :key="monthNum" :value="Number(monthNum)">
                            {{ monthName }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        icon-left="refresh"
                        icon-size="sm"
                        variant="primary"
                        size="sm"
                        @click="applyFilters"
                        :loading="loading"
                    >
                        Apply Filters
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
            </div>  
        </Panel>

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 !z-[101] bg-gray-50 dark:bg-gray-900 !w-[250px]">
                                <div class="!w-[250px]">Category</div>
                            </Th>
                            <Th v-for="storeNumber in storeNumbers" :key="storeNumber" class="!w-[100px]  !text-right">
                                <span class="whitespace-normal break-words">{{ storeNumber }}</span>
                            </Th>
                            <Th class="sticky right-0 !z-[101] bg-gray-50 dark:bg-gray-900 text-right">
                                <span>Total</span>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="reportData.length > 0">
                        <tr
                            v-for="(item, index) in reportData"
                            :key="index"
                            :class="[
                                'border-t border-gray-200 dark:border-gray-700',
                                getRowClass(item.label)
                            ]"
                        >
                            <Td
                                class="!w-[250px] sticky left-0 !z-[100]"
                                :weight="isHeaderRow(item.label) ? 'bold' : 'normal'"
                                :class="getRowClass(item.label)"
                                :title="item.label"
                            >
                                <span class="truncate max-w-[250px] inline-block">{{ item.label }}</span>
                            </Td>
                            <Td
                                v-for="storeNumber in storeNumbers"
                                :key="storeNumber"
                                class="text-right !w-[200px]"
                                :class="getRowClass(item.label)"
                                :weight="isHeaderRow(item.label) ? 'bold' : 'normal'"
                            >
                                {{ formatValue(item.data[storeNumber]) }}
                            </Td>
                            <Td
                                class="sticky right-0 !z-[100] text-right"
                                :weight="isHeaderRow(item.label) ? 'bold' : 'normal'"
                                :class="getRowClass(item.label)"
                            >
                                {{ formatValue(item.total) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="storeNumbers.length + 2">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Finance Data Found"
                                    message="Please select a P&L Configuration and apply filters to view the report."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing data for <span class="font-medium">{{ storeNumbers.length }}</span> stores
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ getFilterSummary() }}
                </div>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'

const message = useMessage()

const monthOptions = {
    1: 'January',
    2: 'February',
    3: 'March',
    4: 'April',
    5: 'May',
    6: 'June',
    7: 'July',
    8: 'August',
    9: 'September',
    10: 'October',
    11: 'November',
    12: 'December'
}
const quarterOptions = [
    { value: '01-03', label: 'Q1 (Jan - Mar)' },
    { value: '04-06', label: 'Q2 (Apr - Jun)' },
    { value: '07-09', label: 'Q3 (Jul - Sep)' },
    { value: '10-12', label: 'Q4 (Oct - Dec)' },
    { value: '01-12', label: 'Full Year' }
]


const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedMonth: new Date().getMonth() + 1,
    selectedQuarter: null,
})

const reportData = ref([])
const storeNumbers = ref([])

const loading = ref(false)
const exportLoading = ref(false)


const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const headerRows = [
    'Total Revenue',
    'Food Percentage',
    'Total Labor Cost',
    'Total Franchise Fee',
    'Total COGS',
    'Total Expense',
    'Net Income',
]

const isHeaderRow = (label) => {
    return headerRows.includes(label)
}

const isPercentageRow = (label) => {
    return label.includes('Percentage')
}

const getRowClass = (label) => {
    if (label === 'Total Revenue') {
        return 'bg-orange-50 dark:bg-orange-900/20 font-bold !border-b-2 !border-orange-300 !dark:border-orange-700'
    }
    if (label === 'Total Food Purchase' || label === 'Total Labor Cost' || label === 'Total Franchise Fee' || label === 'Total Retail') {
        return 'bg-blue-50 dark:bg-blue-900/20 font-bold !border-b-2 !border-blue-300 !dark:border-blue-700'
    }
    if (label === 'Total COGS') {
        return 'bg-orange-50 dark:bg-orange-900/20 font-bold !border-b-2 !border-orange-300 !dark:border-orange-700'
    }
    if (label === 'Total Expense') {
        return 'bg-orange-50 dark:bg-orange-900/20 font-bold !border-b-2 !border-orange-300 !dark:border-orange-700'
    }
    if (label === 'Net Income') {
        return 'bg-green-50 dark:bg-green-900/20 font-bold !border-b-2 !border-green-300 !dark:border-green-700'
    }
    return 'bg-white dark:bg-gray-900 border-t border-b border-gray-200 dark:border-gray-700'
}

const formatValue = (value) => {
    if (value === null || value === undefined) {
        return '0.00'
    }

    return formatNumber(value, 2)
}


const onQuarterChange = () => {
    if (filters.value.selectedQuarter) {
        filters.value.selectedMonth = null
    }
    applyFilters()
}

const onMonthChange = () => {
    if (filters.value.selectedMonth) {
        filters.value.selectedQuarter = null
    }
    applyFilters()
}

const getFilterSummary = () => {
    const parts = [`${monthOptions[filters.value.selectedMonth]} ${filters.value.selectedYear}`]
    if (filters.value.selectedQuarter) {
        parts.push(`${filters.value.selectedQuarter} ${filters.value.selectedYear}`)
    }
    return parts.join(' | ')
}

const onFilterChange = () => {
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true

        const requestData = {
            year: filters.value.selectedYear,
            month: filters.value.selectedMonth,
            quarter: filters.value.selectedQuarter,
        }

        const response = await useRequest('post', '/reports/ttm/get-ttm-report', requestData)

        if (response.success) {
            storeNumbers.value = response.store_numbers || []
            reportData.value = response.report_data || []
        }
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        if (!reportData.value || reportData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        await import('xlsx').then((XLSX) => {
            const exportData = []

            exportData.push(['TTM Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Month:', monthOptions[filters.value.selectedMonth]])
            exportData.push(['Generated:', new Date().toLocaleString()])
            if (filters.value.selectedQuarter) {
                exportData.push(['Quarter:', filters.value.selectedQuarter])
            }
            exportData.push([])

            const headerRow = ['Category', ...storeNumbers.value, 'Total']
            exportData.push(headerRow)

            const toNumber = (v) => {
                const n = parseFloat(v)
                return Number.isFinite(n) ? n : 0
            }

            reportData.value.forEach((item) => {
                const row = [item.label]
                const rowData = item.data || {}

                storeNumbers.value.forEach((storeNumber) => {
                    row.push(toNumber(rowData[storeNumber]))
                })

                row.push(toNumber(item.total))
                exportData.push(row)
            })

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [{ wch: 30 }]
            storeNumbers.value.forEach(() => {
                colWidths.push({ wch: 15 })
            })
            colWidths.push({ wch: 15 })
            ws['!cols'] = colWidths

            const range = XLSX.utils.decode_range(ws['!ref'])
            const headerRowIndex = exportData.findIndex(row => row[0] === 'Category')

            for (let R = headerRowIndex + 1; R <= range.e.r; R++) {
                const rowData = exportData[R]
                const isPercentage = rowData && rowData[0] && isPercentageRow(rowData[0])
                const label = rowData && rowData[0]

                for (let C = 0; C <= range.e.c; C++) {
                    const cellAddress = XLSX.utils.encode_cell({ r: R, c: C })
                    if (!ws[cellAddress]) continue

                    if (C > 0 && typeof ws[cellAddress].v === 'number') {
                        ws[cellAddress].z = isPercentage ? '0.00"%"' : '#,##0.00'
                    }

                    let cellStyle = {}

                    if (label === 'Total Revenue') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'E6F7FF' } }
                        }
                    } else if (
                        label === 'Total Food Purchase' ||
                        label === 'Food Percentage' ||
                        label === 'Total Labor Cost' ||
                        label === 'Labor Cost Percentage' ||
                        label === 'Total Franchise Fee' ||
                        label === 'Franchise Fee Percentage'
                    ) {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'FFF2E6' } }
                        }
                    } else if (label === 'Total COGS' || label === 'COGS Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'E6F7FF' } }
                        }
                    } else if (label === 'Total Expense' || label === 'Expense Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'E6F7FF' } }
                        }
                    } else if (label === 'Net Income' || label === 'Net Income Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'C2EAB4' } }
                        }
                    }

                    if (Object.keys(cellStyle).length > 0) {
                        ws[cellAddress].s = cellStyle
                    }
                }
            }

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'TTM Monthly')

            let filenameParts = ['TTM_Monthly_Report']
            if (filters.value.reportType) {
                filenameParts.push(filters.value.reportType.name)
            }
            filenameParts.push(monthOptions[filters.value.selectedMonth])
            filenameParts.push(filters.value.selectedYear)
            filenameParts.push(new Date().getTime())
            const filename = `${filenameParts.join('_')}.xlsx`

            XLSX.writeFile(wb, filename)

            message.success('Report exported successfully!')
        }).catch((error) => {
            console.error('Failed to load XLSX library:', error)
            message.error('Failed to export report. Please try again.')
        })
    } catch (error) {
        console.error('Export error:', error)
        message.error('An error occurred while exporting the report')
    } finally {
        exportLoading.value = false
    }
}

onMounted(async () => {
    applyFilters()
})
</script>

<style scoped>
.sticky {
    box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.1);
}

.dark .sticky {
    box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.3);
}

thead th.sticky,
tbody td.sticky,
tfoot td.sticky {
    position: sticky;
}

table td,
table th {
    min-width: 120px;
    white-space: nowrap;
}

table td:first-child,
table th:first-child {
    min-width: 250px;
}
</style>
