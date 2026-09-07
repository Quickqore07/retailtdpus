<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    FLM-T Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze FLM-T data (including TPF) for the selected period
                </p>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <!-- Select Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onYearChange"
                        class="form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Report Type
                    </label>
                    <select
                        v-model="filters.reportType"
                        @change="onReportTypeChange"
                        class="form-select"
                    >
                        <option v-for="option in reportTypeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Select Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Period
                    </label>
                    <select
                        v-model="filters.selectedPeriod"
                        @change="onPeriodChange"
                        class="form-select"
                    >
                        <option v-for="period in availablePeriods" :key="period.value" :value="period.value">
                            {{ period.label }}
                        </option>
                    </select>
                </div>
                <div v-if="can('flm-t-report', 'regional-director')">
                    <DynamicDropdown
                        v-model="filters.regionalDirector"
                        :resource="`users?role=Regional Director&workgroups=${filters.workgroup_ids.join(',')}`"
                        display-name="name"
                        placeholder="Select Regional Director"   
                        icon-left="user"
                        label="Select Regional Director"
                        @change="onRegionalDirectorChange"
                    />
                </div>
                <div v-if="can('flm-t-report', 'area-manager')">
                    <DynamicDropdown
                        v-model="filters.areaManager"
                        :resource="`users?role=Area Manager&workgroups=${filters.workgroup_ids.join(',')}`"
                        display-name="name"
                        placeholder="Select Area Manager"
                        icon-left="user"
                        label="Select Area Manager"
                        @change="onAreaManagerChange"
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
                            @change="applyFilters"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <span>{{ workgroup.name }}</span>
                    </label>
                    <p v-if="!workgroups.length" class="text-sm text-gray-500 dark:text-gray-400">
                        No workgroups available
                    </p>
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
                        Apply Filters
                    </Button>
                    <Button
                        icon-left="upload"
                        icon-size="sm"
                        variant="secondary"
                        size="sm"
                        @click="exportReport"
                        :loading="exportLoading"
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
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10">
                        <tr>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('sr_no')">
                                    <span>Sr. No.</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('sr_no')" 
                                        :name="getSortDirection('sr_no') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
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
                                    <span>Food % </span>
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
                                    <span>Labour % </span>
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
                                    <span>Mileage % </span>
                                    <SvgIcon 
                                        v-if="getSortDirection('mileage_percentage')" 
                                        :name="getSortDirection('mileage_percentage') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('tpf')">
                                    <span>TPF</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('tpf')" 
                                        :name="getSortDirection('tpf') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('tpf_percentage')">
                                    <span>TPF %</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('tpf_percentage')" 
                                        :name="getSortDirection('tpf_percentage') === 'asc' ? 'chevron-up' : 'chevron-down'" 
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
                                    <span>Total FLM % </span>
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
                    <tbody class="bg-white dark:bg-gray-800" v-if="sortedFlmData.length > 0">
                        <tr v-for="(item, index) in sortedFlmData" :key="index" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700" @click="handleRowClick(item)">
                            <Td>{{ index + 1 }}</Td>
                            <Td>{{ item.company_name || 'N/A' }}</Td>
                            <Td>{{ formatNumber(item.sales) }}</Td>
                            <Td>{{ formatNumber(item.food) }}</Td>
                            <Td>{{ formatNumber(item.food_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.labour) }}</Td>
                            <Td>{{ formatNumber(item.labour_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.mileage) }}</Td>
                            <Td>{{ formatNumber(item.mileage_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.tpf) }}</Td>
                            <Td>{{ formatNumber(item.tpf_percentage) }}%</Td>
                            <Td>{{ formatNumber(item.flm) }}</Td>
                            <Td>{{ formatNumber(item.flm_percentage) }}%</Td>
                        </tr>
                        
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="13">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No FLM-T Data Found"
                                    message="There is no FLM-T data for the selected period. Please select a different date range or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                    action-text="Apply Filters"
                                    action-icon="refresh"
                                    @action="applyFilters"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Td colspan="2" weight="bold" color="primary">TOTALS</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.sales) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.food) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.food_percentage) }}%</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.labour) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.labour_percentage) }}%</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.mileage) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.mileage_percentage) }}%</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.tpf) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.tpf_percentage) }}%</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.flm) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.flm_percentage) }}%</Td>

                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ sortedFlmData.length }}</span> records
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Period: {{ currentPeriodLabel }}
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
import SvgIcon from '@/components/SvgIcon.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import { usePermission } from '@/composables/usePermission'
import { useAuthStore } from '@/stores/auth'
const message = useMessage()
const authStore = useAuthStore()
const {can} = usePermission()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    reportType: 'weekly',
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: []
})
const workgroups = ref([])
const reportTypeOptions = [
    { label: 'Weekly', value: 'weekly' },
    { label: 'Bi-weekly', value: 'bi-weekly' }
]
const isDC = computed(() => authStore.isDC || false)

// Available Years (current year and past 5 years)
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})
const summaryStats = computed(() => {
    return [
        {
            label: 'Total Sales',
            value: totals.value.sales,
            type: 'amount'
        },
        {
            label: 'Total Food',
            value: totals.value.food,
            type: 'amount'
        },
        {
            label: 'Total Food %',
            value: totals.value.food_percentage,
            type: 'percentage'
        },
        {
            label: 'Total Labour',
            value: totals.value.labour,
            type: 'amount'
        },
        {
            label: 'Total Labour %',
            value: totals.value.labour_percentage,
            type: 'percentage'
        },
        {
            label: 'Total Mileage',
            value: totals.value.mileage,
            type: 'amount'
        },
            {
            label: 'Total Mileage %',
            value: totals.value.mileage_percentage,
            type: 'percentage'
        },
        {
            label: 'Total TPF',
            value: totals.value.tpf,
            type: 'amount'
        },
        {
            label: 'Total TPF %',
            value: totals.value.tpf_percentage,
            type: 'percentage'
        },
        {
            label: 'Total FLM',
            value: totals.value.flm,
            type: 'amount'
        },
        {
            label: 'Total FLM %',
            value: totals.value.flm_percentage,
            type: 'percentage'
        }
    ]
})
const flmData = ref([])
const loading = ref(false)
const exportLoading = ref(false)

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc')
// Generate periods (weekly or bi-weekly) for the selected year
const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const isBiWeekly = filters.value.reportType === 'bi-weekly'
    const periods = []
    
    // Generate periods for the entire year using UTC to avoid timezone issues
    let startDate = new Date(Date.UTC(year, 0, 1)) // January 1st UTC
    
    // Adjust to start on Monday (Monday = 1, Sunday = 0)
    const dayOfWeek = startDate.getUTCDay()
    let daysToMonday = 0
    if (isBiWeekly) {
        // Match payroll bi-weekly offset behavior for DC vs non-DC.
        daysToMonday = isDC.value
            ? (dayOfWeek === 0 ? 0 : 1 - dayOfWeek)
            : (dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7)
    } else {
        daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
    }
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)
    
    let periodNumber = 1
    
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + (isBiWeekly ? 13 : 6))
        
        // Stop if we've gone too far into next year
        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }
        
        const formatDate = (date) => {
            const month = String(date.getUTCMonth() + 1).padStart(2, '0')
            const day = String(date.getUTCDate()).padStart(2, '0')
            const year = date.getUTCFullYear()
            return `${month}-${day}-${year}`
        }
        
        const label = `${formatDate(startDate)} To ${formatDate(endDate)}`
        const value = `${startDate.toISOString()} to ${endDate.toISOString()}`
        
        periods.push({ label, value, startDate: new Date(startDate), endDate: new Date(endDate) })
        
        // Move to next period
        startDate.setUTCDate(startDate.getUTCDate() + (isBiWeekly ? 14 : 7))
        periodNumber++
    }
    
    return periods
})

// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})


// Sorted data
const sortedFlmData = computed(() => {
    const data = [...flmData.value]
    
    if (!sortField.value) return data
    
    return data.sort((a, b) => {
        let aVal, bVal
        
        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store_number') {
            aVal = parseFloat(a.store_number || 0)
            bVal = parseFloat(b.store_number || 0)
        } else if (sortField.value === 'sales') {
            aVal = parseFloat(a.sales || 0)
            bVal = parseFloat(b.sales || 0)
        } else if (sortField.value === 'food') {
            aVal = parseFloat(a.food || 0)
            bVal = parseFloat(b.food || 0)
        } else if (sortField.value === 'food_percentage') {
            aVal = parseFloat(a.food_percentage || 0)
            bVal = parseFloat(b.food_percentage || 0)
        } else if (sortField.value === 'labour') {
            aVal = parseFloat(a.labour || 0)
            bVal = parseFloat(b.labour || 0)
        } else if (sortField.value === 'labour_percentage') {
            aVal = parseFloat(a.labour_percentage || 0)
            bVal = parseFloat(b.labour_percentage || 0)
        } else if (sortField.value === 'mileage') {
            aVal = parseFloat(a.mileage || 0)
            bVal = parseFloat(b.mileage || 0)
        } else if (sortField.value === 'mileage_percentage') {
            aVal = parseFloat(a.mileage_percentage || 0)
            bVal = parseFloat(b.mileage_percentage || 0)
        } else if (sortField.value === 'tpf') {
            aVal = parseFloat(a.tpf || 0)
            bVal = parseFloat(b.tpf || 0)
        } else if (sortField.value === 'tpf_percentage') {
            aVal = parseFloat(a.tpf_percentage || 0)
            bVal = parseFloat(b.tpf_percentage || 0)
        } else if (sortField.value === 'flm') {
            aVal = parseFloat(a.flm || 0)
            bVal = parseFloat(b.flm || 0)
        } else if (sortField.value === 'flm_percentage') {
            aVal = parseFloat(a.flm_percentage || 0)
            bVal = parseFloat(b.flm_percentage || 0)
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

const totals = computed(() => {
    return flmData.value.reduce((acc, item) => {
        acc.sales += parseFloat(item.sales ?? 0)
        acc.food += parseFloat(item.food ?? 0)
        acc.food_percentage = formatNumber(acc.sales > 0 ? (acc.food / acc.sales) * 100 : 0)
        acc.labour += parseFloat(item.labour ?? 0)
        acc.labour_percentage = formatNumber(acc.sales > 0 ? (acc.labour / acc.sales) * 100 : 0)
        acc.mileage += parseFloat(item.mileage ?? 0)
        acc.mileage_percentage = formatNumber(acc.sales > 0 ? (acc.mileage / acc.sales) * 100 : 0)
        acc.tpf += parseFloat(item.tpf ?? 0)
        acc.tpf_percentage = formatNumber(acc.sales > 0 ? (acc.tpf / acc.sales) * 100 : 0)
        acc.flm += parseFloat(item.flm ?? 0)
        acc.flm_percentage = formatNumber(acc.sales > 0 ? (acc.flm / acc.sales) * 100 : 0)
        return acc
    }, {
        amount: 0,
        sales: 0,
        food: 0,
        labour: 0,
        mileage: 0,
        tpf: 0,
        flm: 0,
        food_percentage: 0,
        labour_percentage: 0,
        mileage_percentage: 0,
        tpf_percentage: 0,
        flm_percentage: 0,
    })
})

// Sorting methods
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

// Methods
const onYearChange = () => {
    filters.value.selectedPeriod = ''
    if (availablePeriods.value.length > 0) {
        filters.value.selectedPeriod = availablePeriods.value[0].value
    }
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const onPeriodChange = () => {
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const onReportTypeChange = () => {
    filters.value.selectedPeriod = ''
    if (availablePeriods.value.length > 0) {
        filters.value.selectedPeriod = availablePeriods.value[0].value
    }
    applyFilters()
}

const onRegionalDirectorChange = () => {
    filters.value.areaManager = null
    applyFilters()
}

const onAreaManagerChange = () => {
    filters.value.regionalDirector = null
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true;
        const response = await useRequest('post', '/reports/food-cost/flm-t', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
            report_type: filters.value.reportType,
            user_id: filters.value.regionalDirector?.id || filters.value.areaManager?.id,
            workgroup_ids: filters.value.workgroup_ids
        })
        flmData.value = response
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally{
        loading.value = false
    }
}

const loadWorkgroups = async () => {
    const response = await useRequest('get', '/search/workgroups?query=&column=name')
    const collection = response?.collection ?? []
    workgroups.value = collection
    filters.value.workgroup_ids = collection.map((item) => item.id)
}


const exportReport = async () => {
    try {
        // Check if there's data to export
        if (!flmData.value || flmData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['FLM-T Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            if (filters.value.regionalDirector) {
                exportData.push(['Regional Director:', filters.value.regionalDirector.name])
            }
            if (filters.value.areaManager) {
                exportData.push(['Area Manager:', filters.value.areaManager.name])
            }
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Sr. No.',
                'Store Name',
                'Sales',
                'Food',
                'Food %',
                'Labour',
                'Labour %',
                'Mileage',
                'Mileage %',
                'TPF',
                'TPF %',
                'Total FLM',
                'Total FLM %',
            ])
            
            // Add data rows
            flmData.value.forEach((item, index) => {
                exportData.push([
                    index + 1,
                    item.company_name || '',
                    parseFloat(item.sales || 0),
                    parseFloat(item.food || 0),
                    parseFloat(item.food_percentage || 0),
                    parseFloat(item.labour || 0),
                    parseFloat(item.labour_percentage || 0),
                    parseFloat(item.mileage || 0),
                    parseFloat(item.mileage_percentage || 0),
                    parseFloat(item.tpf || 0),
                    parseFloat(item.tpf_percentage || 0),
                    parseFloat(item.flm || 0),
                    parseFloat(item.flm_percentage || 0),
                ])
            })
            
            // Add totals row
            exportData.push([])
            exportData.push([
                '',  'TOTALS',
                parseFloat(totals.value.sales || 0),
                parseFloat(totals.value.food || 0),
                parseFloat(totals.value.food_percentage || 0),
                parseFloat(totals.value.labour || 0),
                parseFloat(totals.value.labour_percentage || 0),
                parseFloat(totals.value.mileage || 0),
                parseFloat(totals.value.mileage_percentage || 0),
                parseFloat(totals.value.tpf || 0),
                parseFloat(totals.value.tpf_percentage || 0),
                parseFloat(totals.value.flm || 0),
                parseFloat(totals.value.flm_percentage || 0),
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 10 },  // Sr. No.
                { wch: 25 },  // Store Name
                { wch: 15 },  // Sales
                { wch: 15 },  // Food
                { wch: 15 },  // Food %
                { wch: 15 },  // Labour
                { wch: 15 },  // Labour %
                { wch: 15 },  // Mileage
                { wch: 15 },  // Mileage %
                { wch: 15 },  // TPF
                { wch: 15 },  // TPF %
                { wch: 15 },  // Total FLM
                { wch: 15 },  // Total FLM %
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'FLM-T Report')
            
            // Generate filename
            const filename = `FLM-T_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
            // Save file
            XLSX.writeFile(wb, filename)
            
            message.success('Report exported successfully!')
        }).catch((error) => {
            console.error('Failed to load XLSX library:', error)
            message.error('Failed to export report. Please try again.')
        }).finally(() => {
            exportLoading.value = false
        })
    } catch (error) {
        console.error('Export error:', error)
        message.error('An error occurred while exporting the report')
        exportLoading.value = false
    }
}

const setDefaultPeriod = () => {
    filters.value.reportType = isDC.value ? 'bi-weekly' : 'weekly'
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
}
onMounted(async () => {
    await loadWorkgroups()
    setDefaultPeriod()
    applyFilters()
})

</script>
