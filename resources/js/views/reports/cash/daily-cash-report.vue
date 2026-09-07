<template>
    <div class="daily-cash-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Daily Cash Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View cash summary for all companies on a specific date
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Date
                    </label>
                    <input
                        type="date"
                        v-model="filters.selectedDate"
                        @change="onFilterChange"
                        class="filterable-select w-full form-input"
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
                            <Th class="md:sticky md:left-0 !z-[101]">Sr. No.</Th>
                            <Th class="md:sticky md:left-12 !z-[101]">Company</Th>
                            <Th class="text-right">Total Sales</Th>
                            <Th class="text-right">Cash Received</Th>
                            <Th class="text-right">Cash Payment</Th>
                            <Th class="text-right">Net Cash Due</Th>
                            <Th class="text-right">Cash Bag</Th>
                            <Th class="text-right">Diff (Short/Over)</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="reportData.length > 0">
                        <tr 
                            v-for="(item, index) in reportData" 
                            :key="index" 
                            class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            @dblclick="navigateToDailySale(item)"
                        >
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800">{{ item.sr_no }}</Td>
                            <Td class="md:sticky md:left-12 z-10 bg-white dark:bg-gray-800 font-medium">{{ item.company_name }}</Td>
                            <Td align="right">{{ formatNumber(item.total_sales, 2) }}</Td>
                            <Td align="right">{{ formatNumber(item.cash_received, 2) }}</Td>
                            <Td align="right">{{ formatNumber(item.cash_payment, 2) }}</Td>
                            <Td align="right" weight="bold">{{ formatNumber(item.net_cash_due, 2) }}</Td>
                            <Td align="right" weight="bold">{{ formatNumber(item.cash_bag, 2) }}</Td>
                            <Td align="right" weight="bold" :class="getShortOverClass(item.short_over)">
                                {{ formatNumber(item.short_over, 2) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="8" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="There is no data for the selected date. Please select a different date."
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
                            <Td align="right" weight="bold">{{ formatNumber(totals.total_sales, 2) }}</Td>
                            <Td align="right" weight="bold">{{ formatNumber(totals.cash_received, 2) }}</Td>
                            <Td align="right" weight="bold">{{ formatNumber(totals.cash_payment, 2) }}</Td>
                            <Td align="right" weight="bold">{{ formatNumber(totals.net_cash_due, 2) }}</Td>
                            <Td align="right" weight="bold">{{ formatNumber(totals.cash_bag, 2) }}</Td>
                            <Td align="right" weight="bold" :class="getShortOverClass(totals.short_over)">
                                {{ formatNumber(totals.short_over, 2) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ reportData.length }}</span> companies
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatDate(filters.selectedDate) }}
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
const currentDate = new Date()

const filters = ref({
    selectedDate: currentDate.toISOString().split('T')[0]
})

const reportData = ref([])
const loading = ref(false)
const exportLoading = ref(false)

const formatDate = (dateStr) => {
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
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
    if (item.daily_sale_id) {
        window.open(`/data-entry/daily-sales/${item.daily_sale_id}`, '_blank')
    }
}

const totals = computed(() => {
    const result = {
        total_sales: 0,
        cash_received: 0,
        cash_payment: 0,
        net_cash_due: 0,
        cash_bag: 0,
        short_over: 0
    }

    reportData.value.forEach(item => {
        result.total_sales += parseFloat(item.total_sales || 0)
        result.cash_received += parseFloat(item.cash_received || 0)
        result.cash_payment += parseFloat(item.cash_payment || 0)
        result.net_cash_due += parseFloat(item.net_cash_due || 0)
        result.cash_bag += parseFloat(item.cash_bag || 0)
        result.short_over += parseFloat(item.short_over || 0)
    })

    return result
})

const onFilterChange = () => {
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/cash/daily-cash', {
            date: filters.value.selectedDate
        })
        reportData.value = response?.data ?? []
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

            exportData.push(['Daily Cash Report'])
            exportData.push(['Date:', formatDate(filters.value.selectedDate)])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            const headers = [
                'Sr. No.',
                'Company',
                'Total Sales',
                'Cash Received',
                'Cash Payment',
                'Net Cash Due',
                'Cash Bag',
                'Diff (Short/Over)'
            ]
            exportData.push(headers)

            reportData.value.forEach((item) => {
                const row = [
                    item.sr_no,
                    item.company_name,
                    parseFloat(item.total_sales || 0),
                    parseFloat(item.cash_received || 0),
                    parseFloat(item.cash_payment || 0),
                    parseFloat(item.net_cash_due || 0),
                    parseFloat(item.cash_bag || 0),
                    parseFloat(item.short_over || 0)
                ]
                exportData.push(row)
            })

            exportData.push([])
            const totalsRow = [
                '',
                'TOTALS',
                totals.value.total_sales,
                totals.value.cash_received,
                totals.value.cash_payment,
                totals.value.net_cash_due,
                totals.value.cash_bag,
                totals.value.short_over
            ]
            exportData.push(totalsRow)

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [
                { wch: 8 },
                { wch: 30 },
                { wch: 12 },
                { wch: 14 },
                { wch: 14 },
                { wch: 14 },
                { wch: 12 },
                { wch: 16 }
            ]
            ws['!cols'] = colWidths

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Daily Cash Report')

            const filename = `Daily_Cash_Report_${filters.value.selectedDate}_${new Date().getTime()}.xlsx`

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
    applyFilters()
})
</script>

<style scoped>
.sticky {
    position: sticky;
}
</style>
