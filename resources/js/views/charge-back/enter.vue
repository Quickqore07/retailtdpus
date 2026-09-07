<template>
    <div>
        <header class="mb-6 sm:mb-8">
            <h1 class="!text-xl sm:!text-2xl font-bold text-gray-900 dark:text-white mb-2">
                {{ isEditMode ? 'Edit chargeback' : 'Enter chargebacks' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
                {{ isEditMode ? 'Update this chargeback before submission.' : 'Record new chargeback cases from processor notifications.' }}
                <span class="block mt-1 text-xs">{{ documentUploadNote }}</span>
            </p>
        </header>

        <div v-if="loading" class="flex items-center justify-center min-h-[320px]">
            <Spinner size="md" text="Loading form..." centered />
        </div>

        <Panel v-else :divider="true">
            <form @submit.prevent class="space-y-6">
                <WorkgroupCompanySelect
                    v-model:workgroup="form.workgroup"
                    v-model:company="form.company"
                    :workgroup-error="errors.workgroup_id?.[0] || null"
                    :company-error="errors.company_id?.[0] || null"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <Input
                        v-model="form.case_number"
                        label="Case Number"
                        placeholder="Processor case number"
                        :error="errors.case_number?.[0] || null"
                    />

                    <Input
                        v-model="form.network_case_number"
                        label="Network Case Number"
                        placeholder="Card network case number"
                        :error="errors.network_case_number?.[0] || null"
                    />

                    <Input
                        v-model="form.reference_number"
                        label="Reference Number"
                        placeholder="Internal reference"
                        :error="errors.reference_number?.[0] || null"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Reason Code
                        </label>
                        <select
                            v-model="form.reason_code"
                            class="w-full min-h-[36px] px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
                        >
                            <option :value="null">Select reason code</option>
                            <option
                                v-for="option in options.reason_codes"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="errors.reason_code?.[0]" class="text-xs text-red-600 dark:text-red-400">
                            {{ errors.reason_code[0] }}
                        </p>
                    </div>

                    <Input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        label="Amount"
                        placeholder="0.00"
                        :required="true"
                        :error="errors.amount?.[0] || null"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Card Network
                        </label>
                        <select
                            v-model="form.card_network"
                            class="w-full min-h-[36px] px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
                        >
                            <option :value="null">Select card network</option>
                            <option
                                v-for="option in options.card_networks"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="errors.card_network?.[0]" class="text-xs text-red-600 dark:text-red-400">
                            {{ errors.card_network[0] }}
                        </p>
                    </div>

                    <Input
                        v-model="form.card_last_four"
                        label="Card Last Four"
                        placeholder="1234"
                        maxlength="4"
                        :error="errors.card_last_four?.[0] || null"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Entry Mode
                        </label>
                        <select
                            v-model="form.entry_mode"
                            class="w-full min-h-[36px] px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
                        >
                            <option :value="null">Select entry mode</option>
                            <option
                                v-for="option in options.entry_modes"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="errors.entry_mode?.[0]" class="text-xs text-red-600 dark:text-red-400">
                            {{ errors.entry_mode[0] }}
                        </p>
                    </div>

                    <Input
                        v-model="form.transaction_date"
                        type="date"
                        label="Transaction Date"
                        :error="errors.transaction_date?.[0] || null"
                    />

                    <Input
                        v-model="form.chargeback_received_date"
                        type="date"
                        label="Chargeback Received Date"
                        :error="errors.chargeback_received_date?.[0] || null"
                    />

                    <Input
                        v-model="form.processor_due_date"
                        type="date"
                        label="Processor Due Date"
                        :error="errors.processor_due_date?.[0] || null"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Notes
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
                            placeholder="Additional details about this chargeback"
                        />
                        <p v-if="errors.notes?.[0]" class="text-xs text-red-600 dark:text-red-400">
                            {{ errors.notes[0] }}
                        </p>
                    </div>

                    <DocumentFileField
                        v-model="disputeDocument"
                        label="Dispute Document"
                        placeholder="Upload dispute document"
                        :required="!isEditMode"
                        :error="errors.dispute_document?.[0] || null"
                    />
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                    <Button
                        v-if="isEditMode"
                        type="button"
                        variant="outline-secondary"
                        size="md"
                        :disabled="isSaving"
                        @click="cancelEditMode"
                    >
                        Cancel Edit
                    </Button>
                    <Button
                        type="button"
                        variant="outline-secondary"
                        size="md"
                        :disabled="isSaving"
                        @click="resetForm"
                    >
                        Reset
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        size="md"
                        :loading="isSaving"
                        :disabled="isSaving"
                        @click="handleSave('add-another')"
                    >
                        {{ isEditMode ? 'Save Changes' : 'Submit &amp; Add Another' }}
                    </Button>
                    <Button
                        v-if="!isEditMode"
                        type="button"
                        variant="success"
                        size="md"
                        :loading="isSaving"
                        :disabled="isSaving"
                        @click="handleSave('go-list')"
                    >
                        Submit &amp; Go to List
                    </Button>
                </div>
            </form>
        </Panel>

        <Panel class="mt-6 sm:mt-8" padding="none">
            <template #header>
                <div class="flex items-center justify-between gap-4 w-full">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white !mb-0">
                        Expired chargebacks
                    </h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Past due and no receipt uploaded
                    </span>
                </div>
            </template>

            <div v-if="expiredLoading" class="flex items-center justify-center min-h-[180px]">
                <Spinner size="md" text="Loading expired chargebacks..." centered />
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <Th>Case #</Th>
                            <Th>Store</Th>
                            <Th>Amount</Th>
                            <Th>Due Date</Th>
                            <Th>Status</Th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <tr
                            v-for="item in expiredItems"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                        >
                            <Td>
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.case_number || '-' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.reference_number || '-' }}</div>
                            </Td>
                            <Td color="secondary">{{ item.company?.name || '-' }}</Td>
                            <Td weight="medium" color="primary">{{ formatCurrency(item.amount) }}</Td>
                            <Td color="secondary">{{ formatDate(item.processor_due_date) }}</Td> 
                            <Td color="secondary">{{ item.display_status?.label || 'Expired' }}</Td>
                        </tr>
                        <tr v-if="expiredItems.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                No expired chargebacks found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import WorkgroupCompanySelect from '@/components/common/WorkgroupCompanySelect.vue'
import DocumentFileField from '@/components/common/DocumentFileField.vue'
import { DOCUMENT_UPLOAD_NOTE, validateDocumentFileSize } from '@/utils/documentUpload'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatCurrency } from '@/utils/number'
import { formatDate } from '@/utils/date'

const resource = 'charge-backs'
const message = useMessage()
const documentUploadNote = DOCUMENT_UPLOAD_NOTE
const {
    switchTab,
    editingChargeback,
    clearEditing,
    refreshSummary,
} = inject('chargeBackNavigation')

const loading = ref(true)
const isSaving = ref(false)
const errors = ref({})
const disputeDocument = ref(null)
const expiredLoading = ref(false)
const expiredRows = ref([])

const form = ref({
    case_number: '',
    network_case_number: '',
    reference_number: '',
    company_id: null,
    workgroup: null,
    company: null,
    reason_code: null,
    amount: '',
    card_network: null,
    card_last_four: '',
    entry_mode: null,
    transaction_date: '',
    chargeback_received_date: '',
    processor_due_date: '',
    notes: '',
})

const options = ref({
    reason_codes: [],
    card_networks: [],
    entry_modes: [],
})

const isEditMode = computed(() => !!editingChargeback?.value?.id)

watch(() => form.value.company, (company) => {
    form.value.company_id = company?.id || null
})

const expiredItems = computed(() => expiredRows.value.filter((item) => item.display_status?.key === 'expired'))

const formatIsoDate = (value) => {
    if (!value) return '-'
    return value.includes('T') ? value.split('T')[0] : value.split(' ')[0]
}

const loadForm = async () => {
    loading.value = true
    

    try {
        const response = await useRequest('get', `/${resource}/create`)
        options.value = response.options || options.value
        if (!isEditMode.value) {
            form.value = {
                ...form.value,
                ...(response.form || {}),
            }
        }
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to load chargeback form')
    } finally {
        loading.value = false
    }
}

const setFormFromChargeback = (chargeback) => {
    form.value = {
        case_number: chargeback.case_number || '',
        network_case_number: chargeback.network_case_number || '',
        reference_number: chargeback.reference_number || '',
        company_id: chargeback.company_id || null,
        workgroup: null,
        company: chargeback.company || null,
        reason_code: chargeback.reason_code || null,
        amount: chargeback.amount || '',
        card_network: chargeback.card_network || null,
        card_last_four: chargeback.card_last_four || '',
        entry_mode: chargeback.entry_mode || null,
        transaction_date: formatIsoDate(chargeback.transaction_date),
        chargeback_received_date: formatIsoDate(chargeback.chargeback_received_date),
        processor_due_date: formatIsoDate(chargeback.processor_due_date),
        notes: chargeback.notes || '',
    }
    disputeDocument.value = null
    errors.value = {}
}

const cancelEditMode = async () => {
    clearEditing()
    await resetForm()
}

const resetForm = async () => {
    errors.value = {}
    disputeDocument.value = null
    await loadForm()
}

const loadExpired = async () => {
    expiredLoading.value = true
    try {
        const response = await useRequest('get', `/${resource}`, null, {
            params: {
                limit: 1000,
                sort_column: 'chargebacks.processor_due_date',
                sort_direction: 'desc',
            },
        })
        expiredRows.value = response.collection?.data || []
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to load expired chargebacks')
    } finally {
        expiredLoading.value = false
    }
}

const handleSave = async (mode = 'add-another') => {
    isSaving.value = true
    errors.value = {}

    if (!isEditMode.value && !disputeDocument.value) {
        errors.value = { dispute_document: ['The dispute document field is required.'] }
        message.error('Please upload a dispute document.')
        isSaving.value = false
        return
    }

    if (disputeDocument.value) {
        const sizeError = validateDocumentFileSize(disputeDocument.value)
        if (sizeError) {
            errors.value = { dispute_document: [sizeError] }
            message.error(sizeError)
            isSaving.value = false
            return
        }
    }

    try {
        const payload = new FormData()

        Object.entries(form.value).forEach(([key, value]) => {
            if (['workgroup', 'company'].includes(key)) {
                return
            }

            if (value !== null && value !== '') {
                payload.append(key, value)
            }
        })

        if (disputeDocument.value) {
            payload.append('dispute_document', disputeDocument.value)
        }

        const url = isEditMode.value
            ? `/${resource}/${editingChargeback.value.id}`
            : `/${resource}`
        const method = 'post'

        if (isEditMode.value) {
            // Laravel/PHP does not reliably parse multipart payloads on native PUT.
            payload.append('_method', 'PUT')
        }
        const response = await useRequest(method, url, payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })

        if (response.saved) {
            message.success(response.message || (isEditMode.value ? 'Chargeback updated successfully.' : 'Chargeback saved successfully.'))
            await refreshSummary()
            if (isEditMode.value) {
                clearEditing()
                await loadForm()
                await loadExpired()
                switchTab('chargebacks')
                return
            }

            if (mode === 'go-list') {
                switchTab('chargebacks')
            } else {
                form.value = {
                    ...form.value,
                    ...(response.form || {}),
                }
                disputeDocument.value = null
            }
            await loadExpired()
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        }

        message.error(error.response?.data?.message || 'Failed to save chargeback')
    } finally {
        isSaving.value = false
    }
}

onMounted(async () => {
    await Promise.all([loadForm(), loadExpired()])
})

watch(
    () => editingChargeback?.value,
    (chargeback) => {
        if (chargeback?.id) {
            setFormFromChargeback(chargeback)
        }
    },
    { immediate: true },
)
</script>
