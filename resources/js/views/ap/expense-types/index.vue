<template>
  <div class="expense-types-index">
    <Filterable
      ref="filterableRef"
      title="Expense Types"
      url="ap/expense-types"
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
          to="/ap/expense-types/create"
        >
          New Expense Type
        </Button>
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Name</Th>
          <Th>Amount Label</Th>
          <Th>Other Amount Label</Th>
          <Th>Show Other Amount</Th>
          <Th>Status</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">{{ item.name }}</Td>
          <Td color="secondary">{{ item.amount_label }}</Td>
          <Td color="secondary">{{ item.other_amount_label }}</Td>
          <Td color="secondary">{{ item.show_other_amount ? 'Yes' : 'No' }}</Td>
          <Td color="secondary">
            <span
              :class="[
                'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
                item.active
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                  : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
              ]"
            >
              {{ item.active ? 'Active' : 'Inactive' }}
            </span>
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/ap/expense-types/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/ap/expense-types/${item.id}/edit`"
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
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'

const route = useRoute()
const resource = route.meta?.resource || 'ap/expense-types'

const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'expense-type')

const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      { name: 'name', title: 'Name', type: 'string', placeholder: 'Enter name' },
      { name: 'active', title: 'Status', type: 'boolean', placeholder: 'Select status' },
    ]
  }
]

const handleCollectionUpdate = () => {}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

defineExpose({ setData })
</script>
