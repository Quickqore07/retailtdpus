<template>
    <div class="network-weekly-sales-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Network Weekly Sales Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View company-wise total sales for each week of the selected year
                </p>
            </div>

            <!-- Year Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Workgroups
                    </label>
                    <div class="rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 max-h-44 overflow-y-auto space-y-2">
                        <label
                            v-for="workgroup in workgroups"
                            :key="workgroup.id"
                            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <input
                                type="checkbox"
                                :value="workgroup.id"
                                v-model="filters.workgroup_ids"
                                @change="applyFilters"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                            <span>{{ workgroup.name }}</span>
                        </label>
                        <p v-if="!workgroups.length" class="text-sm text-gray-500 dark:text-gray-400">
                            No workgroups available
                        </p>
                    </div>
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
                        :disabled="salesData.length === 0"
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
                            <Th class="md:sticky md:left-12 !z-[101] bg-gray-100 dark:bg-gray-800">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('store_number')">
                                    <span>Store Name</span>
                                    <SvgIcon
                                        v-if="getSortDirection('store_number')"
                                        :name="getSortDirection('store_number') === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-for="eow in eows" :key="eow" class="text-center">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors justify-center" @click="handleSort('pp_' + eow)">
                                    <span>{{ formatWeekDate(eow) }}</span>
                                    <SvgIcon
                                        v-if="getSortDirection('pp_' + eow)"
                                        :name="getSortDirection('pp_' + eow) === 'asc' ? 'chevron-up' : 'chevron-down'"
                                        size="sm"
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="bg-gray-100 dark:bg-gray-800 md:sticky md:right-0 z-100">
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
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" v-if="sortedSalesData.length > 0">
                        <tr v-for="(item, index) in sortedSalesData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800">{{ item.sr_no }}</Td>
                            <Td class="md:sticky md:left-12 z-10 bg-white dark:bg-gray-800 font-medium">{{ item.company_name }}</Td>
                            <Td
                                v-for="eow in eows"
                                :key="eow"
                                align="center"
                                customClass="!min-w-[2.5rem]"
                            >
                                {{ formatNumber(item['pp_' + eow], 2) }}
                            </Td>
                            <Td class="bg-white dark:bg-gray-800 font-semibold text-blue-600 dark:text-blue-400 md:sticky md:right-0 z-100">
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
                                    title="No Sales Data Found"
                                    message="There is no sales data for the selected year. Please select a different year or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold" v-if="salesData.length > 0">
                        <!-- Group Totals -->
                        <template v-for="(group, groupName) in groupedSalesData" :key="groupName">
                            <tr class="bg-blue-50 dark:bg-blue-900/20">
                                <Td colspan="2" class="md:sticky md:left-0 z-10 bg-blue-50 dark:bg-blue-900/20" weight="bold" color="blue">
                                    {{ groupName }} Total
                                </Td>
                                <Td v-for="eow in eows" :key="eow" weight="bold" align="center" color="blue">
                                    {{ formatNumber(getGroupColumnTotal(groupName, eow), 2) }}
                                </Td>
                                <Td class="md:sticky md:right-0 z-10 bg-blue-50 dark:bg-blue-900/20" weight="bold" color="blue">
                                    {{ formatNumber(getGroupGrandTotal(groupName), 2) }}
                                </Td>
                            </tr>
                        </template>
                        
                        <!-- Grand Total -->
                        <tr class="bg-gray-200 dark:bg-gray-700">
                            <Td colspan="2" class="md:sticky md:left-0 z-10 bg-gray-200 dark:bg-gray-700" weight="bold" color="primary">GRAND TOTALS</Td>
                            <Td v-for="eow in eows" :key="eow" weight="bold" align="center">
                                {{ formatNumber(getColumnTotal(eow), 2) }}
                            </Td>
                            <Td class="md:sticky md:right-0 z-10 bg-gray-200 dark:bg-gray-700" weight="bold" color="secondary">
                                {{ formatNumber(grandTotal, 2) }}
                            </Td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ salesData.length }}</span> stores
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
    selectedYear: new Date().getFullYear(),
    workgroup_ids: []
})
const workgroups = ref([])

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const salesData = ref([])
const eows = ref([])
const companyGroups = ref([])
const loading = ref(false)
const exportLoading = ref(false)

const sortField = ref(null)
const sortDirection = ref('asc')

const formatWeekDate = (eow) => {
    if (!eow) return ''
    const [year, month, day] = eow.split('-').map(Number)
    return `${month}-${day}`
}

const getRowTotal = (item) => {
    let total = 0
    eows.value.forEach(eow => {
        total += parseFloat(item['pp_' + eow] ?? 0)
    })
    return total
}

const getColumnTotal = (eow) => {
    let total = 0
    salesData.value.forEach(item => {
        total += parseFloat(item['pp_' + eow] ?? 0)
    })
    return total
}

const grandTotal = computed(() => {
    let total = 0
    salesData.value.forEach(item => {
        total += getRowTotal(item)
    })
    return total
})

// Group companies by their group
const groupedSalesData = computed(() => {
    const groups = {}
    
    salesData.value.forEach(item => {
        const groupName = item.group_name || 'Ungrouped'
        if (!groups[groupName]) {
            groups[groupName] = {
                group_id: item.group_id,
                group_name: groupName,
                companies: []
            }
        }
        groups[groupName].companies.push(item)
    })
    
    return groups
})

// Get group total for a specific week
const getGroupColumnTotal = (groupName, eow) => {
    let total = 0
    const group = groupedSalesData.value[groupName]
    if (group) {
        group.companies.forEach(item => {
            total += parseFloat(item['pp_' + eow] ?? 0)
        })
    }
    return total
}

// Get group total across all weeks
const getGroupGrandTotal = (groupName) => {
    let total = 0
    const group = groupedSalesData.value[groupName]
    if (group) {
        group.companies.forEach(item => {
            total += getRowTotal(item)
        })
    }
    return total
}

const sortedSalesData = computed(() => {
    const data = [...salesData.value]

    if (!sortField.value) return data

    return data.sort((a, b) => {
        let aVal, bVal

        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store_number') {
            aVal = parseFloat(a.store_number || 0)
            bVal = parseFloat(b.store_number || 0)
        } else if (sortField.value === 'total') {
            aVal = getRowTotal(a)
            bVal = getRowTotal(b)
        } else if (sortField.value.startsWith('pp_')) {
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

const onYearChange = () => {
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/sales/network-weekly-sales', {
            year: filters.value.selectedYear,
            workgroup_ids: filters.value.workgroup_ids
        })
        salesData.value = response?.data ?? []
        eows.value = response?.eows ?? []
        companyGroups.value = response?.company_groups ?? []
    } catch (error) {
        console.error('Error loading report:', error)
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

const exportReport = async () => {
    try {
        if (!salesData.value || salesData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        await import('xlsx').then((XLSX) => {
            const exportData = []

            exportData.push(['Network Weekly Sales Report'])
            exportData.push(['Year:', filters.value.selectedYear])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            const headers = ['Sr', 'Store']
            eows.value.forEach(eow => {
                headers.push(formatWeekDate(eow))
            })
            headers.push('Total')
            exportData.push(headers)

            salesData.value.forEach((item) => {
                const row = [
                    item.sr_no,
                    item.company_name
                ]
                eows.value.forEach(eow => {
                    row.push(parseFloat(item['pp_' + eow] || 0))
                })
                row.push(parseFloat(getRowTotal(item) || 0))
                exportData.push(row)
            })

            exportData.push([])
            
            // Add group totals
            Object.keys(groupedSalesData.value).forEach(groupName => {
                const groupTotalsRow = ['', `${groupName} Total`]
                eows.value.forEach(eow => {
                    groupTotalsRow.push(parseFloat(getGroupColumnTotal(groupName, eow) || 0))
                })
                groupTotalsRow.push(parseFloat(getGroupGrandTotal(groupName) || 0))
                exportData.push(groupTotalsRow)
            })
            
            exportData.push([])
            const grandTotalsRow = ['', 'GRAND TOTALS']
            eows.value.forEach(eow => {
                grandTotalsRow.push(parseFloat(getColumnTotal(eow) || 0))
            })
            grandTotalsRow.push(parseFloat(grandTotal.value || 0))
            exportData.push(grandTotalsRow)

            const ws = XLSX.utils.aoa_to_sheet(exportData)

            const colWidths = [
                { wch: 5 },
                { wch: 15 },
                { wch: 12 }
            ]
            eows.value.forEach(() => {
                colWidths.push({ wch: 10 })
            })
            ws['!cols'] = colWidths

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Network Weekly Sales')

            const filename = `Network_Weekly_Sales_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`

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
    applyFilters()
})
</script>

<style scoped>
.sticky {
    position: sticky;
}
</style>
