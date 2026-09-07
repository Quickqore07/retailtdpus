<template>
    <div class="network-hours-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div>
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Pending Bank Deposit Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
                    View pending bank deposit data for all stores
                </p>
            </div>

          
            <div class="flex flex-wrap items-center gap-3 mt-4">
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
                    :disabled="depositData.length === 0"
                >
                    Export to Excel
                </Button>
            </div>
        </Panel>

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th customClass="w-[50px]">
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
                            <Th customClass="w-[200px]">
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
                            <Th class="text-center w-[200px]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors justify-center" @click="handleSort('latest_date')">
                                    <span>Last Deposit Date</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('latest_date')" 
                                        :name="getSortDirection('latest_date') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th customClass="w-[200px] " align="right">
                                <div class="flex justify-end gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('remaining_amount')">
                                    <span>Remaining Cash Bag</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('remaining_amount')" 
                                        :name="getSortDirection('remaining_amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedDeposit.length > 0">
                        <tr v-for="(item, index) in sortedDeposit" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td >{{ item.sr_no }}</Td>
                            <Td >{{ item.company_name }}</Td>
                            <Td
                                align="center"
                                customClass="!min-w-[2.5rem]"
                            >
                                {{ item.latest_date ? formatDate(item.latest_date) : '-' }}
                            </Td>
                            <Td customClass="w-[200px]" align="right">
                                {{ formatNumber(item.remaining_amount, 2) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="5" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Deposit Data Found"
                                    message="There is no deposit data for the selected year. Please select a different year or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="depositData.length > 0">
                        <tr>
                            <Td colspan="3" class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="primary">TOTALS</Td>
                            <Td class="md:sticky md:right-0 z-10 bg-gray-100 dark:bg-gray-800" weight="bold" color="secondary">
                                {{ formatNumber(grandTotal, 2) }}
                            </Td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Footer Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ depositData.length }}</span> companies
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
import { formatDate } from '@/utils/date'

const message = useMessage()

const depositData = ref([])
const filters = ref({
    minAmount: null,
    maxAmount: null,
    fromDate: '',
    toDate: ''
})
const loading = ref(false)
const exportLoading = ref(false)

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc')


// Grand total
const grandTotal = computed(() => {
    let total = 0
    depositData.value.forEach(item => {
        total += parseFloat(item.remaining_amount || 0)
    })
    return total
})

// Filtered data (frontend-only filters)
const filteredDeposit = computed(() => {
    return depositData.value.filter((item) => {
        const amount = parseFloat(item.remaining_amount || 0)
        const latestDate = item.latest_date ? String(item.latest_date).substring(0, 10) : null

        if (filters.value.minAmount !== null && filters.value.minAmount !== '' && amount < filters.value.minAmount) {
            return false
        }

        if (filters.value.maxAmount !== null && filters.value.maxAmount !== '' && amount > filters.value.maxAmount) {
            return false
        }

        if (filters.value.fromDate) {
            if (!latestDate || latestDate < filters.value.fromDate) {
                return false
            }
        }

        if (filters.value.toDate) {
            if (!latestDate || latestDate > filters.value.toDate) {
                return false
            }
        }

        return true
    })
})

// Sorted data (based on filtered data)
const sortedDeposit = computed(() => {
    const data = [...filteredDeposit.value]

    if (!sortField.value) return data
    return data.sort((a, b) => {
        let aVal, bVal
        
        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store') {
            aVal = parseFloat(a.store_number || 0)
            bVal = parseFloat(b.store_number || 0)
        } else if (sortField.value === 'remaining_cash_bag' || sortField.value === 'remaining_amount') {
            aVal = parseFloat(a.remaining_amount || 0)
            bVal = parseFloat(b.remaining_amount || 0)
        } else if (sortField.value === 'latest_date') {
            aVal = a.latest_date ? new Date(a.latest_date).getTime() : 0
            bVal = b.latest_date ? new Date(b.latest_date).getTime() : 0
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


const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/cash/pending-bank-deposit', {})
        depositData.value = response?.data ?? []
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        if (!depositData.value || depositData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            const exportData = []
            
            // Add header information
            exportData.push(['Pending Bank Deposit Report'])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            const headers = ['Sr', 'Store', 'Last Deposit Date', 'Remaining Cash Bag']
            exportData.push(headers)
            
            // Add data rows
            depositData.value.forEach((item) => {
                const row = [
                    item.sr_no,
                    item.company_name,
                    item.latest_date ? formatDate(item.latest_date) : '-',
                    formatNumber(item.remaining_amount, 2)
                ]
                exportData.push(row)
            })
            
            // Add totals row
            exportData.push([])
            const totalsRow = ['', 'TOTALS', '', formatNumber(grandTotal.value, 2)]
            exportData.push(totalsRow)
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            const colWidths = [
                { wch: 5 },   // Sr
                { wch: 15 },  // Store
                { wch: 12 },  // Last Deposi    t Date
                { wch: 12 }   // Total
            ]
            ws['!cols'] = colWidths
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Pending Bank Deposit Report')
            
            // Generate filename
            const filename = `Pending_Bank_Deposit_Report_${new Date().getTime()}.xlsx`
            
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
