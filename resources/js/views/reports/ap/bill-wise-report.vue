<template>
    <div class="bill-wise-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ reportTitle }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Purchase invoices by store for the selected expense type
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
                    <select
                        v-model="filters.selectedYear"
                        class="form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div v-if="can('bill-wise-report', 'regional-director')">
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
                <div v-if="can('bill-wise-report', 'area-manager')">
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
                            <Th class="md:sticky md:left-0 !z-[101]">Sr. No.</Th>
                            <Th class="md:sticky md:left-12 !z-[101] !w-[350px]">
                                <div class="!w-[200px]">Company</div>
                            </Th>
                            <Th class="md:sticky md:left-[250px] !z-[101] text-right border-r border-gray-200 dark:border-gray-700">Total Amount</Th>
                            <template v-for="i in maxBills" :key="i">
                                <Th>Date </Th>
                                <Th>Vendor</Th>
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
                            <Td class="md:sticky md:left-0 !z-[100] bg-white dark:bg-gray-800">{{ index + 1 }}</Td>
                            <Td class="md:sticky md:left-12 !z-[100] bg-white dark:bg-gray-800 !w-[250px]" weight="medium">{{ item.company_name }}</Td>
                            <Td class="md:sticky md:left-[250px] !z-[100] bg-white dark:bg-gray-800 text-right border-r border-gray-200 dark:border-gray-700">{{ getTotalAmount(item) }}</Td>
                            <template v-for="bill in item.bills ?? []" :key="bill.id">
                                <Td>{{ formatDateShort(bill.invoice_date) }}</Td>
                                <Td>{{ bill.vendor_code || bill.vendor_name || '-' }}</Td>
                                <Td class="text-right" :class="{ 'border-r border-gray-200 dark:border-gray-700': !showOtherAmount }">{{ formatCurrency(bill.amount) }}</Td>
                                <Td v-if="showOtherAmount" class="text-right">{{ formatOtherAmount(bill.other_amount) }}</Td>
                                <Td v-if="showOtherAmount" class="text-right border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(getBillTotal(bill)) }}</Td>
                            </template>
                            <template v-for="i in emptyBillCellCount(item)" :key="`empty-${index}-${i}`">
                                <Td />
                                <Td />
                                <Td class="text-right" :class="{ 'border-r border-gray-200 dark:border-gray-700': !showOtherAmount }" />
                                <Td v-if="showOtherAmount" class="text-right" />
                                <Td v-if="showOtherAmount" class="text-right border-r border-gray-200 dark:border-gray-700" />
                            </template>
                        </tr>
                    </tbody>
                    <tbody v-else class="bg-white dark:bg-gray-800">
                        <tr>
                            <td colspan="12" class="p-8">
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
                            <Td colspan="2" weight="bold" color="primary" class="md:sticky md:left-0 !z-[100] bg-gray-100 dark:bg-gray-800">TOTALS</Td>
                            <Td align="right" weight="bold" class="md:sticky md:left-[250px] !z-[100] bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">{{ formatCurrency(grandTotal) }}</Td>
                            <Td :colspan="maxBills * columnsPerBill" />
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
import { ref, onMounted, computed } from 'vue'
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
import { formatDateShort } from '@/utils/date'
import { getExpenseAmountLabels } from '@/utils/purchaseInvoiceExpense'

const route = useRoute()
const message = useMessage()
const { can } = usePermission()
const maxBills = ref(0)
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
const loading = ref(false)
const exportLoading = ref(false)

const amountLabels = computed(() => getExpenseAmountLabels(expenseType.value))
const amountLabel = computed(() => amountLabels.value.amount)
const otherAmountLabel = computed(() => amountLabels.value.otherAmount)
const showOtherAmount = computed(() => amountLabels.value.showOtherAmount)
const columnsPerBill = computed(() => (showOtherAmount.value ? 5 : 3))
const reportTitle = computed(() => {
    const name = expenseType.value?.name
    return name ? `${name} Bill Wise Report` : 'Bill Wise Report'
})
const noDataMessage = computed(() => {
    if (!filters.value.expenseType) {
        return 'Select an expense type to load the report.'
    }
    return `No ${expenseType.value?.name || ''} invoices match the selected filters.`
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

const getBillTotal = (bill) => {
    const amount = parseFloat(bill.amount) || 0
    const otherAmount = parseFloat(bill.other_amount) || 0
    return parseFloat(bill.total_amount) || amount + otherAmount
}

const getTotalAmount = (item) => {
    return formatCurrency(item.bills?.reduce((acc, bill) => acc + getBillTotal(bill), 0) ?? 0)
}

const emptyBillCellCount = (item) => Math.max(0, maxBills.value - (item.bills?.length ?? 0))

const grandTotal = computed(() => {
    return reportData.value.reduce((acc, item) => {
        return acc + (item.bills?.reduce((sum, bill) => sum + getBillTotal(bill), 0) ?? 0)
    }, 0)
})

const applyFilters = async () => {
    if (!filters.value.expenseType?.id) {
        reportData.value = []
        maxBills.value = 0
        return
    }

    try {
        loading.value = true
        const response = await useRequest('post', '/reports/ap/bill-wise', buildReportPayload())
        reportData.value = response?.data ?? []
        maxBills.value = Math.max(0, Number(response?.max_bills) || 0)
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

        let headerRow = ['Sr. No.', 'Company', 'Total Amount']
        for (let i = 1; i <= maxBills.value; i++) {
            headerRow.push('Date', 'Vendor', amountLabel.value)
            if (showOtherAmount.value) {
                headerRow.push(otherAmountLabel.value, 'Total')
            }
        }
        exportData.push(headerRow)

        reportData.value.forEach((item, index) => {
            let row = [index + 1, item.company_name, getTotalAmount(item)]
            for (let i = 1; i <= maxBills.value; i++) {
                const bill = item.bills?.[i - 1]
                row.push(
                    formatDateShort(bill?.invoice_date || ''),
                    bill?.vendor_code || bill?.vendor_name || '',
                    bill ? parseFloat(bill.amount) || 0 : '',
                )
                if (showOtherAmount.value) {
                    row.push(
                        bill ? parseFloat(bill.other_amount) || 0 : '',
                        bill ? getBillTotal(bill) : '',
                    )
                }
            }
            exportData.push(row)
        })

        exportData.push([])
        exportData.push(['TOTALS', '', grandTotal.value])

        const worksheet = XLSX.utils.aoa_to_sheet(exportData)
        const workbook = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Bill Wise Report')
        const slug = (expenseType.value?.name || 'bill_wise').replace(/[^a-z0-9]+/gi, '_').toLowerCase()
        XLSX.writeFile(workbook, `${slug}_bill_wise_report_${filters.value.selectedYear || 'export'}.xlsx`)
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
