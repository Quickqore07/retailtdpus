<template>
    <div class="monthly-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ reportTitle }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Monthly expenses by store for the selected expense type
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div>
                    <DynamicDropdown
                        v-model="filters.expenseType"
                        resource="expense-types?active=1"
                        display-name="name"
                        placeholder="Select Expense Type"
                        icon-left="tag"
                        label="Expense Type"
                        @change="onExpenseTypeChange"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select v-model="filters.selectedYear" class="form-select">
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div v-if="can('monthly-report', 'regional-director')">
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
                <div v-if="can('monthly-report', 'area-manager')">
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
            </div>
            <div class="mb-6">
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

            <div class="flex flex-wrap items-center gap-3">
                <Button
                    icon-left="refresh"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    @click="applyFilters"
                    :loading="loading"
                    :disabled="!filters.expenseType"
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
                    :disabled="reportData.length === 0"
                >
                    Export to Excel
                </Button>
            </div>
        </Panel>

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th rowspan="2" class="md:sticky md:left-0 !z-[101] bg-gray-100 dark:bg-gray-800">Sr. No.</Th>
                            <Th rowspan="2" class="md:sticky md:left-12 !z-[101] bg-gray-100 dark:bg-gray-800">Company Name</Th>
                            <Th rowspan="2">Providers</Th>
                            <Th v-if="showFrequency" rowspan="2">Freq.</Th>
                            <Th rowspan="2" class="text-right border-r border-gray-200 dark:border-gray-700">Total Amount</Th>
                            <Th
                                v-for="(label, monthKey) in months"
                                :key="monthKey"
                                :colspan="monthColumnCount"
                                class="!text-center border-r border-gray-200 dark:border-gray-700"
                            >
                                {{ label }}
                            </Th>
                        </tr>
                        <tr>
                            <template v-for="(label, monthKey) in months" :key="'sub-' + monthKey">
                                <Th class="text-right" :class="{ 'border-r border-gray-200 dark:border-gray-700': !showOtherAmount }">{{ amountLabel }}</Th>
                                <Th v-if="showOtherAmount" class="text-right">{{ otherAmountLabel }}</Th>
                                <Th v-if="showOtherAmount" class="text-right border-r border-gray-200 dark:border-gray-700">Total</Th>
                            </template>
                        </tr>
                    </thead>
                    <tbody
                        v-if="reportData.length > 0"
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="(item, index) in reportData"
                            :key="index"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <Td class="md:sticky md:left-0 z-10 bg-white dark:bg-gray-800">{{ index + 1 }}</Td>
                            <Td weight="medium" class="md:sticky md:left-12 z-10 bg-white dark:bg-gray-800">{{ item.company_name }}</Td>
                            <Td color="secondary">{{ item.providers }}</Td>
                            <Td v-if="showFrequency" color="secondary">{{ item.freq }}</Td>
                            <Td align="right" weight="medium" class="border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(item.total_amount) }}</Td>
                            <template v-for="(label, monthKey) in months" :key="monthKey">
                                <Td align="right" :class="{ 'border-r border-gray-200 dark:border-gray-700': !showOtherAmount }">{{ formatCurrency(item.months?.[monthKey]?.amount) }}</Td>
                                <Td v-if="showOtherAmount" align="right">{{ formatOtherAmount(item.months?.[monthKey]?.other_amount) }}</Td>
                                <Td v-if="showOtherAmount" align="right" weight="medium" class="border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(item.months?.[monthKey]?.total) }}</Td>
                            </template>
                        </tr>
                    </tbody>
                    <tbody v-else class="bg-white dark:bg-gray-800">
                        <tr>
                            <td :colspan="tableColumnCount" class="p-8">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Data Found"
                                    :message="noDataMessage"
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        v-if="reportData.length > 0"
                        class="bg-gray-100 dark:bg-gray-800 font-semibold"
                    >
                        <tr>
                            <Td class="md:sticky md:left-0 z-10 bg-gray-100 dark:bg-gray-800"></Td>
                            <Td weight="bold" color="primary" class="md:sticky md:left-12 !z-[100] bg-gray-100 dark:bg-gray-800">TOTALS</Td>
                            <Td class="bg-gray-100 dark:bg-gray-800"></Td>
                            <Td v-if="showFrequency" class="bg-gray-100 dark:bg-gray-800"></Td>
                            <Td align="right" weight="bold" class="border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(totals.total_amount) }}</Td>
                            <template v-for="(label, monthKey) in months" :key="'total-' + monthKey">
                                <Td align="right" weight="bold" :class="{ 'border-r border-gray-200 dark:border-gray-700': !showOtherAmount }">{{ formatCurrency(totals.months?.[monthKey]?.amount) }}</Td>
                                <Td v-if="showOtherAmount" align="right" weight="bold">{{ formatOtherAmount(totals.months?.[monthKey]?.other_amount) }}</Td>
                                <Td v-if="showOtherAmount" align="right" weight="bold" class="border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(totals.months?.[monthKey]?.total) }}</Td>
                            </template>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ reportData.length }}</span> stores
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
import { useRoute } from 'vue-router'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import { formatCurrency } from '@/utils/number'
import { getExpenseAmountLabels } from '@/utils/purchaseInvoiceExpense'

const route = useRoute()
const message = useMessage()
const { can } = usePermission()
const workgroups = ref([])
const expenseType = ref(null)

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const filters = ref({
    expenseType: null,
    selectedYear: availableYears.value[0],
    regionalDirector: null,
    areaManager: null,
    workgroup_ids: [],
})

const reportData = ref([])
const months = ref({})
const totals = ref({ amount: 0, other_amount: 0, total_amount: 0, months: {} })
const loading = ref(false)
const exportLoading = ref(false)

const amountLabels = computed(() => getExpenseAmountLabels(expenseType.value))
const amountLabel = computed(() => amountLabels.value.amount)
const otherAmountLabel = computed(() => amountLabels.value.otherAmount)
const showOtherAmount = computed(() => amountLabels.value.showOtherAmount)
const showFrequency = computed(() => expenseType.value?.name === 'Trash Tickets')
const monthColumnCount = computed(() => (showOtherAmount.value ? 3 : 1))
const fixedColumnCount = computed(() => 3 + (showFrequency.value ? 1 : 0) + 1)
const tableColumnCount = computed(() => fixedColumnCount.value + Object.keys(months.value).length * monthColumnCount.value)
const reportTitle = computed(() => {
    const name = expenseType.value?.name
    return name ? `${name} Monthly Report` : 'Monthly Report'
})
const noDataMessage = computed(() => {
    if (!filters.value.expenseType) {
        return 'Select an expense type to load the report.'
    }
    return `No ${expenseType.value?.name || ''} invoices match the selected year.`
})

const formatOtherAmount = (value) => {
    if (expenseType.value?.name === 'Trash Tickets') {
        if (!value && value !== 0) return '0'
        return Math.round(parseFloat(value)).toLocaleString()
    }
    return formatCurrency(value)
}

const onExpenseTypeChange = () => {
    expenseType.value = filters.value.expenseType
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

const loadWorkgroups = async () => {
    const response = await useRequest('get', '/search/workgroups?query=&column=name')
    const collection = response?.collection ?? []
    workgroups.value = collection
    filters.value.workgroup_ids = collection.filter(item => item.name == 'PA').map((item) => item.id)
}

const loadExpenseTypeFromQuery = async () => {
    const expenseId = route.query.expense_id
    if (!expenseId) return

    const response = await useRequest('get', `/expense-types/${expenseId}`)
    const item = response?.model ?? response?.item ?? response
    if (item?.id) {
        filters.value.expenseType = item
        expenseType.value = item
    }
}

const buildReportPayload = () => ({
    year: filters.value.selectedYear,
    expense_id: filters.value.expenseType?.id,
    user_id: filters.value.regionalDirector?.id || filters.value.areaManager?.id,
    workgroup_ids: filters.value.workgroup_ids,
})

const applyFilters = async () => {
    if (!filters.value.expenseType?.id) {
        reportData.value = []
        months.value = {}
        totals.value = { amount: 0, other_amount: 0, total_amount: 0, months: {} }
        return
    }

    try {
        loading.value = true
        const response = await useRequest('post', '/reports/ap/monthly', buildReportPayload())
        reportData.value = response?.data ?? []
        months.value = response?.months ?? {}
        totals.value = response?.totals ?? { amount: 0, other_amount: 0, total_amount: 0, months: {} }
        if (response?.expense_type) {
            expenseType.value = response.expense_type
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    if (!reportData.value.length) {
        message.error('No data to export')
        return
    }

    try {
        exportLoading.value = true
        const XLSX = await import('xlsx')
        const exportData = [
            [reportTitle.value],
            ['Year:', filters.value.selectedYear],
            ['Expense Type:', expenseType.value?.name || ''],
            ['Generated:', new Date().toLocaleString()],
        ]
        if (filters.value.regionalDirector) {
            exportData.push(['Regional Director:', filters.value.regionalDirector.name])
        }
        if (filters.value.areaManager) {
            exportData.push(['Area Manager:', filters.value.areaManager.name])
        }
        exportData.push([])

        const headerRow1 = ['Sr. No.', 'Company Name', 'Providers']
        const headerRow2 = ['', '', '']
        if (showFrequency.value) {
            headerRow1.push('Freq.')
            headerRow2.push('')
        }
        headerRow1.push('Total Amount')
        headerRow2.push('')

        Object.values(months.value).forEach((label) => {
            headerRow1.push(label, ...(showOtherAmount.value ? ['', ''] : []))
            headerRow2.push(
                amountLabel.value,
                ...(showOtherAmount.value ? [otherAmountLabel.value, 'Total'] : []),
            )
        })
        exportData.push(headerRow1, headerRow2)

        reportData.value.forEach((item, index) => {
            const row = [
                index + 1,
                item.company_name,
                item.providers,
            ]
            if (showFrequency.value) {
                row.push(item.freq)
            }
            row.push(item.total_amount)

            Object.keys(months.value).forEach((monthKey) => {
                row.push(item.months?.[monthKey]?.amount ?? 0)
                if (showOtherAmount.value) {
                    row.push(item.months?.[monthKey]?.other_amount ?? 0)
                    row.push(item.months?.[monthKey]?.total ?? 0)
                }
            })
            exportData.push(row)
        })

        exportData.push([])
        const totalRow = ['TOTALS', '', '']
        if (showFrequency.value) {
            totalRow.push('')
        }
        totalRow.push(totals.value.total_amount)
        Object.keys(months.value).forEach((monthKey) => {
            totalRow.push(totals.value.months?.[monthKey]?.amount ?? 0)
            if (showOtherAmount.value) {
                totalRow.push(totals.value.months?.[monthKey]?.other_amount ?? 0)
                totalRow.push(totals.value.months?.[monthKey]?.total ?? 0)
            }
        })
        exportData.push(totalRow)

        const worksheet = XLSX.utils.aoa_to_sheet(exportData)
        const workbook = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Monthly Report')
        const slug = (expenseType.value?.name || 'monthly').replace(/[^a-z0-9]+/gi, '_').toLowerCase()
        XLSX.writeFile(workbook, `${slug}_monthly_report_${filters.value.selectedYear}.xlsx`)
    } catch (error) {
        message.error('Failed to export report')
    } finally {
        exportLoading.value = false
    }
}

onMounted(async () => {
    await loadWorkgroups()
    await loadExpenseTypeFromQuery()
    applyFilters()
})
</script>
