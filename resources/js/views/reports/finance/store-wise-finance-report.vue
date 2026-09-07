<template>
    <div class="store-wise-finance-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Store Wise Finance Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze finance data by store with summary or detailed ledger breakdowns
                </p>
            </div>

            <!-- Date Period and Other Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-6 gap-6 mb-6">
                <!-- Select Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onFilterChange"
                        class="form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <!-- Select Quarter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Quarter <span class="text-xs text-gray-500">(or select month)</span>
                    </label>
                    <select
                        v-model="filters.selectedQuarter"
                        @change="onQuarterChange"
                        class="form-select"
                    >
                        <option :value="null">Select Quarter</option>
                        <option v-for="quarter in quarterOptions" :key="quarter.value" :value="quarter.value">
                            {{ quarter.label }}
                        </option>
                    </select>
                </div>

                <!-- Select Month -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Month <span class="text-xs text-gray-500">(or select quarter)</span>
                    </label>
                    <select
                        v-model="filters.selectedMonth"
                        @change="onMonthChange"
                        class="form-select"
                    >
                        <option :value="null">Select Month</option>
                        <option v-for="(monthName, monthNum) in monthOptions" :key="monthNum" :value="monthNum">
                            {{ monthName }}
                        </option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Start Date
                    </label>
                    <input
                        v-model="filters.startDate"
                        type="date"
                        :max="filters.endDate || undefined"
                        class="form-input w-full"
                        @change="onDateChange"
                    />
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        End Date
                    </label>
                    <input
                        v-model="filters.endDate"
                        type="date"
                        :min="filters.startDate || undefined"
                        class="form-input w-full"
                        @change="onDateChange"
                    />
                </div>
                
                <!-- Regional Director -->
                <div v-if="can('store-wise-finance-report', 'regional-director')">
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
                
                <!-- Area Manager -->
                <div v-if="can('store-wise-finance-report', 'area-manager')">
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

                <!-- Workgroup Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Filter by Workgroup
                    </label>
                    <select
                        v-model="filters.selectedWorkgroup"
                        @change="onFilterChange"
                        class="form-select"
                    >
                        <option :value="null">All Workgroups</option>
                        <option v-for="workgroup in workgroups" :key="workgroup.id" :value="workgroup.id">
                            {{ workgroup.name }}
                        </option>
                    </select>
                </div>

            <!-- P&L Configuration Filter -->
                <div>
                    <DynamicDropdown
                        v-model="filters.pandlConfiguration"
                        :customOptions="pandlConfigurations"
                        placeholder="Select P&L Configuration"
                        icon-left="file-text"
                        label="Select P&L Configuration"
                        :removeNullOption="true"
                        @change="applyFilters"
                    />
                </div>
                <div>
                    <DynamicDropdown
                        v-model="filters.reportType"
                        :customOptions="reportTypes"
                        placeholder="Select Report Type"
                        icon-left="file-text"
                        label="Select Report Type"
                        @change="applyFilters"
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
                        :disabled="!filters.pandlConfiguration"
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
                        :disabled="reportData.length === 0"
                    >
                        Export to Excel
                    </Button>
                </div>
            </div>

            <!-- Warning Messages -->
            <div v-if="missingLedgers.length > 0 || missingQuickqoreLedgers.length > 0" class="mt-4">
                <div v-if="missingLedgers.length > 0" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-2">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200 font-medium mb-1">Missing Ledgers in Configuration:</p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">{{ missingLedgers.join(', ') }}</p>
                </div>
                <!-- <div v-if="missingQuickqoreLedgers.length > 0" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200 font-medium mb-1">Ledgers Not in P&L Configuration:</p>
                    <p class="text-xs text-blue-700 dark:text-blue-300">{{ missingQuickqoreLedgers.join(', ') }}</p>
                </div> -->
            </div>
        </Panel>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 !z-[101] bg-gray-50 dark:bg-gray-900 !w-[250px]">
                                <div class="!w-[250px]">Category</div>
                            </Th>
                            <Th v-for="company in companies" :key="company" class="text-right">
                                <div class="!w-[100px] whitespace-normal break-words">
                                    <span>{{ company }}</span>
                                </div>
                            </Th>
                            <Th class="sticky right-0 !z-[101] bg-gray-50 dark:bg-gray-900 text-right">
                                <span>Total</span>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class=" !z-[100]" v-if="reportData.length > 0">
                        <tr v-for="(item, index) in reportData" :key="index" 
                            :class="[
                                'border-t border-gray-200 dark:border-gray-700',
                                getRowClass(item.label)
                            ]">
                            <Td class="!w-[250px] sticky left-0 !z-[100] " 
                                :weight="isHeaderRow(item.label) ? 'bold' : 'normal'"
                                :class="getRowClass(item.label)"
                                :title="item.label">
                                <span class="truncate max-w-[250px] inline-block">{{ item.label }}</span>
                            </Td>
                            <Td v-for="company in companies" :key="company" 
                                :class="getRowClass(item.label)"
                                class="text-right !w-[200px]"
                                :weight="isHeaderRow(item.label) ? 'bold' : 'normal'">
                                {{ formatValue(item.label, item.value[company]) }}
                            </Td>
                            <Td class="sticky right-0 !z-[100]  text-right" 
                                :weight="isHeaderRow(item.label) ? 'bold' : 'normal'"
                                :class="getRowClass(item.label)">
                                {{ formatValue(item.label, item.value.total) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="companies.length + 2">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Finance Data Found"
                                    message="Please select a P&L Configuration and apply filters to view the report."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>  
            </div> 

            <!-- Report Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing data for <span class="font-medium">{{ companies.length }}</span> stores
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ getFilterSummary() }}
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
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'

const message = useMessage()
const { can } = usePermission()

const monthOptions = {
    1: 'January',
    2: 'February',
    3: 'March',
    4: 'April',
    5: 'May',
    6: 'June',
    7: 'July',
    8: 'August',
    9: 'September',
    10: 'October',
    11: 'November',
    12: 'December'
}

const quarterOptions = [
    { value: '01-03', label: 'Q1 (Jan - Mar)' },
    { value: '04-06', label: 'Q2 (Apr - Jun)' },
    { value: '07-09', label: 'Q3 (Jul - Sep)' },
    { value: '10-12', label: 'Q4 (Oct - Dec)' },
    { value: '01-12', label: 'Full Year' }
]

const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedQuarter: null,
    selectedMonth: new Date().getMonth() + 1,
    startDate: null,
    endDate: null,
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: [],
    selectedWorkgroup: null,
    pandlConfiguration: null,
    reportType: { id: 'summary', name: 'Summary' }
})

const workgroups = ref([])
const companies = ref([])
const reportData = ref([])
const missingLedgers = ref([])
const missingQuickqoreLedgers = ref([])
const pandlConfigurations = ref([])

const loading = ref(false)
const exportLoading = ref(false)

const reportTypes = [
    { name: 'Summary', id: 'summary' },
    { name: 'Detailed', id: 'detailed' }
]

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const headerRows = [
    'Total Revenue',
    'Total Food Purchase',
    'Food Percentage',
    'Total Labor Cost',
    'Labor Cost Percentage',
    'Total Franchise Fee',
    'Franchise Fee Percentage',
    'Total COGS',
    'COGS Percentage',
    'Total Expense',
    'Expense Percentage',
    'Net Income',
    'Net Income Percentage'
]

const isHeaderRow = (label) => {
    return headerRows.includes(label)
}

const isPercentageRow = (label) => {
    return label.includes('Percentage')
}

const getRowClass = (label) => {
    if (label === 'Total Revenue') {
        return 'bg-orange-50 dark:bg-orange-900/20 font-bold !border-b-2 !border-orange-300 !dark:border-orange-700'
    }
    if (label === 'Total Food Purchase' || label === 'Food Percentage' || label === 'Total Labor Cost' || label === 'Labor Cost Percentage' || label === 'Total Franchise Fee' || label === 'Franchise Fee Percentage' || label === 'Total Retail' || label === 'Retail Percentage') {
        return 'bg-blue-50 dark:bg-blue-900/20 font-bold !border-b-2 !border-blue-300 !dark:border-blue-700'
    }
    if (label === 'Total COGS' || label === 'COGS Percentage') {
        return 'bg-orange-50 dark:bg-orange-900/20 font-bold !border-b-2 !border-orange-300 !dark:border-orange-700'
    }
    if (label === 'Total Expense' || label === 'Expense Percentage') {
        return 'bg-orange-50 dark:bg-orange-900/20 font-bold !border-b-2 !border-orange-300 !dark:border-orange-700'
    }
    if (label === 'Net Income' || label === 'Net Income Percentage') {
        return 'bg-green-50 dark:bg-green-900/20 font-bold !border-b-2 !border-green-300 !dark:border-green-700'
    }
    return 'bg-white dark:bg-gray-900 border-t border-b border-gray-200 dark:border-gray-700'
}

const getCellClass = (label, value) => {
    if (isPercentageRow(label)) {
        return ''
    }
    
    const numValue = parseFloat(value) || 0
    if (numValue < 0) {
        return 'text-red-600 dark:text-red-400'
    } else if (numValue > 0) {
        return 'text-green-600 dark:text-green-400'
    }
    return ''
}

const formatValue = (label, value) => {
    if (value === null || value === undefined) {
        return '0.00'
    }
    
    if (isPercentageRow(label)) {
        return formatNumber(value, 2) + '%'
    }
    
    return formatNumber(value, 2)
}

const loadPandlConfigurations = async () => {
    const response = await useRequest('get', '/search/pandl?query=')
    const collection = response?.collection ?? []
    pandlConfigurations.value = collection.map(item => ({
        id: item.id,
        name: item.label
    }))
    if(pandlConfigurations.value.length > 0){
        filters.value.pandlConfiguration = pandlConfigurations.value[0]
    }
}

const getFilterSummary = () => {
    const parts = []
    
    if (filters.value.startDate && filters.value.endDate) {
        parts.push(`${filters.value.startDate} to ${filters.value.endDate}`)
    } else if (filters.value.selectedQuarter) {
        const quarterLabel = quarterOptions.find(q => q.value === filters.value.selectedQuarter)?.label || filters.value.selectedQuarter
        parts.push(`${quarterLabel} ${filters.value.selectedYear}`)
    } else if (filters.value.selectedMonth) {
        parts.push(`${monthOptions[filters.value.selectedMonth]} ${filters.value.selectedYear}`)
    } else {
        parts.push(`${filters.value.selectedYear}`)
    }
    
    if (filters.value.regionalDirector) {
        parts.push(`RD: ${filters.value.regionalDirector.name}`)
    }
    if (filters.value.areaManager) {
        parts.push(`AM: ${filters.value.areaManager.name}`)
    }
    if (filters.value.selectedWorkgroup) {
        const workgroup = workgroups.value.find(w => w.id === filters.value.selectedWorkgroup)
        if (workgroup) {
            parts.push(`Workgroup: ${workgroup.name}`)
        }
    }
    if (filters.value.pandlConfiguration) {
        parts.push(`P&L: ${filters.value.pandlConfiguration.name}`)
    }
    if (filters.value.reportType) {
        parts.push(`Type: ${filters.value.reportType.name}`)
    }
    
    return parts.join(' | ')
}

const onFilterChange = () => {
    if (filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const onQuarterChange = () => {
    if (filters.value.selectedQuarter) {
        filters.value.selectedMonth = null
        filters.value.startDate = null
        filters.value.endDate = null
    }
    if (filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const onMonthChange = () => {
    if (filters.value.selectedMonth) {
        filters.value.selectedQuarter = null
        filters.value.startDate = null
        filters.value.endDate = null
    }
    if (filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const onDateChange = () => {
    filters.value.selectedQuarter = null
    filters.value.selectedMonth = null

    if (filters.value.startDate && filters.value.endDate && filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const onRegionalDirectorChange = () => {
    filters.value.areaManager = null
    if (filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const onAreaManagerChange = () => {
    filters.value.regionalDirector = null
    if (filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const applyFilters = async () => {
    if (!filters.value.pandlConfiguration) {
        message.warning('Please select a P&L Configuration')
        return
    }
    if ((filters.value.startDate && !filters.value.endDate) || (!filters.value.startDate && filters.value.endDate)) {
        message.warning('Please select both start date and end date')
        return
    }
    if (filters.value.startDate && filters.value.endDate && filters.value.startDate > filters.value.endDate) {
        message.warning('Start date must be before or equal to end date')
        return
    }

    try {
        loading.value = true
        
        const requestData = {
            year: filters.value.selectedYear,
            quarter: filters.value.selectedQuarter,
            month: filters.value.selectedMonth,
            start_date: filters.value.startDate,
            end_date: filters.value.endDate,
            pandl_configuration_id: filters.value.pandlConfiguration.id,
            user_id: filters.value.regionalDirector?.id || filters.value.areaManager?.id,
            workgroup_id: filters.value.selectedWorkgroup,
            report_type: filters.value.reportType?.id
        }
        
        const response = await useRequest('post', '/reports/finance/get-store-wise-finance-report', requestData)
        
        if (response.success) {
            companies.value = response.companies || []
            reportData.value = response.data || []
            missingLedgers.value = response.missing_ledgers || []
            missingQuickqoreLedgers.value = response.missing_quickqore_ledgers || []
        }
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const loadWorkgroups = async () => {
    try {
        const response = await useRequest('get', '/search/workgroups?query=&column=name')
        const collection = response?.collection ?? []
        workgroups.value = collection
        filters.value.workgroup_ids = collection.map((item) => item.id)
    } catch (error) {
        console.error('Error loading workgroups:', error)
    }
}

const exportReport = async () => {
    try {
        if (!reportData.value || reportData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        await import('xlsx').then((XLSX) => {
            const exportData = []
            
            // Title and metadata section
            exportData.push(['Store Wise Finance Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            if (filters.value.startDate && filters.value.endDate) {
                exportData.push(['Date Range:', `${filters.value.startDate} to ${filters.value.endDate}`])
            }
            if (filters.value.selectedQuarter) {
                const quarterLabel = quarterOptions.find(q => q.value === filters.value.selectedQuarter)?.label || filters.value.selectedQuarter
                exportData.push(['Quarter:', quarterLabel])
            }
            if (filters.value.selectedMonth) {
                exportData.push(['Month:', monthOptions[filters.value.selectedMonth]])
            }
            exportData.push(['Generated:', new Date().toLocaleString()])
            if (filters.value.pandlConfiguration) {
                exportData.push(['P&L Configuration:', filters.value.pandlConfiguration.name])
            }
            if (filters.value.reportType) {
                exportData.push(['Report Type:', filters.value.reportType.name])
            }
            if (filters.value.regionalDirector) {
                exportData.push(['Regional Director:', filters.value.regionalDirector.name])
            }
            if (filters.value.areaManager) {
                exportData.push(['Area Manager:', filters.value.areaManager.name])
            }
            if (filters.value.selectedWorkgroup) {
                const workgroup = workgroups.value.find(w => w.id === filters.value.selectedWorkgroup)
                if (workgroup) {
                    exportData.push(['Workgroup:', workgroup.name])
                }
            }
            exportData.push([])
            
            // Header row
            const headerRow = ['Category', ...companies.value, 'Total']
            exportData.push(headerRow)
            
            // Data rows
            reportData.value.forEach((item) => {
                const row = [item.label]
                
                companies.value.forEach(company => {
                    const value = item.value[company]
                    if (isPercentageRow(item.label)) {
                        row.push(parseFloat(value || 0))
                    } else {
                        row.push(parseFloat(value || 0))
                    }
                })
                
                const totalValue = item.value.total
                if (isPercentageRow(item.label)) {
                    row.push(parseFloat(totalValue || 0))
                } else {
                    row.push(parseFloat(totalValue || 0))
                }
                
                exportData.push(row)
            })
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            const colWidths = [{ wch: 30 }]  // Category column
            companies.value.forEach(() => {
                colWidths.push({ wch: 15 })  // Store columns
            })
            colWidths.push({ wch: 15 })  // Total column
            ws['!cols'] = colWidths
            
            // Apply number formatting and styling
            const range = XLSX.utils.decode_range(ws['!ref'])
            const headerRowIndex = exportData.findIndex(row => row[0] === 'Category')
            
            for (let R = headerRowIndex + 1; R <= range.e.r; R++) {
                const rowData = exportData[R]
                const isPercentage = rowData && rowData[0] && isPercentageRow(rowData[0])
                const label = rowData && rowData[0]
                
                for (let C = 0; C <= range.e.c; C++) {
                    const cellAddress = XLSX.utils.encode_cell({ r: R, c: C })
                    if (!ws[cellAddress]) continue
                    
                    // Apply number formatting
                    if (C > 0 && typeof ws[cellAddress].v === 'number') {
                        ws[cellAddress].z = isPercentage ? '0.00"%"' : '#,##0.00'
                    }
                    
                    // Apply cell styling based on row type
                    let cellStyle = {}
                    
                    if (label === 'Total Revenue') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'E6F7FF' } }
                        }
                    } else if (label === 'Total Food Purchase' || label === 'Food Percentage' || 
                               label === 'Total Labor Cost' || label === 'Labor Cost Percentage' || 
                               label === 'Total Franchise Fee' || label === 'Franchise Fee Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'FFF2E6' } }
                        }
                    } else if (label === 'Total COGS' || label === 'COGS Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'E6F7FF' } }
                        }
                    } else if (label === 'Total Expense' || label === 'Expense Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'E6F7FF' } }
                        }
                    } else if (label === 'Net Income' || label === 'Net Income Percentage') {
                        cellStyle = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: 'C2EAB4' } }
                        }
                    }
                    
                    if (Object.keys(cellStyle).length > 0) {
                        ws[cellAddress].s = cellStyle
                    }
                }
            }
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Store Wise Finance')
            
            let filenameParts = ['Store_Wise_Finance_Report']
            if (filters.value.reportType) {
                filenameParts.push(filters.value.reportType.name)
            }
            if (filters.value.selectedQuarter) {
                filenameParts.push(filters.value.selectedQuarter)
            }
            if (filters.value.selectedMonth) {
                filenameParts.push(monthOptions[filters.value.selectedMonth])
            }
            if (filters.value.startDate && filters.value.endDate) {
                filenameParts.push(filters.value.startDate)
                filenameParts.push(filters.value.endDate)
            }
            filenameParts.push(filters.value.selectedYear)
            filenameParts.push(new Date().getTime())
            const filename = `${filenameParts.join('_')}.xlsx`
            
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

onMounted(async () => {
    await loadWorkgroups()
    await loadPandlConfigurations()
    
    applyFilters()
})
</script>

<style scoped>
/* Sticky columns shadow effect */
.sticky {
    box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.1);
}

.dark .sticky {
    box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.3);
}

/* Ensure sticky columns work properly */
thead th.sticky,
tbody td.sticky,
tfoot td.sticky {
    position: sticky;
}

/* Table cell min width for better readability */
table td,
table th {
    min-width: 120px;
    white-space: nowrap;
}

table td:first-child,
table th:first-child {
    min-width: 250px;
}

</style>
