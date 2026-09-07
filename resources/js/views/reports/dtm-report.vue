<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    DTM Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Weekly Driver, Tips, and Mileage totals by company and employee.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                        Select Week
                    </label>
                    <select v-model="filters.selectedWeek" class="form-select" @change="applyFilters">
                        <option v-for="week in availableWeeks" :key="week.value" :value="week.value">
                            {{ week.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
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
                    :disabled="companies.length === 0"
                >
                    Export to Excel
                </Button>
            </div>
        </Panel>

        <Panel v-if="companies.length">
            <div class="overflow-x-auto max-h-[calc(100vh-180px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 !w-[200px] !z-[101]">Company</Th>
                            <Th class="md:sticky md:left-50 !z-[101] !w-[200px] !z-[101]">Driver Name</Th>
                            <Th>Road Hr</Th>
                            <Th>Cash Tips</Th>
                            <Th>CC Tips</Th>
                            <Th>Total Tips</Th>
                            <Th>Mileage</Th>
                            <Th>Total TM</Th>
                            <Th class="!min-w-[100px]">Avg Drv Pay</Th>
                            <Th>Drv Pay</Th>
                            <Th>Total Pay</Th>
                            <Th>Avg Pay</Th>
                            <Th>Delivery</Th>
                            <Th>Milage</Th>
                            <Th>CPD</Th>
                            <Th>DDD</Th>
                            <Th>Total</Th>
                            <Th>Dasher</Th>
                            <Th>DDD fee</Th>
                            <Th>Total Milage</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]">
                        <template v-for="company in companies" :key="company.id">
                            <tr
                                v-for="(row, idx) in company.rows"
                                :key="`${company.id}-${row.employee_id}-${idx}`"
                                class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 !z-[100]"
                            >
                                <Td class="sticky left-0 bg-white dark:bg-gray-900 !z-[100]">{{ company.name }}</Td>
                                <Td class="!text-left md:sticky md:left-50 bg-white dark:bg-gray-900 !z-[100]">{{ row.driver_name }}</Td>
                                <Td>{{ formatNumber(row.road_hr) }}</Td>
                                <Td>${{ formatNumber(row.cash_tips) }}</Td>
                                <Td>${{ formatNumber(row.cc_tips) }}</Td>
                                <Td>${{ formatNumber(row.total_tips) }}</Td>
                                <Td>{{ formatNumber(row.mileage) }}</Td>
                                <Td>${{ formatNumber(row.total_tm) }}</Td>
                                <Td>${{ formatNumber(row.avg_drv_pay) }}</Td>
                                <Td>${{ formatNumber(row.drv_pay) }}</Td>
                                <Td>${{ formatNumber(row.total_pay) }}</Td>
                                <Td>${{ formatNumber(row.avg_pay) }}</Td>
                                <Td>{{ formatNumber(row.delivery) }}</Td>
                                <Td>{{ formatNumber(row.delivery_mileage) }}</Td>
                                <Td>${{ formatNumber(row.cpd) }}</Td>
                                <Td>-</Td>
                                <Td>-</Td>
                                <Td>-</Td>
                                <Td>-</Td>
                                <Td>-</Td>
                            </tr>

                            <tr class="bg-gray-100 dark:bg-gray-700 font-semibold">
                                <Td weight="bold" class="sticky left-0 bg-gray-100 dark:bg-gray-700 !z-[100]"> </Td>
                                <Td weight="bold" class="md:sticky md:left-50 bg-gray-100 dark:bg-gray-700 !z-[100]">Total </Td>
                                <Td weight="bold">{{ formatNumber(getCompanyTotals(company).road_hr) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).cash_tips) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).cc_tips) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).total_tips) }}</Td>
                                <Td weight="bold">{{ formatNumber(getCompanyTotals(company).mileage) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).total_tm) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).avg_drv_pay) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).drv_pay) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).total_pay) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).avg_pay) }}</Td>
                                <Td weight="bold">{{ formatNumber(getCompanyTotals(company).delivery) }}</Td>
                                <Td weight="bold">{{ formatNumber(getCompanyTotals(company).delivery_mileage) }}</Td>
                                <Td weight="bold">${{ formatNumber(getCompanyTotals(company).cpd) }}</Td>
                                <Td weight="bold">${{ formatNumber(company.delivery_ideal_cost) }}</Td>
                                <Td weight="bold">{{ company.total_delivery }}</Td>
                                <Td weight="bold">${{ formatNumber(company.mileage_ideal_cost) }}</Td>
                                <Td weight="bold">${{ formatNumber(company.ddd_fee) }}</Td>
                                <Td weight="bold">${{ formatNumber(company.total_milage) }}</Td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </Panel>

        <Panel v-else>
            <NoData
                icon="files"
                icon-color="blue"
                title="No DTM Data Found"
                message="No data available for the selected year/week."
                size="sm"
                icon-size="lg"
                :show-action="false"
            />
        </Panel>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'

const message = useMessage()
const loading = ref(false)
const exportLoading = ref(false)
const companies = ref([])

const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedWeek: '',
})

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }, (_, i) => currentYear - i)
})

const availableWeeks = computed(() => {
    const year = filters.value.selectedYear
    const weeks = []
    let cursor = new Date(Date.UTC(year, 0, 1))
    while (cursor.getUTCDay() !== 1) {
        cursor.setUTCDate(cursor.getUTCDate() - 1)
    }

    let index = 1
    while (true) {
        const start = new Date(cursor)
        const end = new Date(cursor)
        end.setUTCDate(end.getUTCDate() + 6)

        if (start.getUTCFullYear() > year && end.getUTCFullYear() > year) {
            break
        }

        weeks.push({
            value: `${start.toISOString()}|${end.toISOString()}`,
            label: `Week ${index} (${formatDate(start)} - ${formatDate(end)})`,
        })

        cursor.setUTCDate(cursor.getUTCDate() + 7)
        index += 1
        if (index > 54) break
    }

    return weeks.filter((w) => w.label.includes(`${year}`) || w.label.includes(`${year - 1}`) || w.label.includes(`${year + 1}`))
})

const formatDate = (date) => {
    const month = String(date.getUTCMonth() + 1).padStart(2, '0')
    const day = String(date.getUTCDate()).padStart(2, '0')
    const year = date.getUTCFullYear()
    return `${month}/${day}/${year}`
}

const onYearChange = () => {
    filters.value.selectedWeek = availableWeeks.value[0]?.value || ''
    applyFilters()
}

const getCompanyTotals = (company) => {
    const base = {
        road_hr: 0,
        cash_tips: 0,
        cc_tips: 0,
        total_tips: 0,
        mileage: 0,
        total_tm: 0,
        drv_pay: 0,
        total_pay: 0,
        delivery: 0,
        avg_drv_pay: 0,
        avg_pay: 0,
        delivery_mileage: 0,
        cpd: 0,
    }

    for (const row of company.rows || []) {
        base.road_hr += Number(row.road_hr || 0)
        base.cash_tips += Number(row.cash_tips || 0)
        base.cc_tips += Number(row.cc_tips || 0)
        base.total_tips += Number(row.total_tips || 0)
        base.mileage += Number(row.mileage || 0)
        base.total_tm += Number(row.total_tm || 0)
        base.drv_pay += Number(row.drv_pay || 0)
        base.total_pay += Number(row.total_pay || 0)
        base.delivery += Number(row.delivery || 0)
    }

    base.avg_drv_pay = base.road_hr > 0 ? base.drv_pay / base.road_hr : 0
    base.avg_pay = base.road_hr > 0 ? base.total_pay / base.road_hr : 0
    base.delivery_mileage = base.delivery > 0 ? base.mileage / base.delivery : 0
    base.cpd = base.delivery > 0 ? base.total_pay / base.delivery : 0
    return base
}

const applyFilters = async () => {
    try {
        if (!filters.value.selectedWeek) {
            message.error('Please select a week.')
            return
        }

        loading.value = true
        const [startDate, endDate] = filters.value.selectedWeek.split('|')
        const response = await useRequest('post', '/reports/payroll/dtm', {
            start_date: startDate,
            end_date: endDate,
        })
        companies.value = response.data || []
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to load DTM report.')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        if (!companies.value || companies.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        await import('xlsx').then((XLSX) => {
            const exportData = []
            const selectedWeekLabel = availableWeeks.value.find((week) => week.value === filters.value.selectedWeek)?.label || 'N/A'

            exportData.push(['DTM Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Week:', selectedWeekLabel])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            exportData.push([
                'Company',
                'Driver Name',
                'Road Hr',
                'Cash Tips',
                'CC Tips',
                'Total Tips',
                'Mileage',
                'Total TM',
                'Avg Drv Pay',
                'Drv Pay',
                'Total Pay',
                'Avg Pay',
                'Delivery',
                'Milage',
                'CPD',
                'DDD',
                'Total',
                'Dasher',
                'DDD fee',
                'Total Milage',
            ])

            companies.value.forEach((company) => {
                company.rows.forEach((row) => {
                    exportData.push([
                        company.name,
                        row.driver_name,
                        formatNumber(row.road_hr || 0),
                        formatNumber(row.cash_tips || 0),
                        formatNumber(row.cc_tips || 0),
                        formatNumber(row.total_tips || 0),
                        formatNumber(row.mileage || 0),
                        formatNumber(row.total_tm || 0),
                        formatNumber(row.avg_drv_pay || 0),
                        formatNumber(row.drv_pay || 0),
                        formatNumber(row.total_pay || 0),
                        formatNumber(row.avg_pay || 0),
                        formatNumber(row.delivery || 0),
                        formatNumber(row.delivery_mileage || 0),
                        formatNumber(row.cpd || 0),
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                    ])
                })

                const totals = getCompanyTotals(company)
                exportData.push([
                    '',
                    `${company.name} Total`,
                    formatNumber(totals.road_hr || 0),
                    formatNumber(totals.cash_tips || 0),
                    formatNumber(totals.cc_tips || 0),
                    formatNumber(totals.total_tips || 0),
                    formatNumber(totals.mileage || 0),
                    formatNumber(totals.total_tm || 0),
                    formatNumber(totals.avg_drv_pay || 0),
                    formatNumber(totals.drv_pay || 0),
                    formatNumber(totals.total_pay || 0),
                    formatNumber(totals.avg_pay || 0),
                    formatNumber(totals.delivery || 0),
                    formatNumber(totals.delivery_mileage || 0),
                    formatNumber(totals.cpd || 0),
                    formatNumber(company.delivery_ideal_cost || 0),
                    formatNumber(company.total_delivery || 0),
                    formatNumber(company.mileage_ideal_cost || 0),
                    formatNumber(company.ddd_fee || 0),
                    formatNumber(company.total_milage || 0),
                ])
                exportData.push([])
            })

            const ws = XLSX.utils.aoa_to_sheet(exportData)
            ws['!cols'] = [
                { wch: 24 },
                { wch: 24 },
                { wch: 10 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 10 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 10 },
                { wch: 10 },
                { wch: 10 },
                { wch: 10 },
                { wch: 10 },
                { wch: 10 },
                { wch: 10 },
                { wch: 12 },
            ]

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'DTM Report')
            const fileName = `DTM_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            XLSX.writeFile(wb, fileName)
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
    filters.value.selectedWeek = availableWeeks.value[0]?.value || ''
    applyFilters()
})
</script>