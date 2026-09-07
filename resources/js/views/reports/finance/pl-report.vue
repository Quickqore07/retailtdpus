<template>
    <div class="pl-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    P&L Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze P&L data by company with category breakdowns
                </p>
            </div>

            <!-- Year, Month, Quarter and Other Filters -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-6">
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
            </div>

            <!-- P&L Configuration Filter -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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
            </div>
        </Panel>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 !z-[101] bg-gray-50 dark:bg-gray-900 !w-[200px]">
                                <div class="!w-[200px]">Company</div>
                            </Th>
                            <Th v-for="category in categories" :key="category" class="text-right !p-0.5" weight="bold" :class="getCellClass(category)">
                                <div class="w-full flex items-center justify-end">
                                    <div class="!min-w-[70px] !w-[70px] whitespace-normal break-words !text-xs font-bold" >
                                        <span class="!text-[10px]">{{ category }}</span>
                                    </div>
                                </div>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class=" !z-[100]" v-if="reportData.length > 0">
                        <tr v-for="(item, index) in reportData" :key="index" 
                            :class="[
                                'border-t border-gray-200 dark:border-gray-700',
                                index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'
                            ]">
                            <Td class="!w-[200px] sticky left-0 !z-[100] !p-1" 
                                :class="index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'"
                                :title="item.company">
                                <span class="truncate max-w-[250px] inline-block">{{ item.company }}</span>
                            </Td>
                            <Td v-for="category in categories" :key="category" 
                                :class="[
                                    'text-right !min-w-[70px] !w-[70px] !p-1.5',
                                    getCellClass(category)
                                ]">
                                {{ formatValue(category, item.values[category]) }} 
                            </Td>
                        </tr>
                        <!-- Total Row -->
                        <tr class="bg-blue-50 dark:bg-blue-900/20 font-bold border-t-2 border-blue-300 dark:border-blue-700">
                            <Td class="sticky left-0 !z-[100] bg-blue-50 dark:bg-blue-900/20 !p-1" weight="bold">
                                Total
                            </Td>
                            <Td v-for="category in categories" :key="category" 
                                class="text-right !p-1"
                                weight="bold"
                                :class="getCellClass(category)"
                                >
                                {{ formatValue(category, totalRow[category]) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="categories.length + 1">
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
                    Showing data for <span class="font-medium">{{ reportData.length }}</span> companies
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
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: [],
    selectedWorkgroup: null,
    pandlConfiguration: null,
    reportType: { id: 'summary', name: 'Summary' }
})

const workgroups = ref([])
const categories = ref([])
const reportData = ref([])
const totalRow = ref({})
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

const isPercentageCategory = (category) => {
    return category.includes('Percentage')
}

const getCellClass = (category) => {
    if(category === 'GP' || category === 'GP %' || category === 'Net Income' || category === 'Net Income %'){
        return 'bg-green-100 dark:bg-green-900/20'
    }
    if(category === 'Sales' || category === 'Sales %'){
        return 'bg-blue-100 dark:bg-blue-900/20'
    }
}

const formatValue = (category, value) => {
    if (value === null || value === undefined) {
        return '0.00'
    }
    
    if (isPercentageCategory(category)) {
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
    
    if (filters.value.selectedQuarter) {
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
    }
    if (filters.value.pandlConfiguration) {
        applyFilters()
    }
}

const onMonthChange = () => {
    if (filters.value.selectedMonth) {
        filters.value.selectedQuarter = null
    }
    if (filters.value.pandlConfiguration) {
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

    try {
        loading.value = true
        
        const requestData = {
            year: filters.value.selectedYear,
            pandl_configuration_id: filters.value.pandlConfiguration.id,
            user_id: filters.value.regionalDirector?.id || filters.value.areaManager?.id,
            workgroup_id: filters.value.selectedWorkgroup,
            report_type: filters.value.reportType?.id
        }
        
        if (filters.value.selectedQuarter) {
            requestData.quarter = filters.value.selectedQuarter
        } else if (filters.value.selectedMonth) {
            requestData.month = filters.value.selectedMonth
        }
        
        const response = await useRequest('post', '/reports/finance/get-store-wise-profit-and-loss-report', requestData)
        
        if (response.success) {
            const companies = response.companies || []
            const originalData = response.data || []
            
            // Transform data: swap rows and columns
            const categorySet = new Set()
            originalData.forEach(item => {
                categorySet.add(item.label)
            })
            categories.value = Array.from(categorySet)
            
            // Build transposed data structure
            const transposedData = []
            const totals = {}
            
            companies.forEach(company => {
                const companyData = {
                    company: company,
                    values: {}
                }
                
                originalData.forEach(item => {
                    const value = item.value[company] || 0
                    companyData.values[item.label] = value
                    
                    if (!totals[item.label]) {
                        totals[item.label] = 0
                    }
                    totals[item.label] = item.value.total || 0
                })
                
                transposedData.push(companyData)
            })
            
            reportData.value = transposedData
            totalRow.value = totals
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
            
            exportData.push(['P&L Report'])
            exportData.push(['Year:', filters.value.selectedYear])
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
            
            const headerRow = ['Company', ...categories.value]
            exportData.push(headerRow)
            
            reportData.value.forEach((item) => {
                const row = [item.company]
                
                categories.value.forEach(category => {
                    const value = item.values[category]
                    row.push(parseFloat(value || 0))
                })
                
                exportData.push(row)
            })
            
            const totalRowData = ['Total']
            categories.value.forEach(category => {
                totalRowData.push(parseFloat(totalRow.value[category] || 0))
            })
            exportData.push(totalRowData)
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            const colWidths = [{ wch: 30 }]
            categories.value.forEach(() => {
                colWidths.push({ wch: 15 })
            })
            ws['!cols'] = colWidths
            
            const range = XLSX.utils.decode_range(ws['!ref'])
            const headerRowIndex = exportData.findIndex(row => row[0] === 'Company')
            
            for (let R = headerRowIndex + 1; R <= range.e.r; R++) {
                for (let C = 0; C <= range.e.c; C++) {
                    const cellAddress = XLSX.utils.encode_cell({ r: R, c: C })
                    if (!ws[cellAddress]) continue
                    
                    if (C > 0 && typeof ws[cellAddress].v === 'number') {
                        const category = categories.value[C - 1]
                        const isPercentage = isPercentageCategory(category)
                        ws[cellAddress].z = isPercentage ? '0.00"%"' : '#,##0.00'
                    }
                }
            }
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'P&L Report')
            
            let filenameParts = ['PL_Report']
            if (filters.value.reportType) {
                filenameParts.push(filters.value.reportType.name)
            }
            if (filters.value.selectedQuarter) {
                filenameParts.push(filters.value.selectedQuarter)
            }
            if (filters.value.selectedMonth) {
                filenameParts.push(monthOptions[filters.value.selectedMonth])
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
.sticky {
    box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.1);
}

.dark .sticky {
    box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.3);
}

thead th.sticky,
tbody td.sticky,
tfoot td.sticky {
    position: sticky;
}

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
