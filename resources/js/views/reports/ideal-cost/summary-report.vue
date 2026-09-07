<template>
    <div class="ideal-cost-company-totals-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Ideal Cost Summary Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Summary of ideal cost by company for a given week
                </p>
            </div>

            <!-- Filters -->
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
                        Select Week (Week Start – Week End)
                    </label>
                    <select
                        v-model="filters.selectedWeek"
                        @change="onWeekChange"
                        class="form-select"
                        >
                        <option :value="null">Select week</option>
                        <option v-for="week in availableWeeks" :key="week.value" :value="week.value">
                            {{ week.label }}
                        </option>
                    </select>
                </div>
                <div>   
                    <DynamicDropdown
                        v-model="filters.selectedPeriod"
                        :resource="`pj-calendars?year=${filters.selectedYear}`"
                        display-name="label"
                        placeholder="Select period"
                        icon-left="calendar"
                        label="Select period"
                        @change="onPeriodChange"
                    />
                </div>
                <div v-if="can('ideal-cost-summary-report', 'regional-director')">   
                    <DynamicDropdown
                        v-model="filters.regionalDirector"
                        :resource="`users?role=Regional Director&workgroups=${filters.workgroup_ids.join(',')}`"
                        display-name="name"
                        placeholder="Select user"
                        icon-left="user"
                        label="Select Regional Director"
                        @change="onRegionalDirectorChange"
                    />
                </div>
                <div v-if="can('ideal-cost-summary-report', 'area-manager')">
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
                            <Th class="w-12 text-center">#</Th>
                            <Th class="min-w-[160px]">Company</Th>
                            <Th class="text-right min-w-[110px]">Network Sales</Th>
                            <Th class="text-right min-w-[110px]">Ideal Cost</Th>
                            <Th class="text-right min-w-[110px]">Actual Purchase</Th>
                            <Th class="text-right min-w-[100px]">Diff (IC - AP)</Th>
                            <Th class="text-right min-w-[90px]">Std IC (IC/NS)</Th>
                            <Th class="text-right min-w-[90px]">Act Pur (AP/NS)</Th>
                            <Th class="text-right min-w-[90px]">Diff (diff/NS)</Th>
                        </tr>
                        <tr v-if="reportPayload.rows?.length && reportPayload.totals">
                            <Td class="text-center bg-gray-100 dark:bg-gray-800 !z-[101]" weight="bold" color="primary">Total</Td>
                            <Td class="bg-gray-100 dark:bg-gray-800 !z-[101]" weight="bold" color="primary"></Td>
                            <Td class="text-right bg-gray-100 dark:bg-gray-800" weight="bold">{{ formatCurrency(reportPayload.totals.network_sales) }}</Td>
                            <Td class="text-right bg-gray-100 dark:bg-gray-800" weight="bold">{{ formatCurrency(reportPayload.totals.ideal_cost) }}</Td>
                            <Td class="text-right bg-gray-100 dark:bg-gray-800" weight="bold">{{ formatCurrency(reportPayload.totals.actual_purchase) }}</Td>
                            <Td class="text-right bg-gray-100 dark:bg-gray-800" weight="bold">{{ formatCurrency(reportPayload.totals.diff) }}</Td>
                            <Td class="text-right bg-gray-100 dark:bg-gray-800" weight="bold">{{ formatPercent(reportPayload.totals.std_ic) }}</Td>
                            <Td class="text-right bg-gray-100 dark:bg-gray-800" weight="bold">{{ formatPercent(reportPayload.totals.act_pur) }}</Td>
                            <Td
                                class="text-right bg-gray-100 dark:bg-gray-800"
                                weight="bold"
                            >
                                {{ formatPercent(reportPayload.totals.diff_ns) }}
                            </Td>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800" v-if="reportPayload.rows?.length">
                        <tr
                            v-for="(row, index) in reportPayload.rows"
                            :key="row.company_id"
                            class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <Td class="text-center">{{ index + 1 }}</Td>
                            <Td>{{ row.company_name }}</Td>
                            <Td class="text-right">{{ formatCurrency(row.network_sales) }}</Td>
                            <Td class="text-right">{{ formatCurrency(row.ideal_cost) }}</Td>
                            <Td class="text-right">{{ formatCurrency(row.actual_purchase) }}</Td>
                            <Td class="text-right"   :style="{ backgroundColor: colorByCondition(row.diff) }">{{ formatCurrency(row.diff) }}</Td>
                            <Td class="text-right">{{ formatPercent(row.std_ic) }}</Td>
                            <Td class="text-right">{{ formatPercent(row.act_pur) }}</Td>
                            <Td
                                class="text-right"
                                :style="{ backgroundColor: colorByCondition(row.diff) }"
                            >
                                {{ formatPercent(row.diff_ns) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="9">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    message="There is no data for the selected week. Try a different week or ensure data has been entered."
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
                        Showing <span class="font-medium">{{ reportPayload.rows.length }}</span> companies
                        · <span class="font-medium">{{ currentWeekLabel }}</span>
                        <template v-if="filters.regionalDirector || filters.areaManager">
                            · Filtered by user
                        </template>
                    </template>
                    <template v-else>
                        No records to display
                    </template>
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
    rows: [],
    totals: null
})

const settingsItems = ref([])

const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedWeek: '',
    selectedPeriod: null,
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: []
})
const workgroups = ref([])

// Available years (current + past 5)
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

// One week = Monday (week start) to Sunday (week end)
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

const currentWeekLabel = computed(() => {
    if (!filters.value.selectedWeek) return 'No week selected'
    const w = availableWeeks.value.find(p => p.value === filters.value.selectedWeek)
    return w ? w.label : 'No week selected'
})

function setDefaultWeek() {
    const weeks = availableWeeks.value
    if (!weeks.length) return
    const now = new Date()
    const toYMD = (d) => d.toISOString().slice(0, 10)
    const today = toYMD(now)
    const current = weeks.find(w => w.startDate <= today && w.endDate >= today)
    filters.value.selectedWeek = current ? current.value : weeks[weeks.length - 1].value
}

function onYearChange() {
    setDefaultWeek()
    applyFilters()
}

function onPeriodChange() {
    filters.value.selectedWeek = null
    applyFilters()
}

function onWeekChange() {
    filters.value.selectedPeriod = null
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

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value ?? 0)
}

const formatPercent = (value) => {
    if (value == null) return '—'
    return new Intl.NumberFormat('en-US', {
        style: 'percent',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}

const colorByCondition = (value) => {
    if (value == null) return ''
    const floatValue = parseFloat(value)
    let color = ''
    settingsItems.value.forEach(item => {
        if (item.difference_condition === 'between' && floatValue >= item.difference_value_1 && floatValue <= item.difference_value_2) {
            color = item.difference_color
        } else if (item.difference_condition === 'less-than' && floatValue < item.difference_value_1) {
            color = item.difference_color
        } else if (item.difference_condition === 'greater-than' && floatValue > item.difference_value_1) {
            color = item.difference_color
        }
    })
    return color
}

function getSelectedWeekDates() {
    const v = filters.value.selectedWeek
    if (!v) return { start_date: null, end_date: null }
    const [start_date, end_date] = v.split('_')
    return { start_date, end_date }
}

const applyFilters = async () => {
    const { start_date, end_date } = getSelectedWeekDates()
    if (!start_date  && !filters.value.selectedPeriod) {
        message.error('Please select a week or period')
        return
    }
    try {
        loading.value = true
        const payload = {
            start_date,
            end_date,
            workgroup_ids: filters.value.workgroup_ids,
            selected_period: filters.value.selectedPeriod?.id ?? null
        }
        if (filters.value.regionalDirector || filters.value.areaManager) {
            payload.user_id = filters.value.regionalDirector?.id || filters.value.areaManager?.id
        }

        const response = await useRequest('post', '/reports/ideal-cost/ideal-cost-summary', payload)
        reportPayload.value = {
            rows: response?.rows ?? [],
            totals: response?.totals ?? null
        }
    } catch (error) {
        console.error('Error loading report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
        reportPayload.value = { rows: [], totals: null }
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

/** Convert CSS color (hex or name) to ExcelJS ARGB string */
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
        const totals = reportPayload.value.totals

        const wb = new ExcelJS.Workbook()
        const ws = wb.addWorksheet('Company Totals', { views: [{ state: 'frozen', ySplit: 6, activeCell: 'A1' }] })

        const headerFill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF374151' } }
        const headerFont = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 }
        const totalRowFill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } }
        const totalRowFont = { bold: true }
        const currencyNumFmt = '$#,##0.00'
        const percentNumFmt = '0.00%'
        const thinBorder = { style: 'thin', color: { argb: 'FFD1D5DB' } }

        let rowNum = 1

        ws.getCell(rowNum, 1).value = 'Ideal Cost Company Totals Report'
        ws.getCell(rowNum, 1).font = { bold: true, size: 14 }
        rowNum += 1
        const weekDates = getSelectedWeekDates()
        ws.getCell(rowNum, 1).value = 'Week Start:'
        ws.getCell(rowNum, 2).value = weekDates.start_date ?? ''
        rowNum += 1
        ws.getCell(rowNum, 1).value = 'Week End:'
        ws.getCell(rowNum, 2).value = weekDates.end_date ?? ''
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
        rowNum += 2

        const headers = ['#', 'Company', 'Network Sales', 'Ideal Cost', 'Actual Purchase', 'Diff (IC - AP)', 'Std IC (IC/NS)', 'Act Pur (AP/NS)', 'Diff (diff/NS)']
        headers.forEach((val, c) => {
            const cell = ws.getCell(rowNum, c + 1)
            cell.value = val
            cell.fill = headerFill
            cell.font = headerFont
            cell.alignment = c === 0 ? { horizontal: 'center' } : c === 1 ? { horizontal: 'left' } : { horizontal: 'right' }
            cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
        })
        rowNum += 1

        if (totals) {
            const totalRow = [
                'Total', '',
                totals.network_sales ?? 0,
                totals.ideal_cost ?? 0,
                totals.actual_purchase ?? 0,
                totals.diff ?? 0,
                totals.std_ic ?? 0,
                totals.act_pur ?? 0,
                totals.diff_ns ?? 0
            ]
            totalRow.forEach((val, c) => {
                const cell = ws.getCell(rowNum, c + 1)
                cell.value = val
                cell.fill = totalRowFill
                cell.font = totalRowFont
                cell.alignment = c === 0 ? { horizontal: 'center' } : c === 1 ? { horizontal: 'left' } : { horizontal: 'right' }
                if (c >= 2 && c <= 5) cell.numFmt = currencyNumFmt
                if (c >= 6) cell.numFmt = percentNumFmt
                cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
                if (c === 5 || c === 8) {
                    const color = colorByCondition(totals.diff)
                    const argb = toExcelArgb(color)
                    if (argb) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } }
                }
            })
            rowNum += 1
        }

        rows.forEach((row, i) => {
            const rowData = [
                i + 1,
                row.company_name,
                row.network_sales ?? 0,
                row.ideal_cost ?? 0,
                row.actual_purchase ?? 0,
                row.diff ?? 0,
                row.std_ic ?? 0,
                row.act_pur ?? 0,
                row.diff_ns ?? 0
            ]
            rowData.forEach((val, c) => {
                const cell = ws.getCell(rowNum, c + 1)
                cell.value = val
                cell.alignment = c === 0 ? { horizontal: 'center' } : c === 1 ? { horizontal: 'left' } : { horizontal: 'right' }
                if (c >= 2 && c <= 5) cell.numFmt = currencyNumFmt
                if (c >= 6) cell.numFmt = percentNumFmt
                cell.border = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder }
                if (c === 5 || c === 8) {
                    const color = colorByCondition(row.diff)
                    const argb = toExcelArgb(color)
                    if (argb) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } }
                }
            })
            rowNum += 1
        })

        ws.columns = [
            { width: 6 }, { width: 28 }, { width: 14 }, { width: 14 }, { width: 14 },
            { width: 14 }, { width: 12 }, { width: 12 }, { width: 12 }
        ]
        const buf = await wb.xlsx.writeBuffer()
        const blob = new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `Ideal_Cost_Company_Totals_${weekDates.start_date}_${weekDates.end_date}.xlsx`
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
    try {
        const response = await useRequest('post', 'settings/settings-items', { report_type: 'ideal-cost-summary-report' })
        settingsItems.value = response.settingsItems || []
    } catch {
        settingsItems.value = []
    }
}

onMounted(async () => {
    setDefaultWeek()
    await loadWorkgroups()
    fetchSettingsItems()
    applyFilters()
})
</script>
