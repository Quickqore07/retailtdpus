<template>
    <div class="employee-hours-anomaly-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Employee Hours Anomaly Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View employee hours records with unusual hour values (greater than 12 or less than 0)
                </p>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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

                <!-- Filter Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Filter Type
                    </label>
                    <select
                        v-model="filters.filterType"
                        @change="applyFilters"
                        class="form-select"
                    >
                        <option value="greater_than_12">Greater than 12 hours</option>
                        <option value="less_than_0">Less than 0 hours</option>
                        <option value="both">Both (&gt; 12 or &lt; 0)</option>
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
                </div>
            </div>
        </Panel>

        <!-- Summary Cards -->
        <div v-if="reportData.length > 0" class="mb-6">
            <MultiStatCard
                label="Anomaly Summary"
                icon="alert"
                icon-color="red"
                :items="[
                    { label: 'Total Anomalies', value: totalAnomalies },
                    { label: 'Affected Companies', value: reportData.length },
                    { label: 'Unique Employees', value: uniqueEmployees }
                ]"
            />
        </div>

        <!-- Data Tables - Company Wise -->
        <div v-if="reportData.length > 0" class="space-y-6 max-h-[calc(100vh-250px)] relative overflow-y-auto">
            <template v-for="company in reportData" :key="company.id">
                <Panel v-if="company?.anomalies && company.anomalies?.length">
                    <!-- Company Header -->
                    <div class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex flex-col md:flex-row items-center justify-between">
                            <h3 class="text-center md:text-left !text-sm !md:text-xl font-bold text-gray-900 dark:text-white !mb-0">
                                {{ company.name }}
                            </h3>
                            <p class="text-sm text-center md:text-left text-gray-600 dark:text-gray-400 mt-1">
                                Store Number: {{ company.store_number }} | Anomalies: {{ company.anomalies.length }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-[calc(100vh-150px)] relative">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                                <tr>
                                    <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">Sr. No.</Th>
                                    <Th class="md:sticky md:left-13 bg-white dark:bg-gray-900 !z-[101]">Date</Th>
                                    <Th class="md:sticky md:left-28 bg-white dark:bg-gray-900 !z-[101]">Employee ID</Th>
                                    <Th class="md:sticky md:left-48 bg-white dark:bg-gray-900 !z-[101]">Employee Name</Th>
                                    <Th>Role</Th>
                                    <Th>Total Hours</Th>
                                    <Th>Pay Rate</Th>
                                    <Th>Pay Type</Th>
                                    <Th>Tips</Th>
                                    <Th>Tips Due</Th>
                                    <Th>Mileage Excess</Th>
                                    <Th>Mileage Due</Th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                <tr 
                                    v-for="(anomaly, index) in company.anomalies" 
                                    :key="anomaly.id"
                                    class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @dblclick="navigateToEmployee(anomaly)"
                                >
                                    <Td color="secondary" class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[100]">
                                        {{ index + 1 }}
                                    </Td>
                                    <Td 
                                        color="secondary" 
                                        class="md:sticky md:left-13 bg-white dark:bg-gray-900 !z-[100]"
                                    >
                                        {{ formatDate(anomaly.date) }}
                                    </Td>
                                    <Td 
                                        color="secondary"
                                        class="border-r-2 border-gray-300 dark:border-gray-600 md:sticky md:left-28 bg-white dark:bg-gray-900 !z-[100]"
                                    >
                                        {{ anomaly.employee?.employee_id }}
                                    </Td>
                                    <Td 
                                        weight="medium"
                                        color="primary"
                                        class="border-r-2 border-gray-300 dark:border-gray-600 max-w-96 truncate md:sticky md:left-48 bg-white dark:bg-gray-900 !z-[100]"
                                        :title="anomaly.employee_name"
                                    >
                                        {{ anomaly.employee_name }}
                                    </Td>
                                    <Td color="secondary">{{ anomaly.role?.name || '-' }}</Td>
                                    <Td 
                                        weight="bold" 
                                        :customClass="getHoursClass(anomaly.total_hours)"
                                    >
                                        {{ formatNumber(anomaly.total_hours) }} hrs
                                    </Td>
                                    <Td color="secondary">${{ formatNumber(anomaly.pay_rate) }}</Td>
                                    <Td color="secondary">{{ anomaly.pay_type || '-' }}</Td>
                                    <Td color="secondary">${{ formatNumber(anomaly.tips) }}</Td>
                                    <Td color="secondary">${{ formatNumber(anomaly.tips_due) }}</Td>
                                    <Td color="secondary">${{ formatNumber(anomaly.mileage_excess) }}</Td>
                                    <Td color="secondary">${{ formatNumber(anomaly.mileage_due) }}</Td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Panel>
            </template>
        </div>

        <!-- No Data Panel -->
        <Panel v-else>
            <div class="py-12">
                <NoData
                    icon="files"
                    icon-color="blue"
                    title="No Anomalies Found"
                    message="There are no employee hours records with the selected criteria for the selected period."
                    size="lg"
                    icon-size="xl"
                    :show-action="true"
                    action-text="Apply Filters"
                    action-icon="refresh"
                    @action="applyFilters"
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

const message = useMessage()
const authStore = useAuthStore()
const isDC = computed(() => {
    return authStore.isDC || false
})
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    filterType: 'greater_than_12'
})

const reportData = ref([])
const totalAnomalies = ref(0)
const loading = ref(false)
const exportLoading = ref(false)

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
    let daysToMonday = 0;
    if(isDC.value){
        daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek // If Sunday, go back 6 days; otherwise go to previous/current Monday
    }else{
        daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7 // If Sunday, go back 6 days; otherwise go to previous/current Monday
    }
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)
    
    let periodNumber = 1
    
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13)
        
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

const uniqueEmployees = computed(() => {
    const employeeIds = new Set()
    reportData.value.forEach(company => {
        company.anomalies?.forEach(anomaly => {
            employeeIds.add(anomaly.employee_id)
        })
    })
    return employeeIds.size
})

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

const applyFilters = async () => {
    try {
        loading.value = true
        const response = await useRequest('post', '/reports/hr/employee-hours-anomaly', {
            start_date: filters.value.selectedPeriod.split('to')[0].trim(),
            end_date: filters.value.selectedPeriod.split('to')[1].trim(),
            filter_type: filters.value.filterType
        })
        reportData.value = response.data
        totalAnomalies.value = response.total_anomalies
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
            
            exportData.push(['Employee Hours Anomaly Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Filter Type:', getFilterTypeLabel(filters.value.filterType)])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push(['Total Anomalies:', totalAnomalies.value])
            exportData.push([])
            
            reportData.value.forEach(company => {
                if (!company.anomalies || company.anomalies.length === 0) return
                
                exportData.push([])
                exportData.push([`Company: ${company.name}`])
                exportData.push([`Store Number: ${company.store_number} | Anomalies: ${company.anomalies.length}`])
                exportData.push([])
                
                const headerRow = [
                    'Sr. No.',
                    'Date',
                    'Employee ID',
                    'Employee Name',
                    'Role',
                    'Total Hours',
                    'Pay Rate',
                    'Pay Type',
                    'Tips',
                    'Tips Due',
                    'Mileage Excess',
                    'Mileage Due',
                ]
                
                exportData.push(headerRow)
                
                company.anomalies.forEach((anomaly, index) => {
                    const row = [
                        index + 1,
                        formatDate(anomaly.date),
                        anomaly.employee?.employee_id || '',
                        anomaly.employee_name || '',
                        anomaly.role?.name || '-',
                        parseFloat(anomaly.total_hours || 0),
                        parseFloat(anomaly.pay_rate || 0),
                        anomaly.pay_type || '-',
                        parseFloat(anomaly.tips || 0),
                        parseFloat(anomaly.tips_due || 0),
                        parseFloat(anomaly.mileage_excess || 0),
                        parseFloat(anomaly.mileage_due || 0),
                    ]
                    
                    exportData.push(row)
                })
                
                exportData.push([])
            })
            
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            const colWidths = []
            exportData.forEach(row => {
                row.forEach((cell, colIndex) => {
                    const cellLength = cell ? cell.toString().length : 10
                    if (!colWidths[colIndex] || colWidths[colIndex] < cellLength) {
                        colWidths[colIndex] = Math.min(cellLength + 2, 30)
                    }
                })
            })
            
            ws['!cols'] = colWidths.map(width => ({ wch: width }))
            
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Hours Anomaly Report')
            
            const filename = `Employee_Hours_Anomaly_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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

const setDefaultPeriod = async () => {
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
    await applyFilters()
}

const formatDate = (date) => {
    if (!date) return '-'
    const d = new Date(date)
    return d.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
}

const getHoursClass = (hours) => {
    if (hours > 12) {
        return '!text-red-600 dark:!text-red-400'
    } else if (hours < 0) {
        return '!text-orange-600 dark:!text-orange-400'
    }
    return ''
}

const getFilterTypeLabel = (filterType) => {
    const labels = {
        'greater_than_12': 'Greater than 12 hours',
        'less_than_0': 'Less than 0 hours',
        'both': 'Both (> 12 or < 0)'
    }
    return labels[filterType] || filterType
}

const navigateToEmployee = (anomaly) => {
    if(anomaly.employee && anomaly.employee.employee_type == 'New'){
        window.open(`/onboarding/employee/new/${anomaly.employee_id}`, '_blank')
    }else if(anomaly.employee && anomaly.employee.employee_type == 'Existing'){
        window.open(`/onboarding/employee/existing/${anomaly.employee_id}`, '_blank')
    }else {
        window.open(`/employee/${anomaly.employee_id}`, '_blank')
    }
}

onMounted(async() => {
    await setDefaultPeriod()
})
</script>
