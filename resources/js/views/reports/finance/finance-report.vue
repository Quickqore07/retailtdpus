<template>
    <div class="finance-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Finance Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze finance data for the selected period
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
                
                <div v-if="can('finance-report', 'regional-director')">
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
                
                <div v-if="can('finance-report', 'area-manager')">
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
                        class="form-select"
                    >
                        <option :value="null">All Workgroups</option>
                        <option v-for="workgroup in workgroups" :key="workgroup.id" :value="workgroup.id">
                            {{ workgroup.name }}
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
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 !z-[101] bg-gray-50 dark:bg-gray-900">
                                <span>Sr. No.</span>
                            </Th>
                            <Th class="sticky left-[80px] !z-[101] bg-gray-50 dark:bg-gray-900 !w-[200px] ">
                                <div class="!w-[200px]">Store Name</div>
                            </Th>
                            <Th class="sticky left-[300px] !z-[101] bg-gray-50 dark:bg-gray-900 w-[100px] text-right">
                                <div class="!w-[100px]">Total</div>
                            </Th>
                            <Th v-for="month in months" :key="month" class="text-right">
                                <span>{{ month }}</span>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="filteredReportData.length > 0">
                        <tr v-for="(item, index) in filteredReportData" :key="index" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 !z-[100]">
                            <Td class="sticky left-0 !z-[100] bg-white dark:bg-gray-800">{{ index + 1 }}</Td>
                            <Td class="sticky left-[80px] !z-[100] bg-white dark:bg-gray-800 !w-[200px] truncate">{{  !isNaN(item.store_data.store_number) && item.store_data.store_number !== null && item.store_data.name ? (item.store_data.store_number + ' - ' + item.store_data.name) : item.store_data.name }}</Td>
                            <Td class="sticky left-[300px] !z-[100] bg-white dark:bg-gray-800 !w-[100px] text-right" weight="bold" color="primary">{{ formatNumber(item.total_amount) }}</Td>
                            <Td v-for="month in months" :key="month" :class="getAmountClass(item[month])" class="text-right">
                                {{ formatNumber(item[month]) }}
                            </Td>
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
                                    action-text="Apply Filters"
                                    action-icon="refresh"
                                    @action="applyFilters"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900 sticky bottom-0 !z-[101]">
                        <tr>
                            <Td  weight="bold" color="primary" class="sticky !z-[101] left-0 bg-gray-50 dark:bg-gray-900">TOTALS</Td>
                            <Td  weight="bold" color="primary" class="sticky !z-[101] w-[200px] left-[80px] bg-gray-50 dark:bg-gray-900"></Td>
                            <Td class="sticky w-[100px] left-[300px] !z-[101] bg-gray-50 dark:bg-gray-800 !w-[100px] text-right" weight="bold" color="primary">{{ formatNumber(totalAmount) }}</Td>
                            <Td v-for="month in months" :key="month" weight="bold" color="secondary" :class="getAmountClass(totals[month])" class="text-right">
                                {{ formatNumber(totals[month]) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ filteredReportData.length }}</span> of <span class="font-medium">{{ reportData.length }}</span> records
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400" v-if="filters.selectedWorkgroup">
                    Filtered by: {{ getWorkgroupName(filters.selectedWorkgroup) }}
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

const filters = ref({
    selectedYear: 2026,
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: [],
    selectedWorkgroup: null
})

const workgroups = ref([])
const months = ref([])
const reportData = ref([])
const loading = ref(false)
const exportLoading = ref(false)

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

// Filter data by selected workgroup (client-side filtering)
const filteredReportData = computed(() => {
    if (!filters.value.selectedWorkgroup) {
        return reportData.value
    }
    return reportData.value.filter(item => 
        item.store_data.workgroup_id === filters.value.selectedWorkgroup
    )
})

// Calculate totals for each month based on filtered data
const totals = computed(() => {
    const totalsObj = {}
    
    months.value.forEach(month => {
        totalsObj[month] = filteredReportData.value.reduce((sum, item) => {
            return sum + (parseFloat(item[month]) || 0)
        }, 0)
    })
    
    return totalsObj
})

const totalAmount = computed(() => {
    return filteredReportData.value.reduce((sum, item) => {
        return sum + (parseFloat(item.total_amount) || 0)
    }, 0)
})

const getAmountClass = (amount) => {
    const value = parseFloat(amount) || 0
    if (value < 0) {
        return 'text-red-600 dark:text-red-400'
    } else if (value > 0) {
        return 'text-green-600 dark:text-green-400'
    }
    return ''
}

const getWorkgroupName = (workgroupId) => {
    const workgroup = workgroups.value.find(w => w.id === workgroupId)
    return workgroup ? workgroup.name : 'Unknown'
}

const onYearChange = () => {
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
        loading.value = true
        const response = await useRequest('post', '/reports/finance/get-finance-report', {
            year: filters.value.selectedYear,
            user_id: filters.value.regionalDirector?.id || filters.value.areaManager?.id,
        })
        
        if (response.success && response.data) {
            months.value = response.data.months || []
            reportData.value = response.data.data || []
        }
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
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

const exportReport = async () => {
    try {
        if (!filteredReportData.value || filteredReportData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        await import('xlsx').then((XLSX) => {
            const exportData = []
            
            // Title and metadata section
            exportData.push(['Finance Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Generated:', new Date().toLocaleString()])
            if (filters.value.regionalDirector) {
                exportData.push(['Regional Director:', filters.value.regionalDirector.name])
            }
            if (filters.value.areaManager) {
                exportData.push(['Area Manager:', filters.value.areaManager.name])
            }
            if (filters.value.selectedWorkgroup) {
                exportData.push(['Workgroup:', getWorkgroupName(filters.value.selectedWorkgroup)])
            }
            exportData.push([])
            
            // Header row - now includes Total column
            const headerRow = ['Sr. No.', 'Store Number', 'Store Name', 'Total', ...months.value]
            exportData.push(headerRow)
            
            // Data rows - now includes total_amount
            filteredReportData.value.forEach((item, index) => {
                const row = [
                    index + 1,
                    item.store_data.store_number || 'N/A',
                    item.store_data.name || 'N/A',
                    parseFloat(item.total_amount || 0)
                ]
                
                months.value.forEach(month => {
                    row.push(parseFloat(item[month] || 0))
                })
                
                exportData.push(row)
            })
            
            // Totals row - properly aligned with Total column
            exportData.push([])
            const totalsRow = ['', '', 'TOTALS', parseFloat(totalAmount.value || 0)]
            months.value.forEach(month => {
                totalsRow.push(parseFloat(totals.value[month] || 0))
            })
            exportData.push(totalsRow)
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            const colWidths = [
                { wch: 10 },  // Sr. No.
                { wch: 15 },  // Store Number
                { wch: 30 },  // Store Name
                { wch: 15 }   // Total
            ]
            months.value.forEach(() => {
                colWidths.push({ wch: 15 })  // Month columns
            })
            ws['!cols'] = colWidths
            
            // Apply number formatting to numeric cells
            const range = XLSX.utils.decode_range(ws['!ref'])
            const headerRowIndex = exportData.findIndex(row => row[0] === 'Sr. No.')
            
            for (let R = headerRowIndex + 1; R <= range.e.r; R++) {
                for (let C = 3; C <= range.e.c; C++) {  // Start from Total column
                    const cellAddress = XLSX.utils.encode_cell({ r: R, c: C })
                    if (ws[cellAddress] && typeof ws[cellAddress].v === 'number') {
                        ws[cellAddress].z = '#,##0.00'  // Number format with 2 decimals
                    }
                }
            }
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Finance Report')
            
            const filename = `Finance_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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

onMounted(async () => {
    await loadWorkgroups()
    filters.value.selectedYear = new Date().getFullYear()
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
    min-width: 80px;
}
</style>
