<template>
  <div class="fund-requirements-index">
    <Filterable
      ref="filterableRef"
      title="Fund Requirements"
      url="settings/fund-requirements"
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
          to="/settings/fund-requirements/create"
        >
          New Fund Requirement
        </Button>
      </template>
      
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Label</Th>
          <Th>Type</Th>
          <Th>Condition Type</Th>
          <Th>Condition Value</Th>
          <Th>Amount</Th>
          <Th>Status</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td weight="medium" color="primary">
            {{ item.label }}
          </Td>
          <Td color="secondary">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="item.type === 'fixed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'">
              {{ renderType(item.type) }}
            </span>
          </Td>
          <Td color="secondary">
            {{ renderConditionType(item.condition_type) }}
          </Td>
          <Td color="secondary">
            {{ renderConditionValue(item.condition_type, item.condition_value) }}
          </Td>
          <Td weight="medium" color="primary">
            ${{ formatCurrency(getTotalAmount(item)) }}
          </Td>
          <Td>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="item.active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'">
              {{ item.active ? 'Active' : 'Inactive' }}
            </span>
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/fund-requirements/${item.id}`"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/fund-requirements/${item.id}/edit`"
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
const { filterableRef, access } = useIndexable('fund-requirements', 'fund-requirement')

const sortableColumns = [
  { value: 'label', label: 'Label' },
  { value: 'type', label: 'Type' },
  { value: 'condition_type', label: 'Condition Type' },
  { value: 'condition_value', label: 'Condition Value' },
  { value: 'amount', label: 'Amount' },
  { value: 'created_at', label: 'Created At' }
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
      },
      {
        name: 'type',
        title: 'Type',
        type: 'select',
        options: [
          { id: 'fixed', name: 'Fixed' },
          { id: 'company wise', name: 'Company Wise' }
        ]
      },
      {
        name: 'condition_type',
        title: 'Condition Type',
        type: 'select',
        options: [
          { id: 'monthly', name: 'Monthly' },
          { id: 'weekly', name: 'Weekly' },
          { id: 'bi-weekly', name: 'Bi-Weekly' }
        ]
      },
      {
        name: 'active',
        title: 'Status',
        type: 'select',
        options: [
          { id: '1', name: 'Active' },
          { id: '0', name: 'Inactive' }
        ]
      }
    ]
  }
]

const handleCollectionUpdate = (collection) => {
  // Handle collection updates if needed
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (value) => {
  if (!value) return '0.00'
  return parseFloat(value).toFixed(2)
}

const getTotalAmount = (item) => {
  if (item.type === 'fixed') {
    return item.amount || 0
  }
  // Sum company amounts
  return item.companies?.reduce((sum, company) => sum + parseFloat(company.amount || 0), 0) || 0
}

const handleDelete = async (id) => {
  if (!confirm('Are you sure you want to delete this fund requirement?')) {
    return
  }

  try {
    const response = await useRequest('delete', `settings/fund-requirements/${id}`)
    if (response.deleted) {
      message.success(response.message || 'Fund requirement deleted successfully')
      filterableRef.value?.fetch()
    } else {
      message.error(response.message || 'Failed to delete fund requirement')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'An error occurred while deleting')
  }
}

const renderConditionType = (conditionType) => {
  return conditionType === 'monthly' ? 'Monthly' : conditionType === 'weekly' ? 'Weekly' : conditionType === 'bi-weekly' ? 'Bi-Weekly' : 'N/A'
}

const renderConditionValue = (conditionType, conditionValue) => {
  if (conditionType === 'monthly') {
    return new Date(conditionValue).toLocaleDateString('en-US', {
      day: 'numeric'
    })
  } else {
    return new Date(conditionValue).toLocaleDateString('en-US', {
      weekday: 'long'
    })
  } 
  return 'N/A'

}

const renderType = (type) => {
  return type === 'fixed' ? 'Fixed' : 'Company Wise'
}
</script>

<style scoped>
.fund-requirements-index {
  padding: 1rem;
}
</style>
