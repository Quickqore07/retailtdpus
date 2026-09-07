<template>
    <div class="cash-ledger-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Cash Ledger Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View detailed cash flow breakdown for the selected company and month
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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
                <div>
                   <DynamicDropdown
                       label="Select Company"
                        v-model="filters.selectedCompany"
                        resource="companies"
                        display-name="name"
                        placeholder="Select Company"
                        remove-null-option
                        @change="applyFilters"
                   />
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

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th class="md:sticky md:left-0 bg-gray-100 dark:bg-gray-800 !z-[101]">Date</Th>
                            <Th colspan="3" class="!text-center bg-blue-50 dark:bg-blue-900/20">Cash received</Th>
                            <Th colspan="3" class="!text-center bg-green-50 dark:bg-green-900/20">Tips & Mileage</Th>
                            <Th colspan="4" class="!text-center bg-purple-50 dark:bg-purple-900/20">E-Tips & DD Tips</Th>
                            <Th colspan="7" class="!text-center bg-orange-50 dark:bg-orange-900/20">Cash Payments</Th>
                            <Th colspan="5" class="!text-center bg-red-50 dark:bg-red-900/20">Cash Position</Th>
                        </tr>
                        <tr>
                            <Th class="md:sticky md:left-0 bg-gray-100 dark:bg-gray-800 !z-[101]"></Th>
                            <Th class="bg-blue-50 dark:bg-blue-900/20">Cash Rece</Th>
                            <Th class="bg-blue-50 dark:bg-blue-900/20">Partial Void</Th>
                            <Th class="bg-blue-50 dark:bg-blue-900/20">Total</Th>
                            <Th class="bg-green-50 dark:bg-green-900/20">Tips</Th>
                            <Th class="bg-green-50 dark:bg-green-900/20">Mileage</Th>
                            <Th class="bg-green-50 dark:bg-green-900/20">Total</Th>
                            <Th class="bg-purple-50 dark:bg-purple-900/20">DD Tip</Th>
                            <Th class="bg-purple-50 dark:bg-purple-900/20">E-Tip</Th>
                            <Th class="bg-purple-50 dark:bg-purple-900/20">E-Tip Payroll</Th>
                            <Th class="bg-purple-50 dark:bg-purple-900/20">Total</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Cash Payment</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Small Maint</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Office Exp</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Food</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Supplies</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Misc</Th>
                            <Th class="bg-orange-50 dark:bg-orange-900/20">Total</Th>
                            <Th class="bg-red-50 dark:bg-red-900/20">Cash Due</Th>
                            <Th class="bg-red-50 dark:bg-red-900/20">Cash Bag</Th>
                            <Th class="bg-red-50 dark:bg-red-900/20">Short/Over</Th>
                            <Th class="bg-red-50 dark:bg-red-900/20">Deposit</Th>
                            <Th class="bg-red-50 dark:bg-red-900/20">Diff</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="reportData.length > 0">
                        <tr v-for="(item, index) in reportData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700" @dblclick="navigateToDailySale(item)">
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800 font-medium">{{ formatDate(item.date) }}</Td>
                            <Td align="right" class="bg-blue-50/50 dark:bg-blue-900/10" :customClass="getCellClass(item.cash_received)">{{ formatNumber(item.cash_received, 2) }}</Td>
                            <Td align="right" class="bg-blue-50/50 dark:bg-blue-900/10" :customClass="getCellClass(item.partial_void)">{{ formatNumber(item.partial_void, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-blue-50 dark:bg-blue-900/20">{{ formatNumber(item.total_cash, 2) }}</Td>
                            <Td align="right" class="bg-green-50/50 dark:bg-green-900/10" :customClass="getCellClass(item.tips)">{{ formatNumber(item.tips, 2) }}</Td>
                            <Td align="right" class="bg-green-50/50 dark:bg-green-900/10" :customClass="getCellClass(item.mileage)">{{ formatNumber(item.mileage, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-green-50 dark:bg-green-900/20">{{ formatNumber(item.total_tips_mileage, 2) }}</Td>
                            <Td align="right" class="bg-purple-50/50 dark:bg-purple-900/10" :customClass="getCellClass(item.dd_tips)">{{ formatNumber(item.dd_tips, 2) }}</Td>
                            <Td align="right" class="bg-purple-50/50 dark:bg-purple-900/10" :customClass="getCellClass(item.e_tips)">{{ formatNumber(item.e_tips, 2) }}</Td>
                            <Td align="right" class="bg-purple-50/50 dark:bg-purple-900/10" :customClass="getCellClass(item.e_tips_payroll)">{{ formatNumber(item.e_tips_payroll, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-purple-50 dark:bg-purple-900/20">{{ formatNumber(item.total_e_and_dd_tips, 2) }}</Td>
                            <Td align="right" class="bg-orange-50/50 dark:bg-orange-900/10" :customClass="getCellClass(item.cash_payment)">{{ formatNumber(item.cash_payment, 2) }}</Td>
                            <Td align="right" class="bg-orange-50/50 dark:bg-orange-900/10" :customClass="getCellClass(item.small_maintenance)">{{ formatNumber(item.small_maintenance, 2) }}</Td>
                            <Td align="right" class="bg-orange-50/50 dark:bg-orange-900/10" :customClass="getCellClass(item.office_exp)">{{ formatNumber(item.office_exp, 2) }}</Td>
                            <Td align="right" class="bg-orange-50/50 dark:bg-orange-900/10" :customClass="getCellClass(item.food)">{{ formatNumber(item.food, 2) }}</Td>
                            <Td align="right" class="bg-orange-50/50 dark:bg-orange-900/10" :customClass="getCellClass(item.supplies)">{{ formatNumber(item.supplies, 2) }}</Td>
                            <Td align="right" class="bg-orange-50/50 dark:bg-orange-900/10" :customClass="getCellClass(item.misc)">{{ formatNumber(item.misc, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(item.total_cash_payment, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(item.net_cash_due, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(item.cash_bag, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(item.short_over, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(item.deposit, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(item.diff, 2) }}</Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="21" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="There is no data for the selected filters. Please select different options."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="reportData.length > 0">
                        <tr>
                            <Td class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="primary">TOTALS</Td>
                            <Td align="right" weight="bold" class="bg-blue-50 dark:bg-blue-900/20">{{ formatNumber(totals.cash_received, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-blue-50 dark:bg-blue-900/20">{{ formatNumber(totals.partial_void, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-blue-50 dark:bg-blue-900/20">{{ formatNumber(totals.total_cash, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-green-50 dark:bg-green-900/20">{{ formatNumber(totals.tips, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-green-50 dark:bg-green-900/20">{{ formatNumber(totals.mileage, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-green-50 dark:bg-green-900/20">{{ formatNumber(totals.total_tips_mileage, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-purple-50 dark:bg-purple-900/20">{{ formatNumber(totals.dd_tips, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-purple-50 dark:bg-purple-900/20">{{ formatNumber(totals.e_tips, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-purple-50 dark:bg-purple-900/20">{{ formatNumber(totals.e_tips_payroll, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-purple-50 dark:bg-purple-900/20">{{ formatNumber(totals.total_e_and_dd_tips, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.cash_payment, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.small_maintenance, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.office_exp, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.food, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.supplies, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.misc, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-orange-50 dark:bg-orange-900/20">{{ formatNumber(totals.total_cash_payment, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(totals.net_cash_due, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(totals.cash_bag, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(totals.short_over, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(totals.deposit, 2) }}</Td>
                            <Td align="right" weight="bold" class="bg-red-50 dark:bg-red-900/20">{{ formatNumber(totals.diff, 2) }}</Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ reportData.length }}</span> records
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ companyName || 'All Companies' }} - {{ monthName }} {{ filters.selectedYear }}
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
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const message = useMessage()
const currentDate = new Date()
const filters = ref({
    selectedYear: currentDate.getFullYear(),
    selectedMonth: currentDate.getMonth() + 1,
    selectedCompany: null
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

const getCellClass = (value) => {
    const num = parseFloat(value)

    return num ? '!font-semibold' : ''
}
const reportData = ref([])
const monthName = ref('')
const companyName = ref('')
const loading = ref(false)
const exportLoading = ref(false)

const formatDate = (dateStr) => {
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const getShortOverClass = (value) => {
    const num = parseFloat(value)
    if (num < 0) {
        return 'text-red-600 dark:text-red-400 font-semibold'
    } else if (num > 0) {
        return 'text-green-600 dark:text-green-400 font-semibold'
    }
    return ''
}

const navigateToDailySale = (item) => {
    window.open(`/data-entry/daily-sales/${item.daily_sale_id}`, '_blank')
}

const totals = computed(() => {
    const result = {
        cash_received: 0,
        partial_void: 0,
        total_cash: 0,
        tips: 0,
        mileage: 0,
        total_tips_mileage: 0,
        dd_tips: 0,
        e_tips: 0,
        e_tips_payroll: 0,
        total_e_and_dd_tips: 0,
        cash_payment: 0,
        small_maintenance: 0,
        office_exp: 0,
        food: 0,
        supplies: 0,
        misc: 0,
        total_cash_payment: 0,
        cash_bag: 0,
        short_over: 0,
        net_cash_due: 0,
        diff: 0
    }

    reportData.value.forEach(item => {
        result.cash_received += parseFloat(item.cash_received || 0)
        result.partial_void += parseFloat(item.partial_void || 0)
        result.total_cash += parseFloat(item.total_cash || 0)
        result.tips += parseFloat(item.tips || 0)
        result.mileage += parseFloat(item.mileage || 0)
        result.total_tips_mileage += parseFloat(item.total_tips_mileage || 0)
        result.dd_tips += parseFloat(item.dd_tips || 0)
        result.e_tips += parseFloat(item.e_tips || 0)
        result.e_tips_payroll += parseFloat(item.e_tips_payroll || 0)
        result.total_e_and_dd_tips += parseFloat(item.total_e_and_dd_tips || 0)
        result.cash_payment += parseFloat(item.cash_payment || 0)
        result.small_maintenance += parseFloat(item.small_maintenance || 0)
        result.office_exp += parseFloat(item.office_exp || 0)
        result.food += parseFloat(item.food || 0)
        result.supplies += parseFloat(item.supplies || 0)
        result.misc += parseFloat(item.misc || 0)
        result.total_cash_payment += parseFloat(item.total_cash_payment || 0)
        result.cash_bag += parseFloat(item.cash_bag || 0)
        result.short_over += parseFloat(item.short_over || 0)
        result.net_cash_due += parseFloat(item.net_cash_due || 0)
        result.diff += parseFloat(item.diff || 0)
    })

    return result
})

const onFilterChange = () => {
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/cash/ledger', {
            year: filters.value.selectedYear,
            month: filters.value.selectedMonth,
            company_id: filters.value.selectedCompany?.id
        })
        reportData.value = response?.data ?? []
        monthName.value = response?.month_name ?? ''
        companyName.value = response?.company_name ?? ''
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

            exportData.push(['Cash Ledger Report'])
            exportData.push(['Company:', companyName.value || 'All Companies'])
            exportData.push(['Month:', `${monthName.value} ${filters.value.selectedYear}`])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            const headers = [
                'Date',
                'Cash Rece', 'Partial Void', 'Total',
                'Tips', 'Mileage', 'Total',
                'DD Tip', 'E-Tip', 'E-Tip Payroll', 'Total',
                'Cash Payment', 'Small Maint', 'Office Exp', 'Food', 'Supplies', 'Misc', 'Total',
                'Total', 'Cash Bag', 'Short/Over', 'Deposit', 'Diff'
            ]
            exportData.push(headers)

            reportData.value.forEach((item) => {
                const row = [
                    formatDate(item.date),
                    parseFloat(item.cash_received || 0),
                    parseFloat(item.partial_void || 0),
                    parseFloat(item.total_cash || 0),
                    parseFloat(item.tips || 0),
                    parseFloat(item.mileage || 0),
                    parseFloat(item.total_tips_mileage || 0),
                    parseFloat(item.dd_tips || 0),
                    parseFloat(item.e_tips || 0),
                    parseFloat(item.e_tips_payroll || 0),
                    parseFloat(item.total_e_and_dd_tips || 0),
                    parseFloat(item.cash_payment || 0),
                    parseFloat(item.small_maintenance || 0),
                    parseFloat(item.office_exp || 0),
                    parseFloat(item.food || 0),
                    parseFloat(item.supplies || 0),
                    parseFloat(item.misc || 0),
                    parseFloat(item.total_cash_payment || 0),
                    parseFloat(item.net_cash_due || 0),
                    parseFloat(item.cash_bag || 0),
                    parseFloat(item.short_over || 0),
                    parseFloat(item.deposit || 0),
                    parseFloat(item.diff || 0)
                ]
                exportData.push(row)
            })

            exportData.push([])
            const totalsRow = [
                'TOTALS',
                totals.value.cash_received,
                totals.value.partial_void,
                totals.value.total_cash,
                totals.value.tips,
                totals.value.mileage,
                totals.value.total_tips_mileage,
                totals.value.dd_tips,
                totals.value.e_tips,
                totals.value.e_tips_payroll,
                totals.value.total_e_and_dd_tips,
                totals.value.cash_payment,
                totals.value.small_maintenance,
                totals.value.office_exp,
                totals.value.food,
                totals.value.supplies,
                totals.value.misc,
                totals.value.total_cash_payment,
                totals.value.cash_bag,
                totals.value.net_cash_due,
                totals.value.short_over,
                totals.value.deposit,
                totals.value.diff
            ]
            exportData.push(totalsRow)

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [
                { wch: 12 },
                { wch: 10 }, { wch: 12 }, { wch: 10 },
                { wch: 10 }, { wch: 10 }, { wch: 10 },
                { wch: 10 }, { wch: 10 }, { wch: 12 }, { wch: 10 },
                { wch: 12 }, { wch: 12 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 },
                { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 }
            ]
            ws['!cols'] = colWidths

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Cash Ledger Report')

            const filename = `Cash_Ledger_Report_${monthName.value}_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`

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

onMounted(() => {
    filters.value.selectedCompany = authStore?.company ?? null
    applyFilters()
})
</script>

<style scoped>
.sticky {
    position: sticky;
}
</style>
