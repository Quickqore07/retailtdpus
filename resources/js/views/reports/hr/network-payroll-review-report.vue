<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Network Payroll Review Report       
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze payroll review data for the selected period
                </p>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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
                <div v-if="can('network-payroll-review-report', 'regional-director')">
                    <DynamicDropdown
                        v-model="filters.regionalDirector"
                        :resource="`users?role=Regional Director`"
                        display-name="name"
                        placeholder="Select Regional Director"
                        icon-left="user"
                        label="Select Regional Director"
                        @change="onRegionalDirectorChange"
                    />
                </div>
                <div v-if="can('network-payroll-review-report', 'area-manager')">
                    <DynamicDropdown
                        v-model="filters.areaManager"
                        :resource="`users?role=Area Manager`"
                        display-name="name"
                        placeholder="Select Area Manager"
                        icon-left="user"
                        label="Select Area Manager"
                        @change="onAreaManagerChange"
                    />
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

        <div class="grid  xl:grid-cols-8 gap-4 mb-6">
            <div class="xl:col-span-6 ">
                <MultiStatCard
                    label="Summary"
                    :items="DataSummaryStats"
                    icon="chart"
                    icon-color="indigo"
                    format-type="number"
                />
            </div>

            <div class="xl:col-span-2">
                <MultiStatCard
                    label="Payment"
                    :items="paymentMethodsStats"
                    icon="dollar"
                    icon-color="indigo"
                    description="Distribution by method"
                    format-type="number"
                />
            </div>
        </div>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">Sr. No.</Th>
                            <Th class="md:sticky md:left-12 bg-white dark:bg-gray-900 !z-[101]" >
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('store_number')">
                                    <span>Store Number</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('store_number')" 
                                        :name="getSortDirection('store_number') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-33 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('name')">
                                    <span>Store Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('name')" 
                                        :name="getSortDirection('name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('regular_hours')">
                                    <span>Regular Hours</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('regular_hours')" 
                                        :name="getSortDirection('regular_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('overtime_hours')">
                                    <span>Overtime Hours</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('overtime_hours')" 
                                        :name="getSortDirection('overtime_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('total_hours')">
                                    <span>Total Hours</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('total_hours')" 
                                        :name="getSortDirection('total_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('gross_pay')">
                                    <span>Net Payroll</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('gross_pay')" 
                                        :name="getSortDirection('gross_pay') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('tips')">
                                    <span>Tips</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('tips')" 
                                        :name="getSortDirection('tips') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mwa_amount')">
                                    <span>MWA Amount</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('mwa_amount')" 
                                        :name="getSortDirection('mwa_amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mileage_excess')">
                                    <span>Milage</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('mileage_excess')" 
                                        :name="getSortDirection('mileage_excess') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('tips_due')">
                                    <span>Tips Due</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('tips_due')" 
                                        :name="getSortDirection('tips_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mileage_due')">
                                    <span>Milage Due</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('mileage_due')" 
                                        :name="getSortDirection('mileage_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('total_earnings')">
                                    <span>Gross</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('total_earnings')" 
                                        :name="getSortDirection('total_earnings') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('payroll_methods')">
                                    <span>Payroll</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('payroll_methods')" 
                                        :name="getSortDirection('payroll_methods') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-if="!isDC">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('check_methods')">
                                    <span>Check</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('check_methods')" 
                                        :name="getSortDirection('check_methods') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-if="!isDC">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('instant_methods')">
                                    <span>Instant</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('instant_methods')" 
                                        :name="getSortDirection('instant_methods') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-if="!isDC">Status</Th>    
                            <Th v-if="!isDC">Reviewed By</Th>
                            <Th v-if="!isDC">Reviewed At</Th>
                           
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800" v-if="sortedPayrollData.length > 0">
                                <tr 
                                    v-for="(item, index) in sortedPayrollData" 
                                    :key="index"
                                    class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <Td class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[100]">{{ index + 1 }}</Td>
                                    <Td class="md:sticky md:left-12 bg-white dark:bg-gray-900 !z-[100]">{{ item.store_number }}</Td>
                                    <Td class="md:sticky md:left-33 bg-white dark:bg-gray-900 !z-[100]">{{ item.name }}</Td>
                                    <Td>{{ formatNumber(item.regular_hours) }} hrs</Td>
                                    <Td>{{ formatNumber(item.overtime_hours) }} hrs</Td>
                                    <Td>{{ formatNumber(item.total_hours) }} hrs</Td>
                                    <Td>{{ formatNumber(item.gross_pay) }}</Td>
                                    <Td>{{ formatNumber(item.tips) }}</Td>
                                    <Td>{{ formatNumber(item.mwa_amount) }}</Td>
                                    <Td>{{ formatNumber(item.mileage_excess) }}</Td>
                                    <Td>{{ formatNumber(item.tips_due) }}</Td>
                                    <Td>{{ formatNumber(item.mileage_due) }}</Td>
                                    <Td>{{ formatNumber(item.total_earnings) }}</Td>
                                    <Td>{{ formatNumber(item.payroll_methods) }}</Td>
                                    <Td v-if="!isDC">{{ formatNumber(item.check_methods) }}</Td>
                                    <Td v-if="!isDC">{{ formatNumber(item.instant_methods) }}</Td>
                                    <Td v-if="!isDC"> <span :class="{ 'text-green-600 dark:text-green-400': item.status === 'Reviewed', 'text-red-600 dark:text-red-400': item.status === 'Pending' }">{{ item.status }}</span></Td>
                                    <Td v-if="!isDC">{{ item.review_by ? item.review_by : '-' }}</Td>
                                    <Td v-if="!isDC">{{ item.reviewed_at ? formatDateTime(item.reviewed_at) : '-' }}</Td>
                                </tr>
                        
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="isDC ? 14 : 19">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Payroll Data Found"
                                    message="There is no payroll data for the selected period. Please select a different date range or ensure data has been entered."
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
                            <Td colspan="2" weight="bold" color="primary" class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800"></Td>
                            <Td  weight="bold" color="primary" class="md:sticky md:left-33 z-10 bg-gray-100 dark:bg-gray-800">TOTALS</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.regularHours) }} hrs</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.overtimeHours) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.totalHours) }} hrs</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.grossPay) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.tips) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.mwa_amount) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.mileage) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.tips_due) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.mileage_due) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.totalEarnings) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.payrollMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary">${{ formatNumber(totals.checkMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary">${{ formatNumber(totals.instantMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary"> -</Td>
                            <Td v-if="!isDC" weight="bold" color="primary"> -</Td>
                            <Td v-if="!isDC" weight="bold" color="primary"> -</Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ payrollData.length }}</span> records
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
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/date'
import { usePermission } from '@/composables/usePermission'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'

const message = useMessage()
const authStore = useAuthStore()
const isDC = computed(() => {
    return authStore.isDC || false
})
const { can } = usePermission()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    company: '',
    status: '',
    user_id: null,
    regionalDirector: null,
    areaManager: null,
})

// Available Years (current year and past 5 years)
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})
const payrollData = ref([])
const loading = ref(false)
const exportLoading = ref(false)

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc')

// Generate bi-weekly periods (Monday to Sunday) for the selected year
const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const periods = []
    
    // Generate bi-weekly periods for the entire year using UTC to avoid timezone issues
    let startDate = new Date(Date.UTC(year, 0, 1)) // January 1st UTC
    
    // Adjust to start on Monday (Monday = 1, Sunday = 0)
    const dayOfWeek = startDate.getUTCDay()
    let daysToMonday = 0;
    if(isDC.value){
        daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek // If Sunday, go back 6 days; otherwise go to previous/current Monday
    }else{
        daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7 // If Sunday, go back 6 days; otherwise go to previous/current Monday
    }
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)
    
    let periodNumber = 1
    
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13) // 14 days (Monday to Sunday of second week)
        
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
        
        // Move to next bi-weekly period (14 days)
        startDate.setUTCDate(startDate.getUTCDate() + 14)
        periodNumber++
    }
    
    return periods
})

const DataSummaryStats = computed(() => {
    return [
        {
            label: 'Total Stores',
            value: payrollData.value.length
        },
        {
            label: 'Toal Regular Hours',
            value: totals.value.regularHours
        },
        {
            label: 'Total Overtime Hours',
            value: totals.value.overtimeHours
        },
        {
            label: 'Total Hours',
            value: totals.value.totalHours
        },
        { 
            label: 'Net Payroll',
            value: totals.value.grossPay,
            type: 'amount'
        },
        {
            label: 'Total tips',
            value: totals.value.tips,
            type: 'amount'
        },
        {
            label: 'Total MWA Amount',
            value: totals.value.mwa_amount,
            type: 'amount'
        },
        {
            label: 'Total mileage',
            value: totals.value.mileage,
            type: 'amount'
        },
        {
            label: 'Total tips due',
            value: totals.value.tips_due,
            type: 'amount'
        },

        {
            label: 'Total mileage due',
            value: totals.value.mileage_due,
            type: 'amount'
        },
       
        {
            label: 'Gross',
            value: totals.value.totalEarnings,
            type: 'amount'
        },
    ]
})
// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})

const onRegionalDirectorChange = (value) => {
    filters.value.areaManager = null
    filters.value.user_id = value.id
    applyFilters()
}

const onAreaManagerChange = (value) => {
    filters.value.regionalDirector = null
    filters.value.user_id = value.id
    applyFilters()
}

// Sorted data
const sortedPayrollData = computed(() => {
    const data = [...payrollData.value]
    
    if (!sortField.value) return data
    
    return data.sort((a, b) => {
        let aVal, bVal
        
        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store_number') {
            aVal = parseFloat(a.store_number || 0)
            bVal = parseFloat(b.store_number || 0)
        } else if (sortField.value === 'name') {
            aVal = (a.name || '').toLowerCase()
            bVal = (b.name || '').toLowerCase()
        } else {
            aVal = parseFloat(a[sortField.value] || 0)
            bVal = parseFloat(b[sortField.value] || 0)
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
    return payrollData.value.reduce((acc, item) => {
            acc.totalHours += parseFloat(item.total_hours ?? 0)
            acc.regularHours += parseFloat(item.regular_hours ?? 0)
            acc.overtimeHours += parseFloat(item.overtime_hours ?? 0)
            acc.grossPay += parseFloat(item.gross_pay ?? 0)
            acc.tips += parseFloat(item.tips ?? 0)
            acc.mwa_amount += parseFloat(item.mwa_amount ?? 0)
            acc.mileage += parseFloat(item.mileage_excess ?? 0)
            acc.tips_due += parseFloat(item.tips_due ?? 0)
            acc.mileage_due += parseFloat(item.mileage_due ?? 0)
            acc.totalEarnings += parseFloat(item.total_earnings ?? 0)
            acc.payrollMethods += parseFloat(item.payroll_methods ?? 0)
            acc.checkMethods += parseFloat(item.check_methods ?? 0)
            acc.instantMethods += parseFloat(item.instant_methods ?? 0)
        return acc
    }, {
        totalHours: 0,
        regularHours: 0,
        overtimeHours: 0,
        grossPay: 0,
        tips: 0,
        mwa_amount: 0,
        mileage: 0,
        tips_due: 0,
        mileage_due: 0,
        totalEarnings: 0,
        payrollMethods: 0,
        checkMethods: 0,
        instantMethods: 0
    })
})


const paymentMethodsStats = computed(() => {
    return [
        {
            label: 'Payroll',
            value: totals.value.payrollMethods,
            type: 'amount'
        },  
        ...(isDC.value ? [] : [{
            label: 'Check',
            value: totals.value.checkMethods,
            type: 'amount'
        }]),
        ...(isDC.value ? [] : [{
            label: 'Instant',
            value: totals.value.instantMethods,
            type: 'amount'
        }]),
    ]
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

const applyFilters = async () => {
    try {
        loading.value = true;
        const response = await useRequest('post', '/reports/hr/network-summary', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
            user_id: filters.value.user_id,
            is_payroll_review: true,
        })
        payrollData.value = response.data
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally{
        loading.value = false
    }
}


const exportReport = async () => {
    try {
        // Check if there's data to export
        if (!payrollData.value || payrollData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['Network Payroll Review Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            if(filters.value.regionalDirector){
                exportData.push(['Regional Director:', filters.value.regionalDirector.name])
            }
            if(filters.value.areaManager){
                exportData.push(['Area Manager:', filters.value.areaManager.name])
            }
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Sr. No.',
                'Store Number',
                'Store Name',
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Net Payroll',
                'Tips',
                'MWA Amount',
                'Milage',
                'Tips Due',
                'Milage Due',
                'Gross',
                'Payroll',
                ...(isDC.value ? [] : ['Check']),
                ...(isDC.value ? [] : ['Instant']),
                ...(isDC.value ? [] : ['Status']),
                ...(isDC.value ? [] : ['Reviewed By']),
                ...(isDC.value ? [] : ['Reviewed At']),
            ])
            
            // Process store data
            payrollData.value.forEach((item, index) => {
                exportData.push([
                    index + 1,
                    item.store_number || '',
                    item.name || '',
                    parseFloat(item.regular_hours || 0),
                    parseFloat(item.overtime_hours || 0),
                    parseFloat(item.total_hours || 0),
                    parseFloat(item.gross_pay || 0),
                    parseFloat(item.tips || 0),
                    parseFloat(item.mwa_amount || 0),
                    parseFloat(item.mileage_excess || 0),
                    parseFloat(item.tips_due || 0),
                    parseFloat(item.mileage_due || 0),
                    parseFloat(item.total_earnings || 0),
                    parseFloat(item.payroll_methods || 0),
                    ...(isDC.value ? [] : [parseFloat(item.check_methods || 0)]),
                    ...(isDC.value ? [] : [parseFloat(item.instant_methods || 0)]),
                    ...(isDC.value ? [] : [item.status]),
                    ...(isDC.value ? [] : [item.review_by ? item.review_by.name : '-']),
                    ...(isDC.value ? [] : [item.reviewed_at ? formatDateTime(item.reviewed_at) : '-']),
                ])
            })
            
            // Add grand totals
            exportData.push([])
            exportData.push([
                '', '', 'TOTALS',
                totals.value.regularHours,
                totals.value.overtimeHours,
                totals.value.totalHours,
                totals.value.grossPay,
                totals.value.tips,
                totals.value.mwa_amount,
                totals.value.mileage,
                totals.value.tips_due,
                totals.value.mileage_due,
                totals.value.totalEarnings,
                totals.value.payrollMethods,
                ...(isDC.value ? [] : [totals.value.checkMethods]),
                ...(isDC.value ? [] : [totals.value.instantMethods]),
                ...(isDC.value ? [] : ['-']),
                ...(isDC.value ? [] : ['-']),
                ...(isDC.value ? [] : ['-']),
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 10 },  // Sr. No.
                { wch: 15 },  // Store Number
                { wch: 25 },  // Store Name
                { wch: 14 },  // Regular Hours
                { wch: 14 },  // Overtime Hours
                { wch: 12 },  // Total Hours
                { wch: 12 },  // Gross Pay
                { wch: 10 },  // Tips
                { wch: 10 },  // MWA Amount
                { wch: 10 },  // Milage
                { wch: 10 },  // Tips Due
                { wch: 12 },  // Milage Due
                { wch: 14 },  // Total Earnings
                { wch: 12 },  // Payroll
                ...(isDC.value ? [] : [{ wch: 12 }]),  // Check
                ...(isDC.value ? [] : [{ wch: 12 }]),  // Instant
                ...(isDC.value ? [] : [{ wch: 12 }]),  // Status
                ...(isDC.value ? [] : [{ wch: 12 }]),  // Reviewed By
                ...(isDC.value ? [] : [{ wch: 12 }]),  // Reviewed At
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Network Payroll Review Report')
            
            // Generate filename
            const filename = `Network_Payroll_Review_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
}
onMounted(() => {
    setDefaultPeriod()
    applyFilters()
})

</script>
