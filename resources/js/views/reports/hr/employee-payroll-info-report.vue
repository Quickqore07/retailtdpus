<template>
    <div class="employee-payroll-info-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Employee Payroll Information Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View active employees with the rate applicable for the selected payroll period
                </p>
            </div>

            <!-- Payroll Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onYearChange"
                        class="form-select w-full"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Period
                    </label>
                    <select
                        v-model="filters.selectedPeriod"
                        @change="onPeriodChange"
                        class="form-select w-full"
                    >
                        <option v-for="period in availablePeriods" :key="period.value" :value="period.value">
                            {{ period.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Min Rate
                    </label>
                    <input
                        v-model="filters.min_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Min rate"
                        class="form-input w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Max Rate
                    </label>
                    <input
                        v-model="filters.max_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Max rate"
                        class="form-input w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Rate Type
                    </label>
                    <select v-model="filters.rate_type" class="form-select w-full">
                        <option value="">All Rate Types</option>
                        <option v-for="type in rateTypeOptions" :key="type" :value="type">
                            {{ type }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pay Type
                    </label>
                    <select v-model="filters.pay_type" class="form-select w-full">
                        <option value="">All Pay Types</option>
                        <option v-for="type in payTypeOptions" :key="type" :value="type">
                            {{ type }}
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
                        icon-left="x"
                        icon-size="sm"
                        variant="secondary"
                        size="sm"
                        @click="clearFilters"
                        :disabled="loading"
                    >
                        Clear Filters
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
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Period: <span class="font-semibold">{{ currentPeriodLabel }}</span>
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Total Records: <span class="font-semibold">{{ totalRecords }}</span>
                    </span>
                </div>
            </div>
        </Panel>

        <!-- Summary Cards -->
        <div v-if="reportData.length > 0" class="mb-6">
            <MultiStatCard
                label="Report Summary"
                icon="users"
                icon-color="blue"
                :items="[
                    { label: 'Total Records', value: totalRecords },
                    { label: 'Unique Employees', value: uniqueEmployees },
                    { label: 'Companies', value: uniqueCompanies }
                ]"
            />
        </div>

        <!-- Data Table -->
        <Panel v-if="reportData.length > 0">
            <div class="overflow-x-auto max-h-[calc(100vh-250px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">Sr. No.</Th>
                            <Th class="md:sticky md:left-15 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('employee_id')">
                                    <span>Employee ID</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'employee_id'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-38 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('employee_name')">
                                    <span>Employee Name</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'employee_name'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-80 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('company')">
                                    <span>Company</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'company'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('role')">
                                    <span>Role</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'role'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('rate')">
                                    <span>Rate</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'rate'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="text-right">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('total_hours')">
                                    <span>Total Hours</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'total_hours'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('rate_type')">
                                    <span>Rate Type</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'rate_type'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('pay_type')">
                                    <span>Pay Type</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'pay_type'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('payroll_type')">
                                    <span>Payroll Type</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'payroll_type'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sortBy('payroll_period')">
                                    <span>Payroll Period</span>
                                    <SvgIcon 
                                        v-if="sortColumn === 'payroll_period'" 
                                        :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>Slab Info</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        <tr 
                            v-for="(record, index) in reportData" 
                            :key="`${record.id}-${record.role_name}-${index}`"
                            class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                            @dblclick="navigateToEmployee(record)"
                        >
                            <Td color="secondary" class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[100]">
                                {{ index + 1 }}
                            </Td>
                            <Td 
                                color="secondary"
                                class="md:sticky md:left-15 bg-white dark:bg-gray-900 !z-[100]"
                            >
                                {{ record.employee_id }}
                            </Td>
                            <Td 
                                weight="medium"
                                color="primary"
                                class="border-r-2 border-gray-300 dark:border-gray-600 max-w-[300px] truncate md:sticky md:left-38 bg-white dark:bg-gray-900 !z-[100]"
                                :title="getEmployeeName(record)"
                            >
                                {{ getEmployeeName(record) }}
                            </Td>
                            <Td color="secondary"
                            class="border-r-2 border-gray-300 dark:border-gray-600 max-w-[300px] truncate md:sticky md:left-80 bg-white dark:bg-gray-900 !z-[100]"
                            >{{ record.company?.name || '-' }}</Td>
                            <Td color="secondary">{{ record.role_name }}</Td>
                            <Td weight="bold" color="primary">
                                ${{ formatNumber(record.rate) }}
                                <span v-if="record.rate_type === 'Payroll Slab'" class="text-xs text-gray-500">
                                    ({{ record.rate_type }})
                                </span>
                            </Td>
                            <Td class="text-right">
                                {{ record.total_hours }}
                            </Td>
                            <Td color="secondary">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getRateTypeClass(record.rate_type)">
                                    {{ record.rate_type ? record.rate_type : '-' }}
                                </span>
                            </Td>
                            <Td color="secondary">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getPayTypeClass(record.pay_type)">
                                    {{ record.pay_type ? record.pay_type : '-' }}
                                </span>
                            </Td>
                            <Td color="secondary">{{ record.payroll_type ? record.payroll_type : '-' }}</Td>
                            <Td color="secondary">{{ record.payroll_period ? record.payroll_period : currentPeriodLabel }}</Td>
                            <Td color="secondary">
                                <span v-if="record.rate_type === 'Payroll Slab' && record.slab_first_hours">
                                    {{ record.slab_first_hours }}h @ ${{ formatNumber(record.rate) }},
                                    then ${{ formatNumber(record.slab_rest_rate) }}
                                </span>
                                <span v-else-if="record.payroll_hours">
                                    {{ record.payroll_hours }} {{ record.payroll_hours_type }}
                                </span>
                                <span v-else>-</span>
                            </Td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>

        <!-- No Data Panel -->
        <Panel v-else-if="!loading">
            <div class="py-12">
                <NoData
                    icon="users"
                    icon-color="blue"
                    title="No Employee Data Found"
                    message="There are no active employees with payroll rates configured in the system."
                    size="lg"
                    icon-size="xl"
                    :show-action="true"
                    action-text="Refresh Data"
                    action-icon="refresh"
                    @action="loadReport"
                />
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
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const message = useMessage()

const reportData = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const exportLoading = ref(false)
const sortColumn = ref('')
const sortDirection = ref('asc')

const filters = ref({
    selectedYear: new Date().getFullYear(),
    selectedPeriod: '',
    min_rate: '',
    max_rate: '',
    rate_type: '',
    pay_type: '',
})
const isDC = computed(() => authStore.isDC || false)

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})

const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const periods = []

    let startDate = new Date(Date.UTC(year, 0, 1))

    const dayOfWeek = startDate.getUTCDay()
    let daysToMonday = 0
    if (isDC.value) {
        daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek
    } else {
        daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7
    }
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

    let periodNumber = 1

    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13)

        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }

        const formatPeriodDate = (date) => {
            const month = String(date.getUTCMonth() + 1).padStart(2, '0')
            const day = String(date.getUTCDate()).padStart(2, '0')
            const yearValue = date.getUTCFullYear()
            return `${month}-${day}-${yearValue}`
        }

        const label = `${formatPeriodDate(startDate)} To ${formatPeriodDate(endDate)}`
        const value = `${startDate.toISOString()} to ${endDate.toISOString()}`

        periods.push({ label, value, startDate: new Date(startDate), endDate: new Date(endDate) })

        startDate.setUTCDate(startDate.getUTCDate() + 14)
        periodNumber++
    }

    return periods
})

const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})

const rateTypeOptionsPayroll = [
    'Payroll Regular',
    'Payroll Slab',
]
const rateTypeOptions1099 = [
    'Payroll 1099',
    '1099 Regular',
    '1099 Slab',
    '1099 1099',
]
const rateTypeOptions = computed(() => {
    return [...rateTypeOptionsPayroll, ...(!isDC.value ? rateTypeOptions1099 : [])]
})

const payTypeOptions = ['HR', 'WK']

const uniqueEmployees = computed(() => {
    const employeeIds = new Set()
    reportData.value.forEach(record => {
        employeeIds.add(record.employee_id)
    })
    return employeeIds.size
})

const uniqueCompanies = computed(() => {
    const companyIds = new Set()
    reportData.value.forEach(record => {
        if (record.company?.id) {
            companyIds.add(record.company.id)
        }
    })
    return companyIds.size
})

const sortBy = async (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortColumn.value = column
        sortDirection.value = 'asc'
    }
    await loadReport()
}

const buildRequestPayload = () => {
    const payload = {
        sort_column: sortColumn.value,
        sort_direction: sortDirection.value,
        start_date: filters.value.selectedPeriod.split('to')[0].trim(),
        end_date: filters.value.selectedPeriod.split('to')[1].trim(),
    }

    const rateFilters = ['min_rate', 'max_rate', 'rate_type', 'pay_type']
    rateFilters.forEach((key) => {
        const value = filters.value[key]
        if (value !== '' && value !== null && value !== undefined) {
            payload[key] = value
        }
    })

    return payload
}

const loadReport = async () => {
    if (!filters.value.selectedPeriod) {
        return
    }

    try {
        loading.value = true
        const response = await useRequest('post', '/reports/hr/employee-payroll-info', buildRequestPayload())
        reportData.value = response.data
        totalRecords.value = response.total_records
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message || 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const applyFilters = async () => {
    await loadReport()
}

const clearFilters = async () => {
    filters.value.min_rate = ''
    filters.value.max_rate = ''
    filters.value.rate_type = ''
    filters.value.pay_type = ''
    await loadReport()
}

const onYearChange = () => {
    filters.value.selectedPeriod = ''
    if (availablePeriods.value.length > 0) {
        filters.value.selectedPeriod = availablePeriods.value[0].value
    }
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const onPeriodChange = () => {
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const setDefaultPeriod = () => {
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
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
            
            exportData.push(['Employee Payroll Information Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push(['Total Records:', totalRecords.value])
            exportData.push([])
            
            const headerRow = [
                'Sr. No.',
                'Employee ID',
                'Employee Name',
                'Company',
                'Store Number',
                'Role',
                'Rate',
                'Total Hours',
                'Rate Type',
                'Pay Type',
                'Payroll Type',
                'Payroll Period',
                'Slab First Hours',
                'Slab Rest Rate',
                'Payroll Hours',
                'Payroll Hours Type',
            ]
            
            exportData.push(headerRow)
            
            reportData.value.forEach((record, index) => {
                const row = [
                    index + 1,
                    record.employee_id || '',
                    getEmployeeName(record),
                    record.company?.name || '-',
                    record.company?.store_number || '-',
                    record.role_name || '-',
                    parseFloat(record.rate || 0),
                    record.total_hours || 0,
                    record.rate_type,
                    record.pay_type || '-',
                    record.payroll_type || '-',
                    record.payroll_period || currentPeriodLabel.value,
                    record.slab_first_hours || '',
                    record.slab_rest_rate || '',
                    record.payroll_hours || '',
                    record.payroll_hours_type || '',
                ]
                
                exportData.push(row)
            })
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            const colWidths = []
            exportData.forEach(row => {
                row.forEach((cell, colIndex) => {
                    const cellLength = cell ? cell.toString().length : 10
                    if (!colWidths[colIndex] || colWidths[colIndex] < cellLength) {
                        colWidths[colIndex] = Math.min(cellLength + 2, 40)
                    }
                })
            })
            
            ws['!cols'] = colWidths.map(width => ({ wch: width }))
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Employee Payroll Info')
            
            const filename = `Employee_Payroll_Info_Report_${new Date().getTime()}.xlsx`
            
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

const getEmployeeName = (record) => {
    const aliasNames = (record.aliases || []).map(a => a.alias_name)
    const names = [record.pos_name, ...aliasNames, record.employee_name]
        .filter(name => name && String(name).trim() !== '')
    // Prefer combined employee_name from API when present
    if (record.employee_name && String(record.employee_name).trim() !== '' && record.employee_name !== '-') {
        return record.employee_name
    }
    return names.length > 0 ? [...new Set(names)].join(' ') : '-'
}

const formatDate = (date) => {
    if (!date) return '-'
    const d = new Date(date)
    return d.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
}


const getRateTypeClass = (rateType) => {
    const classMap = {
        'Payroll Regular': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'Payroll Slab': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        'Payroll 1099': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'Hourly': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
    return classMap[rateType] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const getPayTypeClass = (payType) => {
    const classMap = {
        'HR': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        'SAL': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300'
    }
    return classMap[payType] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const navigateToEmployee = (record) => {
    window.open(`/employee/${record.id}`, '_blank')
}

onMounted(() => {
    setDefaultPeriod()
    loadReport()
})
</script>
