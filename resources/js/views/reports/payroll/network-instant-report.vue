<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Network Instant Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze network instant data for the selected period
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
                            <Th>
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
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('amount')">
                                    <span>Amount</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('amount')" 
                                        :name="getSortDirection('amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>From store</Th>
                            <Th>Instant</Th>
                            <Th>Date</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800" v-if="sortedPayrollData.length > 0">
                        <tr v-for="(item, index) in sortedPayrollData" :key="index" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700" @click="handleRowClick(item)">
                            <Td>{{ index + 1 }}</Td>
                            <Td>{{ item.company?.name || 'N/A' }}</Td>
                            <Td>{{ item.employee?.pos_name }}</Td>
                            <Td>{{ formatNumber(item.amount) }}</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                        </tr>
                        
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="7">
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
                            <Td colspan="3" weight="bold" color="primary">TOTALS</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.amount) }}</Td>
                            <Td colspan="3" weight="bold" color="primary"></Td>

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
import { mmddyyyyToYmd } from '@/utils/date'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import { usePermission } from '@/composables/usePermission'
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

// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})


// Sorted data
const sortedPayrollData = computed(() => {
    const data = [...payrollData.value]
    
    if (!sortField.value) return data
    
    return data.sort((a, b) => {
        let aVal, bVal
        
        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store_name') {
            aVal = (a.company?.name ? a.company?.name : '').toLowerCase()
            bVal = (b.company?.name ? b.company?.name : '').toLowerCase()
        } else if (sortField.value === 'employee_name') {
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
})

const totals = computed(() => {
    return payrollData.value.reduce((acc, item) => {
        acc.amount += parseFloat(item.amount ?? 0)
        return acc
    }, {
        amount: 0,
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

const handleRowClick = (item) => {
    if(item.employee && item.employee.employee_type == 'New'){
        window.open(`/onboarding/employee/new/${item.employee_id}`, '_blank')
    }else if(item.employee && item.employee.employee_type == 'Existing'){
        window.open(`/onboarding/employee/existing/${item.employee_id}`, '_blank')
    }else {
        window.open(`/employee/${item.employee_id}`, '_blank')
    }
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
        const response = await useRequest('post', '/reports/payroll/network-instant', {
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
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['Network Instant Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Sr. No.',
                'Store Name',
                'Employee Name',
                'Amount',
                'From Store',
                'Instant',
                'Date'
            ])
            
            // Add data rows
            payrollData.value.forEach((item, index) => {
                exportData.push([
                    index + 1,
                    item.company?.name || '',
                    item.employee?.pos_name || '',
                    parseFloat(item.amount || 0),
                    '-',
                    '-',
                    '-'
                ])
            })
            
            // Add totals row
            exportData.push([])
            exportData.push([
                '', '', 'TOTALS',
                parseFloat(totals.value.amount || 0),
                '', '', ''
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 10 },  // Sr. No.
                { wch: 25 },  // Store Name
                { wch: 25 },  // Employee Name
                { wch: 15 },  // Amount
                { wch: 15 },  // From Store
                { wch: 15 },  // Instant
                { wch: 15 }   // Date
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Network Instant Report')
            
            // Generate filename
            const filename = `Network_Instant_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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
