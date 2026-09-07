<template>
  <div class="bank-rules-index">
    <Filterable
      ref="filterableRef"
      title="Bank Rules"
      url="settings/bank-rules"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
      :show-search="true"
    >
      <template #extra>
        <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/settings/bank-rules/create"
        >
          New Bank Rule
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Name</Th>
          <Th>Ledger</Th>
          <Th>Condition</Th>
          <Th>Created At</Th>
          <Th>Updated At</Th>
          <!-- <Th>Created By</Th>
          <Th>Updated By</Th> -->
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">{{ item.name || 'N/A' }}</Td>
          <Td color="secondary">{{ item.ledger?.ledger_details?.code ? `${item.ledger?.ledger_details?.code} - ${item.ledger?.name}` : item.ledger?.name ?? 'Both' }}</Td>
          <Td color="secondary">{{ item.condition || 'N/A' }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td color="secondary">{{ formatDate(item.updated_at) }}</Td>
          <!-- <Td color="secondary">{{ item.created_by?.name ?? 'N/A' }}</Td>
          <Td color="secondary">{{ item.updated_by?.name ?? 'N/A' }}</Td> -->
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/bank-rules/${item.id}`"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/bank-rules/${item.id}/edit`"
                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                title="Edit"
              >
                <SvgIcon name="edit" size="lg" />
              </router-link>
              <button
                v-if="access.includes('delete')"
                @click="handleDelete(item.id)"
                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                title="Delete"
              >
                <SvgIcon name="trash" size="lg" />
              </button>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
  </div>
</template>

<script setup>
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useIndexable } from '@/composables/useIndexable'

const { filterableRef, access, removeDB } = useIndexable('settings/bank-rules', 'bank-rule')

const sortableColumns = [
  { value: 'name', label: 'Name' },
  { value: 'ledger_id', label: 'Ledger' },
  { value: 'condition', label: 'Condition' },
  { value: 'created_at', label: 'Created At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'name',
        title: 'Name',
        type: 'text',
        placeholder: 'Enter name'
      },
      {
        name: 'condition',
        title: 'Condition',
        type: 'select',
        options: [
          { value: 'All', label: 'All' },
          { value: 'some', label: 'Some' }
        ]
      }
    ]
  }
]

const handleCollectionUpdate = () => {}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const handleDelete = async (id) => {
  const success = await removeDB('settings/bank-rules', id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}
</script>

<style scoped>
.bank-rules-index {
  padding: 1rem;
}
</style>
