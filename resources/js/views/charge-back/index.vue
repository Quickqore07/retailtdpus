<template>
    <div class="mx-auto  pb-8">
        <nav
            class="mb-6 sm:mb-8 -mx-4  sm:mx-0 sm:px-0 overflow-x-auto"
            role="tablist"
            aria-label="Chargebacks sections"
        >
            <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700 min-w-max sm:min-w-0">
                <button
                    v-for="tab in tabAccess"
                    :key="tab.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.id"
                    class="px-1 pb-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                    :class="activeTab === tab.id
                        ? 'text-primary border-primary dark:text-emerald-400 dark:border-emerald-400'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'"
                    @click="handleTabClick(tab.id)"
                >
                    {{ tab.label }}
                    <span
                        v-if="tabCounts[tab.id] !== undefined"
                        class="ml-1.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums"
                        :class="activeTab === tab.id
                            ? 'bg-primary/10 text-primary dark:bg-emerald-400/15 dark:text-emerald-400'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    >
                        {{ tabCounts[tab.id] }}
                    </span>
                </button>
            </div>
        </nav>

        <Dashboard v-if="activeTab === 'dashboard'" />
        <Enter v-else-if="activeTab === 'enter' && can('charge-back', 'create')" />
        <Chargebacks v-else-if="activeTab === 'chargebacks' && route.query.tab === 'chargebacks'" />
        <SalesReceipts v-else-if="activeTab === 'sales-receipts'" />
        <Expired v-else-if="activeTab === 'expired'" />
        <Reimbursed v-else-if="activeTab === 'reimbursed'" />
        <ReasonCodes v-else-if="activeTab === 'reason-codes' && route.query.tab === 'reason-codes' && can('charge-back-reason-code', 'index')" />
        <EntryModes v-else-if="activeTab === 'entry-modes' && route.query.tab === 'entry-modes' && can('charge-back-entry-mode', 'index')" />
    </div>
</template>

<script setup>
import { onMounted, ref, provide, computed } from 'vue'
import Dashboard from './dashboard.vue'
import Enter from './enter.vue'
import Chargebacks from './chargebacks.vue'
import SalesReceipts from './sales-receipts.vue'
import Expired from './expired.vue'
import Reimbursed from './reimbursed.vue'
import ReasonCodes from './reason-codes.vue'
import EntryModes from './entry-modes.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import { useRouter } from 'vue-router'
import { useRoute } from 'vue-router'
const message = useMessage()
const summaryLoading = ref(false)
const { can } = usePermission()
const reasonCodeLabels = ref({})
const router = useRouter()
const route = useRoute()
const summary = ref({
    scopeLabel: 'All 0 stores shown',
    productionCount: 0,
    metrics: [],
    amountByStatus: [],
    pendingReceipts: [],
})
const totals = ref({
    sales_receipts: { label: 'PENDING CLAIM', amount: 0, cases: 0 },
    expired: { label: 'EXPIRED CLAIM', amount: 0, cases: 0 },
    reimbursed: { label: 'REIMBURSED CLAIM', amount: 0, cases: 0, pending_cases: 0 },
})

const tabCounts = computed(() => ({
    'sales-receipts': totals.value.sales_receipts?.cases ?? 0,
    expired: totals.value.expired?.cases ?? 0,
    reimbursed: totals.value.reimbursed?.pending_cases ?? 0,
}))

const dueLabel = (value) => {
    if (!value || isNaN(value)) return null

    const today = new Date()
    today.setHours(0, 0, 0, 0)

    const dueDate = new Date(value)
    dueDate.setHours(0, 0, 0, 0)

    const dayDiff = Math.round((dueDate - today) / (1000 * 60 * 60 * 24))

    if (dayDiff < 0) return `${Math.abs(dayDiff)}d overdue`
    if (dayDiff === 0) return 'due today'
    return `due in ${dayDiff}d`
}

const refreshReasonCodeLabels = async () => {
    try {
        const response = await useRequest('get', '/charge-back-reason-codes/options')
        reasonCodeLabels.value = response.labels || {}
    } catch {
        reasonCodeLabels.value = {}
    }
}

const refreshSummary = async () => {
    summaryLoading.value = true
    try {
        const response = await useRequest('get', '/charge-backs/summary')
        summary.value = {
            ...(response.summary || {}),
            pendingReceipts: (response.summary?.pendingReceipts || []).map((item) => ({
                ...item,
                due_label: dueLabel(item.processor_due_date),
            })),
        }
        totals.value = response.totals || totals.value
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to load summary')
    } finally {
        summaryLoading.value = false
    }
}

onMounted(async () => {
    await Promise.all([refreshSummary(), refreshReasonCodeLabels()])
})
const tabs = [
    { id: 'dashboard', label: 'Dashboard' },
    { id: 'enter', label: 'Enter chargebacks' },
    { id: 'chargebacks', label: 'Chargebacks' },
    { id: 'sales-receipts', label: 'Sales Receipts' },
    { id: 'expired', label: 'Expired' },
    { id: 'reimbursed', label: 'Reimbursed' },
    { id: 'reason-codes', label: 'Reason Codes' },
    { id: 'entry-modes', label: 'Entry Modes' },
]

const tabAccess = computed(() => {
    return tabs.filter((tab) => {
        if (tab.id === 'enter') {
            return can('charge-back', 'create')
        }
        if (tab.id === 'reason-codes') {
            return can('charge-back-reason-code', 'index')
        }
        if (tab.id === 'entry-modes') {
            return can('charge-back-entry-mode', 'index')
        }
        return true
    })
})

const activeTab = ref('dashboard')
const editingChargeback = ref(null)

const handleTabClick = (tab) => {
    activeTab.value = tab

    router.push({
        query: {
            tab: tab
        }
    });
}

const startEditing = (chargeback) => {
    editingChargeback.value = chargeback
    activeTab.value = 'enter'
}

const clearEditing = () => {
    editingChargeback.value = null
}
const viewAttachment = async (id) => {
    try {
        const response = await useRequest('post', '/view-document', {
            id,
        })
        if (response.url) {
            window.open(response.url, '_blank', 'noopener,noreferrer')
        } else {
            message.error('Failed to view attachment')
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to view attachment')
    }
}

provide('chargeBackNavigation', {
    handleTabClick,
    switchTab: handleTabClick,
    activeTab,
    editingChargeback,
    startEditing,
    clearEditing,
    viewAttachment,
    summary,
    totals,
    summaryLoading,
    refreshSummary,
    reasonCodeLabels,
    refreshReasonCodeLabels,
})
</script>
