<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    PJ Ledgers
                </h3>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <DynamicDropdown
                    v-model="filters.ledger"
                    resource="ledgers"
                    :params="{ type: 'pj' }"
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
                            <Th>Date</Th>
                            <Th class="w-[20px]"></Th>
                            <Th>Due</Th>
                            <Th>Deposite</Th>
                            <Th>Fees</Th>
                            <Th v-if="filters.ledger?.code === '1001.52'">DDC Doordash </Th>
                            <Th>Balance</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="ledgerData.length > 0 || openingBalance !== 0">
                        <tr>
                            <Td weight="bold" color="primary">Closing Balance</Td>
                            <Td>-</Td>
                            <Td></Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td v-if="filters.ledger?.code === '1001.52'">-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.balance || 0) }}</Td>
                        </tr>
                            <tr v-for="(item, index) in sortedLedgerData" :key="index"  class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <Td>{{ index + 1 }}</Td>
                                <Td>{{ formatDate(item.date) }}</Td>
                                <Td> <SvgIcon name="check-circle" v-if="item.settled" size="sm" color="green" /> </Td>
                                <Td> <span @dblclick="paymentVouchers(item.voucher_ids, item.dbtables)">{{ formatNumber(item.debit || 0) }}</span> </Td>
                                <Td :title="renderChildAmounts(item.child_amounts)"> <span @dblclick="depositeVouchers(item.voucher_ids, item.dbtables)">{{ formatNumber(item.credit || 0) }}</span> </Td>
                                <Td> <span @dblclick="feesVouchers(item.voucher_ids, item.dbtables)">{{ formatNumber(item.fees || 0) }}</span> </Td>
                                <Td v-if="filters.ledger?.code === '1001.52'"> <span @dblclick="ddcDoordashVouchers(item.voucher_ids, item.dbtables)">{{ formatNumber(item.ddc_doordash || 0) }}</span> </Td>
                                <Td> {{ formatNumber(item.balance || 0) }} </Td>
                            </tr>
                           
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="filters.ledger?.code === '1001.52' ? 8 : 7">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No PJ Ledgers Data Found"
                                    message="There is no PJ Ledgers data found. Please select a different date range or ensure data has been entered."
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
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Td  weight="bold" color="primary">TOTALS</Td>
                            <Td>-</Td>
                            <Td></Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.debit || 0) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.credit || 0) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.fees || 0) }}</Td>
                            <Td weight="bold"  v-if="filters.ledger?.code === '1001.52'">{{ formatNumber(totals.ddc_doordash || 0) }}</Td>
                            <Td weight="bold" color="primary">-</Td>
                        </tr>
                        <tr>
                            <Td  weight="bold" color="primary">Opening Balance</Td>
                            <Td>-</Td>
                            <Td></Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td v-if="filters.ledger?.code === '1001.52'" >-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(openingBalance || 0) }}</Td>
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
import { useRouter } from 'vue-router'
const message = useMessage()
const authStore = useAuthStore()
const router = useRouter()
const filters = ref({
    company: '',
    start_date: '',
    end_date: '',
    ledger: '',
})

const ledgerData = ref([])
const loading = ref(false)
const openingBalance = ref(0)
const exportLoading = ref(false)

const totals = computed(() => {
    return ledgerData.value.reduce((acc, item) => {
        acc.debit += parseFloat(item.debit ?? 0)
        acc.credit += parseFloat(item.credit ?? 0)
        acc.fees += parseFloat(item.fees ?? 0)
        acc.balance = parseFloat(item.balance ?? 0)
        acc.ddc_doordash += parseFloat(item.ddc_doordash ?? 0)
        return acc
    }, {
        debit: 0,
        credit: 0,
        balance: openingBalance.value,
        fees: 0,
        ddc_doordash: 0,
    })

})

const sortedLedgerData = computed(() => {
    return [...ledgerData.value].sort((a, b) => {
        return new Date(b.date) - new Date(a.date)
    })
})

const formatDateForInput = (date) => {
    return date.toISOString().split('T')[0]
}

const paymentVouchers = (voucherIds, dbtables) => {
    let voucherIdsArray = voucherIds.split(',')
    let dbtablesArray = dbtables.split(',')

    const PjPaymentIndex = dbtablesArray.indexOf('pj_payments')
    if(PjPaymentIndex !== -1){
        const pjPaymentId = voucherIdsArray[PjPaymentIndex]
        window.open(`/ar/pj-payments/${pjPaymentId}`, '_blank')
    }

}

const feesVouchers = (voucherIds, dbtables) => {
    let voucherIdsArray = voucherIds.split(',')
    let dbtablesArray = dbtables.split(',')
    const FeesUploadIndex = dbtablesArray.indexOf('fees_uploads')
    if(FeesUploadIndex !== -1){
        const feesUploadId = voucherIdsArray[FeesUploadIndex]
        window.open(`/ar/fees-uploads/${feesUploadId}`, '_blank')
    }
}
const depositeVouchers = (voucherIds, dbtables) => {
    let voucherIdsArray = voucherIds.split(',')
    let dbtablesArray = dbtables.split(',')

    const BankEntryIndex = dbtablesArray.indexOf('bank_entries')
    if(BankEntryIndex !== -1){
        const bankEntryId = voucherIdsArray[BankEntryIndex]
        window.open(`/data-entry/bank-entries/${bankEntryId}`, '_blank')
    }
}

onMounted(() => {
    const endDate = new Date()
    const startDate = new Date()
    startDate.setDate(endDate.getDate() - 30)

    filters.value.start_date = formatDateForInput(startDate)
    filters.value.end_date = formatDateForInput(endDate)
    filters.value.ledger = null
 })
 watch(()=>authStore.company, (newVal) => {
    filters.value.company = newVal
    filters.value.company.name = newVal.name
 })

// Methods

const applyFilters = async () => {
    if(!filters.value.start_date || !filters.value.end_date || !filters.value.ledger || !filters.value.company){
        message.error('Please select all filters')
        return
    }
    try {
        loading.value = true;
        const response = await useRequest('post', '/ar/pj-ledgers', {
            start_date: filters.value.start_date,
            end_date: filters.value.end_date,
            ledger_id: filters.value.ledger.id,
            company_id: filters.value.company.id,
        })
        ledgerData.value = response.ledger_data
        openingBalance.value = response.opening_balance
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally{
        loading.value = false
    }
}

const renderChildAmounts = (childAmounts) => {
    return childAmounts?.map(childAmount => {
        return `${childAmount.deposit}`
    }).join('\n')
}

const exportReport = async () => {
    try {
        // Check if there's data to export
        if (!ledgerData.value || ledgerData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['PJ Ledgers Report'])
            exportData.push(['Period:', filters.value.start_date + ' to ' + filters.value.end_date])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Sr. No.',
                'Date',
                'Due',
                'Deposite',
                'Fees',
                ...(filters.value.ledger.code === '1001.52' ? ['DDC Doordash'] : []),
                'Balance',
            ])
            
            // Add data rows
            ledgerData.value.forEach((item, index) => {
                exportData.push([
                    index + 1,
                    item.date ,
                    item.debit || 0,
                    item.credit || 0,
                    item.fees || 0,
                    ...(filters.value.ledger.code === '1001.52' ? [item.ddc_doordash || 0] : []),
                    item.balance || 0,
                ])
            })
            
            // Add totals row
            exportData.push([])
            exportData.push([
                '', '', '', '', 'TOTALS',
                parseFloat(totals.value.credit || 0),
                parseFloat(totals.value.debit || 0),
                parseFloat(totals.value.fees || 0),
                parseFloat(totals.value.ddc_doordash || 0),
                ...(filters.value.ledger.code === '1001.52' ? [parseFloat(totals.value.ddc_doordash || 0)] : []),
                parseFloat(totals.value.balance || 0)
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 10 },  // Sr. No.
                { wch: 25 },  // Date
                { wch: 25 },  // Payment
                { wch: 15 },  // Deposite
                { wch: 15 },  // Fees
                ...(filters.value.ledger.code === '1001.52' ? [{ wch: 15 }] : []),
                { wch: 15 },  // Balance
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'PJ Ledgers Report')
            
            // Generate filename
            const filename = `PJ_Ledgers_Report_${filters.value.start_date}_${filters.value.end_date}_${new Date().getTime()}.xlsx`
            
            // Save file
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