<template>
  <div class="ledgers-index">
    <Filterable
      ref="filterableRef"
      title="Ledgers"
      url="settings/ledgers"
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
          to="/settings/ledgers/create"
        >
          New Ledger
        </Button>
      </template>
      
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Account Type</Th>
          <Th>Code</Th>
          <Th>Name</Th>
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
            {{ item.account_type || 'N/A' }}
          </Td>
          <Td weight="medium" color="primary">
            {{ item.code || 'N/A' }}
          </Td>
          <Td weight="medium" color="primary">
            {{ item.name || 'N/A' }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/ledgers/${item.id}`"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/ledgers/${item.id}/edit`"
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

const { filterableRef, access, removeDB } = useIndexable('settings/ledgers', 'ledger')

const sortableColumns = [
  { value: 'code', label: 'Code' },
  { value: 'name', label: 'Name' },
  { value: 'account_type', label: 'Account Type' },
  { value: 'account_no', label: 'Account No.' },
  { value: 'bank_name', label: 'Bank Name' },
  { value: 'bank_address', label: 'Bank Address' },
  { value: 'created_at', label: 'Created At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'account_type',
        title: 'Account Type',
        type: 'select',
        options: [
          { value: 'General', label: 'General' },
          { value: 'Bank', label: 'Bank' }
        ]
      },
      {
        name: 'account_no',
        title: 'Account No.',
        type: 'text',
        placeholder: 'Enter account no.'
      },
      {
        name: 'bank_name',
        title: 'Bank Name',
        type: 'text',
        placeholder: 'Enter bank name'
      },
      {
        name: 'bank_address',
        title: 'Bank Address',
        type: 'text',
        placeholder: 'Enter bank address'
      },  
      {
        name: 'code',
        title: 'Code',
        type: 'text',
        placeholder: 'Enter code'
      },
      {
        name: 'name',
        title: 'Name',
        type: 'text',
        placeholder: 'Enter name'
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
  const success = await removeDB('settings/ledgers', id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}
</script>

<style scoped>
.ledgers-index {
  padding: 1rem;
}
</style>
