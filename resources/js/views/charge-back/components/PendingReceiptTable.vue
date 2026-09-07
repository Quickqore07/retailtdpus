<template>
    <Panel padding="none">
        <template #header>
            <div class="flex items-center justify-between gap-4 w-full">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white !mb-0">
                    Pending receipt upload
                </h4>
                <Button
                    variant="ghost"
                    size="sm"
                    icon-right="arrow-right"
                    icon-size="sm"
                    class="!text-primary dark:!text-emerald-400 shrink-0"
                    @click="viewAll"
                >
                    View all
                </Button>
            </div>
        </template>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <Th>Case #</Th>
                        <Th>Store</Th>
                        <Th>Reason</Th>
                        <Th>Amount</Th>
                        <Th>Card</Th>
                        <Th>Received</Th>
                        <Th>Due</Th>
                        <Th>Sales Receipt</Th>
                        <Th>Status</Th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    <tr
                        v-for="item in items"
                        :key="item.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                    >
                        <Td>
                            <div class="font-medium text-gray-900 dark:text-white">{{ item.case_number }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.reference_number }}</div>
                        </Td>
                        <Td color="secondary">{{ item.store_name }}</Td>
                        <Td color="secondary" custom-class="max-w-xs">
                            <span class="text-xs leading-snug">
                                {{ item.reason_code }} - {{ reasonLabel(item.reason_code) }}
                            </span>
                        </Td>
                        <Td weight="medium" color="primary">{{ formatCurrency(item.amount) }}</Td>
                        <Td color="secondary">{{ item.card_network }} **{{ item.card_last_four }}</Td>
                        <Td color="secondary">{{ item.chargeback_received_date }}</Td>
                        <Td color="secondary">{{ item.processor_due_date }}</Td>
                        <Td>
                            <Button
                                v-if="canUploadReceipt(item)"
                                variant="success"
                                size="xs"
                                icon-left="upload"
                                icon-size="sm"
                                @click="uploadReceipt(item)"
                            >
                                Upload receipt
                            </Button>
                        </Td>
                        <Td>
                            <div class="flex flex-col gap-0.5">
                                <span
                                    class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusClass(item.status_tone)"
                                >
                                    {{ item.status }}
                                </span>
                                <span v-if="item.due_label" class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ item.due_label }}
                                </span>
                            </div>
                        </Td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Panel>
</template>

<script setup>
import { inject } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import { formatCurrency } from '@/utils/number'
import { formatDate } from '@/utils/date'   
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()
const { reasonCodeLabels } = inject('chargeBackNavigation', { reasonCodeLabels: { value: {} } })
defineProps({
    items: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['view-all', 'upload-receipt'])

const reasonLabel = (code) => reasonCodeLabels?.value?.[code] || code


const statusClass = (tone) => {
    const map = {
        warning: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
        info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        danger: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    }
    return map[tone] || map.info
}

const canUploadReceipt = (item) => {
    return can('charge-back', 'upload-receipt') && !item.submitted && !item.marked_as_received
}

const viewAll = () => {
    emit('view-all')
}

const uploadReceipt = (item) => {
    emit('upload-receipt', item)
}
</script>
