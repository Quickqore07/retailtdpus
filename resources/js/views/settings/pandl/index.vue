<template>
  <div class="pandl-index">
    <Filterable
      ref="filterableRef"
      title="P&L Configuration"
      url="settings/pandl"
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
          to="/settings/pandl/create"
        >
          New P&L Configuration
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Label</Th>
          <Th>Income Items</Th>
          <Th>COGS Items</Th>
          <Th>Expense Items</Th>
          <Th>Created At</Th>
          <Th>Updated At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">{{ item.label || 'N/A' }}</Td>
          <Td color="secondary">{{ getItemCount(item.details, 'Income') }}</Td>
          <Td color="secondary">{{ getItemCount(item.details, 'COGS') }}</Td>
          <Td color="secondary">{{ getItemCount(item.details, 'Expense') }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td color="secondary">{{ formatDate(item.updated_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/pandl/${item.id}`"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/pandl/${item.id}/edit`"
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

const { filterableRef, access, removeDB } = useIndexable('settings/pandl', 'pandl')

const sortableColumns = [
  { value: 'label', label: 'Label' },
  { value: 'created_at', label: 'Created At' },
  { value: 'updated_at', label: 'Updated At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'label',
        title: 'Label',
        type: 'text',
        placeholder: 'Enter label'
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

const getItemCount = (details, type) => {
  if (!details || !Array.isArray(details)) return 0
  return details.filter(d => d.type === type).length
}

const handleDelete = async (id) => {
  const success = await removeDB('settings/pandl', id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}
</script>

<style scoped>
.pandl-index {
  padding: 1rem;
}
</style>
