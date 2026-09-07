<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Paychex Report
                </h3>   
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze Paychex data for the selected period
                </p>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th rowspan="2" class="md:sticky md:left-0 !z-[101] min-w-[50px]">
                                <div class="flex items-center gap-2">
                                    <span>Sr. No.</span>
                                </div>
                            </Th>
                            <Th rowspan="2" class="md:sticky  md:left-16 !z-[101] min-w-[200px]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('store_name')">
                                    <span>Store Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('store_name')" 
                                        :name="getSortDirection('store_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th rowspan="2" class="md:sticky md:left-60 !z-[101] min-w-[200px]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('employee_name')">
                                    <span>Employee Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('employee_name')" 
                                        :name="getSortDirection('employee_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th rowspan="2" class="md:sticky md:left-110 !z-[101] min-w-[200px]">Role</Th>
                            <Th rowspan="2">Regular Hours</Th>
                            <Th rowspan="2">Overtime Hours</Th>
                            <Th rowspan="2">Total Hours</Th>
                            <Th rowspan="2">Rate</Th>
                            <Th rowspan="2">Gross Pay</Th>
                            <Th rowspan="2">Tips</Th>
                            <Th rowspan="2">MWA Amount</Th>
                            <Th rowspan="2">Tips Due</Th>
                            <Th rowspan="2">Mileage Due</Th>
                            <Th rowspan="2">Total Earnings</Th>
                            <Th rowspan="2">HR Pay</Th>
                            <Th colspan="2" customClass="!text-center">
                                <div class="flex justify-center items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('amount')">
                                    <span>Payroll Amount</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('amount')" 
                                        :name="getSortDirection('amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                        </tr>
                        <tr>
                            <Th>Direct Deposit</Th>
                            <Th>Print on site</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="groupedPayrollData.length > 0">
                        <template v-for="(group, groupIndex) in groupedPayrollData" :key="groupIndex">
                            <!-- Company Header Row -->
                            <tr class="bg-blue-50 dark:bg-blue-900/20 border-t-2 border-blue-300 dark:border-blue-700">
                                <Td colspan="3" weight="bold" color="primary" class="md:sticky left-0 bg-blue-50 dark:bg-blue-900/20 !z-[100]"> {{ group.companyName }}</Td>
                                <Td  weight="bold" color="primary" class="md:sticky left-110 bg-blue-50 dark:bg-blue-900/20 !z-[100]"></Td>

                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_regular_hours) }} hrs</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_overtime_hours) }} hrs</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_hours) }} hrs</Td>
                                <Td weight="bold" color="primary"> -</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_gross_pay) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_tips) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_mwa_amount) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_tips_due) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_mileage_due) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_earnings) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber((parseFloat(group.total_gross_pay) + parseFloat(group.total_tips)) / parseFloat(group.total_hours) || 0) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_direct_deposit) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_print_on_site) }}</Td>
                            </tr>
                            <!-- Employee Rows for this Company -->
                            <tr v-for="(item, index) in group.items" :key="`${groupIndex}-${index}`" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700" >
                                <Td class="md:sticky left-0 bg-white dark:bg-gray-900 !z-[100] min-w-[50px]">
                                        {{ item.globalIndex }}
                                </Td>
                                <Td class="md:sticky left-16 bg-white dark:bg-gray-900 !z-[100] min-w-[200px]">{{ item.company?.name || 'N/A' }}</Td>
                                <Td class="md:sticky left-60 bg-white dark:bg-gray-900 !z-[100] min-w-[200px]">
                                    <a :href="`/employee/${item.employee?.id}`" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ item.employee_name || item.employee?.pos_name || 'N/A' }}</a>
                                </Td>
                                <Td class="md:sticky left-110 bg-white dark:bg-gray-900 !z-[100] min-w-[200px]">{{ item.role?.name || 'N/A' }}</Td>
                                <Td>{{ formatNumber(item.regular_hours) }}</Td>
                                <Td>{{ formatNumber(item.overtime_hours) }}</Td>
                                <Td>{{ formatNumber(item.total_hours) }}</Td>
                                <Td>{{ formatNumber(item.employee_rate) }}</Td>
                                <Td>{{ formatNumber(item.gross_pay) }}</Td>
                                <Td>{{ formatNumber(item.tips) }}</Td>
                                <Td>{{ formatNumber(item.mwa_amount) }}</Td>
                                <Td>{{ formatNumber(item.tips_due) }}</Td>
                                <Td>{{ formatNumber(item.mileage_due) }}</Td>
                                <Td>{{ formatNumber(item.total_earnings) }}</Td>
                                <Td>{{ formatNumber((parseFloat(item.gross_pay) + parseFloat(item.tips)) / parseFloat(item.total_hours) || 0) }}</Td>
                                <!-- <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''"
                                        :title="renderMinWageTitle(item)"
                                    >
                                    ${{ formatNumber(item.amount) }}
                                </Td> -->
                                <Td>{{ item.direct_deposit || 'N/A' }}</Td>
                                <Td>{{ item.print_on_site || 'N/A' }}</Td>
                            </tr>
                        </template>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="16">
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
                            <Td  weight="bold" color="primary" class="md:sticky left-0 bg-gray-50 dark:bg-gray-900 !z-[100]">TOTALS</Td>
                            <Td weight="bold" color="primary" class="md:sticky left-17 bg-gray-50 dark:bg-gray-900 !z-[100]"></Td>
                            <Td weight="bold" color="primary" class="md:sticky left-60 bg-gray-50 dark:bg-gray-900 !z-[100]"></Td>
                            <Td weight="bold" color="primary" class="md:sticky left-110 bg-gray-50 dark:bg-gray-900 !z-[100]"></Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.regular_hours) }} hrs</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.overtime_hours) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total_hours) }} hrs</Td>
                            <Td weight="bold" color="primary">-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.gross_pay) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.tips) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.mwa_amount) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.tips_due) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.mileage_due) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total_earnings) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.hr_pay) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total_direct_deposit) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total_print_on_site) }}</Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ payrollData.length }}</span> records across <span class="font-medium">{{ groupedPayrollData.length }}</span> companies
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

const selectAll = ref(false)

const message = useMessage()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    company: '',
    status: ''
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
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7 // If Sunday, go back 6 days; otherwise go to previous/current Monday
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


const renderMinWageTitle = computed(() => {
    return (item) => {
        if(item.tipped){
            return 'Minimum tipped wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum tipped wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly)
        }else{
            return 'Minimum wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly)
        }
    }
})
// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})



// Group data by company
const groupedPayrollData = computed(() => {
    const data = [...payrollData.value]
    const groups = {}
    
    // First, group all items by company
    data.forEach(item => {
        const companyKey = item.company?.id || 'unknown'
        const companyName = item.company?.name 
            ? `${item.company.name}` 
            : 'Unknown Company'
        
        if (!groups[companyKey]) {
            groups[companyKey] = {
                companyName,
                companyId: companyKey,
                items: [],
                total_regular_hours: 0,
                total_overtime_hours: 0,
                total_hours: 0,
                total_gross_pay: 0,
                total_tips: 0,
                total_mwa_amount: 0,
                total_tips_due: 0,
                total_mileage_due: 0,
                total_earnings: 0,
                total_hr_pay: 0,
                total_amount: 0,
                total_direct_deposit: 0,
                total_print_on_site: 0,
                sortKey: item.company?.name || 'Unknown Company'
            }
        }
        item.direct_deposit = item.payroll_type === 'Direct Deposit' ? parseFloat(item.amount)  : '-';
        item.print_on_site = item.payroll_type === 'Print on site' ? parseFloat(item.amount) :'-';
        groups[companyKey].items.push(item)
        groups[companyKey].total_regular_hours += parseFloat(item.regular_hours || 0)
        groups[companyKey].total_overtime_hours += parseFloat(item.overtime_hours || 0)
        groups[companyKey].total_hours += parseFloat(item.total_hours || 0)
        groups[companyKey].total_gross_pay += parseFloat(item.gross_pay || 0)
        groups[companyKey].total_tips += parseFloat(item.tips || 0)
        groups[companyKey].total_mwa_amount += parseFloat(item.mwa_amount || 0)
        groups[companyKey].total_tips_due += parseFloat(item.tips_due || 0)
        groups[companyKey].total_mileage_due += parseFloat(item.mileage_due || 0)
        groups[companyKey].total_earnings += parseFloat(item.total_earnings || 0)
        groups[companyKey].total_hr_pay += parseFloat((parseFloat(item.gross_pay) + parseFloat(item.tips)) / parseFloat(item.total_hours) || 0)
        groups[companyKey].total_amount += parseFloat(item.amount || 0)
        groups[companyKey].total_direct_deposit += parseFloat(item.direct_deposit === '-' ? 0 : item.direct_deposit || 0)
        groups[companyKey].total_print_on_site += parseFloat(item.print_on_site === '-' ? 0 : item.print_on_site || 0)
    })
    
    // Convert to array
    let groupArray = Object.values(groups)
    
    // Sort groups by company name if sorting by store_name, otherwise alphabetically
    if (sortField.value === 'store_name') {
        groupArray.sort((a, b) => {
            const comparison = a.sortKey.localeCompare(b.sortKey)
            return sortDirection.value === 'asc' ? comparison : -comparison
        })
    } else {
        // Default alphabetical sort by company name
        groupArray.sort((a, b) => a.sortKey.localeCompare(b.sortKey))
    }
    
    // Sort items within each group
    groupArray.forEach(group => {
        if (sortField.value && sortField.value !== 'store_name') {
            group.items.sort((a, b) => {
                let aVal, bVal
                
                if (sortField.value === 'employee_name') {
                    aVal = (a.employee?.pos_name || '').toLowerCase()
                    bVal = (b.employee?.pos_name || '').toLowerCase()
                } else if (sortField.value === 'amount') {
                    aVal = parseFloat(a.amount || 0)
                    bVal = parseFloat(b.amount || 0)
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
        }
    })
    
    // Add global index to all items
    let globalIndex = 1
    groupArray.forEach(group => {
        group.items.forEach(item => {
            item.globalIndex = globalIndex++
        })
    })
    
    return groupArray
})

const totals = computed(() => {
    return payrollData.value.reduce((acc, item) => {
        acc.amount += parseFloat(item.amount ?? 0)
        acc.regular_hours += parseFloat(item.regular_hours ?? 0)
        acc.overtime_hours += parseFloat(item.overtime_hours ?? 0)
        acc.total_hours += parseFloat(item.total_hours ?? 0)
        acc.gross_pay += parseFloat(item.gross_pay ?? 0)
        acc.tips += parseFloat(item.tips ?? 0)
        acc.mwa_amount += parseFloat(item.mwa_amount ?? 0)
        acc.tips_due += parseFloat(item.tips_due ?? 0)
        acc.mileage_due += parseFloat(item.mileage_due ?? 0)
        acc.total_earnings += parseFloat(item.total_earnings ?? 0)
        acc.hr_pay += parseFloat((parseFloat(item.gross_pay) + parseFloat(item.tips)) / parseFloat(item.total_hours) ?? 0)
        acc.total_direct_deposit += item.payroll_type === 'Direct Deposit' ? parseFloat(item.amount ?? 0) : 0
        acc.total_print_on_site += item.payroll_type === 'Print on site' ? parseFloat(item.amount ?? 0) : 0
        return acc
    }, {
        amount: 0,
        regular_hours: 0,
        overtime_hours: 0,
        total_hours: 0,
        gross_pay: 0,
        tips: 0,
        mwa_amount: 0,
        tips_due: 0,
        mileage_due: 0,
        total_earnings: 0,
        hr_pay: 0,
        total_direct_deposit: 0,
        total_print_on_site: 0,
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
    payrollData.value=[];
    selectAll.value = false;
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true;
        selectAll.value = false;
        const response = await useRequest('post', '/reports/payroll/paychex', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
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
            // Prepare data for export (columns match table: Direct Deposit & Print on site)
            const exportData = []
            
            // Add header information
            exportData.push(['Paychex Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers (match table: Direct Deposit, Print on site)
            exportData.push([
                'Sr. No.',
                'Store Name',
                'Employee Name',
                'Role',
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Rate',
                'Gross Pay',
                'Tips',
                'MWA Amount',
                'Tips Due',
                'Mileage Due',
                'Total Earnings',
                'HR Pay',
                'Direct Deposit',
                'Print on site'
            ])
            
            // Add data rows (use same direct_deposit/print_on_site logic as table)
            payrollData.value.forEach((item, index) => {
                const directDeposit = item.payroll_type === 'Direct Deposit' ? parseFloat(item.amount || 0) : '-'
                const printOnSite = item.payroll_type === 'Print on site' ? parseFloat(item.amount || 0) : '-'
                const hrPay = parseFloat((parseFloat(item.gross_pay || 0) + parseFloat(item.tips || 0)) / parseFloat(item.total_hours || 1) || 0)
                exportData.push([
                    index + 1,
                    item.company?.name ?? '',
                    item.employee?.pos_name ?? item.employee_name ?? '',
                    item.role?.name ?? '',
                    parseFloat(item.regular_hours || 0),
                    parseFloat(item.overtime_hours || 0),
                    parseFloat(item.total_hours || 0),
                    parseFloat(item.employee_rate || 0),
                    parseFloat(item.gross_pay || 0),
                    parseFloat(item.tips || 0),
                    parseFloat(item.mwa_amount || 0),
                    parseFloat(item.tips_due || 0),
                    parseFloat(item.mileage_due || 0),
                    parseFloat(item.total_earnings || 0),
                    hrPay,
                    directDeposit,
                    printOnSite
                ])
            })
            
            // Add totals row (match table footer: total_direct_deposit, total_print_on_site)
            exportData.push([])
            exportData.push([
                '', '', '', 'TOTALS',
                parseFloat(totals.value.regular_hours || 0),
                parseFloat(totals.value.overtime_hours || 0),
                parseFloat(totals.value.total_hours || 0),
                '',
                parseFloat(totals.value.gross_pay || 0),
                parseFloat(totals.value.tips || 0),
                parseFloat(totals.value.mwa_amount || 0),
                parseFloat(totals.value.tips_due || 0),
                parseFloat(totals.value.mileage_due || 0),
                parseFloat(totals.value.total_earnings || 0),
                parseFloat(totals.value.hr_pay || 0),
                parseFloat(totals.value.total_direct_deposit || 0),
                parseFloat(totals.value.total_print_on_site || 0)
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 10 },  // Sr. No.
                { wch: 25 },  // Store Name
                { wch: 25 },  // Employee Name
                { wch: 15 },  // Role
                { wch: 15 },  // Regular Hours
                { wch: 15 },  // Overtime Hours
                { wch: 15 },  // Total Hours
                { wch: 12 },  // Rate
                { wch: 15 },  // Gross Pay
                { wch: 12 },  // Tips
                { wch: 12 },  // MWA Amount
                { wch: 12 },  // Tips Due
                { wch: 15 },  // Mileage Due
                { wch: 15 },  // Total Earnings
                { wch: 12 },  // HR Pay
                { wch: 15 },  // Direct Deposit
                { wch: 15 }   // Print on site
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Paychex Report')
            
            // Generate filename
            const filename = `Paychex_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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