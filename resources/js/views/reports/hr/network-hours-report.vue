<template>
    <div class="network-hours-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Network Hours Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View company-wise total hours for all bi-weekly pay periods
                </p>
            </div>

            <!-- Year Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Select Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onYearChange"
                        class="filterable-select w-full form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
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
                        :disabled="payrollData.length === 0"
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
                            <Th class="md:sticky md:left-0 bg-gray-100 dark:bg-gray-800 !z-[101]">
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
                            <Th class="md:sticky md:left-11 !z-[101] bg-gray-100 dark:bg-gray-800">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('store')">
                                    <span>Store</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('store')" 
                                        :name="getSortDirection('store') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-for="eow in eows" :key="eow" class="text-center">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors justify-center" @click="handleSort('pp_' + eow)">
                                    <span>{{ formatPPDate(eow) }}</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('pp_' + eow)" 
                                        :name="getSortDirection('pp_' + eow) === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class=" bg-gray-100 dark:bg-gray-800 md:sticky md:right-0 z-100">
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
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedPayrollData.length > 0">
                        <tr v-for="(item, index) in sortedPayrollData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800">{{ item.sr_no }}</Td>
                            <Td class="md:sticky md:left-11 z-10 bg-white dark:bg-gray-800 font-medium">{{ item.store_number + ' - ' + item.store_name }}</Td>
                            <Td
                                v-for="eow in eows"
                                :key="eow"
                                align="center"
                                customClass="!min-w-[2.5rem]"
                            >
                                {{ formatNumber(item['pp_' + eow]) }}
                            </Td>
                            <Td class=" bg-white dark:bg-gray-800 font-semibold text-blue-600 dark:text-blue-400 md:sticky md:right-0 z-100">
                                {{ formatNumber(getRowTotal(item), 2) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="eows.length + 3" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Payroll Data Found"
                                    message="There is no payroll data for the selected year. Please select a different year or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="payrollData.length > 0">
                        <tr>
                            <Td colspan="2" class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="primary">TOTALS</Td>
                            <Td v-for="eow in eows" :key="eow" weight="bold" align="center">
                                {{ formatNumber(getColumnTotal(eow)) }}
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
                    Showing <span class="font-medium">{{ payrollData.length }}</span> stores
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Year: {{ filters.selectedYear }}
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

const filters = ref({
    selectedYear: new Date().getFullYear()
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
const eows = ref([])
const loading = ref(false)
const exportLoading = ref(false)

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc')

// Format PP date for headers
const formatPPDate = (eow) => {
    const date = new Date(eow)
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${month}-${day}`
}

// Get row total
const getRowTotal = (item) => {
    let total = 0
    eows.value.forEach(eow => {
        total += parseFloat(item['pp_' + eow] ?? 0)
    })
    return total
}

// Get column total
const getColumnTotal = (eow) => {
    let total = 0
    payrollData.value.forEach(item => {
        total += parseFloat(item['pp_' + eow] ?? 0)
    })
    return total
}

// Grand total
const grandTotal = computed(() => {
    let total = 0
    payrollData.value.forEach(item => {
        total += getRowTotal(item)
    })
    return total
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
        } else if (sortField.value === 'store') {
            aVal = parseFloat(a.store_number || 0)
            bVal = parseFloat(b.store_number || 0)
        } else if (sortField.value === 'total') {
            aVal = getRowTotal(a)
            bVal = getRowTotal(b)
        } else if (sortField.value.startsWith('pp_')) {
            // Sorting by a specific pay period column
            aVal = parseFloat(a[sortField.value] || 0)
            bVal = parseFloat(b[sortField.value] || 0)
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
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/hr/network-hours', {
            year: filters.value.selectedYear
        })
        payrollData.value = response?.data ?? []
        eows.value = response?.eows ?? []
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        if (!payrollData.value || payrollData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            const exportData = []
            
            // Add header information
            exportData.push(['Network Hours Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            const headers = ['Sr', 'Store', 'Total']
            eows.value.forEach(eow => {
                headers.push(formatPPDate(eow))
            })
            exportData.push(headers)
            
            // Add data rows
            payrollData.value.forEach((item) => {
                const row = [
                    item.sr_no,
                    item.store_number + ' - ' + item.store_name,
                    parseFloat(getRowTotal(item) || 0)
                ]
                eows.value.forEach(eow => {
                    row.push(parseFloat(item['pp_' + eow] || 0))
                })
                exportData.push(row)
            })
            
            // Add totals row
            exportData.push([])
            const totalsRow = ['', 'TOTALS', parseFloat(grandTotal.value || 0)]
            eows.value.forEach(eow => {
                totalsRow.push(parseFloat(getColumnTotal(eow) || 0))
            })
            exportData.push(totalsRow)
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            const colWidths = [
                { wch: 5 },   // Sr
                { wch: 15 },  // Store
                { wch: 12 }   // Total
            ]
            eows.value.forEach(() => {
                colWidths.push({ wch: 10 })
            })
            ws['!cols'] = colWidths
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Network Hours Report')
            
            // Generate filename
            const filename = `Network_Hours_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
            // Save file
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
    applyFilters()
})
</script>

<style scoped>
/* Sticky columns styling */
.sticky {
    position: sticky;
}
</style>
