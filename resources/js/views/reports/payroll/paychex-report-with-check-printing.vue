<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Paychex Report
                </h3>   
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze Paychex data for the selected period
                </p>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Select Year -->
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

                <!-- Select Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Period
                    </label>
                    <select
                        v-model="filters.selectedPeriod"
                        @change="onPeriodChange"
                        class="form-select"
                    >
                        <option v-for="period in availablePeriods" :key="period.value" :value="period.value">
                            {{ period.label }}
                        </option>
                    </select>
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
                    >
                        Export to Excel
                    </Button>
                    <Button
                        v-if="canUpdate"
                        icon-left="check"
                        icon-size="sm"
                        variant="success"
                        size="sm"
                        @click="reviewCheck"
                        :loading="reviewCheckLoading"
                        :disabled="!payrollData || payrollData.length === 0"
                    >
                        Review Check
                    </Button>

                    <Button
                        v-if="canUpdate"
                        icon-left="print"
                        icon-size="sm"
                        variant="secondary"
                        size="sm"
                        @click="printCheck"
                        :loading="printingLoading"
                        :disabled="!payrollData || payrollData.length === 0"
                    >
                        Print Check
                    </Button>
                </div>
            </div>
        </Panel>
        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="handleSelectAll"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <span>Sr. No.</span>
                                </div>
                            </Th>
                            <Th class="sticky  left-19 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('store_name')">
                                    <span>Store Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('store_name')" 
                                        :name="getSortDirection('store_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="sticky left-60 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('employee_name')">
                                    <span>Employee Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('employee_name')" 
                                        :name="getSortDirection('employee_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="sticky left-100 bg-white dark:bg-gray-900 !z-[101]">Role</Th>
                            <Th>Regular Hours</Th>
                            <Th>Overtime Hours</Th>
                            <Th>Total Hours</Th>
                            <Th>Rate</Th>
                            <Th>Gross Pay</Th>
                            <Th>Tips</Th>
                            <Th>Tips Due</Th>
                            <Th>Mileage Due</Th>
                            <Th>Total Earnings</Th>
                            <Th>HR Pay</Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('amount')">
                                    <span>Payroll Amount</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('amount')" 
                                        :name="getSortDirection('amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>From store</Th>
                            <Th>Check #</Th>
                            <Th>Date</Th>
                            <Th>Actions</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="groupedPayrollData.length > 0">
                        <template v-for="(group, groupIndex) in groupedPayrollData" :key="groupIndex">
                            <!-- Company Header Row -->
                            <tr class="bg-blue-50 dark:bg-blue-900/20 border-t-2 border-blue-300 dark:border-blue-700">
                                <Td  weight="bold" color="primary" class="sticky left-0 bg-blue-50 dark:bg-blue-900/20 !z-[100]">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                :checked="isCompanyAllSelected(group)"
                                                @change="handleCompanySelectAll(group, $event)"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                            />
                                        </div>
                                    </div>
                                </Td>
                                <Td weight="bold" color="primary" class="sticky left-19 bg-blue-50 dark:bg-blue-900/20 !z-[100]"> {{ group.companyName }}</Td>
                                <Td weight="bold" color="primary" class="sticky left-60 bg-blue-50 dark:bg-blue-900/20 !z-[100]"></Td>
                                <Td weight="bold" color="primary" class="sticky left-100 bg-blue-50 dark:bg-blue-900/20 !z-[100]"></Td>

                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_regular_hours) }} hrs</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_overtime_hours) }} hrs</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_hours) }} hrs</Td>
                                <Td weight="bold" color="primary"> -</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_gross_pay) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_tips) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_tips_due) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_mileage_due) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_earnings) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_earnings / group.total_hours) }}</Td>
                                <Td weight="bold" color="primary"> {{ formatNumber(group.total_amount) }}</Td>
                                <Td colspan="4" weight="bold" color="primary"></Td>
                            </tr>
                            <!-- Employee Rows for this Company -->
                            <tr v-for="(item, index) in group.items" :key="`${groupIndex}-${index}`" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700" >
                                <Td class="sticky left-0 bg-white dark:bg-gray-900 !z-[100]">
                                    <div class="flex items-center gap-2">
                                        <span class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                            <SvgIcon
                                                v-if="item.reviewed"
                                                name="check-circle"
                                                size="sm"
                                                class="text-green-600 dark:text-green-400"
                                            />
                                            <input
                                                v-else
                                                type="checkbox"
                                                v-model="item.checked"
                                                @change="handleReviewChange(item, $event)"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                            />
                                        </span>
                                        {{ item.globalIndex }}
                                    </div>
                                </Td>
                                <Td class="sticky left-19 bg-white dark:bg-gray-900 !z-[100]">{{ item.company?.store_number ? item.company?.store_number + ' - ' + item.company?.name : item.company?.name || 'N/A' }}</Td>
                                <Td class="sticky left-60 bg-white dark:bg-gray-900 !z-[100]">
                                    <a :href="`/employee/${item.employee?.id}`" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ item.employee_name || item.employee?.pos_name || 'N/A' }}</a>
                                </Td>
                                <Td class="sticky left-100 bg-white dark:bg-gray-900 !z-[100]">{{ item.role?.name || 'N/A' }}</Td>
                                <Td>{{ formatNumber(item.regular_hours) }}</Td>
                                <Td>{{ formatNumber(item.overtime_hours) }}</Td>
                                <Td>{{ formatNumber(item.total_hours) }}</Td>
                                <Td>{{ formatNumber(item.employee_rate) }}</Td>
                                <Td>{{ formatNumber(item.gross_pay) }}</Td>
                                <Td>{{ formatNumber(item.tips) }}</Td>
                                <Td>{{ formatNumber(item.tips_due) }}</Td>
                                <Td>{{ formatNumber(item.mileage_due) }}</Td>
                                <Td>{{ formatNumber(item.total_earnings) }}</Td>
                                <Td>{{ formatNumber(item.total_earnings / item.total_hours) }}</Td>
                                <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''"
                                        :title="renderMinWageTitle(item)"
                                    >${{ formatNumber(item.amount) }}</Td>
                                <Td>
                                    <div class="flex items-center gap-2">
                                        <DynamicDropdown
                                            v-model="item.fromCompany"
                                            resource="companies"
                                            display-name="name"
                                            :disabled="makeDisabled(item)"
                                            placeholder="Select a from company"
                                            :required="false"
                                            :removeNullOption="true"
                                            customClass="min-w-[200px]"
                                            @change="handleFromCompanyChange(item)"
                                        />
                                            <!-- :disabled="item.reviewed" -->

                                        <DynamicDropdown
                                            v-model="item.ledger"
                                            :disabled="makeDisabled(item)"
                                            resource="banks"
                                            display-name="name"
                                            placeholder="Select a ledger"
                                            :required="false"
                                            :removeNullOption="true"
                                            customClass="min-w-[100px]"
                                            @change="handleLedgerChange(item)"
                                        />  
                                            <!-- :disabled="item.reviewed" -->

                                    </div>
                                </Td>
                                <Td>

                                    <div class="min-w-[100px]">
                                        <Input
                                            v-model="item.check_number"
                                            :required="false"
                                            :disabled="makeDisabled(item)"
                                        />
                                    </div>

                                        <!-- :disabled="item.reviewed" -->
                                </Td>
                                <Td>{{ item.check_date ? formatDate(item.check_date) : '-' }}</Td>
                                <Td>
                                    <Button
                                       v-if="canUpdate && item.reviewed"
                                        icon-left="edit"
                                        icon-size="sm"
                                        variant="primary"
                                        size="sm"
                                        @click="handleEdit(item)"
                                    >
                                        Edit
                                    </Button>
                                </Td>
                            </tr>
                        </template>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="18">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Payroll Data Found"
                                    message="There is no payroll data for the selected period. Please select a different date range or ensure data has been entered."
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
                            <Td colspan="4" weight="bold" color="primary">TOTALS</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.regular_hours) }} hrs</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.overtime_hours) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total_hours) }} hrs</Td>
                            <Td weight="bold" color="primary">-</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.gross_pay) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.tips) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.tips_due) }}</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.mileage_due) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.total_earnings) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.hr_pay) }}</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.amount) }}</Td>
                            <Td colspan="13" weight="bold" color="primary"></Td>

                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ payrollData.length }}</span> records across <span class="font-medium">{{ groupedPayrollData.length }}</span> companies
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Period: {{ currentPeriodLabel }}
                </div>
            </div>
        </Panel>

        <!-- Print Modal -->
        <Modal
            v-model="showPrintModal"
            title="Select Checks to Print"
            size="2xl"
            :show-footer="true"
            body-class="max-h-[70vh] overflow-y-auto"
            @close="closePrintModal"
        >
            <template #header>
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Select Checks to Print
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ selectedPrintItems.length }} of {{ reviewedChecksForPrint.length }} checks selected
                    </p>
                </div>
            </template>

            <div class="mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="selectAllPrintItems"
                        @change="handleSelectAllPrintItems"
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Select All</span>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Th>
                                <div class="flex items-center gap-2">
                                    <span>Select</span>
                                </div>
                            </Th>
                            <Th>Sr. No.</Th>
                            <Th>Store Name</Th>
                            <Th>Employee Name</Th>
                            <Th>Role</Th>
                            <Th>Amount</Th>
                            <Th>From Store</Th>
                            <Th>Ledger</Th>
                            <Th>Check #</Th>
                            <Th>Date</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(item, index) in reviewedChecksForPrint" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Td>
                                <input
                                    type="checkbox"
                                    v-model="item.selectedForPrint"
                                    @change="updateSelectAllPrintItems"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                />
                            </Td>
                            <Td>{{ item.globalIndex }}</Td>
                            <Td>{{ item.company?.store_number ? item.company?.store_number + ' - ' + item.company?.name : item.company?.name || 'N/A' }}</Td>
                            <Td>{{ item.employee_name || item.employee?.pos_name || 'N/A' }}</Td>
                            <Td>{{ item.role?.name || 'N/A' }}</Td>
                            <Td weight="medium" color="primary">${{ formatNumber(item.amount) }}</Td>
                            <Td>{{ item.fromCompany?.name || '-' }}</Td>
                            <Td>{{ item.ledger?.name || '-' }}</Td>
                            <Td>{{ item.check_number || '-' }}</Td>
                            <Td>{{ item.check_date ? formatDate(item.check_date) : '-' }}</Td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <template #footer>
                <Button
                    variant="secondary"
                    size="sm"
                    @click="closePrintModal"
                >
                    Cancel
                </Button>
                <Button
                    icon-left="print"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    @click="confirmPrint"
                    :loading="printingLoading"
                    :disabled="selectedPrintItems.length === 0"
                >
                    Print {{ selectedPrintItems.length }} Check{{ selectedPrintItems.length !== 1 ? 's' : '' }}
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import NoData from '@/components/ui/no-data.vue'
import Modal from '@/components/common/Modal.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'
import Input from '@/components/ui/input.vue'

const { can } = usePermission()
const reviewCheckLoading = ref(false)
const selectAll = ref(false)
const showPrintModal = ref(false)
const reviewedChecksForPrint = ref([])
const selectAllPrintItems = ref(false)

const canUpdate = computed(() => {
    return can('paychex-report', 'update')
})

const selectedPrintItems = computed(() => {
    return reviewedChecksForPrint.value.filter(item => item.selectedForPrint)
})

const message = useMessage()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    company: '',
    status: ''
})

// Available Years (current year and past 5 years)
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})
const payrollData = ref([])
const loading = ref(false)
const exportLoading = ref(false)
const printingLoading = ref(false)
const editingItem = ref(null)

const makeDisabled = (item) => {
    if(editingItem.value && editingItem.value === item.employee_id + '-' + item.company_id){
        return false
    }
    return (item.reviewed ) && canUpdate.value
}

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc')
// Generate bi-weekly periods (Monday to Sunday) for the selected year
const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const periods = []
    
    // Generate bi-weekly periods for the entire year using UTC to avoid timezone issues
    let startDate = new Date(Date.UTC(year, 0, 1)) // January 1st UTC
    
    // Adjust to start on Monday (Monday = 1, Sunday = 0)
    const dayOfWeek = startDate.getUTCDay()
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7 // If Sunday, go back 6 days; otherwise go to previous/current Monday
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)
    
    let periodNumber = 1
    
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13) // 14 days (Monday to Sunday of second week)
        
        // Stop if we've gone too far into next year
        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }
        
        const formatDate = (date) => {
            const month = String(date.getUTCMonth() + 1).padStart(2, '0')
            const day = String(date.getUTCDate()).padStart(2, '0')
            const year = date.getUTCFullYear()
            return `${month}-${day}-${year}`
        }
        
        const label = `${formatDate(startDate)} To ${formatDate(endDate)}`
        const value = `${startDate.toISOString()} to ${endDate.toISOString()}`
        
        periods.push({ label, value, startDate: new Date(startDate), endDate: new Date(endDate) })
        
        // Move to next bi-weekly period (14 days)
        startDate.setUTCDate(startDate.getUTCDate() + 14)
        periodNumber++
    }
    
    return periods
})


const renderMinWageTitle = computed(() => {
    return (item) => {
        if(item.tipped){
            return 'Minimum tipped wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum tipped wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly)
        }else{
            return 'Minimum wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly)
        }
    }
})
// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})


// Sorted data
const sortedPayrollData = computed(() => {
    const data = [...payrollData.value]
    
    if (!sortField.value) return data
    
    return data.sort((a, b) => {
        let aVal, bVal
        
        if (sortField.value === 'sr_no') {
            aVal = a.sr_no || 0
            bVal = b.sr_no || 0
        } else if (sortField.value === 'store_name') {
            aVal = (a.company?.store_number ? a.company?.store_number + ' - ' + a.company?.name : a.company?.name || '').toLowerCase()
            bVal = (b.company?.store_number ? b.company?.store_number + ' - ' + b.company?.name : b.company?.name || '').toLowerCase()
        } else if (sortField.value === 'employee_name') {
            aVal = (a.employee?.pos_name || '').toLowerCase()
            bVal = (b.employee?.pos_name || '').toLowerCase()
        } else if (sortField.value === 'amount') {
            aVal = parseFloat(a.amount || 0)
            bVal = parseFloat(b.amount || 0)
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

// Group data by company
const groupedPayrollData = computed(() => {
    const data = [...payrollData.value]
    const groups = {}
    
    // First, group all items by company
    data.forEach(item => {
        const companyKey = item.company?.id || 'unknown'
        const companyName = item.company?.store_number 
            ? `${item.company.store_number} - ${item.company.name}` 
            : item.company?.name || 'Unknown Company'
        
        if (!groups[companyKey]) {
            groups[companyKey] = {
                companyName,
                companyId: companyKey,
                items: [],
                total_regular_hours: 0,
                total_overtime_hours: 0,
                total_hours: 0,
                total_gross_pay: 0,
                total_tips: 0,
                total_tips_due: 0,
                total_mileage_due: 0,
                total_earnings: 0,
                total_hr_pay: 0,
                total_amount: 0,
                sortKey: item.company?.store_number 
                    ? `${String(item.company.store_number).padStart(10, '0')} ${item.company.name}` 
                    : item.company?.name || 'Unknown Company'
            }
        }
        groups[companyKey].items.push(item)
        groups[companyKey].total_regular_hours += parseFloat(item.regular_hours || 0)
        groups[companyKey].total_overtime_hours += parseFloat(item.overtime_hours || 0)
        groups[companyKey].total_hours += parseFloat(item.total_hours || 0)
        groups[companyKey].total_gross_pay += parseFloat(item.gross_pay || 0)
        groups[companyKey].total_tips += parseFloat(item.tips || 0)
        groups[companyKey].total_tips_due += parseFloat(item.tips_due || 0)
        groups[companyKey].total_mileage_due += parseFloat(item.mileage_due || 0)
        groups[companyKey].total_earnings += parseFloat(item.total_earnings || 0)
        groups[companyKey].total_hr_pay += parseFloat(item.total_earnings / item.total_hours || 0)
        groups[companyKey].total_amount += parseFloat(item.amount || 0)
    })
    
    // Convert to array
    let groupArray = Object.values(groups)
    
    // Sort groups by company name if sorting by store_name, otherwise alphabetically
    if (sortField.value === 'store_name') {
        groupArray.sort((a, b) => {
            const comparison = a.sortKey.localeCompare(b.sortKey)
            return sortDirection.value === 'asc' ? comparison : -comparison
        })
    } else {
        // Default alphabetical sort by company name
        groupArray.sort((a, b) => a.sortKey.localeCompare(b.sortKey))
    }
    
    // Sort items within each group
    groupArray.forEach(group => {
        if (sortField.value && sortField.value !== 'store_name') {
            group.items.sort((a, b) => {
                let aVal, bVal
                
                if (sortField.value === 'employee_name') {
                    aVal = (a.employee?.pos_name || '').toLowerCase()
                    bVal = (b.employee?.pos_name || '').toLowerCase()
                } else if (sortField.value === 'amount') {
                    aVal = parseFloat(a.amount || 0)
                    bVal = parseFloat(b.amount || 0)
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
        }
    })
    
    // Add global index to all items
    let globalIndex = 1
    groupArray.forEach(group => {
        group.items.forEach(item => {
            item.globalIndex = globalIndex++
        })
    })
    
    return groupArray
})

const totals = computed(() => {
    return payrollData.value.reduce((acc, item) => {
        acc.amount += parseFloat(item.amount ?? 0)
        acc.regular_hours += parseFloat(item.regular_hours ?? 0)
        acc.overtime_hours += parseFloat(item.overtime_hours ?? 0)
        acc.total_hours += parseFloat(item.total_hours ?? 0)
        acc.gross_pay += parseFloat(item.gross_pay ?? 0)
        acc.tips += parseFloat(item.tips ?? 0)
        acc.tips_due += parseFloat(item.tips_due ?? 0)
        acc.mileage_due += parseFloat(item.mileage_due ?? 0)
        acc.total_earnings += parseFloat(item.total_earnings ?? 0)
        acc.hr_pay += parseFloat(acc.total_earnings / acc.total_hours ?? 0)
        return acc
    }, {
        amount: 0,
        regular_hours: 0,
        overtime_hours: 0,
        total_hours: 0,
        gross_pay: 0,
        tips: 0,
        tips_due: 0,
        mileage_due: 0,
        total_earnings: 0,
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

// Methods
const onYearChange = () => {
    filters.value.selectedPeriod = ''
    if (availablePeriods.value.length > 0) {
        filters.value.selectedPeriod = availablePeriods.value[0].value
    }
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const onPeriodChange = () => {
    payrollData.value=[];
    selectAll.value = false;
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
}

const applyFilters = async () => {
    try {
        loading.value = true;
        selectAll.value = false;
        const response = await useRequest('post', '/reports/payroll/paychex', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
        })
        payrollData.value = response.data
        
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally{
        loading.value = false
    }
}


const exportReport = async () => {
    try {
        // Check if there's data to export
        if (!payrollData.value || payrollData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['Network Check Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Sr. No.',
                'Store Name',
                'Employee Name',
                'Role',
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Rate',
                'Gross Pay',
                'Tips',
                'Tips Due',
                'Mileage Due',
                'Total Earnings',
                'HR Pay',
                'Payroll Amount'
            ])
            
            // Add data rows
            payrollData.value.forEach((item, index) => {
                exportData.push([
                    index + 1,
                    item.company?.store_number ? item.company?.store_number + ' - ' + item.company?.name : item.company?.name || '',
                    item.employee?.pos_name || '',
                    item.role?.name || '',
                    parseFloat(item.regular_hours || 0),
                    parseFloat(item.overtime_hours || 0),
                    parseFloat(item.total_hours || 0),
                    parseFloat(item.employee_rate || 0),
                    parseFloat(item.gross_pay || 0),
                    parseFloat(item.tips || 0),
                    parseFloat(item.tips_due || 0),
                    parseFloat(item.mileage_due || 0),
                    parseFloat(item.total_earnings || 0),
                    parseFloat(item.total_earnings / item.total_hours || 0),
                    parseFloat(item.amount || 0)
                ])
            })
            
            // Add totals row
            exportData.push([])
            exportData.push([
                '', '', '', 'TOTALS',
                parseFloat(totals.value.regular_hours || 0),
                parseFloat(totals.value.overtime_hours || 0),
                parseFloat(totals.value.total_hours || 0),
                '',
                parseFloat(totals.value.gross_pay || 0),
                parseFloat(totals.value.tips || 0),
                parseFloat(totals.value.tips_due || 0),
                parseFloat(totals.value.mileage_due || 0),
                parseFloat(totals.value.total_earnings || 0),
                parseFloat(totals.value.hr_pay || 0),
                parseFloat(totals.value.amount || 0)
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 10 },  // Sr. No.
                { wch: 25 },  // Store Name
                { wch: 25 },  // Employee Name
                { wch: 15 },  // Role
                { wch: 15 },  // Regular Hours
                { wch: 15 },  // Overtime Hours
                { wch: 15 },  // Total Hours
                { wch: 12 },  // Rate
                { wch: 15 },  // Gross Pay
                { wch: 12 },  // Tips
                { wch: 12 },  // Tips Due
                { wch: 15 },  // Mileage Due
                { wch: 15 },  // Total Earnings
                { wch: 12 },  // HR Pay
                { wch: 15 }   // Payroll Amount
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Network Check Report')
            
            // Generate filename
            const filename = `Network_Check_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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

const setDefaultPeriod = () => {
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
}

const handleReviewChange = (item,e) => {
    if(item.amount <= 0 || !item.employee || !item.company || !item.ledger || !item.fromCompany || !item.check_number || item.min_wage_due > 0){
        item.checked = false
        if(item.min_wage_due > 0){
            message.error('Minimum wage is due for this employee. Please review the minimum wage before reviewing the check.')
        }else{
            message.error('Please fill the check number and from store before reviewing the check.')
        }
    }
    // Update global selectAll state when individual item is changed
    updateGlobalSelectAll()
}

const handleSelectAll = () => {
    payrollData.value.forEach(item => {
        if (!item.reviewed) {
            // Only select items that have all required fields
            if (item.amount > 0 && item.employee && item.company && item.ledger && item.fromCompany && item.check_number) {
                item.checked = selectAll.value
            }
        }
    })
}

const isCompanyAllSelected = (group) => {
    const eligibleItems = group.items.filter(item => 
        !item.reviewed && 
        item.amount > 0 && 
        item.employee && 
        item.company && 
        item.ledger && 
        item.fromCompany && 
        item.check_number
    )
    
    if (eligibleItems.length === 0) return false
    
    return eligibleItems.every(item => item.checked)
}

const handleCompanySelectAll = (group, event) => {
    const isChecked = event.target.checked
    
    group.items.forEach(item => {
        if (!item.reviewed) {
            // Only select items that have all required fields
            if (item.amount > 0 && item.employee && item.company && item.ledger && item.fromCompany && item.check_number) {
                item.checked = isChecked
            }
        }
    })
    
    // Update the global selectAll checkbox state
    updateGlobalSelectAll()
}

const updateGlobalSelectAll = () => {
    const allEligibleItems = payrollData.value.filter(item => 
        !item.reviewed && 
        item.amount > 0 && 
        item.employee && 
        item.company && 
        item.ledger && 
        item.fromCompany && 
        item.check_number
    )
    
    if (allEligibleItems.length === 0) {
        selectAll.value = false
        return
    }
    
    selectAll.value = allEligibleItems.every(item => item.checked)
}

const reviewCheck = async () => {
    try {
        const count = sortedPayrollData.value.filter(item => (item.checked || item.reviewed) && item.amount > 0 && item.employee && item.company && item.ledger && item.fromCompany && item.check_number > 0).length;
        const r = confirm(`Are you sure you want to review the check? ${count} checks will be reviewed.`)
        if (r != true) {
            return
        }
        reviewCheckLoading.value = true
        const check_data = []
        sortedPayrollData.value.forEach(item => {

            if((item.checked || item.reviewed) && item.amount > 0 && item.employee && item.company && item.ledger && item.fromCompany && item.check_number > 0){
                check_data.push({
                    employee_id: item.employee?.id,
                    company_id: item.company?.id,
                    from_company_id: item.fromCompany?.id,
                    ledger_id: item.ledger?.id,
                    payroll_eow: filters.value.selectedPeriod.split(' to ')[1].split('T')[0],
                    check_amount: item.amount ?? 0,
                    check_number: item.check_number,
                    check_date: item.check_date,
                    role_id: item.role?.id,
                    check_type: 'payroll',
                })
            }
        })
        const response = await useRequest('post', '/payroll/network-check-review', {data:check_data})
        applyFilters()
        message.success(response.message)
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally {
        reviewCheckLoading.value = false
    }
}

const handleLedgerChange = async (item) => {
    try {
        const response = await useRequest('get', `/get-latest-check-number/${item.ledger?.id}/${item.company?.id}`)
        item.check_number = response.check_number ? response.check_number : 0


    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    }
}

const handleEdit = (item) => {
    editingItem.value = item.employee_id + '-' + item.company_id
}

const handleFromCompanyChange = async (item) => {
    try {
        const response = await useRequest('get', `/get-default-bank/${item.fromCompany?.id}`)
        item.ledger = response.bank ? response.bank : null
        if(item.ledger){
            handleLedgerChange(item)
        }
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    }
}

const printCheck = async ()=>{
    try {
        const reviewedChecks = sortedPayrollData.value.filter(item=>item.reviewed )
        if(!reviewedChecks.length){
            message.warning('There is no any reviewed check')
            return
        }
        
        // Prepare reviewed checks with selection flag (all selected by default)
        reviewedChecksForPrint.value = reviewedChecks.map(item => ({
            ...item,
            selectedForPrint: true
        }))
        
        selectAllPrintItems.value = true
        showPrintModal.value = true
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    }
}

const closePrintModal = () => {
    showPrintModal.value = false
    reviewedChecksForPrint.value = []
    selectAllPrintItems.value = false
}

const handleSelectAllPrintItems = () => {
    reviewedChecksForPrint.value.forEach(item => {
        item.selectedForPrint = selectAllPrintItems.value
    })
}

const updateSelectAllPrintItems = () => {
    selectAllPrintItems.value = reviewedChecksForPrint.value.every(item => item.selectedForPrint)
}

const confirmPrint = async () => {
    try {
        if (selectedPrintItems.value.length === 0) {
            message.warning('Please select at least one check to print')
            return
        }

        printingLoading.value = true
        
        // Prepare payload with selected items data
        const printData = selectedPrintItems.value.map(item => ({
            employee_id: item.employee?.id,
            company_id: item.company?.id,
            from_company_id: item.fromCompany?.id,
            ledger_id: item.ledger?.id,
            payroll_eow: filters.value.selectedPeriod.split(' to ')[1].split('T')[0],
            check_amount: item.amount ?? 0,
            check_number: item.check_number,
            check_date: item.check_date,
            role_id: item.role?.id,
            check_type: 'payroll',
            employee_name: item.employee_name || item.employee?.pos_name,
            store_name: item.company?.store_number ? item.company?.store_number + ' - ' + item.company?.name : item.company?.name,
            regular_hours: item.regular_hours,
            overtime_hours: item.overtime_hours,
            total_hours: item.total_hours,
            gross_pay: item.gross_pay,
            tips: item.tips,
            tips_due: item.tips_due,
            mileage_due: item.mileage_due,
            total_earnings: item.total_earnings
        }))
        
        const response = await useRequest('post', `/print-check`, {
            check_ids: selectedPrintItems.value.map(item => item.check_id),
        })
        const pdfUrls = response.pdfpaths || (response.pdfpath ? [response.pdfpath] : [])
        pdfUrls.forEach((url, i) => setTimeout(() => window.open(url, '_blank'), i * 200))
        closePrintModal()
        message.success(`${selectedPrintItems.value.length} check(s) printed successfully`)
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally {
        printingLoading.value = false
    }
}

onMounted(() => {
    setDefaultPeriod()
})

</script>