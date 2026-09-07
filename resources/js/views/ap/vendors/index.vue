<template>
  <div class="vendors-index">
    <Filterable
      ref="filterableRef"
      title="Vendors"
      url="ap/vendors"
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
          to="/ap/vendors/create"
        >
          New Vendor
        </Button>
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Code</Th>
          <Th>Name</Th>
          <Th>Email</Th>
          <Th>Mobile</Th>
          <Th>City</Th>
          <Th>State</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td color="secondary">
            {{ item.code || '-' }}
          </Td>
          <Td weight="medium" color="primary">
            {{ item.name }}
          </Td>
          <Td color="secondary">
            {{ item.email || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.mobile || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.city || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.state || '-' }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/ap/vendors/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/ap/vendors/${item.id}/edit`"
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
const resource = route.meta?.resource || 'ap/vendors'

const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'vendor')

const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'code', label: 'Code' },
  { value: 'name', label: 'Name' },
  { value: 'email', label: 'Email' },
  { value: 'city', label: 'City' },
  { value: 'state', label: 'State' },
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
        name: 'code',
        title: 'Code',
        type: 'string',
        placeholder: 'Enter code'
      },
      {
        name: 'email',
        title: 'Email',
        type: 'string',
        placeholder: 'Enter email'
      },
      {
        name: 'mobile',
        title: 'Mobile',
        type: 'string',
        placeholder: 'Enter mobile number'
      },
      {
        name: 'city',
        title: 'City',
        type: 'string',
        placeholder: 'Enter city'
      },
      {
        name: 'state',
        title: 'State',
        type: 'string',
        placeholder: 'Enter state'
      },
      {
        name: 'country',
        title: 'Country',
        type: 'string',
        placeholder: 'Enter country'
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
      },
      {
        name: 'updated_at',
        title: 'Updated At',
        type: 'datetime',
        placeholder: 'Select date'
      }
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

defineExpose({
  setData
})
</script>
