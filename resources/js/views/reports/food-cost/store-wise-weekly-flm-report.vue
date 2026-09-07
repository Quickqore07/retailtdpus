<template>
    <div class="store-wise-weekly-flm-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Store Wise Weekly FLM Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View FLM data by store and week for the selected year
                </p>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <DynamicDropdown
                    v-model="filters.company"
                    resource="companies"
                    display-name="name"
                    placeholder="Select company"
                    icon-left="building"
                    label="Select Company"
                    @change="applyFilters"
                />

                <!-- Select Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onYearChange"
                        class="form-select w-full"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Report Type
                    </label>
                    <select
                        v-model="filters.reportType"
                        @change="onReportTypeChange"
                        class="form-select w-full"
                    >
                        <option v-for="option in reportTypeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
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
                        :disabled="flmData.length === 0"
                    >
                        Export to Excel
                    </Button>
                </div>
            </div>
        </Panel>

        <div class="mb-6">
            <MultiStatCard
                label="Summary"
                :items="summaryStats"
                icon="chart"
                icon-color="indigo"
                format-type="number"
            />
        </div>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10">
                        <tr>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('week_ending')">
                                    <span>Week Ending</span>
                                    <SvgIcon
                                        v-if="getSortDirection('week_ending')"
                                        :name="getSortDirection('week_ending') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('sales')">
                                    <span>Sales</span>
                                    <SvgIcon
                                        v-if="getSortDirection('sales')"
                                        :name="getSortDirection('sales') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('food')">
                                    <span>Food</span>
                                    <SvgIcon
                                        v-if="getSortDirection('food')"
                                        :name="getSortDirection('food') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('food_percentage')">
                                    <span>Food %</span>
                                    <SvgIcon
                                        v-if="getSortDirection('food_percentage')"
                                        :name="getSortDirection('food_percentage') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('labour')">
                                    <span>Labour</span>
                                    <SvgIcon
                                        v-if="getSortDirection('labour')"
                                        :name="getSortDirection('labour') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('labour_percentage')">
                                    <span>Labour %</span>
                                    <SvgIcon
                                        v-if="getSortDirection('labour_percentage')"
                                        :name="getSortDirection('labour_percentage') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mileage')">
                                    <span>Mileage</span>
                                    <SvgIcon
                                        v-if="getSortDirection('mileage')"
                                        :name="getSortDirection('mileage') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mileage_percentage')">
                                    <span>Mileage %</span>
                                    <SvgIcon
                                        v-if="getSortDirection('mileage_percentage')"
                                        :name="getSortDirection('mileage_percentage') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('flm')">
                                    <span>Total FLM</span>
                                    <SvgIcon
                                        v-if="getSortDirection('flm')"
                                        :name="getSortDirection('flm') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('flm_percentage')">
                                    <span>Total FLM %</span>
                                    <SvgIcon
                                        v-if="getSortDirection('flm_percentage')"
                                        :name="getSortDirection('flm_percentage') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedFlmData.length > 0">
                        <tr v-for="(item, index) in sortedFlmData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td>{{ item.week_ending }}</Td>
                            <Td>{{ formatNumber(item.sales) }}</Td>
                            <Td>{{ formatNumber(item.food) }}</Td>
                            <Td>{{ formatNumber(item.food_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.labour) }}</Td>
                            <Td>{{ formatNumber(item.labour_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.mileage) }}</Td>
                            <Td>{{ formatNumber(item.mileage_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.flm) }}</Td>
                            <Td>{{ formatNumber(item.flm_percentage) }}%</Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="10" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No FLM Data Found"
                                    message="There is no FLM data for the selected store and year. Please select a different company/year or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="flmData.length > 0">
                        <tr>
                            <Td weight="bold" color="primary">TOTALS</Td>
                            <Td weight="bold">{{ formatNumber(totals.sales) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.food) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.food_percentage) }}%</Td>
                            <Td weight="bold">{{ formatNumber(totals.labour) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.labour_percentage) }}%</Td>
                            <Td weight="bold">{{ formatNumber(totals.mileage) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.mileage_percentage) }}%</Td>
                            <Td weight="bold">{{ formatNumber(totals.flm) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.flm_percentage) }}%</Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ flmData.length }}</span> weeks
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span v-if="filters.company">{{ filters.company.name }} | </span>Year: {{ filters.selectedYear }}
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
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const message = useMessage()

const filters = ref({
    selectedYear: new Date().getFullYear(),
    company: null,
    reportType: authStore.isDC ? 'bi-weekly' : 'weekly'
})

const reportTypeOptions = [
    { label: 'Weekly', value: 'weekly' },
    { label: 'Bi-weekly', value: 'bi-weekly' }
]

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const flmData = ref([])
const loading = ref(false)
const exportLoading = ref(false)

const sortField = ref(null)
const sortDirection = ref('asc')

const totals = computed(() => {
    return flmData.value.reduce((acc, item) => {
        acc.sales += parseFloat(item.sales ?? 0)
        acc.food += parseFloat(item.food ?? 0)
        acc.labour += parseFloat(item.labour ?? 0)
        acc.mileage += parseFloat(item.mileage ?? 0)
        acc.flm += parseFloat(item.flm ?? 0)
        return acc
    }, { sales: 0, food: 0, labour: 0, mileage: 0, flm: 0 })
})

const food_percentage_total = computed(() => totals.value.sales > 0 ? (totals.value.food / totals.value.sales) * 100 : 0)
const labour_percentage_total = computed(() => totals.value.sales > 0 ? (totals.value.labour / totals.value.sales) * 100 : 0)
const mileage_percentage_total = computed(() => totals.value.sales > 0 ? (totals.value.mileage / totals.value.sales) * 100 : 0)
const flm_percentage_total = computed(() => totals.value.sales > 0 ? (totals.value.flm / totals.value.sales) * 100 : 0)

const summaryStats = computed(() => [
    { label: 'Total Sales', value: totals.value.sales, type: 'amount' },
    { label: 'Total Food', value: totals.value.food },
    { label: 'Total Food %', value: formatNumber(food_percentage_total.value) },
    { label: 'Total Labour', value: totals.value.labour, type: 'amount' },
    { label: 'Total Labour %', value: formatNumber(labour_percentage_total.value) },
    { label: 'Total Mileage', value: totals.value.mileage, type: 'amount' },
    { label: 'Total Mileage %', value: formatNumber(mileage_percentage_total.value) },
    { label: 'Total FLM', value: totals.value.flm, type: 'amount' },
    { label: 'Total FLM %', value: formatNumber(flm_percentage_total.value) }
])

const sortedFlmData = computed(() => {
    const data = [...flmData.value]
    if (!sortField.value) return data
    return data.sort((a, b) => {
        let aVal, bVal
        if (sortField.value === 'week_ending' || sortField.value === 'eow') {
            aVal = new Date(a.eow || a.week_ending || 0).getTime()
            bVal = new Date(b.eow || b.week_ending || 0).getTime()
        } else {
            aVal = parseFloat(a[sortField.value] ?? 0)
            bVal = parseFloat(b[sortField.value] ?? 0)
        }
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
    if (sortField.value !== field) return null
    return sortDirection.value
}

const onYearChange = () => {
    applyFilters()
}

const onReportTypeChange = () => {
    applyFilters()
}

const applyFilters = async () => {
    if (!filters.value.company?.id) {
        message.warning('Please select a company')
        return
    }
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/food-cost/flm-store-summary', {
            year: filters.value.selectedYear,
            company_id: filters.value.company.id,
            report_type: filters.value.reportType
        })
        const data = response?.data ?? response
        flmData.value = Array.isArray(data) ? data : (data?.data ?? [])
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        if (!flmData.value || flmData.value.length === 0) {
            message.error('No data to export')
            return
        }
        exportLoading.value = true
        await import('xlsx').then((XLSX) => {
            const exportData = []
            exportData.push(['Store Wise Weekly FLM Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            if (filters.value.company) {
                exportData.push(['Store:', filters.value.company.name])
            }
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])
            exportData.push([
                'Week Ending', 'Sales', 'Food', 'Food %', 'Labour', 'Labour %', 'Mileage', 'Mileage %', 'Total FLM', 'Total FLM %'
            ])
            flmData.value.forEach((item) => {
                exportData.push([
                    item.week_ending,
                    parseFloat(item.sales || 0),
                    parseFloat(item.food || 0),
                    parseFloat(item.food_percentage || 0),
                    parseFloat(item.labour || 0),
                    parseFloat(item.labour_percentage || 0),
                    parseFloat(item.mileage || 0),
                    parseFloat(item.mileage_percentage || 0),
                    parseFloat(item.flm || 0),
                    parseFloat(item.flm_percentage || 0)
                ])
            })
            exportData.push([])
            exportData.push([
                'TOTALS',
                parseFloat(totals.value.sales || 0),
                parseFloat(totals.value.food || 0),
                parseFloat(food_percentage_total.value || 0),
                parseFloat(totals.value.labour || 0),
                parseFloat(labour_percentage_total.value || 0),
                parseFloat(totals.value.mileage || 0),
                parseFloat(mileage_percentage_total.value || 0),
                parseFloat(totals.value.flm || 0),
                parseFloat(flm_percentage_total.value || 0)
            ])
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            ws['!cols'] = [
                { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 10 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }
            ]
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Store Wise Weekly FLM')
            XLSX.writeFile(wb, `Store_Wise_Weekly_FLM_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`)
            message.success('Report exported successfully!')
        }).catch((err) => {
            console.error('Failed to load XLSX library:', err)
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
    filters.value.company = authStore.company
    if (filters.value.company?.id) {
        applyFilters()
    }
})
</script>
