<template>
    <div>
        <header class="mb-6 sm:mb-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <h1 class="!text-xl sm:!text-2xl font-bold text-gray-900 dark:text-white !mb-0">
                            Dashboard
                        </h1>
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs text-gray-600 dark:text-gray-300">
                            Scope: {{ summary.scopeLabel }} · {{ summary.productionCount }} in production
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
                        Live status of credit-card chargebacks and the sales receipts needed to dispute them.
                    </p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-4 mb-6 sm:mb-8">
            <SummaryMetricCard
                v-for="metric in summary.metrics"
                :key="metric.key"
                :label="metric.label"
                :amount="metric.amount"
                :cases="metric.cases"
                :sub-label="metric.subLabel"
                :cases-only="metric.casesOnly"
                :accent="metric.accent"
            />
        </div>

        <div class="mb-6 sm:mb-8">
            <StatusAmountBars :rows="summary.amountByStatus" />
        </div>

        <PendingReceiptTable
            :items="summary.pendingReceipts"
            @view-all="goToSalesReceipts"
            @upload-receipt="handleUploadReceipt"
        />
    </div>
</template>

<script setup>
import { inject } from 'vue'
import SummaryMetricCard from './components/SummaryMetricCard.vue'
import StatusAmountBars from './components/StatusAmountBars.vue'
import PendingReceiptTable from './components/PendingReceiptTable.vue'

const { switchTab, summary } = inject('chargeBackNavigation')

const goToSalesReceipts = () => {
    switchTab('chargebacks')
}

const handleUploadReceipt = () => {
    switchTab('sales-receipts')
}
</script>
