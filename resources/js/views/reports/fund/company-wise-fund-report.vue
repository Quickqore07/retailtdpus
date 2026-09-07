<template>
    <div class="company-wise-fund-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Company Wise Fund Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View fund requirements by company for upcoming days
                </p>
            </div>

            <!-- Filters -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Label
                </label>
                <select 
                    v-model="selectedLabel" 
                    @change="loadReport"
                    class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white text-sm"
                >
                    <option v-for="label in labels" :key="label.value" :value="label.value">
                        {{ label.label }}
                    </option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        icon-left="refresh"
                        icon-size="sm"
                        variant="primary"
                        size="sm"
                        @click="loadReport"
                        :loading="loading"
                    >
                        Refresh Report
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
                            <Th v-for="dateInfo in dates" :key="dateInfo.date" class="text-center">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('date_' + dateInfo.date)">
                                        <span>{{ formatDateLabel(dateInfo.date) }}</span>
                                        <SvgIcon
                                            v-if="getSortDirection('date_' + dateInfo.date)"
                                            :name="getSortDirection('date_' + dateInfo.date) === 'asc' ? 'chevron-up' : 'chevron-down'"
                                            size="sm"
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ dateInfo.day }}</span>
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
                                v-for="dateInfo in dates"
                                :key="dateInfo.date"
                                align="center"
                                customClass="!min-w-[2.5rem]"
                                :weight="item['date_' + dateInfo.date].amount > 0 ? 'medium' : 'normal'"
                                :class="getCellClass(item['date_' + dateInfo.date])"

                            >
                                {{ formatNumber(item['date_' + dateInfo.date].amount, 2) }}
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
                                    message="There is no fund requirement data available for the selected label."
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
                            <Td v-for="dateInfo in dates" :key="dateInfo.date" weight="bold" align="center">
                                {{ formatNumber(getColumnTotal(dateInfo.date), 2) }}
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
                    Fund Due Days: {{ fundDueDays }}
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

const message = useMessage()

const reportData = ref([])
const dates = ref([])
const fundDueDays = ref(6)
const loading = ref(false)
const exportLoading = ref(false)
const labels = ref([])
const selectedLabel = ref('food_purchase')

const sortField = ref(null)
const sortDirection = ref('asc')

const formatDateLabel = (dateStr) => {
    const date = new Date(dateStr)
    return `${date.getMonth() + 1}/${date.getDate()}`
}

const getCellClass = (dateData) => {
    if (!dateData) return ''
    
    // Highlight weekend (Saturday/Sunday) with lighter shade
    if (dateData.day === 'Saturday' || dateData.day === 'Sunday') {
        return 'bg-gray-100 dark:bg-gray-700'
    }
    
    const amount = parseFloat(dateData.amount)
    if (amount > 0) {
        return 'text-green-600 dark:text-green-400 font-medium'
    }
    return ''
}

const getColumnTotal = (date) => {
    let total = 0
    reportData.value.forEach(item => {
        total += parseFloat(item['date_' + date]?.amount ?? 0)
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
            aVal = parseFloat(a[sortField.value]?.amount || 0)
            bVal = parseFloat(b[sortField.value]?.amount || 0)
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

const loadLabels = async () => {
    try {
        const response = await useRequest('get', '/reports/fund/labels')
        labels.value = response ?? []
    } catch (error) {
        console.error('Error loading labels:', error)
        message.error(error.response?.data?.message || 'Failed to load labels')
    }
}

const loadReport = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/fund/company-wise', {
            label: selectedLabel.value
        })
        reportData.value = response?.data ?? []
        dates.value = response?.dates ?? []
        fundDueDays.value = response?.fund_due_days ?? 6
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

            // Get current label display name
            const currentLabelObj = labels.value.find(l => l.value === selectedLabel.value)
            const labelDisplay = currentLabelObj ? currentLabelObj.label : 'Fund Report'

            exportData.push([`Company Wise Fund Report - ${labelDisplay}`])
            exportData.push(['Fund Due Days:', fundDueDays.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            const headers = ['Sr', 'Store']
            dates.value.forEach(dateInfo => {
                headers.push(`${formatDateLabel(dateInfo.date)} (${dateInfo.day})`)
            })
            headers.push('Total')
            exportData.push(headers)

            reportData.value.forEach((item) => {
                const row = [
                    item.sr_no,
                    item.company_name
                ]
                dates.value.forEach(dateInfo => {
                    row.push(parseFloat(item['date_' + dateInfo.date]?.amount || 0) ?? 0)
                })
                row.push(parseFloat(item.total || 0))
                exportData.push(row)
            })

            exportData.push([])
            const totalsRow = ['', 'TOTALS']
            dates.value.forEach(dateInfo => {
                totalsRow.push(parseFloat(getColumnTotal(dateInfo.date) ?? 0))
            })
            totalsRow.push(parseFloat(grandTotal.value || 0))
            exportData.push(totalsRow)

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [
                { wch: 5 },
                { wch: 30 }
            ]
            dates.value.forEach(() => {
                colWidths.push({ wch: 15 })
            })
            colWidths.push({ wch: 12 })
            ws['!cols'] = colWidths

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Company Wise Fund Report')

            const labelSlug = selectedLabel.value.replace(/_/g, '-')
            const filename = `Company_Wise_Fund_Report_${labelSlug}_${new Date().getTime()}.xlsx`

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
    loadLabels()
    loadReport()
})
</script>

<style scoped>
.sticky {
    position: sticky;
}
</style>
