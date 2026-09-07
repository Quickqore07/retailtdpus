<template>
  <div class="company-groups-index">
    <Filterable
      ref="filterableRef"
      title="Company Groups"
      url="settings/company-groups"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
    >
      <template #extra>
        <div class="flex items-center gap-2">
          <Button
            v-if="access.includes('create')"
            icon-left="plus"
            icon-size="sm"
            variant="primary"
            size="sm"
            to="/settings/company-groups/create"
          >
            New Company Group
          </Button>
        </div>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Name</Th>
          <Th>Description</Th>
          <Th>Companies</Th>
          <Th>Status</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="semibold" color="primary">{{ item.name }}</Td>
          <Td color="secondary">{{ item.description || '-' }}</Td>
          <Td weight="medium">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
              {{ item.company_count || 0 }} companies
            </span>
          </Td>
          <Td weight="medium">
            <span 
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                item.active 
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                  : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
              ]"
            >
              {{ item.active ? 'Active' : 'Inactive' }}
            </span>
          </Td>
          <Td color="secondary">{{ formatDateTime(item.created_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/company-groups/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/company-groups/${item.id}/edit`"
                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                title="Edit"
              >
                <SvgIcon name="edit" size="lg" />
              </router-link>
              <button
                v-if="access.includes('delete')"
                @click="handleDelete(item.id)"
                class="text-red-600 hover:text-red-900 transition-colors"
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
import { ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useIndexable } from '@/composables/useIndexable'
import { formatDateTime } from '@/utils/date'

const resource = 'settings/company-groups'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'company-group')

const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
  { value: 'active', label: 'Status' },
  { value: 'updated_at', label: 'Updated At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'name',
        title: 'Group Name',
        type: 'text',
        placeholder: 'Enter group name'
      },
      {
        name: 'description',
        title: 'Description',
        type: 'text',
        placeholder: 'Enter description'
      },
      {
        name: 'active',
        title: 'Status',
        type: 'select',
        placeholder: 'Select status',
        options: [
          { value: '1', label: 'Active' },
          { value: '0', label: 'Inactive' }
        ]
      }
    ]
  }
]

const handleCollectionUpdate = (collection) => {
  // Handle any collection updates if needed
  // This follows the pattern from daily-sales
}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id, {
    confirmTitle: 'Delete Company Group',
    confirmText: 'This company group will be deleted and companies will be removed from the group. Are you sure?'
  })
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

defineExpose({
  setData
})
</script>