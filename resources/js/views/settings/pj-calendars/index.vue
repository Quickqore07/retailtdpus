<template>
  <div class="pj-calendars-index">
    <Filterable
      ref="filterableRef"
      title="PJ Calendars"
      url="settings/pj-calendars"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
    >
      <template #extra>
        <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/settings/pj-calendars/create"
        >
          New PJ Calendar
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Year</Th>
          <Th>Labels</Th>
          <Th>Total Weeks</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">{{ item.year }}</Td>
          <Td color="secondary">{{ item.items?.length || 0 }}</Td>
          <Td color="secondary">{{ totalWeeks(item) }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/pj-calendars/${item.id}`"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/pj-calendars/${item.id}/edit`"
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
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { useIndexable } from '@/composables/useIndexable'

const message = useMessage()
const { filterableRef, access } = useIndexable('pj-calendars', 'pj-calendar')

const sortableColumns = [
  { value: 'year', label: 'Year' },
  { value: 'created_at', label: 'Created At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'year',
        title: 'Year',
        type: 'text'
      }
    ]
  }
]

const handleCollectionUpdate = () => {}

const totalWeeks = (item) => {
  if (!item.items?.length) return 0
  return item.items.reduce((sum, row) => sum + (row.weeks?.length || 0), 0)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const handleDelete = async (id) => {
  if (!confirm('Are you sure you want to delete this PJ calendar?')) {
    return
  }

  try {
    const response = await useRequest('delete', `settings/pj-calendars/${id}`)

    if (response.deleted) {
      message.success(response.message || 'PJ calendar deleted successfully')
      filterableRef.value?.refresh()
    } else {
      message.error(response.message || 'Failed to delete PJ calendar')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'An error occurred while deleting')
  }
}
</script>

<style scoped>
.pj-calendars-index {
  padding: 1rem;
}
</style>
