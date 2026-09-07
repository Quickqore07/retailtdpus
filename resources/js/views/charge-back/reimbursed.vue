<template>
    <div>
        <header class="mb-6 sm:mb-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="!text-xl sm:!text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Reimbursed
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
                        Submitted chargebacks awaiting or marked as credited.
                    </p>
                </div>
                <Button
                    variant="secondary"
                    size="sm"
                    icon-left="download"
                    icon-size="sm"
                    custom-class="shrink-0"
                    :loading="isExporting"
                    @click="exportToExcel"
                >
                    Export to Excel
                </Button>
            </div>
        </header>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4 mb-6">
            <p class="text-[10px] sm:text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase mb-1">
                {{ total.label }}
            </p>
            <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white !mb-0">
                {{ formatCurrency(total.amount) }} <span class="text-sm font-medium text-gray-500 dark:text-gray-400">/ {{ total.cases }} {{ total.cases === 1 ? 'case' : 'cases' }}</span>
            </p>
        </div>

        <ManagerFilters @change="onManagerFilterChange" />

        <Panel padding="none">
            <div v-if="loading" class="flex items-center justify-center min-h-[220px]">
                <Spinner size="md" text="Loading chargebacks..." centered />
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <Th>Case #</Th>
                            <Th>Store</Th>
                            <Th>Amount</Th>
                            <Th>Submitted By</Th>
                            <Th>Credited By</Th>
                            <Th>Submitted At</Th>
                            <Th>Credited At</Th>
                            <Th>Credited Date</Th>
                            <Th>Due Date</Th>
                            <Th>Status</Th>
                            <Th class="text-right">Actions</Th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <tr
                            v-for="item in reimbursedItems"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                        >
                            <Td>
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.case_number || '-' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.reference_number || '-' }}</div>
                            </Td>
                            <Td color="secondary">{{ item.company?.name || '-' }}</Td>
                            <Td weight="medium" color="primary">{{ formatCurrency(item.amount) }}</Td>
                            <Td color="secondary">{{ item.submitted_by?.name || '-' }}</Td>
                            <Td color="secondary">{{ item.marked_as_received_by?.name || '-' }}</Td>
                            <Td color="secondary">{{ formatDate(item.submitted_at) }}</Td>
                            <Td color="secondary">{{ formatDate(item.marked_as_received_at) }}</Td>
                            <Td color="secondary">{{ formatDate(item.credited_date) }}</Td>
                            <Td color="secondary">{{ formatDate(item.processor_due_date) }}</Td>
                            <Td color="secondary">{{ item.display_status?.label || '-' }}</Td>
                            <Td >
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        v-if="canMarkAsCredited(item)"
                                        variant="success"
                                        size="xs"
                                        icon-left="check-circle"
                                        icon-size="sm"
                                        :loading="creditingId === item.id"
                                        @click="handleMarkAsCredited(item)"
                                    >
                                        Mark as credited
                                    </Button>
                                    <!-- <span
                                        v-else-if="item.marked_as_received"
                                        class="text-xs text-emerald-600 dark:text-emerald-400"
                                    >
                                        Credited
                                    </span> -->
                                    <div class="flex justify-end">
                                        <IconMenuDropdown title="Attachments">
                                            <template #default="{ close }">
                                                <button
                                                    v-if="item.document?.file_path"
                                                    type="button"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                                    role="menuitem"
                                                    @click="viewAttachment(item.document.id); close()"
                                                >
                                                    <SvgIcon name="file-text" size="sm" />
                                                    View dispute
                                                </button>
                                                <button
                                                    v-if="item.sales_receipt_document?.file_path"
                                                    type="button"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                                    role="menuitem"
                                                    @click="viewAttachment(item.sales_receipt_document.id); close()"
                                                >
                                                   <SvgIcon name="file-text" size="sm" />
                                                    View receipt
                                                </button>
                                                <div
                                                    v-if="!item.document?.file_path && !item.sales_receipt_document?.file_path"
                                                    class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    No attachments
                                                </div>
                                            </template>
                                        </IconMenuDropdown>
                                    </div>
                                </div>
                            </Td>
                        </tr>
                        <tr v-if="reimbursedItems.length === 0">
                            <td colspan="11" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No chargebacks found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, unref } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Spinner from '@/components/ui/spinner.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import ManagerFilters from './components/ManagerFilters.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatCurrency } from '@/utils/number'
import { formatDate } from '@/utils/date'
import { usePermission } from '@/composables/usePermission'
import { useChargeBackExcelExport } from '@/composables/useChargeBackExcelExport'

const { can } = usePermission()
const { isExporting, exportFromBackend } = useChargeBackExcelExport()
const loading = ref(true)
const items = ref([])
const filterUserId = ref(null)
const creditingId = ref(null)
const message = useMessage()
const { viewAttachment, totals, refreshSummary } = inject('chargeBackNavigation')
const total = computed(() => ({
    label: unref(totals)?.reimbursed?.label || 'REIMBURSED CLAIM',
    amount: reimbursedItems.value.reduce((sum, item) => sum + Number(item.amount || 0), 0),
    cases: reimbursedItems.value.length,
}))

const reimbursedItems = computed(() => items.value.filter((item) => !!item.submitted))


const canMarkAsCredited = (item) => item.submitted && !item.marked_as_received && can('charge-back', 'mark-as-credited')

const onManagerFilterChange = (userId) => {
    filterUserId.value = userId
    loadData()
}

const loadData = async () => {
    loading.value = true
    try {
        const params = {
            limit: 10000,
            sort_column: 'chargebacks.processor_due_date',
            sort_direction: 'desc',
        }

        if (filterUserId.value) {
            params.user_id = filterUserId.value
        }

        const response = await useRequest('get', '/charge-backs', null, { params })
        items.value = response.collection?.data || []
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to load chargebacks')
    } finally {
        loading.value = false
    }
}

const handleMarkAsCredited = async (item) => {
    const confirmed = confirm('Mark this chargeback as credited?')
    if (!confirmed) {
        return
    }

    creditingId.value = item.id

    try {
        const response = await useRequest('post', `/charge-backs/${item.id}/mark-as-credited`)

        if (response.saved) {
            message.success(response.message || 'Chargeback marked as credited.')
            await Promise.all([loadData(), refreshSummary()])
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to mark chargeback as credited.')
    } finally {
        creditingId.value = null
    }
}

const exportToExcel = () => {
    exportFromBackend({
        url: '/charge-backs-export',
        filename: 'chargeback_reimbursed_export.xlsx',
        params: {
            tab: 'reimbursed',
            ...(filterUserId.value ? { user_id: filterUserId.value } : {}),
        },
    })
}

onMounted(loadData)
</script>
