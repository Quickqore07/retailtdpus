<template>
  <div class="upload-companies-index">
    <Filterable
      ref="filterableRef"
      title="Upload Companies"
      url="upload/companies"
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
        to="/upload/companies/create"
      >
        New Company
      </Button>
    </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Store Number</Th>
          <Th>Name</Th>
          <Th>Workgroup</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item , index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td weight="medium" color="primary">
            {{ item.store_number }}
          </Td>
          <Td color="secondary">
            {{ item.name }}
          </Td>
          <Td color="secondary">
            {{ item.workgroup?.name || '-' }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/upload/companies/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/upload/companies/${item.id}/edit`"
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
import { formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'upload/companies'

const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'upload-company')

const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
  { value: 'store_number', label: 'Store Number' },
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'name',
        title: 'Name',
        type: 'string',
        placeholder: 'Enter name'
      },
      {
        name: 'store_number',
        title: 'Store Number',
        type: 'string',
        placeholder: 'Enter store number'
      }
    ]
  },
  {
    title: 'Dates',
    filters: [
      {
        name: 'created_at',
        title: 'Created At',
        type: 'datetime',
        placeholder: 'Select date'
      }
    ]
  }
]

const handleCollectionUpdate = (collection) => {
  
}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

defineExpose({
  setData
})
</script>
