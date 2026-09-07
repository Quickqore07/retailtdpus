<template>
    <div class="detailed-finance-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Detailed Finance Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View comprehensive finance data with category breakdowns and detailed analysis
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
                        @change="applyFilters"
                        class="form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                
                <!-- Company Filter -->
             
                <div>
                    <DynamicDropdown
                        v-model="filters.selectedWorkgroup"
                        resource="workgroups?query=&column=name"
                        display-name="name"
                        placeholder="Select Workgroup"
                        label="Select Workgroup"
                        @change="applyFilters"
                    />
                </div>
                <div>
                    <DynamicDropdown
                        v-model="filters.selectedCompanies"
                        :resource="`companies?query=&column=name&${filters.selectedWorkgroup?.id ? 'workgroup_id=' + filters.selectedWorkgroup?.id : 'workgroup_skip=true'}`"
                        display-name="name"
                        placeholder="Select Companies"
                        label="Select Companies"
                        :multiple="true"
                        @change="applyFilters"
                    />
                </div>
                <div v-if="can('detailed-finance-report', 'regional-director')">
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
                
                <div v-if="can('detailed-finance-report', 'area-manager')">
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
                <DynamicDropdown
                    v-model="filters.reportType"
                    :customOptions="reportTypes"
                    placeholder="Select Report Type"
                    icon-left="file-text"
                    label="Select Report Type"
                    @change="applyFilters"
                />
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
        <!-- Data Table - Category View -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-[-1px] !z-[101]">
                        <tr>
                            <Th class="sticky left-0 !z-[101] bg-gray-50 dark:bg-gray-900 !w-[250px]" :class="getRowClass('Lables')">
                                <div class="!w-[250px]">Lables</div>
                            </Th>
                            <Th class="sticky left-[180px] !z-[101] bg-gray-50 dark:bg-gray-900 w-[60px] text-right" :class="getRowClass('Total')">
                                <div class="!w-[60px]">Total</div>
                            </Th>
                            <Th v-for="month in months" :key="month" class="text-right">
                                <span>{{ month }}</span>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="reportData && reportData?.length > 0">
                        <tr v-for="item in reportData" :key="item.label" >
                            <Td class="!w-[250px] sticky left-0 "  :class="getRowClass(item.label)" :title="item.label"> 
                                <span class="truncate max-w-[250px] inline-block">{{ item.label }}</span>
                            </Td>
                            <Td class="!w-[60px] sticky left-[180px]  text-right" :class="getRowClass(item.label)">{{ formatValue(item.label, item.value.total) }}</Td>
                            <Td v-for="month in months" :key="month" class="text-right" :class="getRowClass(item.label)">{{ formatValue(item.label, item.value[month]) }}</Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="3 + months.length">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Finance Data Found"
                                    message="There is no finance data for the selected period. Please select a different date range or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>  
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

const filters = ref({
    selectedYear: 2026,
    selectedCompanies: [],
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: [],
    selectedWorkgroup: null,
    reportType: { id: 'summary', name: 'Summary' }
})

const workgroups = ref([])
const pandlConfigurations = ref([])
const months = ref([])
const reportData = ref([])
const loading = ref(false)
const exportLoading = ref(false)
const missingLedgers = ref([])
const missingQuickqoreLedgers = ref([])
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



const onRegionalDirectorChange = () => {
    filters.value.areaManager = null
    applyFilters()
}

const onAreaManagerChange = () => {
    filters.value.regionalDirector = null
    applyFilters()
}


const getRowClass = (label) => {
    if(label === 'Total Revenue'){
        return '!bg-[#e6f7ff] !dark:bg-[#002766] !font-bold border-t-2 border-b-2 border-gray-300 dark:border-gray-700'
    }
    if(label === 'Total Food Purchase' || label === 'Food Percentage'  || label === 'Total Labor Cost' || label === 'Labor Cost Percentage' || label === 'Total Franchise Fee' || label === 'Franchise Fee Percentage' || label === 'Total Retail' || label === 'Retail Percentage'){
        return '!bg-[#fff2e6] !dark:bg-[#5c3500] !font-bold border-t-2 border-b-2 border-gray-300 dark:border-gray-700'
    }
    if(label === 'Total COGS' || label === 'COGS Percentage'){
        return '!bg-[#e6f7ff] !dark:bg-[#002766] !font-bold border-t-2 border-b-2 border-gray-300 dark:border-gray-700'
    }
    if(label === 'Total Expense' || label === 'Expense Percentage'){
        return '!bg-[#e6f7ff] !dark:bg-[#002766] !font-bold border-t-2 border-b-2 border-gray-300 dark:border-gray-700'
    }
    if(label === 'Net Income' || label === 'Net Income Percentage'){
        return '!bg-[#c2eab4] !dark:bg-[#c2eab4] !font-bold border-t-2 border-b-2 border-gray-300 dark:border-gray-700'
    }
    return 'bg-white dark:bg-gray-900 border-t border-b border-gray-200 dark:border-gray-700'
}

const applyFilters = async () => {
    if(!filters.value.pandlConfiguration){
        message.error('Please select a P&L Configuration')
        return
    }
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/finance/get-detailed-finance-report', {
            year: filters.value.selectedYear,
            user_id: filters.value.regionalDirector?.id || filters.value.areaManager?.id,
            pandl_configuration_id: filters.value.pandlConfiguration?.id,
            company_ids: filters.value.selectedCompanies?.map(company => company.id) || [],
            workgroup_id: filters.value.selectedWorkgroup?.id,
            report_type: filters.value.reportType?.id
        })
        
        if (response.success && response.data) {
            months.value = response.months || []
            reportData.value = response.data || []
            missingLedgers.value = response.missing_ledgers || []
            missingQuickqoreLedgers.value = response.missing_quickqore_ledgers || []
        }
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const loadWorkgroups = async () => {
    const response = await useRequest('get', '/search/workgroups?query=&column=name')
    const collection = response?.collection ?? []
    workgroups.value = collection
    filters.value.workgroup_ids = collection.map((item) => item.id)
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
            exportData.push(['Detailed Finance Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Generated:', new Date().toLocaleString()])
            if (filters.value.selectedCompanies && filters.value.selectedCompanies.length > 0) {
                exportData.push(['Companies:', filters.value.selectedCompanies.map(c => c.name).join(', ')])
            }
            if (filters.value.regionalDirector) {
                exportData.push(['Regional Director:', filters.value.regionalDirector.name])
            }
            if (filters.value.areaManager) {
                exportData.push(['Area Manager:', filters.value.areaManager.name])
            }
            if (filters.value.pandlConfiguration) {
                exportData.push(['P&L Configuration:', filters.value.pandlConfiguration.name])
            }
            exportData.push([])
            
            // Header row
            const headerRow = ['Label', 'Total', ...months.value]
            exportData.push(headerRow)
            
            // Data rows
            reportData.value.forEach(item => {
                const row = [
                    item.label,
                    parseFloat(item.value.total || 0)
                ]
                
                months.value.forEach(month => {
                    row.push(parseFloat(item.value[month] || 0))
                })
                
                exportData.push(row)
            })
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            const colWidths = [
                { wch: 40 },  // Label
                { wch: 15 }   // Total
            ]
            months.value.forEach(() => {
                colWidths.push({ wch: 15 })  // Month columns
            })
            ws['!cols'] = colWidths
            
            // Apply number formatting to numeric cells
            const range = XLSX.utils.decode_range(ws['!ref'])
            const headerRowIndex = exportData.findIndex(row => row[0] === 'Label')
            
            for (let R = headerRowIndex + 1; R <= range.e.r; R++) {
                for (let C = 1; C <= range.e.c; C++) {  // Start from Total column (index 1)
                    const cellAddress = XLSX.utils.encode_cell({ r: R, c: C })
                    if (ws[cellAddress] && typeof ws[cellAddress].v === 'number') {
                        ws[cellAddress].z = '#,##0.00'
                    }
                }
            }
            
            // Apply bold styling to specific rows (Revenue, COGS, Expense, Net Income)
            for (let R = headerRowIndex + 1; R <= range.e.r; R++) {
                const labelCell = XLSX.utils.encode_cell({ r: R, c: 0 })
                if (ws[labelCell]) {
                    const label = ws[labelCell].v
                    if (label === 'Total Revenue' || label === 'Total Food Purchase' || 
                        label === 'Food Percentage' || label === 'Total Labor Cost' || 
                        label === 'Labor Cost Percentage' || label === 'Total Franchise Fee' || 
                        label === 'Franchise Fee Percentage' || label === 'Total COGS' || 
                        label === 'COGS Percentage' || label === 'Total Expense' || 
                        label === 'Expense Percentage' || label === 'Net Income' || 
                        label === 'Net Income Percentage') {
                        for (let C = 0; C <= range.e.c; C++) {
                            const cellAddress = XLSX.utils.encode_cell({ r: R, c: C })
                            if (ws[cellAddress]) {
                                ws[cellAddress].s = {
                                    font: { bold: true }
                                }
                            }
                        }
                    }
                }
            }
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Detailed Finance Report')
            
            const filename = `Detailed_Finance_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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

const isPercentageRow = (label) => {
    return label.includes('Percentage')
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

onMounted(async () => {
    await loadPandlConfigurations()
    await loadWorkgroups()
    filters.value.selectedYear = new Date().getFullYear()
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
    min-width: 50px;
}
</style>
