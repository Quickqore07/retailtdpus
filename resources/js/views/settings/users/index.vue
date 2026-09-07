<template>
  <div class="users-index">

    <Filterable
      ref="filterableRef"
      title="Users"
      url="settings/users"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      :show-search="true"
      @update:collection="handleCollectionUpdate"
    >
    <template #extra>
      <Button 
        v-if="can('user', 'create')"
        icon-left="plus" 
        icon-size="sm" 
        variant="primary" 
        size="sm" 
        to="/settings/users/create"
      >
        New User
      </Button>
    </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Name</Th>
          <Th>Username</Th>
          <Th>Email</Th>
          <Th>Phone</Th>
          <Th>Role</Th>
          <Th align="center">Companies</Th>
          <Th v-if="isAdmin">User Code</Th>
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
            {{ item.name }}
          </Td>
          <Td color="secondary">
            {{ item.username }}
          </Td>
          <Td color="secondary">
            {{ item.email }}
          </Td>
          <Td color="secondary">
            {{ item.phone || '-' }}
          </Td>
          <Td color="secondary">
            <span
              v-if="item.role"
              class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
            >
              {{ item.role.name || item.role.title || '-' }}
            </span>
            <span v-else class="text-gray-400">-</span>
          </Td>
          <Td align="center" color="secondary">
            {{renderCount(item)}}
          </Td>
          <Td v-if="isAdmin" color="secondary">
            {{ item.user_code }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="can('user', 'show')"
                :to="`/settings/users/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="can('user', 'update')"
                :to="`/settings/users/${item.id}/edit`"
                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                title="Edit"
              >
                <SvgIcon name="edit" size="lg" />
              </router-link>
              <button
                v-if="can('user', 'delete') && item.deletable"
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
import { usePermission } from '@/composables/usePermission'
import { useAuthStore } from '@/stores/auth'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'
import { computed, render } from 'vue'

// Permission check
const { can } = usePermission()
const authStore = useAuthStore()
const isAdmin = computed(() => authStore.user?.role?.name?.toLowerCase() === 'admin')

const route = useRoute()
const resource = route.meta?.resource || 'users'

// Use the useIndexable composable
const { filterableRef, setData, removeDB } = useIndexable(resource)

// Sortable columns configuration
const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
  { value: 'username', label: 'Username' },
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Phone' },
  { value: 'companies_count', label: 'Companies' },

]

// Filter groups configuration
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
        name: 'username',
        title: 'Username',
        type: 'string',
        placeholder: 'Enter username'
      },
      {
        name: 'email',
        title: 'Email',
        type: 'string',
        placeholder: 'Enter email'
      },
      {
        name: 'phone',
        title: 'Phone',
        type: 'string',
        placeholder: 'Enter phone number'
      },
      {
        name: 'role_id',
        title: 'Role',
        type: 'lookup_only',
        placeholder: 'Enter role name',
        resource: 'roles',
        column: 'name'
      },
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

// Handle collection update from filterable component
const handleCollectionUpdate = (collection) => {
  
}

// Handle delete action
const handleDelete = async (id) => {
  await removeDB(resource, id)
  // Refresh the filterable component after deletion
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
}
const renderCount = computed(()=>{
  return (item)=>{
      if(item.role?.name){
         return item.role?.name.toLowerCase() == 'admin' || item.role?.name.toLowerCase() == 'superadmin'  ? 'All' : item.companies_count ?? 0
      }
      return 0
  }
})

// Expose setData for useIndexable route guards
defineExpose({
  setData
})
</script>

