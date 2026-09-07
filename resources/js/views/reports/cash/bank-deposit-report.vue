<template>
    <div class="bank-deposit-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Bank Deposit Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View company-wise cash bag minus deposits and shortages for each day of the selected month
                </p>
            </div>

            <!-- Year and Month Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onFilterChange"
                        class="filterable-select w-full form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Month
                    </label>
                    <select
                        v-model="filters.selectedMonth"
                        @change="onFilterChange"
                        class="filterable-select w-full form-select"
                    >
                        <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">
                            {{ monthName }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
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
            </div>
        </Panel>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th class="md:sticky md:left-0 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('sr_no')">
                                    <span>Sr</span>
                                    <SvgIcon
                                        v-if="getSortDirection('sr_no')"
                                        :name="getSortDirection('sr_no') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-12 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('store_number')">
                                    <span>Store Name</span>
                                    <SvgIcon
                                        v-if="getSortDirection('store_number')"
                                        :name="getSortDirection('store_number') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-for="date in dates" :key="date" class="text-center">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors justify-center" @click="handleSort('date_' + date)">
                                    <span>{{ formatDateLabel(date) }}</span>
                                    <SvgIcon
                                        v-if="getSortDirection('date_' + date)"
                                        :name="getSortDirection('date_' + date) === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="bg-gray-100 dark:bg-gray-800 md:sticky md:right-0 z-100">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('total')">
                                    <span>Total</span>
                                    <SvgIcon
                                        v-if="getSortDirection('total')"
                                        :name="getSortDirection('total') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedReportData.length > 0">
                        <tr v-for="(item, index) in sortedReportData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800">{{ item.sr_no }}</Td>
                            <Td class="md:sticky md:left-12 z-10 bg-white dark:bg-gray-800 font-medium">{{ item.company_name }}</Td>
                            <Td
                                v-for="date in dates"
                                :key="date"
                                align="center"
                                customClass="!min-w-[2.5rem]"
                                :class="getCellClass(item['date_' + date].amount)"
                                @dblclick="handleDoubleClick(item, date)"
                            >
                                {{ formatNumber(item['date_' + date].amount, 2) }}
                            </Td>
                            <Td class="bg-white dark:bg-gray-800 font-semibold text-blue-600 dark:text-blue-400 md:sticky md:right-0 z-100">
                                {{ formatNumber(item.total, 2) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="dates.length + 3" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="There is no data for the selected month. Please select a different month or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="reportData.length > 0">
                        <tr>
                            <Td colspan="2" class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="primary">TOTALS</Td>
                            <Td v-for="date in dates" :key="date" weight="bold" align="center">
                                {{ formatNumber(getColumnTotal(date), 2) }}
                            </Td>
                            <Td class="md:sticky md:right-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="secondary">
                                {{ formatNumber(grandTotal, 2) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ reportData.length }}</span> stores
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ monthName }} {{ filters.selectedYear }}
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
import SvgIcon from '@/components/SvgIcon.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import { useRouter } from 'vue-router'

const message = useMessage()
const router = useRouter()
const currentDate = new Date()
const filters = ref({
    selectedYear: currentDate.getFullYear(),
    selectedMonth: currentDate.getMonth() + 1
})

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const reportData = ref([])
const dates = ref([])
const monthName = ref('')
const loading = ref(false)
const exportLoading = ref(false)

const sortField = ref(null)
const sortDirection = ref('asc')

const formatDateLabel = (dateStr) => {
    const date = new Date(dateStr)
    return date.getDate()
}

const getCellClass = (value) => {
    const num = parseFloat(value)
    if (num < 0) {
        return 'text-red-600 dark:text-red-400 !font-bold'
    } else if (num > 0) {
        return 'text-green-600 dark:text-green-400 !font-bold'
    }
    return ''
}

const getColumnTotal = (date) => {
    let total = 0
    reportData.value.forEach(item => {
        total += parseFloat(item['date_' + date].amount ?? 0)
    })
    return total
}

const grandTotal = computed(() => {
    let total = 0
    reportData.value.forEach(item => {
        total += parseFloat(item.total ?? 0)
    })
    return total
})

const sortedReportData = computed(() => {
    const data = [...reportData.value]

    if (!sortField.value) return data

    return data.sort((a, b) => {
        let aVal, bVal

        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store_number') {
            aVal = parseFloat(a.store_number || 0)
            bVal = parseFloat(b.store_number || 0)
        } else if (sortField.value === 'total') {
            aVal = parseFloat(a.total || 0)
            bVal = parseFloat(b.total || 0)
        } else if (sortField.value.startsWith('date_')) {
            aVal = parseFloat(a[sortField.value] || 0)
            bVal = parseFloat(b[sortField.value] || 0)
        } else {
            aVal = a[sortField.value] || ''
            bVal = b[sortField.value] || ''
        }

        if (typeof aVal === 'string') {
            return sortDirection.value === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal)
        } else {
            return sortDirection.value === 'asc'
                ? aVal - bVal
                : bVal - aVal
        }
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
    if (sortField.value !== field) return null
    return sortDirection.value
}

const onFilterChange = () => {
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/cash/bank-deposit', {
            year: filters.value.selectedYear,
            month: filters.value.selectedMonth
        })
        reportData.value = response?.data ?? []
        dates.value = response?.dates ?? []
        monthName.value = response?.month_name ?? ''
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

            exportData.push(['Bank Deposit Report'])
            exportData.push(['Month:', `${monthName.value} ${filters.value.selectedYear}`])
            exportData.push(['Formula: Cash Bag - Deposits - Shortages'])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            const headers = ['Sr', 'Store']
            dates.value.forEach(date => {
                headers.push(formatDateLabel(date))
            })
            headers.push('Total')
            exportData.push(headers)

            reportData.value.forEach((item) => {
                const row = [
                    item.sr_no,
                    item.company_name
                ]
                dates.value.forEach(date => {
                    row.push(parseFloat(item['date_' + date].amount || 0) ?? 0)
                })
                row.push(parseFloat(item.total || 0))
                exportData.push(row)
            })

            exportData.push([])
            const totalsRow = ['', 'TOTALS']
            dates.value.forEach(date => {
                totalsRow.push(parseFloat(getColumnTotal(date) ?? 0))
            })
            totalsRow.push(parseFloat(grandTotal.value || 0))
            exportData.push(totalsRow)

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [
                { wch: 5 },
                { wch: 30 }
            ]
            dates.value.forEach(() => {
                colWidths.push({ wch: 10 })
            })
            colWidths.push({ wch: 12 })
            ws['!cols'] = colWidths

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Bank Deposit Report')

            const filename = `Bank_Deposit_Report_${monthName.value}_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`

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

const handleDoubleClick = (item, date) => {
    if(item['date_' + date].daily_sale_id){
        window.open(`/data-entry/bank-deposits/${item['date_' + date].daily_sale_id}`, '_blank')
    }
}

onMounted(() => {
    applyFilters()
})
</script>

<style scoped>
.sticky {
    position: sticky;
}
</style>
