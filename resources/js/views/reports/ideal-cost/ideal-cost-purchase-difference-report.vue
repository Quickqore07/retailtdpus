<template>
    <div class="ideal-cost-purchase-difference-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Ideal Cost & Purchase Difference Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Year-wise weekly comparison of ideal cost vs purchase amounts by store
                </p>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select
                        v-model="filters.year"
                        @change="onYearChange"
                        class="form-select"
                    >
                        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
                <div v-if="can('ideal-cost-purchase-difference-report', 'regional-director')">
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
                <div v-if="can('ideal-cost-purchase-difference-report', 'area-manager')">
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
                        :disabled="!reportPayload.rows?.length"
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
                    <thead class="bg-gray-50 dark:bg-gray-900 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th class="w-12 text-center md:sticky md:left-0 !z-[101]">#</Th>
                            <Th class="w-[180px] md:sticky md:left-16 !z-[101]">Store</Th>
                            <Th class="text-right w-[110px] md:sticky md:left-70 !z-[101] bg-gray-50 dark:bg-gray-800">Difference Total</Th>
                            <Th
                                v-for="(week, idx) in reportPayload.weeks"
                                :key="idx"
                                class="text-right w-[80px] whitespace-nowrap"
                            >
                                {{idx + 1 }} - {{ formatWeekLabel(week) }}
                            </Th>
                        </tr>
                        <tr class="">
                            <Td class="text-center md:sticky md:left-0 bg-gray-100 dark:bg-gray-800 !z-[101]" weight="bold" color="primary">Total</Td>
                            <Td class="md:sticky md:left-16 bg-gray-100 dark:bg-gray-800 !z-[101]" weight="bold" color="primary"></Td>
                            <Td class="text-right md:sticky bg-gray-100 dark:bg-gray-800 md:left-70  !z-[101] " weight="bold" :style="{ backgroundColor: reportPayload.grand_total?.color }">{{ formatCurrency(reportPayload.grand_total) }}</Td>
                            <Td
                                v-for="(week, idx) in reportPayload.weeks"
                                :key="'tot-' + idx"
                                class="text-right bg-gray-100 dark:bg-gray-800 "
                                weight="bold"
                                :style="{ backgroundColor: reportPayload.week_totals?.[week]?.color }"
                            >
                                {{ formatCurrency(reportPayload.week_totals?.[week]?.value ?? 0) }}
                            </Td>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="reportPayload.rows?.length">
                        <tr
                            v-for="(row, index) in reportPayload.rows"
                            :key="row.company_id"
                            class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[100]"
                        >
                            <Td class="text-center md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[100]">{{ index + 1 }}</Td>
                            <Td class="md:sticky md:left-16 bg-white dark:bg-gray-900 !z-[100]">{{ row.company_name }}</Td>
                            <Td class="text-right md:sticky md:left-70 bg-gray-50 dark:bg-gray-800 !z-[100] " weight="bold" :style="{ backgroundColor: row.total?.color }">{{ formatCurrency(row.total?.value) }}</Td>
                            <Td
                                v-for="(week, idx) in reportPayload.weeks"
                                :key="'r-' + idx"
                                class="text-right"
                                :style="{ backgroundColor: row.weeks?.[week]?.color }"
                            >
                               {{ formatCurrency(row.weeks?.[week]?.value ?? 0) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="(reportPayload.weeks?.length ?? 0) + 3">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="There is no ideal cost or purchase data for the selected year. Please select a different year or ensure data has been entered."
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
                </table>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <template v-if="reportPayload.rows?.length">
                        Showing <span class="font-medium">{{ reportPayload.rows.length }}</span> stores
                        · Year <span class="font-medium">{{ filters.year }}</span>
                        <template v-if="filters.regionalDirector || filters.areaManager">
                            · Filtered by user
                        </template>
                    </template>
                    <template v-else>
                        No records to display
                    </template>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ reportPayload.weeks?.length ?? 0 }} weeks
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
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'

const message = useMessage()
const loading = ref(false)
const exportLoading = ref(false)
const {can} = usePermission()
const reportPayload = ref({
    weeks: [],
    week_totals: {},
    rows: [],
    grand_total: 0
})

const settingsItems = ref([])
const filters = ref({
    year: new Date().getFullYear(),
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: []
})
const workgroups = ref([])

const availableYears = computed(() => {
    const current = new Date().getFullYear()
    const out = []
    for (let i = 0; i <= 6; i++) out.push(current - i)
    return out
})

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

const formatWeekLabel = (dateStr) => {
    if (!dateStr) return ''
    const [year, month, day] = dateStr.split('-').map(Number)
    return `${month}/${day}`
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
    try {
        loading.value = true
        const payload = { year: filters.value.year }
        if (filters.value.regionalDirector || filters.value.areaManager) {
            payload.user_id = filters.value.regionalDirector?.id || filters.value.areaManager?.id
        }
        payload.workgroup_ids = filters.value.workgroup_ids
        
        const response = await useRequest('post', '/reports/ideal-cost/ideal-cost-purchase-difference', payload)
        reportPayload.value = {
            weeks: response?.weeks ?? [],
            week_totals: response?.week_totals ?? {},
            rows: response?.rows ?? [],
            grand_total: response?.grand_total ?? 0
        }


            reportPayload.value.rows.forEach(row => {
                const weeks = {};
                const total = {
                    color: '',
                    value: row.total
                }
                row.total = total
                Object.entries(row?.weeks)?.forEach(([week, value]) => {
                    weeks[week] = {
                        week: week,
                        color: colorByCondition(value),
                        value: value
                    }
                })
                console.log(weeks);
                row.weeks = weeks
            })
            Object.entries(reportPayload.value.week_totals)?.forEach(([week, value]) => {
                reportPayload.value.week_totals[week]={
                    color: '',
                    value: value
                };
            })
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
        reportPayload.value = {
            weeks: [],
            week_totals: {},
            rows: [],
            grand_total: 0
        }
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
const colorByCondition = (value) => {
    if (settingsItems.value.length === 0) return ''
    let floatValue = parseFloat(value)
    let color = ''
    settingsItems.value.forEach(item => {
        if (item.difference_condition === 'between' && floatValue >= item.difference_value_1 && floatValue <= item.difference_value_2) {
            color = item.difference_color
        }else if (item.difference_condition === 'less-than' && floatValue < item.difference_value_1) {
            color = item.difference_color
        }else if (item.difference_condition === 'greater-than' && floatValue > item.difference_value_1) {
            color = item.difference_color
        }
    })
    return color
}

/** Convert CSS color (hex or name) to ExcelJS ARGB string (e.g. 'FFFF0000'). */
const toExcelArgb = (color) => {
    if (!color || color === 'primary') return null
    const hex = color.startsWith('#') ? color.slice(1) : color
    if (/^[0-9A-Fa-f]{6}$/.test(hex)) return 'FF' + hex.toUpperCase()
    const named = {
        red: 'FF0000', green: '00FF00', blue: '0000FF', yellow: 'FFFF00',
        orange: 'FFA500', gray: '808080', grey: '808080'
    }
    const rgb = named[color.toLowerCase()]
    return rgb ? 'FF' + rgb : null
}

const exportReport = async () => {
    const rows = reportPayload.value.rows
    if (!rows?.length) {
        message.error('No data to export')
        return
    }
    try {
        exportLoading.value = true
        const excelMod = await import('exceljs')
        const ExcelJS = excelMod.default ?? excelMod
        const weeks = reportPayload.value.weeks ?? []
        const weekTotals = reportPayload.value.week_totals ?? {}
        const grandTotalRaw = reportPayload.value.grand_total
        const grandTotalVal = typeof grandTotalRaw === 'object' && grandTotalRaw?.value !== undefined ? grandTotalRaw.value : grandTotalRaw
        const grandTotalColor = typeof grandTotalRaw === 'object' ? grandTotalRaw?.color : null

        const wb = new ExcelJS.Workbook()
        const ws = wb.addWorksheet('IC & Purchase Difference', { views: [{ state: 'frozen', ySplit: 7, activeCell: 'A1' }] })

        const headerFill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF374151' } }
        const headerFont = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 }
        const totalRowFill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } }
        const totalRowFont = { bold: true }
        const colCount = 3 + weeks.length
        const currencyNumFmt = '$#,##0.00'
        const thinBorder = { style: 'thin', color: { argb: 'FFD1D5DB' } }

        let rowNum = 1

        ws.getCell(rowNum, 1).value = 'Ideal Cost & Purchase Difference Report'
        ws.getCell(rowNum, 1).font = { bold: true, size: 14 }
        rowNum += 1
        ws.getCell(rowNum, 1).value = 'Year:'
        ws.getCell(rowNum, 2).value = filters.value.year
        rowNum += 1
        if (filters.value.regionalDirector) {
            ws.getCell(rowNum, 1).value = 'Regional Director:'
            ws.getCell(rowNum, 2).value = filters.value.regionalDirector?.name ?? ''
            rowNum += 1
        }
        if (filters.value.areaManager) {
            ws.getCell(rowNum, 1).value = 'Area Manager:'
            ws.getCell(rowNum, 2).value = filters.value.areaManager?.name ?? ''
            rowNum += 1
        }
        ws.getCell(rowNum, 1).value = 'Generated:'
        ws.getCell(rowNum, 2).value = new Date().toLocaleString()
        rowNum += 1
        ws.getCell(rowNum, 1).value = 'Total Stores:'
        ws.getCell(rowNum, 2).value = rows.length
        rowNum += 2

        const dataStartRow = rowNum
        const headerRow = ['#', 'Store', 'Difference Total', ...weeks.map((w, idx) => `${idx + 1} - ${formatWeekLabel(w)}`)]
        headerRow.forEach((val, c) => {
            const cell = ws.getCell(rowNum, c + 1)
            cell.value = val
            cell.fill = headerFill
            cell.font = headerFont
            cell.alignment = c === 0 ? { horizontal: 'center' } : c === 1 ? { horizontal: 'left' } : { horizontal: 'right' }
            cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
        })
        rowNum += 1

        const totalRow = ['Total', '', grandTotalVal ?? 0, ...weeks.map(w => {
            const t = weekTotals[w]
            return (t && typeof t === 'object' && t.value !== undefined) ? t.value : (t ?? 0)
        })]
        totalRow.forEach((val, c) => {
            const cell = ws.getCell(rowNum, c + 1)
            cell.value = val
            cell.fill = totalRowFill
            cell.font = totalRowFont
            cell.alignment = c === 0 ? { horizontal: 'center' } : c === 1 ? { horizontal: 'left' } : { horizontal: 'right' }
            if (c >= 2) cell.numFmt = currencyNumFmt
            cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
            if (c === 2 && grandTotalColor) {
                const argb = toExcelArgb(grandTotalColor)
                if (argb) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } }
            } else if (c >= 3) {
                const weekKey = weeks[c - 3]
                const wt = weekTotals[weekKey]
                const color = wt && typeof wt === 'object' ? wt.color : null
                const argb = toExcelArgb(color)
                if (argb) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } }
            }
        })
        rowNum += 1

        rows.forEach((row, i) => {
            const totalObj = row.total
            const totalVal = totalObj && typeof totalObj === 'object' && totalObj.value !== undefined ? totalObj.value : (row.total ?? 0)
            const totalColor = totalObj && typeof totalObj === 'object' ? totalObj.color : null
            const rowData = [
                i + 1,
                row.company_name,
                totalVal,
                ...weeks.map(w => {
                    const cellData = row.weeks?.[w]
                    return (cellData && typeof cellData === 'object' && cellData.value !== undefined) ? cellData.value : (cellData ?? 0)
                })
            ]
            rowData.forEach((val, c) => {
                const cell = ws.getCell(rowNum, c + 1)
                cell.value = val
                cell.alignment = c === 0 ? { horizontal: 'center' } : c === 1 ? { horizontal: 'left' } : { horizontal: 'right' }
                if (c >= 2) cell.numFmt = currencyNumFmt
                cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
                if (c === 2) {
                    const argb = toExcelArgb(totalColor)
                    if (argb) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } }
                }
                if (c >= 3) {
                    const weekKey = weeks[c - 3]
                    const cellData = row.weeks?.[weekKey]
                    const color = cellData && typeof cellData === 'object' ? cellData.color : null
                    const argb = toExcelArgb(color)
                    if (argb) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } }
                }
            })
            rowNum += 1
        })

        ws.autoFilter = {
            from: { row: dataStartRow, column: 1 },
            to: { row: rowNum - 1, column: colCount }
        }
        ws.columns = Array.from({ length: colCount }, (_, i) => ({ width: i === 1 ? 28 : 14 }))
        const buf = await wb.xlsx.writeBuffer()
        const blob = new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `Ideal_Cost_Purchase_Difference_Report_${filters.value.year}.xlsx`
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

const fetchSettingsItems = async () => {
    const response = await useRequest('post', 'settings/settings-items', { report_type: 'ideal-cost-purchase-difference-report' })
    settingsItems.value = response.settingsItems || []
}
onMounted(async () => {
    await loadWorkgroups()
    fetchSettingsItems()
    applyFilters()
})
</script>
