<template>
    <div class="ideal-cost-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Ideal Cost Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Year-wise weekly totals by store
                </p>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @change="applyFilters"
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
                    <thead class="bg-gray-50 dark:bg-gray-900 md:sticky top-[-1px] !z-[101]">
                        <tr>
                            <Th class="w-12 text-center md:sticky md:left-0 !z-[101]">#</Th>
                            <Th class="w-[180px] md:sticky md:left-16 !z-[101]">Store</Th>
                            <Th class="text-right -[110px] md:sticky md:left-70 !z-[101] ">Total</Th>
                            <Th
                                v-for="(week, idx) in reportPayload.weeks"
                                :key="idx"
                                class="text-right w-[80px] whitespace-nowrap"
                            >
                                {{idx + 1 }} - {{ formatWeekLabel(week) }}
                            </Th>
                        </tr>
                        <tr class=" border-t border-gray-200 dark:border-gray-700">
                            <Td class="text-center md:sticky md:left-0 bg-gray-100 dark:bg-gray-800 !z-[101]" weight="bold" color="primary">Total</Td>
                            <Td class="md:sticky md:left-16 bg-gray-100 dark:bg-gray-800 !z-[101]" weight="bold" color="primary"></Td>
                            <Td class="text-right md:sticky bg-gray-100 dark:bg-gray-800 md:left-70  !z-[101] " weight="bold" color="primary">{{ formatCurrency(reportPayload.grand_total) }}</Td>
                            <Td
                                v-for="(week, idx) in reportPayload.weeks"
                                :key="'tot-' + idx"
                                class="text-right bg-gray-100 dark:bg-gray-800 "
                                weight="bold"
                                color="primary"

                            >
                                {{ formatCurrency(reportPayload.week_totals?.[week] ?? 0) }}
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
                            <Td class="text-right md:sticky md:left-70 bg-white dark:bg-gray-900 !z-[100] " weight="bold" color="primary">{{ formatCurrency(row.total) }}</Td>
                            <Td
                                v-for="(week, idx) in reportPayload.weeks"
                                :key="'r-' + idx"
                                class="text-right"
                            >
                               {{ formatCurrency(row.weeks?.[week] ?? 0) }}
                            </Td>
                        </tr>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="(reportPayload.weeks?.length ?? 0) + 3">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Ideal Cost Data Found"
                                    message="There is no ideal cost data for the selected year. Please select a different year or ensure ideal cost data has been entered."
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

const message = useMessage()
const loading = ref(false)
const exportLoading = ref(false)
const reportPayload = ref({
    weeks: [],
    week_totals: {},
    rows: [],
    grand_total: 0
})

const filters = ref({
    year: new Date().getFullYear(),
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
        const response = await useRequest('post', '/reports/ideal-cost/ideal-cost', {
            year: filters.value.year,
            workgroup_ids: filters.value.workgroup_ids
        })
        reportPayload.value = {
            weeks: response?.weeks ?? [],
            week_totals: response?.week_totals ?? {},
            rows: response?.rows ?? [],
            grand_total: response?.grand_total ?? 0
        }
    } catch (error) {
        console.error('Error loading ideal cost report:', error)
        message.error(error.response?.data?.message || 'Failed to load report')
        reportPayload.value = { weeks: [], week_totals: {}, rows: [], grand_total: 0 }
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
    const rows = reportPayload.value.rows
    if (!rows?.length) {
        message.error('No data to export')
        return
    }
    try {
        exportLoading.value = true
        const XLSX = await import('xlsx')
        const weeks = reportPayload.value.weeks ?? []
        const weekTotals = reportPayload.value.week_totals ?? {}
        const grandTotal = reportPayload.value.grand_total ?? 0

        const buildSheet = () => {
            const data = []
            data.push(['Ideal Cost Report'])
            data.push(['Year:', filters.value.year])
            data.push(['Generated:', new Date().toLocaleString()])
            data.push(['Total Stores:', rows.length])
            data.push([])

            const header1 = ['#', 'Store', 'Total', ...weeks.map((w, idx) => `${idx + 1} - ${formatWeekLabel(w)}`)]
            const header2 = ['Total', '', grandTotal, ...weeks.map(w => weekTotals[w] ?? 0)]
            data.push(header1)
            data.push(header2)
            rows.forEach((row, i) => {
                data.push([
                    i + 1,
                    row.company_name,
                    row.total ?? 0,
                    ...weeks.map(w => row.weeks?.[w] ?? 0)
                ])
            })
            data.push([])
            data.push(['Total', '', grandTotal, ...weeks.map(w => weekTotals[w] ?? 0)])
            return data
        }
        const aoa = buildSheet()
        const ws = XLSX.utils.aoa_to_sheet(aoa)
        const colCount = 3 + weeks.length
        ws['!cols'] = Array.from({ length: colCount }, (_, i) => ({ wch: i === 1 ? 28 : 14 }))
        const wb = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(wb, ws, 'Ideal Cost Report')
        XLSX.writeFile(wb, `Ideal_Cost_Report_${filters.value.year}.xlsx`)
        message.success('Report exported successfully!')
    } catch (error) {
        console.error('Export error:', error)
        message.error('Failed to export report')
    } finally {
        exportLoading.value = false
    }
}

onMounted(async () => {
    await loadWorkgroups()
    applyFilters()
})
</script>
