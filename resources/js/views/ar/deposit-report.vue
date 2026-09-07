<template>
    <div class="deposit-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Deposit Report
                </h3>
            </div>

            <!-- Filter Selection -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <DynamicDropdown
                    v-model="filters.ledger"
                    :custom-options="ledgers"
                    display-name="name"
                    label="Ledger"
                    placeholder="Select Ledger"
                />
                <DynamicDropdown
                    v-model="filters.company"
                    resource="companies"
                    display-name="name"
                    label="Company"
                    placeholder="Select Company"
                />
                <Input
                    v-model="filters.start_date"
                    type="date"
                    label="Start Date"
                    placeholder="Select Start Date"
                />
                <Input
                    v-model="filters.end_date"
                    type="date"
                    label="End Date"
                    placeholder="Select End Date"
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

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th>Sr. No.</Th>
                            <Th>Due Date</Th>
                            <Th>Due</Th>
                            <Th>Deposit Date</Th>
                            <Th>Deposit Amount</Th>
                            <Th>Fees</Th>
                            <Th>Total</Th>
                            <Th>Cumulative Balance</Th>
                            <Th>Actions</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="reportData.length > 0 || openingBalance !== 0">
                        <tr>
                            <Td weight="bold" color="primary">Closing Balance</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(closingBalance) }}</Td>
                            <Td>-</Td>
                        </tr>
                        <tr v-for="(item, index) in sortedReportData" :key="index" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <Td>{{ index + 1 }}</Td>
                            <Td>{{ formatDate(item.payment_date) }}</Td>
                            <Td>
                                <span @dblclick="openPayment(item.pj_payment_id)">
                                    {{ formatNumber(item.payment_amount || 0) }}
                                </span>
                            </Td>
                            <Td>
                                <span v-if="item.settled && item.deposit_date" class="text-green-600 dark:text-green-400">
                                    {{ formatDate(item.deposit_date) }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </Td>
                            <Td>
                                <span @dblclick="openBankEntry(item.bank_entry_id)" v-if="item.settled">
                                    {{ formatNumber(item.deposit_amount || 0) }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </Td>
                            <Td>
                                <span v-if="item.settled">
                                    {{ formatNumber(item.fees || 0) }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </Td>
                            <Td>
                                <span v-if="item.settled">
                                    {{ formatNumber(item.total || 0) }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </Td>
                            <Td :class="{'text-red-600 dark:text-red-400': item.cumulative_balance > 0, 'text-green-600 dark:text-green-400': item.cumulative_balance <= 0}">
                                {{ formatNumber(item.cumulative_balance || 0) }}
                            </Td>
                            <Td>
                                <Button
                                    v-if="item.settled && can('deposit-report', 'unsettle')"
                                    icon-left="x"
                                    icon-size="sm"
                                    variant="outline-danger"
                                    size="sm"
                                    @click="unsettleItem(item)"
                                    title="Unsettle"
                                >
                                    Unsettle
                                </Button>
                                <span v-else class="text-gray-400">-</span>
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
                                    message="There is no data found. Please select filters and click Apply Filters."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Td weight="bold" color="primary">TOTALS</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.payment_amount || 0) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.deposit_amount || 0) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.fees || 0) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total || 0) }}</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                        </tr>
                        <tr>
                            <Td weight="bold" color="primary">Opening Balance</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(openingBalance || 0) }}</Td>
                            <Td>-</Td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Input from '@/components/ui/input.vue'
import { formatNumber } from '@/utils/number'
import { formatDate } from '@/utils/date'
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()
const message = useMessage()
const authStore = useAuthStore()

const filters = ref({
    ledger: null,
    company: '',
    start_date: '',
    end_date: '',
})

const reportData = ref([])
const loading = ref(false)
const openingBalance = ref(0)
const exportLoading = ref(false)
const ledgers = ref(null)

const totals = computed(() => {
    return reportData.value.reduce((acc, item) => {
        acc.payment_amount += parseFloat(item.payment_amount ?? 0)
        if (item.settled) {
            acc.deposit_amount += parseFloat(item.deposit_amount ?? 0)
            acc.fees += parseFloat(item.fees ?? 0)
            acc.total += parseFloat(item.total ?? 0)
        }
        return acc
    }, {
        payment_amount: 0,
        deposit_amount: 0,
        fees: 0,
        total: 0,
    })
})

const closingBalance = computed(() => {
    if (reportData.value.length > 0) {
        return reportData.value[reportData.value.length - 1].cumulative_balance
    }
    return openingBalance.value
})

const sortedReportData = computed(() => {
    return [...reportData.value].sort((a, b) => {
        return new Date(b.payment_date) - new Date(a.payment_date)
    })
})

const formatDateForInput = (date) => {
    return date.toISOString().split('T')[0]
}

const openPayment = (pjPaymentId) => {
    if (pjPaymentId) {
        window.open(`/ar/pj-payments/${pjPaymentId}`, '_blank')
    }
}

const openBankEntry = (bankEntryId) => {
    if (bankEntryId) {
        window.open(`/data-entry/bank-entries/${bankEntryId}`, '_blank')
    }
}

const unsettleItem = async (item) => {
    if (!confirm('Are you sure you want to unsettle this item? This will remove the settlement link.')) {
        return
    }

    try {
        loading.value = true
        await useRequest('post', '/ar/deposit-report/unsettle', {
            item_id: item.id,
            ledger_id: filters.value.ledger?.id,
        })
        
        message.success('Item unsettled successfully')
        await applyFilters()
    } catch (error) {
        console.error('Failed to unsettle item:', error)
        message.error(error.response?.data?.message || 'Failed to unsettle item')
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    const endDate = new Date()
    const startDate = new Date()
    filters.value.company = authStore.company
    startDate.setDate(endDate.getDate() - 30)

    filters.value.start_date = formatDateForInput(startDate)
    filters.value.end_date = formatDateForInput(endDate)
    
    await loadLedgers()
    await applyFilters()
})

watch(() => authStore.company, (newVal) => {
    filters.value.company = newVal
})

const loadLedgers = async () => {
    try {
        const response = await useRequest('get', '/search/ledgers', null, {
            params: {
                codes: ['1001.01', '1003.01']
            }
        })
        ledgers.value = response?.collection || []
        filters.value.ledger = ledgers.value.find(ledger => ledger.name.includes('1001.01'))
    } catch (error) {
        console.error('Failed to load ledgers:', error)
        message.error('Failed to load ledgers')
    }
}

const applyFilters = async () => {
    if (!filters.value.start_date || !filters.value.end_date || !filters.value.company) {
        message.error('Please select Company and Date Range')
        return
    }
    try {
        loading.value = true
        const response = await useRequest('post', '/ar/deposit-report', {
            start_date: filters.value.start_date,
            end_date: filters.value.end_date,
            company_id: filters.value.company.id,
            ledger_id: filters.value.ledger?.id,
        })
        reportData.value = response.report_data
        openingBalance.value = response.opening_balance
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
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
            
            exportData.push(['Deposit Report'])
            exportData.push(['Period:', filters.value.start_date + ' to ' + filters.value.end_date])
            exportData.push(['Company:', filters.value.company?.name || '-'])
            exportData.push(['Ledger:', filters.value.ledger?.name || 'All'])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])
            
            exportData.push([
                'Sr. No.',
                'Due Date',
                'Deposit Date',
                'Due',
                'Deposit Amount',
                'Fees',
                'Total',
                'Cumulative Balance',
            ])
            
            reportData.value.forEach((item, index) => {
                exportData.push([
                    index + 1,
                    item.payment_date,
                    item.settled && item.deposit_date ? item.deposit_date : '-',
                    parseFloat(item.payment_amount || 0),
                    item.settled ? parseFloat(item.deposit_amount || 0) : '-',
                    item.settled ? parseFloat(item.fees || 0) : '-',
                    item.settled ? parseFloat(item.total || 0) : '-',
                    parseFloat(item.cumulative_balance || 0),
                ])
            })
            
            exportData.push([])
            exportData.push([
                'TOTALS', '', '',
                parseFloat(totals.value.payment_amount || 0),
                parseFloat(totals.value.deposit_amount || 0),
                parseFloat(totals.value.fees || 0),
                parseFloat(totals.value.total || 0),
                ''
            ])
            exportData.push([
                'Opening Balance', '', '', '', '', '', '',
                parseFloat(openingBalance.value || 0)
            ])
            exportData.push([
                'Closing Balance', '', '', '', '', '', '',
                parseFloat(closingBalance.value || 0)
            ])
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            ws['!cols'] = [
                { wch: 10 },
                { wch: 15 },
                { wch: 15 },
                { wch: 15 },
                { wch: 15 },
                { wch: 10 },
                { wch: 15 },
                { wch: 20 },
            ]
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Deposit')
            
            const filename = `Deposit_Report_${filters.value.start_date}_${filters.value.end_date}_${new Date().getTime()}.xlsx`
            
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
</script>
