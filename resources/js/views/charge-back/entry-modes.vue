<template>
    <div>
        <Filterable
            ref="filterableRef"
            title="Entry Modes"
            url="charge-back-entry-modes"
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
                        New Entry Mode
                    </Button>
                </div>
            </template>

            <template #heading>
                <tr>
                    <Th>Name</Th>
                    <Th>Created At</Th>
                    <Th>Updated At</Th>
                    <Th class="text-right">Actions</Th>
                </tr>
            </template>

            <template #default="{ item }">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <Td weight="medium" color="primary">{{ item.name }}</Td>
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

        <EntryModeFormModal
            v-model="showModal"
            :entry-mode="selectedEntryMode"
            @saved="handleSaved"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import EntryModeFormModal from './components/EntryModeFormModal.vue'
import { useIndexable } from '@/composables/useIndexable'
import { formatDate } from '@/utils/date'
import { useChargeBackExcelExport } from '@/composables/useChargeBackExcelExport'

const resource = 'charge-back-entry-modes'
const { isExporting, exportFromBackend } = useChargeBackExcelExport()
const { filterableRef, removeDB, access } = useIndexable(resource, 'charge-back-entry-mode')

const showModal = ref(false)
const selectedEntryMode = ref(null)

const sortableColumns = [
    { value: 'name', label: 'Name' },
    { value: 'created_at', label: 'Created At' },
    { value: 'updated_at', label: 'Updated At' },
]

const filterGroups = [
    {
        title: 'Entry Mode',
        filters: [
            {
                name: 'name',
                title: 'Name',
                type: 'text',
                placeholder: 'Enter name',
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
    selectedEntryMode.value = null
    showModal.value = true
}

const openEditModal = (item) => {
    selectedEntryMode.value = item
    showModal.value = true
}

const refreshList = () => {
    if (filterableRef.value) {
        filterableRef.value.fetch()
    }
}

const handleSaved = () => {
    refreshList()
}

const exportToExcel = () => {
    exportFromBackend({
        url: '/charge-back-entry-modes-export',
        filename: 'chargeback_entry_modes_export.xlsx',
        filterableRef,
    })
}

const handleDelete = async (id) => {
    const success = await removeDB(resource, id)
    if (success) {
        refreshList()
    }
}
</script>
