<template>
    <div>
        <header class="mb-6 sm:mb-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="!text-xl sm:!text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Sales Receipts
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
                        Chargebacks where a sales receipt is uploaded but not submitted yet.
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
                            <Th>Receipt Uploaded</Th>
                            <Th>Receipt Uploaded By</Th>
                            <Th>Due Date</Th>
                            <Th>Status</Th>
                            <Th>Actions</Th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <tr
                            v-for="item in salesReceiptItems"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                        >
                            <Td>
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.case_number || '-' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.reference_number || '-' }}</div>
                            </Td>
                            <Td color="secondary">{{ item.company?.name || '-' }}</Td>
                            <Td weight="medium" color="primary">{{ formatCurrency(item.amount) }}</Td>
                            <Td color="secondary">{{ formatDate(item.upload_sales_receipt_date) }}</Td>
                            <Td color="secondary">{{ item.sales_receipt_uploaded_by?.name || '-' }}</Td>
                            <Td color="secondary">{{ formatDate(item.processor_due_date) }}</Td>
                            <Td color="secondary">
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="statusClass(item.display_status?.tone)"
                                    >
                                        {{ item.display_status?.label || '-' }}
                                    </span>
                                    <span
                                        v-if="item.display_status?.sublabel"
                                        class="text-xs text-amber-600 dark:text-amber-400"
                                    >
                                        {{ item.display_status.sublabel }}
                                    </span>
                                </div>
                            </Td>
                            <Td>
                                <div class="flex  gap-2">
                                     
                                    <Button
                                        v-if="canSubmit(item)"
                                        variant="primary"
                                        size="xs"
                                        icon-left="check"
                                        icon-size="sm"
                                        custom-class="text-xs !px- !py-1"
                                        :loading="submittingId === item.id"
                                        @click="handleSubmit(item)"
                                    >
                                        Submit
                                    </Button>
                                   
                                   
                                    <div class="flex justify-end">
                                        <IconMenuDropdown title="Attachments">
                                            <template #default="{ close }">
                                                <button
                                                    v-if="canEdit(item)"
                                                    type="button"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                                    role="menuitem"
                                                    @click="handleEdit(item)"
                                                >
                                                    <SvgIcon name="edit" size="sm" />
                                                    Edit
                                                </button>

                                                <button
                                                    v-if="canUploadReceipt(item)"
                                                    type="button"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                                    role="menuitem"
                                                    @click="openUploadModal(item); close()"
                                                >
                                                    <SvgIcon name="upload" size="sm" />
                                                    Re-upload receipt
                                                </button>
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
                        <tr v-if="salesReceiptItems.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No chargebacks found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>

        <ReceiptUploadModal
            v-model="showUploadModal"
            :chargeback="selectedChargeback"
            @uploaded="handleUploaded"
        />
    </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, unref } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Spinner from '@/components/ui/spinner.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import ReceiptUploadModal from './components/ReceiptUploadModal.vue'
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
const showUploadModal = ref(false)
const selectedChargeback = ref(null)
const message = useMessage()
const submittingId = ref(null)
const { startEditing, viewAttachment, totals, refreshSummary } = inject('chargeBackNavigation')
const total = computed(() => ({
    label: unref(totals)?.sales_receipts?.label || 'PENDING CLAIM',
    amount: salesReceiptItems.value.reduce((sum, item) => sum + Number(item.amount || 0), 0),
    cases: salesReceiptItems.value.length,
}))

const salesReceiptItems = computed(() => items.value.filter((item) => item.sales_receipt_document_id && !item.submitted))

const canUploadReceipt = (item) => {
    return can('charge-back', 'upload-receipt') && !item.submitted && !item.marked_as_received
}

const canEdit = (item) => {
    return can('charge-back', 'update') && !item.submitted && !item.marked_as_received
}

const canSubmit = (item) => {
    return can('charge-back', 'submit') && !item.submitted && !item.marked_as_received
}

const handleEdit = (item) => {
    startEditing(item)
}

const openUploadModal = (item) => {
    selectedChargeback.value = item
    showUploadModal.value = true
}

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


const handleSubmit = async (item) => {
    const confirmed = confirm('Submit this chargeback to the processor?')
    if (!confirmed) {
        return
    }

    submittingId.value = item.id

    try {
        const response = await useRequest('post', `/charge-backs/${item.id}/submit`)

        if (response.saved) {
            message.success(response.message || 'Chargeback submitted to processor.')
            loadData()
            await refreshSummary()
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to submit chargeback.')
    } finally {
        submittingId.value = null
    }
}


const handleUploaded = async () => {
    await Promise.all([loadData(), refreshSummary()])
}

const exportToExcel = () => {
    exportFromBackend({
        url: '/charge-backs-export',
        filename: 'chargeback_sales_receipts_export.xlsx',
        params: {
            tab: 'sales-receipts',
            ...(filterUserId.value ? { user_id: filterUserId.value } : {}),
        },
    })
}

const statusClass = (tone) => {
    const map = {
        warning: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
        info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        danger: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
    }

    return map[tone] || map.info
}
onMounted(loadData)
</script>
