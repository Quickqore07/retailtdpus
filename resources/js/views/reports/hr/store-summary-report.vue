<template>
    <div class="pay-period-store-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                     Store Summary Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View payroll data by store number
                </p>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Select Company -->
                    <DynamicDropdown
                        v-model="filters.company"
                        @change="applyFilters"
                        resource="companies"
                        display-name="name"
                        placeholder="Select company"
                        icon-left="building"
                        label="Select Company"
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
            <div class="mb-6">
                <MultiStatCard
                    label="Summary"
                    :items="DataSummaryStats"
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
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('pp_ends')">
                                    <span>PP Ends</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('pp_ends')" 
                                        :name="getSortDirection('pp_ends') === 'asc' ? 'chevron-up' : 'chevron-down'" 
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
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('regular_hours')">
                                    <span>Reg Hour</span>
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
                                    <span>OT</span>
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
                                    <span>Total</span>
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
                                    <span>Mileage Due</span>
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
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedPayrollData.length > 0">
                        <tr v-for="(item, index) in sortedPayrollData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td>{{ item.pp_ends }}</Td>
                            <Td>{{ formatNumber(item.sales) }}</Td>
                            <Td>{{ formatNumber(item.regular_hours, 2) }}</Td>
                            <Td>{{ formatNumber(item.overtime_hours, 2) }}</Td>
                            <Td>{{ formatNumber(item.total_hours, 2) }}</Td>
                            <Td>{{ formatCurrency(item.gross_pay) }}</Td>
                            <Td>{{ formatCurrency(item.tips) }}</Td>
                            <Td>{{ formatCurrency(item.mwa_amount) }}</Td>
                            <Td>{{ formatCurrency(item.tips_due) }}</Td>
                            <Td>{{ formatCurrency(item.mileage_due) }}</Td>
                            <Td>{{ formatCurrency(item.total_earnings) }}</Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="10" class="p-8">
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
                            <Td weight="bold" color="primary">TOTALS</Td>
                            <Td weight="bold">{{ formatNumber(totals.sales) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.regular_hours, 2) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.overtime_hours, 2) }}</Td>
                            <Td weight="bold">{{ formatNumber(totals.total_hours, 2) }}</Td>
                            <Td weight="bold">{{ formatCurrency(totals.net_payroll) }}</Td>
                            <Td weight="bold">{{ formatCurrency(totals.tips) }}</Td>
                            <Td weight="bold">{{ formatCurrency(totals.mwa_amount) }}</Td>
                            <Td weight="bold">{{ formatCurrency(totals.tips_due) }}</Td>
                            <Td weight="bold">{{ formatCurrency(totals.mileage_due) }}</Td>
                            <Td weight="bold">{{ formatCurrency(totals.gross_pay) }}</Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Footer Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ payrollData.length }}</span> pay periods
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
    company: null
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

// Computed totals
const totals = computed(() => {
    return payrollData.value.reduce((acc, item) => {
        acc.sales += parseFloat(item.sales ?? 0)
        acc.regular_hours += parseFloat(item.regular_hours ?? 0)
        acc.overtime_hours += parseFloat(item.overtime_hours ?? 0)
        acc.total_hours += parseFloat(item.total_hours ?? 0)
        acc.net_payroll += parseFloat(item.net_payroll ?? 0)
        acc.tips += parseFloat(item.tips ?? 0)
        acc.mwa_amount += parseFloat(item.mwa_amount ?? 0)
        acc.tips_due += parseFloat(item.tips_due ?? 0)
        acc.mileage_due += parseFloat(item.mileage_due ?? 0)
        acc.gross_pay += parseFloat(item.gross_pay ?? 0)
        acc.sales += parseFloat(item.sales ?? 0)
        return acc
    }, {
        sales: 0,
        regular_hours: 0,
        overtime_hours: 0,
        total_hours: 0,
        net_payroll: 0,
        tips: 0,
        mwa_amount: 0,
        tips_due: 0,
        mileage_due: 0,
        gross_pay: 0,
        sales: 0
    })
})

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value ?? 0)
}

// Sorted data
const sortedPayrollData = computed(() => {
    const data = [...payrollData.value]
    
    if (!sortField.value) return data
    
    return data.sort((a, b) => {
        let aVal, bVal
        
        if (sortField.value === 'pp_ends') {
            aVal = new Date(a.pp_ends || 0).getTime()
            bVal = new Date(b.pp_ends || 0).getTime()
        } else {
            aVal = parseFloat(a[sortField.value] || 0)
            bVal = parseFloat(b[sortField.value] || 0)
        }
        
        return sortDirection.value === 'asc' 
            ? aVal - bVal
            : bVal - aVal
    })
})

const DataSummaryStats = computed(() => {
    return [
        {
            label: 'Total Sales',
            value: totals.value.sales,
            type: 'amount'
        },
      {
        label: 'Total Regular Hours',
        value: totals.value.regular_hours
      },
      {
        label: 'Total Overtime Hours',
        value: totals.value.overtime_hours
      },
      {
        label: 'Total Hours',
        value: totals.value.total_hours
      },
      {
        label: 'Total Net Payroll',
        value: totals.value.net_payroll,    
        type: 'amount'
      },
      {
        label: 'Total Tips',
        value: totals.value.tips,
        type: 'amount'
      },
      {
        label: 'Total MWA Amount',
        value: totals.value.mwa_amount,
        type: 'amount'
      },
      {
        label: 'Total Tips Due',
        value: totals.value.tips_due,
        type: 'amount'
      },
      {
        label: 'Total Mileage Due',
        value: totals.value.mileage_due,
        type: 'amount'
      },
      {
        label: 'Total Gross',
        value: totals.value.gross_pay,
        type: 'amount'
      }
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
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/hr/store-summary', {
            year: filters.value.selectedYear,
            company_id: filters.value.company?.id
        })
        payrollData.value = response.data
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
            exportData.push(['Pay Period Store Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'PP Ends',
                'Sales',
                'Reg Hour',
                'OT',
                'Total',
                'Net Payroll',
                'Tips',
                'MWA Amount',
                'Tips Due',
                'Mileage Due',
                'Gross'
            ])
            
            // Add data rows
            payrollData.value.forEach((item) => {
                exportData.push([
                    item.pp_ends,
                    parseFloat(item.sales || 0),
                    parseFloat(item.regular_hours || 0),
                    parseFloat(item.overtime_hours || 0),
                    parseFloat(item.total_hours || 0),
                    parseFloat(item.gross_pay || 0),
                    parseFloat(item.tips || 0),
                    parseFloat(item.mwa_amount || 0),
                    parseFloat(item.tips_due || 0),
                    parseFloat(item.mileage_due || 0),
                    parseFloat(item.total_earnings || 0)
                ])
            })
            
            // Add totals row
            exportData.push([])
            exportData.push([
                'TOTALS',
                parseFloat(totals.value.sales || 0),
                parseFloat(totals.value.regular_hours || 0),
                parseFloat(totals.value.overtime_hours || 0),
                parseFloat(totals.value.total_hours || 0),
                parseFloat(totals.value.gross_pay || 0),
                parseFloat(totals.value.tips || 0),
                parseFloat(totals.value.mwa_amount || 0),
                parseFloat(totals.value.tips_due || 0),
                parseFloat(totals.value.mileage_due || 0),
                parseFloat(totals.value.total_earnings || 0)
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 12 },  // PP Ends
                { wch: 12 },  // Sales
                { wch: 12 },  // Reg Hour
                { wch: 10 },  // OT
                { wch: 12 },  // Total
                { wch: 15 },  // Net Payroll
                { wch: 12 },  // Tips
                { wch: 12 },  // MWA Amount
                { wch: 12 },  // Tips Due
                { wch: 15 },  // Mileage Due
                { wch: 12 }   // Gross
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Pay Period Report')
            
            // Generate filename
            const filename = `Pay_Period_Store_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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
    filters.value.company = authStore.company
    applyFilters()
})
</script>

<style scoped>
/* Add any custom styles if needed */
</style>
