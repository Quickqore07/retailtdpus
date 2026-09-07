<template>
    <div>
        <!-- <header class="mb-6 sm:mb-8">
            <h1 class="!text-xl sm:!text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Chargebacks
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
                Browse and manage all chargeback cases across your stores.
            </p>
        </header> -->

        <Filterable
            ref="filterableRef"
            title="Chargebacks"
            url="/charge-backs"
            :sortable="sortableColumns"
            :filter-groups="filterGroups"
            :extra-params="extraParams"
        >
            <template #extra>
                <div class="flex items-center gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        icon-left="download"
                        icon-size="sm"
                        :loading="isExporting"
                        @click="exportToExcel"
                    >
                        Export to Excel
                    </Button>
                    <Button
                        v-if="can('charge-back', 'create')"
                        variant="primary"
                        size="sm"
                        icon-left="plus"
                        icon-size="sm"
                        @click="goToEnterTab"
                    >
                        New Chargeback
                    </Button>
                </div>
            </template>

            <template #extra-controls>
                <div
                    v-if="can('charge-back', 'regional-director') || can('charge-back', 'area-manager')"
                    class="flex flex-wrap items-end gap-2"
                >
                    <div v-if="can('charge-back', 'regional-director')" class="min-w-[220px]">
                        <DynamicDropdown
                            v-model="filters.regionalDirector"
                            :resource="`users?role=Regional Director`"
                            display-name="name"
                            placeholder="Select Regional Director"
                            icon-left="user"
                            label="Regional Director"
                            @change="onRegionalDirectorChange"
                        />
                    </div>
                    <div v-if="can('charge-back', 'area-manager')" class="min-w-[220px]">
                        <DynamicDropdown
                            v-model="filters.areaManager"
                            :resource="`users?role=Area Manager`"
                            display-name="name"
                            placeholder="Select Area Manager"
                            icon-left="user"
                            label="Area Manager"
                            @change="onAreaManagerChange"
                        />
                    </div>
                </div>
            </template>

            <template #heading>
                <tr>
                    <Th>Case #</Th>
                    <Th>Store</Th>
                    <Th>Reason</Th>
                    <Th>Amount</Th>
                    <Th>Card</Th>
                    <Th>Received</Th>
                    <Th>Due</Th>
                    <Th>Sales Receipt</Th>
                    <!-- <Th>Receipt Uploaded By</Th>
                    <Th>Submitted By</Th> -->
                    <Th>Status</Th>
                    <Th class="text-right">Actions</Th>
                </tr>
            </template>


            <template #default="{ item }">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <Td>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ item.case_number || '-' }}
                        </div>
                        <div v-if="item.reference_number" class="text-xs text-gray-500 dark:text-gray-400">
                            {{ item.reference_number }}
                        </div>
                    </Td>
                    <Td color="secondary">{{ item.company?.name || '-' }}</Td>
                    <Td color="secondary" custom-class="max-w-xs truncate">
                        <span v-if="item.reason_code" class="text-xs leading-snug truncate">
                            {{ item.reason_code }} - {{ reasonLabel(item.reason_code) }}
                        </span>
                        <span v-else>-</span>
                    </Td>
                    <Td weight="medium" color="primary">{{ formatCurrency(item.amount) }}</Td>
                    <Td color="secondary">
                        <span v-if="item.card_network || item.card_last_four">
                            {{ item.card_network || '-' }} **{{ item.card_last_four || '----' }}
                        </span>
                        <span v-else>-</span>
                    </Td>
                        <Td color="secondary">{{ formatDate(item.chargeback_received_date) }}</Td>
                    <Td color="secondary">{{ formatDate(item.processor_due_date) }}</Td>
                    <Td>
                        <div class="flex flex-col gap-2">

                            <span
                                v-if=" item.sales_receipt_document"
                                class="text-xs text-gray-500 dark:text-gray-400 flex flex-col items-center gap-1"
                            >
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(item.upload_sales_receipt_date) }}
                                </span>
                               <small :class="receiptStatusClass(item)" >{{ renderStatus(item) }}</small>
                            </span>
                        </div>
                    </Td>
                    <!-- <Td color="secondary">{{ item.sales_receipt_uploaded_by?.name || '-' }}</Td>
                    <Td color="secondary">{{ item.submitted_by?.name || '-' }}</Td> -->
                    <Td>
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
                        <div class="flex items-center justify-end gap-1 w-full">
                           
                            <!-- <span
                                v-else
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                Locked
                            </span> -->
                            
                            <Button
                                v-if="canSubmit(item)"
                                variant="primary"
                                size="xs"
                                icon-left="check"
                                icon-size="sm"
                                custom-class="w-full text-xs !px-1 !py-0.5"
                                :loading="submittingId === item.id"
                                @click="handleSubmit(item)"
                            >
                                Submit
                            </Button>
                            <Button
                                v-if="canUploadReceipt(item) && !item.sales_receipt_document_id"
                                variant="success"
                                size="xs"
                                icon-left="upload"
                                icon-size="sm"
                                custom-class="w-full text-xs !px-2 !py-1"
                                @click="openUploadModal(item)"
                            >
                                Upload receipt
                            </Button>
                            <IconMenuDropdown title="Attachments">
                                <template #default="{ close }">
                                    <button
                                        v-if="canUploadReceipt(item) && item.sales_receipt_document_id"
                                        type="button"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                        role="menuitem"
                                        @click="openUploadModal(item); close()"
                                    >
                                        <SvgIcon name="upload" size="sm" />
                                        Re-upload receipt
                                    </button>
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
                    </Td>
                </tr>
            </template>
        </Filterable>

        <ReceiptUploadModal
            v-model="showUploadModal"
            :chargeback="selectedChargeback"
            @uploaded="handleReceiptUploaded"
        />
    </div>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import ReceiptUploadModal from './components/ReceiptUploadModal.vue'
import { formatCurrency } from '@/utils/number'
import { formatDate } from '@/utils/date'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import { useChargeBackExcelExport } from '@/composables/useChargeBackExcelExport'

const { can } = usePermission()
const { isExporting, exportFromBackend } = useChargeBackExcelExport()
const message = useMessage()
const filterableRef = ref(null)
const showUploadModal = ref(false)
const selectedChargeback = ref(null)
const submittingId = ref(null)
const { switchTab, startEditing, viewAttachment, refreshSummary, reasonCodeLabels } = inject('chargeBackNavigation')

const filters = ref({
    regionalDirector: null,
    areaManager: null,
})

const extraParams = computed(() => {
    const params = {}
    const userId = filters.value.regionalDirector?.id || filters.value.areaManager?.id

    if (userId) {
        params.user_id = userId
    }

    return params
})

const onRegionalDirectorChange = () => {
    filters.value.areaManager = null
}

const onAreaManagerChange = () => {
    filters.value.regionalDirector = null
}

const sortableColumns = [
    { value: 'chargebacks.chargeback_received_date', label: 'Received Date' },
    { value: 'chargebacks.processor_due_date', label: 'Due Date' },
    { value: 'chargebacks.case_number', label: 'Case Number' },
    { value: 'company.store_number', label: 'Store' },
    { value: 'chargebacks.amount', label: 'Amount' },
    { value: 'chargebacks.chargeback_received_date', label: 'Received Date' },
    { value: 'chargebacks.submitted', label: 'Submitted' },
    { value: 'chargebacks.marked_as_received', label: 'Credited' },
    { value: 'chargebacks.created_at', label: 'Created At' },
]

const filterGroups = [
    {
        title: 'Chargeback',
        filters: [
            {
                name: 'company_id',
                title: 'Company',
                type: 'lookup_only',
                resource: 'companies',
                column: 'name',
                placeholder: 'Select company',
            },
            {
                name: 'case_number',
                title: 'Case Number',
                type: 'text',
                placeholder: 'Enter case number',
            },
            {
                name: 'reference_number',
                title: 'Reference Number',
                type: 'text',
                placeholder: 'Enter reference number',
            },
            {
                name: 'processor_due_date',
                title: 'Processor Due Date',
                type: 'datetime',
                placeholder: 'Select due date',
            },
            {
                name: 'submitted',
                title: 'Submitted',
                type: 'dropdown',
                options: [
                    { value: '1', label: 'Yes' },
                    { value: '0', label: 'No' },
                ],
                placeholder: 'Select submitted status',
            },
            {
                name: 'marked_as_received',
                title: 'Credited',
                type: 'dropdown',
                options: [
                    { value: '1', label: 'Yes' },
                    { value: '0', label: 'No' },
                ],
                placeholder: 'Select credited status',
            },
        ],
    },
]

const reasonLabel = (code) => reasonCodeLabels?.value?.[code] || code

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

const canUploadReceipt = (item) => {
    return can('charge-back', 'upload-receipt') && !item.submitted && !item.marked_as_received
}

const canSubmit = (item) => {
    return can('charge-back', 'submit') && !!item.sales_receipt_document_id
        && !item.submitted
        && !item.marked_as_received
}

const canEdit = (item) => {
    return !item.submitted && !item.marked_as_received
}

const openUploadModal = (item) => {
    selectedChargeback.value = item
    showUploadModal.value = true
}

const handleEdit = (item) => {
    startEditing(item)
}

const goToEnterTab = () => {
    switchTab('enter')
}

const exportToExcel = () => {
    exportFromBackend({
        url: '/charge-backs-export',
        filename: 'chargebacks_export.xlsx',
        params: { tab: 'chargebacks' },
        filterableRef,
        extraParams: extraParams.value,
    })
}

const refreshList = () => {
    if (filterableRef.value) {
        filterableRef.value.fetch()
    }
}

const handleReceiptUploaded = async () => {
    refreshList()
    await refreshSummary()
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
            refreshList()
            await refreshSummary()
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to submit chargeback.')
    } finally {
        submittingId.value = null
    }
}

const renderStatus = (item) => {
    if(item.upload_sales_receipt_date && new Date(item.upload_sales_receipt_date) > new Date(item.processor_due_date)) {
        return 'after due date'
    }else if(item.upload_sales_receipt_date) {
        return 'Uploaded on time'
    }
}

const receiptStatusClass = (item) => {
    if(item.upload_sales_receipt_date && new Date(item.upload_sales_receipt_date) > new Date(item.processor_due_date)) {
        return 'text-red-500'
    }else if(item.upload_sales_receipt_date) {
        return 'text-green-500'
    }
}
</script>
