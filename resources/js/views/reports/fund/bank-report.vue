<template>
    <div class="bank-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Bank Upload Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View bank upload transactions grouped by account or categorized by description
                </p>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="filters.startDate"
                        type="date"
                        class="form-input w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white text-sm"
                    />
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        End Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="filters.endDate"
                        type="date"
                        class="form-input w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white text-sm"
                    />
                </div>

                <!-- Bank Filter (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bank (Optional)
                    </label>
                    <DynamicDropdown
                        v-model="filters.bank"
                        resource="banks"
                        display-name="name"
                        placeholder="All Banks"
                        @change="applyFilters"
                        :params="{ code: 'bank' }"
                    />
                </div>

                <!-- Report Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Report Type <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="filters.reportType"
                        @change="applyFilters"
                        class="form-select w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white text-sm"
                    >
                        <option value="account_wise">Account Wise</option>
                        <option value="description_wise">Category Wise (Description)</option>
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
                        :disabled="!isFilterValid"
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

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6" v-if="summary.total_entries > 0">
           
            <StatCard
                label="Opening Balance"
                :value="formatNumber(summary.opening_balance)"
                icon="dollar"
                icon-color="blue"
            />
            <StatCard
                label="Total Credit"
                :value="formatNumber(summary.total_credit)"
                icon="up-arrow"
                icon-color="green"
            />
            <StatCard
                label="Total Debit"
                :value="formatNumber(summary.total_debit)"
                icon="down-arrow"
                icon-color="red"
            />
            <StatCard
                label="Net Change"
                :value="formatNumber(summary.total_credit - summary.total_debit)"
                icon="dollar"
                icon-color="green"
            />
            <StatCard
                label="Net Amount"
                :value="formatNumber(summary.net_amount + summary.opening_balance)"
                icon="dollar"
                icon-color="purple"
            />
        </div>

        <!-- Data Table -->
        <Panel>
            
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 !z-[101]">
                        <tr>
                            <Th>
                                <span>{{ filters.reportType === 'account_wise' ? 'Account' : 'Category' }}</span>
                            </Th>
                            <Th>
                                <span>Bank</span>
                            </Th>
                            <Th align="center">
                                <span>Entries</span>
                            </Th>
                            <Th align="right">
                                <span>Credit</span>
                            </Th>
                            <Th align="right">
                                <span>Debit</span>
                            </Th>
                            <Th align="right">
                                <span>Net</span>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedReportData.length > 0">
                        <tr>
                            <Td weight="bold">Opening Balance</Td>
                            <Td align="left">-</Td>
                            <Td align="center" weight="bold" >-</Td>
                            <Td align="right" weight="bold" >-</Td>
                            <Td align="right" weight="bold" >-</Td>
                            <Td align="right" weight="bold" > {{ formatNumber(summary.opening_balance) }}</Td>
                        </tr>
                        <tr v-for="(item, index) in sortedReportData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td weight="medium" class="truncate max-w-[500px]" :title="item.group_name">{{ item.group_name }}</Td>
                            <Td>{{ item.bank_name }}</Td>
                            <Td align="center">{{ item.count }}</Td>
                            <Td align="right" weight="medium">
                                {{ formatNumber(item.credit) }}
                            </Td>
                            <Td align="right" weight="medium">
                                {{ formatNumber(item.debit) }}
                            </Td>
                            <Td align="right" weight="bold">
                                {{ formatNumber(item.net) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="6" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="There are no bank upload entries for the selected filters. Please adjust your filters and try again."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="reportData.length > 0">
                        <tr>
                            <Td colspan="2" weight="bold" color="primary">TOTAL</Td>
                            <Td align="center" weight="bold">{{ summary.total_entries }}</Td>
                            <Td align="right" weight="bold">
                                {{ formatNumber(summary.total_credit) }}
                            </Td>
                            <Td align="right" weight="bold">
                                {{ formatNumber(summary.total_debit) }}
                            </Td>
                            <Td align="right" weight="bold">
                                {{ formatNumber(summary.net_amount + summary.opening_balance) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ reportData.length }}</span> {{ filters.reportType === 'account_wise' ? 'accounts' : 'categories' }} 
                    with <span class="font-medium">{{ summary.total_entries }}</span> total entries
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
import StatCard from '@/components/ui/stat-card.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'

const message = useMessage()

const filters = ref({
    startDate: null,
    endDate: null,
    bank: null,
    reportType: 'account_wise',
})

const reportData = ref([])
const summary = ref({
    total_entries: 0,
    total_groups: 0,
    total_credit: 0,
    total_debit: 0,
    net_amount: 0,
    start_date: null,
    end_date: null,
    report_type: null,
})
const loading = ref(false)
const exportLoading = ref(false)

const isFilterValid = computed(() => {
    return filters.value.startDate && filters.value.endDate && filters.value.reportType
})

const formatDateRange = computed(() => {
    if (!summary.value.start_date || !summary.value.end_date) return 'N/A'
    return `${new Date(summary.value.start_date).toLocaleDateString()} - ${new Date(summary.value.end_date).toLocaleDateString()}`
})

const sortedReportData = computed(() => {
    return reportData.value
})

const applyFilters = async () => {
    if (!isFilterValid.value) {
        message.error('Please select start date, end date, and report type')
        return
    }

    try {
        loading.value = true
        const response = await useRequest('post', '/reports/fund/bank-report', {
            start_date: filters.value.startDate,
            end_date: filters.value.endDate,
            bank_id: filters.value.bank?.id || null,
            report_type: filters.value.reportType,
        })

        if (response.success) {
            reportData.value = response.data ?? []
            const otherOP = reportData.value.findIndex(item => item.group_name === 'Opening Balance')
            if (otherOP !== -1) {
                const openingBalance = reportData.value.splice(otherOP, 1)
                reportData.value.unshift(openingBalance[0])
            }
            summary.value = response.summary ?? {}
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

            exportData.push(['Bank Upload Report'])
            exportData.push(['Report Type:', filters.value.reportType === 'account_wise' ? 'Account Wise' : 'Category Wise (Description)'])
            exportData.push(['Date Range:', formatDateRange.value])
            exportData.push(['Total Entries:', summary.value.total_entries])
            exportData.push(['Opening Balance:', summary.value.opening_balance])
            exportData.push(['Total Credit:', summary.value.total_credit])
            exportData.push(['Total Debit:', summary.value.total_debit])
            exportData.push(['Net Change:', summary.value.total_credit - summary.value.total_debit])
            exportData.push(['Net Amount:', summary.value.net_amount + summary.value.opening_balance ])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            const headers = [
                filters.value.reportType === 'account_wise' ? 'Account' : 'Category',
                'Bank',
                'Entries',
                'Credit',
                'Debit',
                'Net'
            ]
            exportData.push(headers)
            exportData.push([
                'Opening Balance',
                '',
                '',
                '',
                '',
                parseFloat(summary.value.opening_balance || 0)
            ])

            reportData.value.forEach((item) => {
                const row = [
                    item.group_name,
                    item.bank_name,
                    item.count,
                    parseFloat(item.credit || 0),
                    parseFloat(item.debit || 0),
                    parseFloat(item.net || 0)
                ]
                exportData.push(row)
            })

            exportData.push([])
            const totalsRow = [
                'TOTAL',
                '',
                summary.value.total_entries,
                parseFloat(summary.value.total_credit || 0),
                parseFloat(summary.value.total_debit || 0),
                parseFloat(summary.value.net_amount + summary.value.opening_balance || 0)
            ]
            exportData.push(totalsRow)

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [
                { wch: 30 },
                { wch: 25 },
                { wch: 10 },
                { wch: 15 },
                { wch: 15 },
                { wch: 15 }
            ]
            ws['!cols'] = colWidths

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Bank Report')

            const filename = `Bank_Report_${filters.value.reportType}_${new Date().getTime()}.xlsx`

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

const initializeDates = () => {
    const today = new Date()
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
    
    filters.value.startDate = firstDay.toISOString().split('T')[0]
    filters.value.endDate = today.toISOString().split('T')[0]
}

onMounted(() => {
    initializeDates()
    applyFilters()
})
</script>

<style scoped>
.sticky {
    position: sticky;
}
</style>
