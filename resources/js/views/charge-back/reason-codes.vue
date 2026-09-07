<template>
    <div>
        <Filterable
            ref="filterableRef"
            title="Reason Codes"
            url="charge-back-reason-codes"
            :sortable="sortableColumns"
            :filter-groups="filterGroups"
            :show-search="true"
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
                        v-if="access.includes('create')"
                        variant="primary"
                        size="sm"
                        icon-left="plus"
                        icon-size="sm"
                        @click="openCreateModal"
                    >
                        New Reason Code
                    </Button>
                </div>
            </template>

            <template #heading>
                <tr>
                    <Th>Code</Th>
                    <Th>Description</Th>
                    <!-- <Th>Created By</Th>
                    <Th>Updated By</Th> -->
                    <Th>Created At</Th>
                    <Th>Updated At</Th>
                    <Th class="text-right">Actions</Th>
                </tr>
            </template>

            <template #default="{ item }">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <Td weight="medium" color="primary">{{ item.code }}</Td>
                    <Td color="secondary" custom-class="max-w-md">
                        {{ item.description }}
                    </Td>
                    <!-- <Td color="secondary">{{ item.created_by?.name || '-' }}</Td>
                    <Td color="secondary">{{ item.updated_by?.name || '-' }}</Td> -->
                    <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
                    <Td color="secondary">{{ formatDate(item.updated_at) }}</Td>
                    <Td>
                        <div class="flex items-center justify-end gap-2">
                            <button
                                v-if="access.includes('update')"
                                type="button"
                                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                title="Edit"
                                @click="openEditModal(item)"
                            >
                                <SvgIcon name="edit" size="lg" />
                            </button>
                            <button
                                v-if="access.includes('delete')"
                                type="button"
                                class="text-red-600 hover:text-red-900 transition-colors"
                                title="Delete"
                                @click="handleDelete(item.id)"
                            >
                                <SvgIcon name="trash" size="lg" />
                            </button>
                        </div>
                    </Td>
                </tr>
            </template>
        </Filterable>

        <ReasonCodeFormModal
            v-model="showModal"
            :reason-code="selectedReasonCode"
            @saved="handleSaved"
        />
    </div>
</template>

<script setup>
import { inject, ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import ReasonCodeFormModal from './components/ReasonCodeFormModal.vue'
import { useIndexable } from '@/composables/useIndexable'
import { formatDate } from '@/utils/date'
import { useChargeBackExcelExport } from '@/composables/useChargeBackExcelExport'

const resource = 'charge-back-reason-codes'
const { isExporting, exportFromBackend } = useChargeBackExcelExport()
const { filterableRef, removeDB, access } = useIndexable(resource, 'charge-back-reason-code')
const { refreshReasonCodeLabels } = inject('chargeBackNavigation', {
    refreshReasonCodeLabels: async () => {},
})

const showModal = ref(false)
const selectedReasonCode = ref(null)

const sortableColumns = [
    { value: 'code', label: 'Code' },
    { value: 'description', label: 'Description' },
    { value: 'created_at', label: 'Created At' },
    { value: 'updated_at', label: 'Updated At' },
]

const filterGroups = [
    {
        title: 'Reason Code',
        filters: [
            {
                name: 'code',
                title: 'Code',
                type: 'text',
                placeholder: 'Enter code',
            },
            {
                name: 'description',
                title: 'Description',
                type: 'text',
                placeholder: 'Enter description',
            },
        ],
    },
    {
        title: 'Dates',
        filters: [
            {
                name: 'created_at',
                title: 'Created At',
                type: 'datetime',
                placeholder: 'Select date',
            },
            {
                name: 'updated_at',
                title: 'Updated At',
                type: 'datetime',
                placeholder: 'Select date',
            },
        ],
    },
]

const openCreateModal = () => {
    selectedReasonCode.value = null
    showModal.value = true
}

const openEditModal = (item) => {
    selectedReasonCode.value = item
    showModal.value = true
}

const refreshList = () => {
    if (filterableRef.value) {
        filterableRef.value.fetch()
    }
}

const handleSaved = async () => {
    refreshList()
    await refreshReasonCodeLabels()
}

const exportToExcel = () => {
    exportFromBackend({
        url: '/charge-back-reason-codes-export',
        filename: 'chargeback_reason_codes_export.xlsx',
        filterableRef,
    })
}

const handleDelete = async (id) => {
    const success = await removeDB(resource, id)
    if (success) {
        refreshList()
        await refreshReasonCodeLabels()
    }
}
</script>
