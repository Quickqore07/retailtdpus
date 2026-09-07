<template>
    <div class="mwa-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                     MWA Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View Minimum Wage Adjustment (MWA) report for bi-weekly periods
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
                    <Button
                    v-if="can('mwa-report', 'update')"
                        icon-left="save"
                        icon-size="sm"
                        variant="success"
                        size="sm"
                        @click="updateMWA"
                        :loading="updateLoading"
                        :disabled="selectedItems.length === 0"
                    >
                        Update MWA ({{ selectedItems.length }})
                    </Button>
                </div>
            </div>
        </Panel>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101] w-16">
                                <input 
                                    type="checkbox" 
                                    @change="toggleSelectAll"
                                    :checked="isAllSelected"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </Th>
                            <Th class="md:sticky md:left-16 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('company')">
                                    <span>Company</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('company')" 
                                        :name="getSortDirection('company') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-56 bg-white dark:bg-gray-900 !z-[101] w-[200px]">
                                <div class="flex items-center w-[200px] gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('employee_name')">
                                    <span>Employee Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('employee_name')" 
                                        :name="getSortDirection('employee_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-56 bg-white dark:bg-gray-900 !z-[101] w-[200px]">
                                <div class="flex items-center w-[200px] gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('role')">
                                    <span>Role</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('role')" 
                                        :name="getSortDirection('role') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-96 bg-white dark:bg-gray-900 !z-[101] w-[200px]">
                                EOW
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('min_wage_due')">
                                    <span>Min Wage Due</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('min_wage_due')" 
                                        :name="getSortDirection('min_wage_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="sortedData.length > 0">
                        <!-- Loop through sorted data -->
                        <template v-for="(storeGroup, storeIndex) in sortedData" :key="`store-${storeIndex}`">
                            <!-- Store rows -->
                            <tr 
                                v-for="(item, itemIndex) in storeGroup.employees" 
                                :key="`${storeIndex}-${itemIndex}`"
                                class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 !z-[100]"
                            >
                                <Td class="md:sticky left-0 bg-white dark:bg-gray-900 !z-[100]">
                                    <input 
                                        type="checkbox" 
                                        :checked="isSelected(item)"
                                        @change="toggleSelection(item)"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </Td>
                                <Td  
                                    color="secondary"
                                    class="border-r-2 border-gray-300 dark:border-gray-600 md:sticky left-16 bg-white dark:bg-gray-900 !z-[100]"
                                >
                                    {{ item.company?.name }}
                                </Td>
                                <Td  
                                    weight="medium"
                                    color="primary"
                                    class="border-r-2 border-gray-300 w-[200px] max-w-[200px] dark:border-gray-600 max-w-96 truncate md:sticky left-56 bg-white dark:bg-gray-900 !z-[100]"
                                    :title="item.employee?.pos_name"
                                >
                                    {{ item.employee?.pos_name }}
                                </Td>
                                <Td weight="medium" color="primary">
                                    {{ item.role?.name ? item.role?.code + ' - ' + item.role?.name : 'N/A' }}
                                </Td>
                                <Td weight="medium" color="primary">
                                    {{ item.eow }}
                                </Td>
                                <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''">
                                    ${{ formatNumber(item.min_wage_due) }}
                                </Td>
                            </tr>
                            
                            <!-- Store Total Row -->
                            <tr class="bg-blue-50 dark:bg-blue-900/20 font-bold border-b-2 border-blue-300 dark:border-blue-700" v-if="storeGroup.employees.length > 1">
                                <Td class="md:sticky left-0 bg-blue-50 dark:bg-blue-900/20 !z-[100]"></Td>
                                <Td class="md:sticky left-16 bg-blue-50 dark:bg-blue-900/20 !z-[100]" colspan="3"></Td>
                                <Td weight="bold" color="primary" class="text-right md:sticky left-56 bg-blue-50 dark:bg-blue-900/20 !z-[100]">
                                    Store Total:
                                </Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(storeGroup.storeTotal) }}
                                </Td>
                            </tr>
                            <tr>
                                <Td colspan="6" weight="bold" color="primary" class="h-2">

                                </Td>
                            </tr>
                        </template>
                        
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="6">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No MWA Data Found"
                                    message="There is no minimum wage adjustment data for the selected period."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Td class="md:sticky left-0 bg-gray-50 dark:bg-gray-900 !z-[101]"></Td>
                            <Td class="md:sticky left-16 bg-gray-50 dark:bg-gray-900 !z-[101]" colspan="3"></Td>
                            <Td  weight="bold" color="primary" class="text-right md:sticky left-56 bg-gray-50 dark:bg-gray-900 !z-[101]">GRAND TOTAL</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(grandTotal) }}</Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ totalRecords }}</span> records
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
import { useAuthStore } from '@/stores/auth'
import { usePermission } from '@/composables/usePermission'
const message = useMessage()
const authStore = useAuthStore()
const { can } = usePermission()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
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

const reportData = ref([])
const loading = ref(false)
const exportLoading = ref(false)
const updateLoading = ref(false)
const selectedItems = ref([])

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc') // 'asc' or 'desc'

// Generate bi-weekly periods (Monday to Sunday) for the selected year
const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const periods = []
    
    // Generate bi-weekly periods for the entire year using UTC to avoid timezone issues
    let startDate = new Date(Date.UTC(year, 0, 1)) // January 1st UTC
    
    // Adjust to start on Monday (Monday = 1, Sunday = 0)
    const dayOfWeek = startDate.getUTCDay()
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7
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

// Group data by company
const groupedData = computed(() => {
    const groups = {}
    
    reportData.value.forEach(item => {
        const companyId = item.company_id
        if (!groups[companyId]) {
            groups[companyId] = {
                company: item.company,
                employees: [],
                storeTotal: 0
            }
        }
        groups[companyId].employees.push(item)
        groups[companyId].storeTotal += parseFloat(item.min_wage_due || 0)
    })
    
    return Object.values(groups)
})

// Return sorted array
const sortedData = computed(() => {
    const currentSortField = sortField.value
    const currentSortDirection = sortDirection.value
    
    let data = [...groupedData.value]
    
    if (currentSortField) {
        data.sort((a, b) => {
            if (currentSortField === 'company') {
                const valA = a.company?.name || ''
                const valB = b.company?.name || ''
                return currentSortDirection === 'asc' 
                    ? valA.localeCompare(valB)
                    : valB.localeCompare(valA)
            }
            
            if (currentSortField === 'employee_name') {
                const valA = a.employees[0]?.employee?.pos_name || ''
                const valB = b.employees[0]?.employee?.pos_name || ''
                return currentSortDirection === 'asc'
                    ? valA.localeCompare(valB)
                    : valB.localeCompare(valA)
            }
            
            if (currentSortField === 'min_wage_due') {
                const totalA = a.storeTotal
                const totalB = b.storeTotal
                const diff = totalA - totalB
                return currentSortDirection === 'asc' ? diff : -diff
            }
            
            return 0
        })
    }
    
    return data
})

const grandTotal = computed(() => {
    return reportData.value.reduce((sum, item) => sum + parseFloat(item.min_wage_due || 0), 0)
})

const totalRecords = computed(() => {
    return reportData.value.length
})

const isAllSelected = computed(() => {
    return reportData.value.length > 0 && selectedItems.value.length === reportData.value.length
})

const allItemsFlat = computed(() => {
    return reportData.value
})

// Methods
const handleSort = (field) => {
    if (sortField.value === field) {
        // Toggle direction if same field
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        // Set new field and default to ascending
        sortField.value = field
        sortDirection.value = 'asc'
    }
}

const getSortDirection = (field) => {
    if (sortField.value !== field) return null
    return sortDirection.value
}

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
        loading.value = true
        const response = await useRequest('post', '/reports/payroll/mwa', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
        })
        reportData.value = response.data
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to fetch data')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        // Check if there's data to export
        if (!reportData.value || reportData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['MWA Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Company',
                'Employee Name',    
                'Role',
                'EOW',
                'Min Wage Due'
            ])

            // Process grouped data
            sortedData.value.forEach(storeGroup => {
                // Add data for each employee
                storeGroup.employees.forEach(item => {
                    exportData.push([
                        item.company?.name || '',
                        item.employee?.pos_name || '',
                        item.role?.name ? item.role?.code + ' - ' + item.role?.name : 'N/A',
                        item.eow,
                        parseFloat(item.min_wage_due || 0)
                    ])
                })
                
                if (storeGroup.employees.length > 1) {
                    // Add store total
                    exportData.push([
                        '', '', '', 
                        'Store Total:',
                        storeGroup.storeTotal
                    ])
                }
                exportData.push([]) // Empty row between stores
            })
            
            // Add grand total
            exportData.push([])
            exportData.push([
                '',
                '',
                '',
                'GRAND TOTAL:',
                grandTotal.value
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 30 },  // Company
                { wch: 30 },  // Employee Name
                { wch: 15 },  // Role
                { wch: 15 },  // EOW
                { wch: 15 }   // Min Wage Due
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'MWA Report')
            
            // Generate filename
            const filename = `MWA_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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

const isSelected = (item) => {
    return selectedItems.value.some(selected => 
        selected.employee_id === item.employee_id &&
        selected.company_id === item.company_id &&
        selected.role_id === item.role_id &&
        selected.eow === item.eow
    )
}

const toggleSelection = (item) => {
    const index = selectedItems.value.findIndex(selected => 
        selected.employee_id === item.employee_id &&
        selected.company_id === item.company_id &&
        selected.role_id === item.role_id &&
        selected.eow === item.eow
    )
    
    if (index > -1) {
        selectedItems.value.splice(index, 1)
    } else {
        selectedItems.value.push({
            employee_id: item.employee_id,
            company_id: item.company_id,
            role_id: item.role_id,
            eow: item.eow,
            amount: item.min_wage_due
        })
    }
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedItems.value = []
    } else {
        selectedItems.value = reportData.value.map(item => ({
            employee_id: item.employee_id,
            company_id: item.company_id,
            role_id: item.role_id,
            eow: item.eow,
            amount: item.min_wage_due
        }))
    }
}

const updateMWA = async () => {
    if (selectedItems.value.length === 0) {
        message.error('Please select at least one item')
        return
    }

    try {
        updateLoading.value = true
        const response = await useRequest('post', '/reports/payroll/mwa/update', {
            selected_items: selectedItems.value
        })
        applyFilters()
        message.success(response.message || 'MWA data updated successfully')
        selectedItems.value = []
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to update MWA data')
    } finally {
        updateLoading.value = false
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
