<template>
    <div>
        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <StatWidget v-if="can('employee', 'show')" :value="stats.new_hires" label="New Hire Employees" icon="user"
                variant="primary" to="/onboarding/employee/new" />

            <StatWidget v-if="can('employee-rate-request', 'show')" :value="stats.rate_requests_pending"
                label="Rate Requests Pending" icon="forms" variant="success" :sub-label="rateRequestsSubLabel"
                to="/onboarding/employee-rate-request" />

            <StatWidget v-if="can('employee-hours-request', 'show')" :value="stats.hours_requests_pending"
                label="Hours Requests Pending" icon="calendar" variant="warning" :sub-label="hoursRequestsSubLabel"
                to="/payroll/employee-hours-request" />
        </div>

        <!-- Gross Payroll Chart -->
        <ChartCard
            v-if="can('payroll-report', 'index')"
            title="Gross Payroll"
            :labels="chartLabels"
            :series="chartSeries"
            :loading="chartLoading"
            chart-type="line"
            value-format="currency"
        >
            <template #toolbar>
                <div class="flex flex-col sm:flex-row flex-wrap sm:flex-nowrap items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <div class="w-full sm:min-w-[180px] sm:max-w-[400px]">
                        <DynamicDropdown v-model="selectedCompanies" resource="companies" multiple
                            placeholder="All companies" display-name="name" :remove-null-option="true"
                            @change="loadChartData" />
                    </div>
                    <select v-model="chartYear" @change="loadChartData"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary/50 w-full sm:w-auto">
                        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </template>
        </ChartCard>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRequest } from '@/services/api'
import StatWidget from '@/components/ui/StatWidget.vue'
import ChartCard from '@/components/ui/ChartCard.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()

const stats = ref({
    new_hires: null,
    rate_requests_pending: null,
    rate_requests_pending_hr: null,
    rate_requests_pending_do: null,
    hours_requests_pending: null,
    hours_requests_pending_hr: null,
    hours_requests_pending_do: null,
})

const rateRequestsSubLabel = computed(() => {
    const hr = stats.value.rate_requests_pending_hr
    const do_ = stats.value.rate_requests_pending_do
    if (hr == null && do_ == null) return null
    return `HR Pending: ${hr ?? 0} · DO Pending: ${do_ ?? 0}`
})

const hoursRequestsSubLabel = computed(() => {
    const hr = stats.value.hours_requests_pending_hr
    const do_ = stats.value.hours_requests_pending_do
    if (hr == null && do_ == null) return null
    return `HR Pending: ${hr ?? 0} · DO Pending: ${do_ ?? 0}`
})

const chartYear = ref(new Date().getFullYear())
const selectedCompanies = ref([])
const chartLoading = ref(true)
const chartLabels = ref([])
const chartData = ref([])

const availableYears = computed(() => {
    const current = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => current - i)
})

const chartSeries = computed(() => [
    { name: 'Gross Payroll', data: chartData.value },
])

async function loadChartData() {
    chartLoading.value = true
    try {
        const params = { year: chartYear.value }
        const companyIds = Array.isArray(selectedCompanies.value) && selectedCompanies.value.length > 0
            ? selectedCompanies.value.map(c => c.id).filter(Boolean)
            : []
        if (companyIds.length > 0) {
            params.company_ids = companyIds
        }
        const data = await useRequest('get', 'dashboard/bi-weekly-payroll-chart', null, {
            params,
        })
        chartLabels.value = data.labels || []
        chartData.value = data.series || []
    } catch (err) {
        console.error('Failed to load chart data:', err)
        chartLabels.value = []
        chartData.value = []
    } finally {
        chartLoading.value = false
    }
}

async function loadDashboardStats() {
    try {
        const data = await useRequest('get', 'dashboard/stats')
        stats.value = data
    } catch (err) {
        console.error('Failed to load dashboard stats:', err)
    }
}

onMounted(() => {
    let companyId = localStorage.getItem('company')
    if (companyId) {
        loadDashboardStats()
        if (can('payroll-report', 'index')) {
            loadChartData()
        }
    }
})
</script>
