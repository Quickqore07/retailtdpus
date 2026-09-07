<template>
    <div class="p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Payroll Journal Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    DC payroll journal report with bi-weekly period and summary/details views
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select v-model="filters.selectedYear" @change="onYearChange" class="form-select">
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bi-Weekly Period
                    </label>
                    <select v-model="filters.selectedPeriod" class="form-select" @change="onPeriodChange">
                        <option v-for="period in availablePeriods" :key="period.value" :value="period.value">
                            {{ period.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Report Type
                    </label>
                    <select v-model="filters.reportType" class="form-select" >
                        <option value="summary">Summary</option>
                        <option value="details">Details</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Button icon-left="refresh" icon-size="sm" variant="primary" size="sm" @click="applyFilters" :loading="loading">
                    Apply Filters
                </Button>
                <Button icon-left="upload" icon-size="sm" variant="secondary" size="sm" @click="exportReport" :loading="exportLoading">
                    Export to Excel
                </Button>
            </div>
        </Panel>

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-180px)] min-h-[200px] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 ">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr v-if="isSummary" class="!z-[101]">
                            <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">NO</Th>
                            <Th class="md:sticky md:left-12 bg-white dark:bg-gray-900 !z-[101]">Company</Th>
                            <Th>Total Earnings</Th>
                            <Th>Total Tips</Th>
                            <Th>Total Mileage</Th>
                            <Th>ER Withholdings</Th>
                            <Th>CTC</Th>
                        </tr>
                        <tr v-else class="!z-[101]">
                            <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">No</Th>
                            <Th class="md:sticky md:left-12 bg-white dark:bg-gray-900 !z-[101]">Company</Th>
                            <Th v-for="column in detailColumns" :key="column.key">{{ column.label }}</Th>
                        </tr>
                    </thead>

                    <tbody v-if="isSummary && tableRows.length > 0" class="bg-white dark:bg-gray-800 !z-[100]">
                        <tr v-for="(item, index) in tableRows" :key="`summary-${index}`" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <Td class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">{{ index + 1 }}</Td>
                            <Td class="md:sticky md:left-12 bg-white dark:bg-gray-900 !z-[101]">{{ item.company_name || '-' }}</Td>
                            <Td :class="item.total_earnings > 0 ? '!font-medium' : ''">{{ formatNumber(item.total_earnings) }}</Td>
                            <Td :class="item.charge_tips_reimb > 0 ? '!font-medium' : ''">{{ formatNumber(item.charge_tips_reimb) }}</Td>
                            <Td :class="item.mileage_reimb > 0 ? '!font-medium' : ''">{{ formatNumber(item.mileage_reimb) }}</Td>
                            <Td :class="item.er_withholdings > 0 ? '!font-medium' : ''">{{ formatNumber(item.er_withholdings) }}</Td>
                            <Td :class="item.ctc > 0 ? '!font-medium' : ''">{{ formatNumber(item.ctc) }}</Td>
                        </tr>
                    </tbody>

                    <tbody v-else-if="tableRows.length > 0" class="bg-white dark:bg-gray-800 !z-[100]">
                        <tr v-for="(item, index) in tableRows" :key="`detail-${item.id}-${index}`" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <Td class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">{{ index + 1 }}</Td>
                            <Td class="md:sticky md:left-12 bg-white dark:bg-gray-900 !z-[101]">{{ item.company?.name || '-' }}</Td>
                            <Td v-for="column in detailColumns" :key="`${item.id}-${column.key}`" :class="item[column.key] > 0 ? '!font-medium' : ''">{{ formatNumber(item[column.key]) }}</Td>
                        </tr>
                    </tbody>

                    <tbody v-else class="bg-white dark:bg-gray-800 !z-[100]">
                        <tr>
                            <td :colspan="isSummary ? 9 : detailColumns.length + 3">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Payroll Journal Data Found"
                                    message="No data found for the selected bi-weekly period."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>

                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="reportData.data.length > 0">
                        <tr v-if="isSummary">
                            <Td class="md:sticky md:left-0 bg-gray-100 dark:bg-gray-900 !z-[101]"></Td>
                            <Td class="md:sticky md:left-12 bg-gray-100 dark:bg-gray-900 !z-[101]">Total</Td>
                            <Td>{{ formatNumber(summaryTotals.total_earnings) }}</Td>
                            <Td>{{ formatNumber(summaryTotals.charge_tips_reimb) }}</Td>
                            <Td>{{ formatNumber(summaryTotals.mileage_reimb) }}</Td>
                            <Td>{{ formatNumber(summaryTotals.er_withholdings) }}</Td>
                            <Td>{{ formatNumber(summaryTotals.ctc) }}</Td>
                        </tr>
                        <tr v-else>
                            <Td class="md:sticky md:left-0 bg-gray-100 dark:bg-gray-900 !z-[101]"></Td>
                            <Td class="md:sticky md:left-12 bg-gray-100 dark:bg-gray-900 !z-[101]">Total</Td>
                            <Td
                                v-for="column in detailColumns"
                                :key="`total-${column.key}`"
                            >
                                {{ formatNumber(detailTotals[column.key]) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'

const message = useMessage()

const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedPeriod: '',
    reportType: 'summary',
})

const loading = ref(false)
const exportLoading = ref(false)
const reportData = ref({
    data: [],
})

const detailColumns = [
    { key: 'cs_accident', label: 'CS Accident' },
    { key: 'cs_term_life', label: 'CS Term Life' },
    { key: 'crit_illness', label: 'Crit Illness' },
    { key: 'dental_a', label: 'Dental A' },
    { key: 'dental_b', label: 'Dental B' },
    { key: 'dental_d', label: 'Dental D' },
    { key: 'ee_term_life', label: 'EE Term Life' },
    { key: 'hospital_ind', label: 'Hospital Ind' },
    { key: 'id_theft', label: 'ID Theft' },
    { key: 'legal', label: 'Legal' },
    { key: 'life_ltc', label: 'Life LTC' },
    { key: 'pet_Well', label: 'Pet Well' },
    { key: 'sp_term_life', label: 'SP Term Life' },
    { key: 'std', label: 'STD' },
    { key: 'vision', label: 'Vision' },
    { key: 'cash_tips', label: 'Cash Tips' },
    { key: 'charge_tips', label: 'Charge Tips' },
    { key: 'charge_tips_reimb', label: 'Charge Tips Reimb' },
    { key: 'deduction', label: 'Deduction' },
    { key: 'dental', label: 'Dental' },
    { key: 'direct_deposit_debit', label: 'Direct Deposit Debit' },
    { key: 'hours', label: 'Hours' },
    { key: 'wages', label: 'Wages' },
    { key: 'medical', label: 'Medical' },
    { key: 'mileage_reimb', label: 'Mileage Reimb' },
    { key: 'min_wage_adjust', label: 'Min Wage Adjust' },
    { key: 'min_wage_adjust_anne', label: 'Min Wage Adjust Anne' },
    { key: 'min_wage_adjust_bcit', label: 'Min Wage Adjust BCIT' },
    { key: 'min_wage_adjust_balt', label: 'Min Wage Adjust BALT' },
    { key: 'min_wage_adjust_calv', label: 'Min Wage Adjust CALV' },
    { key: 'min_wage_adjust_carr', label: 'Min Wage Adjust CARR' },
    { key: 'min_wage_adjust_char', label: 'Min Wage Adjust CHAR' },
    { key: 'min_wage_adjust_fred', label: 'Min Wage Adjust FRED' },
    { key: 'min_wage_adjust_harf', label: 'Min Wage Adjust HARF' },
    { key: 'min_wage_adjust_howa', label: 'Min Wage Adjust HOWA' },
    { key: 'min_wage_adjust_mont', label: 'Min Wage Adjust MONT' },
    { key: 'min_wage_adjust_prin', label: 'Min Wage Adjust PRIN' },
    { key: 'min_wage_adjust_stma', label: 'Min Wage Adjust STMA' },
    { key: 'overtime', label: 'Overtime' },
    { key: 'overtime_wages', label: 'Overtime Wages' },
    { key: 'px_garnishment', label: 'PX Garnishment' },
    { key: 'px_garnishment_2', label: 'PX Garnishment 2' },
    { key: 'retro_pretax_premium', label: 'Retro Pretax Premium' },
    { key: 'salary', label: 'Salary' },
    { key: 'sick', label: 'Sick' },
    { key: 'term_life_pretax', label: 'Term Life Pretax' },
    { key: 'vacation', label: 'Vacation' },
    { key: 'total_hours', label: 'Total Hours' },
    { key: 'total_earnings', label: 'Total Earnings' },
    { key: 'total_deductions', label: 'Total Deductions' },
    { key: 'total_other_payments', label: 'Total Other Payments' },
    { key: 'ee_withholdings', label: 'EE Withholdings' },
    { key: 'er_withholdings', label: 'ER Withholdings' },
    { key: 'manual_net_pay', label: 'Manual Net Pay' },
    { key: 'negotiable_net_pay', label: 'Negotiable Net Pay' },
    { key: 'non_negotiable_net_pay', label: 'Non-Negotiable Net Pay' },
    { key: 'ctc', label: 'CTC' },
]

const isSummary = computed(() => filters.value.reportType === 'summary')
const tableRows = computed(() => reportData.value.data)
const summaryTotalFields = ['total_earnings', 'charge_tips_reimb', 'mileage_reimb', 'er_withholdings', 'ctc']

const summaryTotals = computed(() => {
    return tableRows.value.reduce((totals, row) => {
        summaryTotalFields.forEach((field) => {
            totals[field] += Number(row[field] || 0)
        })
        return totals
    }, {
        total_earnings: 0,
        charge_tips_reimb: 0,
        mileage_reimb: 0,
        er_withholdings: 0,
        ctc: 0,
    })
})

const detailTotals = computed(() => {
    return tableRows.value.reduce((totals, row) => {
        detailColumns.forEach(({ key }) => {
            totals[key] += Number(row[key] || 0)
        })
        return totals
    }, detailColumns.reduce((acc, { key }) => {
        acc[key] = 0
        return acc
    }, {}))
})

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
    const daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

    let periodNumber = 1
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13)

        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }

        const formatPeriodDate = (date) => {
            const month = String(date.getUTCMonth() + 1).padStart(2, '0')
            const day = String(date.getUTCDate()).padStart(2, '0')
            const dateYear = date.getUTCFullYear()
            return `${month}-${day}-${dateYear}`
        }

        periods.push({
            label: `${formatPeriodDate(startDate)} To ${formatPeriodDate(endDate)}`,
            value: `${startDate.toISOString()} to ${endDate.toISOString()}`,
        })

        startDate.setUTCDate(startDate.getUTCDate() + 14)
        periodNumber++
    }

    return periods
})

const onYearChange = () => {
    filters.value.selectedPeriod = availablePeriods.value[0]?.value || ''
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const onPeriodChange = () => {
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const applyFilters = async () => {
    if (!filters.value.selectedPeriod) return
    try {
        loading.value = true
        const [startDate, endDate] = filters.value.selectedPeriod.split('to')
        const response = await useRequest('post', '/reports/payroll/payroll-journal', {
            start_date: startDate,
            end_date: endDate,
        })
        reportData.value = {
            data: response.data || [],
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to load payroll journal report')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    if (!reportData.value.data.length) {
        message.error('No data to export')
        return
    }

    try {
        exportLoading.value = true
        const XLSX = await import('xlsx')
        const exportData = []
        exportData.push(['Payroll Journal Report'])
        exportData.push(['Type', isSummary.value ? 'Summary' : 'Details'])
        exportData.push(['Period', availablePeriods.value.find((x) => x.value === filters.value.selectedPeriod)?.label || ''])
        exportData.push(['Generated', new Date().toLocaleString()])
        exportData.push([])

        if (isSummary.value) {
            exportData.push([
                'Company',
                'Total Earnings',
                'Total Deductions',
                'Total Other Payments',
                'EE Withholdings',
                'ER Withholdings',
                'Manual Net Pay',
                'Negotiable Net Pay',
                'Non-Negotiable Net Pay',
            ])
            reportData.value.data.forEach((row) => {
                exportData.push([
                    row.company_name || '-',
                    Number(row.total_earnings || 0),
                    Number(row.total_deductions || 0),
                    Number(row.total_other_payments || 0),
                    Number(row.ee_withholdings || 0),
                    Number(row.er_withholdings || 0),
                    Number(row.manual_net_pay || 0),
                    Number(row.negotiable_net_pay || 0),
                    Number(row.non_negotiable_net_pay || 0),
                ])
            })
        } else {
            exportData.push(['No', 'EOW', 'Company', ...detailColumns.map((column) => column.label)])
                reportData.value.data.forEach((row, index) => {
                exportData.push([
                    index + 1,
                    formatDate(row.eow),
                    row.company?.name || '-',
                    ...detailColumns.map((column) => Number(row[column.key] || 0)),
                ])
            })
        }

        const ws = XLSX.utils.aoa_to_sheet(exportData)
        const wb = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(wb, ws, 'Payroll Journal')
        XLSX.writeFile(wb, `Payroll_Journal_${filters.value.reportType}_${Date.now()}.xlsx`)
        message.success('Report exported successfully')
    } catch (error) {
        message.error('Failed to export report')
    } finally {
        exportLoading.value = false
    }
}

onMounted(() => {
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
    applyFilters()
})
</script>
