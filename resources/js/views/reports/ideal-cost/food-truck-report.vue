<template>
    <div class="food-truck-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Food Truck Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Food truck planning report by store for selected week
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Week (Week Start - Week End)
                    </label>
                    <select
                        v-model="filters.selectedWeek"
                        class="form-select"
                        @change="applyFilters"
                    >
                        <option v-for="week in availableWeeks" :key="week.value" :value="week.value">
                            {{ week.label }}
                        </option>
                    </select>
                </div>
                <div v-if="can('food-truck-report', 'regional-director')">   
                    <DynamicDropdown
                        v-model="filters.regionalDirector"
                        :resource="`users?role=Regional Director&workgroups=${filters.workgroup_ids.join(',')}`"
                        display-name="name"
                        placeholder="Select RM"
                        icon-left="user"
                        label="Select Regional Director"
                        @change="onRegionalDirectorChange"
                    />
                </div>
                <div v-if="can('food-truck-report', 'area-manager')">
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
                        :disabled="!rows.length"
                    >
                        Export to Excel
                    </Button>
                </div>
            </div>
        </Panel>

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th class="w-12 text-center">#</Th>
                            <Th class="min-w-[180px]">Store</Th>
                            <Th class="text-right min-w-[120px]">Sales Projection</Th>
                            <Th class="text-right min-w-[120px]">Ideal Cost %</Th>
                            <Th class="text-right min-w-[120px]">Food Projection</Th>
                            <Th class="text-right min-w-[120px]">Pre Wk Extra</Th>
                            <Th class="text-right min-w-[120px]">Net Order</Th>
                            <!-- <Th class="text-right min-w-[100px]">Truck-1</Th>
                            <Th class="text-right min-w-[90px]">Diff</Th>
                            <Th class="text-right min-w-[100px]">Truck-2</Th> -->
                            <template v-for="i in trucks" :key="i">
                                <Th class="text-right min-w-[100px]">Truck-{{ i }}</Th>
                                <Th class="text-right min-w-[90px]" v-if="i < trucks">Diff</Th>
                            </template>
                            <Th class="text-right min-w-[100px]">Total</Th>
                            <Th class="text-right min-w-[90px]">Diff</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800" v-if="rows.length">
                        <tr
                            v-for="(row, index) in rows"
                            :key="row.company_id"
                            class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <Td class="text-center">{{ index + 1 }}</Td>
                            <Td>{{ row.store }}</Td>
                            <Td class="text-right">{{ formatCurrency(row.sales_projection) }}</Td>
                            <Td class="text-right">{{ row.ideal_cost_percent ?? 0 }}%</Td>
                            <Td class="text-right">{{ formatCurrency(row.ideal_food_projection) }}</Td>
                            <Td class="text-right">{{ row.pre_wk_extra < 0 ? formatCurrency(row.pre_wk_extra) : '-' }}</Td>
                            <Td class="text-right">{{ formatCurrency(row.net_order) }}</Td>
                            <template v-for="i in trucks" :key="i">
                                <Td class="text-right">{{ formatCurrency(row['truck_'+i]) }}</Td>
                                <Td class="text-right" v-if="i < trucks"    >{{ formatCurrency(row['diff_after_truck_'+i]) }}</Td>
                            </template>
                            <Td class="text-right">{{ formatCurrency(row.total) }}</Td>
                            <Td class="text-right">{{ formatCurrency(row.diff_after_total) }}</Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="12">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="No records found for selected filters."
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
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
const message = useMessage()
const loading = ref(false)
const exportLoading = ref(false)
const rows = ref([])
const trucks = ref(2)
const {can} = usePermission()
const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedWeek: '',
    regionalDirector: null,
    areaManager: null,
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

const availableWeeks = computed(() => {
    const year = filters.value.selectedYear
    const weeks = []
    let startDate = new Date(Date.UTC(year, 0, 1))
    const dayOfWeek = startDate.getUTCDay()
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

    const formatDate = (d) => {
        const m = String(d.getUTCMonth() + 1).padStart(2, '0')
        const day = String(d.getUTCDate()).padStart(2, '0')
        return `${m}-${day}-${d.getUTCFullYear()}`
    }
    const toYMD = (d) => d.toISOString().slice(0, 10)

    while (startDate.getUTCFullYear() === year || startDate.getUTCFullYear() === year - 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 6)
        if (endDate.getUTCFullYear() > year) break

        const label = `${formatDate(startDate)} To ${formatDate(endDate)}`
        const value = `${toYMD(startDate)}_${toYMD(endDate)}`
        weeks.push({
            label,
            value,
            startDate: toYMD(startDate),
            endDate: toYMD(endDate)
        })
        startDate.setUTCDate(startDate.getUTCDate() + 7)
    }

    return weeks
})

function setDefaultWeek() {
    const weeks = availableWeeks.value
    if (!weeks.length) return

    const now = new Date()
    const today = now.toISOString().slice(0, 10)
    const current = weeks.find(w => w.startDate <= today && w.endDate >= today)
    filters.value.selectedWeek = current ? current.value : weeks[weeks.length - 1].value
}

function onYearChange() {
    setDefaultWeek()
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

function getSelectedWeekDates() {
    const v = filters.value.selectedWeek
    if (!v) return { start_date: null, end_date: null }
    const [start_date, end_date] = v.split('_')
    return { start_date, end_date }
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value ?? 0)
}


const applyFilters = async () => {
    const { start_date, end_date } = getSelectedWeekDates()
    if (!start_date || !end_date) {
        message.error('Please select a week')
        return
    }

    try {
        loading.value = true
        const payload = {
            year: filters.value.selectedYear,
            start_date,
            end_date,
            workgroup_ids: filters.value.workgroup_ids
        }
        if (filters.value.regionalDirector?.id || filters.value.areaManager?.id) {
            payload.user_id = filters.value.regionalDirector?.id || filters.value.areaManager?.id
        }

        const response = await useRequest('post', '/reports/ideal-cost/food-truck', payload)
        rows.value = response?.rows ?? []
        if (response && typeof response.trucks === 'number' && response.trucks >= 0) {
            trucks.value = response.trucks
        }
    } catch (error) {
        console.error('Error loading food truck report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
        rows.value = []
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
    if (!rows.value.length) {
        message.error('No data to export')
        return
    }

    try {
        exportLoading.value = true
        const excelMod = await import('exceljs')
        const ExcelJS = excelMod.default ?? excelMod
        const wb = new ExcelJS.Workbook()
        const ws = wb.addWorksheet('Food Truck Report', {
            views: [{ state: 'frozen', ySplit: 6, activeCell: 'A1' }]
        })

        const headerFill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF374151' } }
        const headerFont = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 }
        const currencyNumFmt = '$#,##0.00'
        const percentNumFmt = '0.00%'
        const thinBorder = { style: 'thin', color: { argb: 'FFD1D5DB' } }

        let rowNum = 1

        ws.getCell(rowNum, 1).value = 'Food Truck Report'
        ws.getCell(rowNum, 1).font = { bold: true, size: 14 }
        rowNum += 1

        const week = availableWeeks.value.find(w => w.value === filters.value.selectedWeek)
        ws.getCell(rowNum, 1).value = 'Year:'
        ws.getCell(rowNum, 2).value = filters.value.selectedYear
        rowNum += 1

        ws.getCell(rowNum, 1).value = 'Week:'
        ws.getCell(rowNum, 2).value = week?.label ?? ''
        rowNum += 1

        ws.getCell(rowNum, 1).value = 'Regional Director:'
        ws.getCell(rowNum, 2).value = filters.value.regionalDirector?.name ?? 'All'
        rowNum += 1

        ws.getCell(rowNum, 1).value = 'Area Manager:'
        ws.getCell(rowNum, 2).value = filters.value.areaManager?.name ?? 'All'
        rowNum += 1

        ws.getCell(rowNum, 1).value = 'Generated:'
        ws.getCell(rowNum, 2).value = new Date().toLocaleString()
        rowNum += 2

        const headers = [
            '#',
            'Store',
            'Sales Projection',
            'Ideal Cost %',
            'Food Projection',
            'Pre Wk Extra',
            'Net Order'
            // 'Truck-1',
            // 'Diff',
            // 'Truck-2',
            // 'Total',
            // 'Diff'
        ]
        for(let i=1; i<=trucks.value; i++) {
            headers.push('Truck-'+i)
            if(i < trucks.value) {
                headers.push('Diff')
            }
        }
        headers.push('Total')
        headers.push('Diff')

        headers.forEach((val, c) => {
            const cell = ws.getCell(rowNum, c + 1)
            cell.value = val
            cell.fill = headerFill
            cell.font = headerFont
            cell.alignment = c <= 1 ? { horizontal: c === 0 ? 'center' : 'left' } : { horizontal: 'right' }
            cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
        })
        rowNum += 1

        rows.value.forEach((row, index) => {
            const preWk = Number(row.pre_wk_extra ?? 0)
            const rowData = [
                index + 1,
                row.store ?? '',
                Number(row.sales_projection ?? 0),
                Number(row.ideal_cost_percent ?? 0) / 100,
                Number(row.ideal_food_projection ?? 0),
                preWk >= 0 ? 0 : preWk,
                Number(row.net_order ?? 0)
            ]

            for (let i = 1; i <= trucks.value; i++) {
                rowData.push(Number(row[`truck_${i}`] ?? 0))
                if (i < trucks.value) {
                    rowData.push(Number(row[`diff_after_truck_${i}`] ?? 0))
                }
            }
            rowData.push(Number(row.total ?? 0))
            rowData.push(Number(row.diff_after_total ?? 0))

            rowData.forEach((val, c) => {
                const cell = ws.getCell(rowNum, c + 1)
                cell.value = val
                cell.alignment = c <= 1 ? { horizontal: c === 0 ? 'center' : 'left' } : { horizontal: 'right' }
                if (c === 3) {
                    cell.numFmt = percentNumFmt
                } else if (c >= 2 && c !== 3) {
                    cell.numFmt = currencyNumFmt
                }
                cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
            })
            rowNum += 1
        })

        ws.columns = headers.map((_, i) => ({
            width: i === 0 ? 6 : i === 1 ? 30 : 14
        }))

        const { start_date, end_date } = getSelectedWeekDates()
        const buf = await wb.xlsx.writeBuffer()
        const blob = new Blob([buf], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `Food_Truck_Report_${start_date ?? 'start'}_${end_date ?? 'end'}.xlsx`
        a.click()
        URL.revokeObjectURL(url)
        message.success('Report exported successfully!')
    } catch (error) {
        console.error('Export error:', error)
        message.error('Failed to export report')
    } finally {
        exportLoading.value = false
    }
}

onMounted(async () => {
    setDefaultWeek()
    await loadWorkgroups()
    applyFilters()
})
</script>
