<template>
  <div class="activity-logs-index">
    <Filterable ref="filterableRef" title="Activity Logs" url="settings/activity-logs" :sortable="sortableColumns"
      :filter-groups="filterGroups" :show-search="true" @update:collection="handleCollectionUpdate">
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Action</Th>
          <Th>Module</Th>
          <Th>Index</Th>
          <Th>User</Th>
          <Th>Description</Th>
          <Th>IP Address</Th>
          <Th>Date</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td>
            <span :class="actionBadgeClass(item.action)">
              {{ formatAction(item.action) }}
            </span>
          </Td>
          <Td color="secondary">{{ formatModule(item.auditable_type) }}</Td>
          <Td color="secondary">{{ item.index_value || '-' }}</Td>
          <Td color="secondary">{{ item.user?.name || item.user?.username || 'System' }}</Td>
          <Td color="secondary" class="max-w-[240px] truncate" :title="item.description">
            {{ item.description || '-' }}
          </Td>
          <Td color="secondary">{{ item.ip_address || '-' }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td align="right" weight="medium">
            <router-link v-if="can('activity-log', 'show')" :to="`/settings/activity-logs/${item.id}`"
              class="text-blue-600 hover:text-blue-900 transition-colors" title="View">
              <SvgIcon name="eye" size="lg" />
            </router-link>
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
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'

const { can } = usePermission()
const route = useRoute()
const resource = route.meta?.resource || 'settings/activity-logs'

const { filterableRef, setData } = useIndexable(resource, 'activity-log')

const sortableColumns = [
  { value: 'created_at', label: 'Date' },
  { value: 'action', label: 'Action' },
  { value: 'auditable_type', label: 'Module' },
  { value: 'index_value', label: 'Index' },
  { value: 'user_id', label: 'User' },
]

const actionOptions = [
  { id: 'create', name: 'Create' },
  { id: 'update', name: 'Update' },
  { id: 'delete', name: 'Delete' },
  { id: 'approve', name: 'Approve' },
  { id: 'bulk_import', name: 'Bulk Import' },
  { id: 'printing', name: 'Printing' },
  { id: 'review', name: 'Review' },
]

const filterGroups = [
  {
    title: 'Activity',
    filters: [
      {
        name: 'action',
        title: 'Action',
        type: 'lookup_only',
        options: actionOptions,
      },
      {
        name: 'auditable_type',
        title: 'Module',
        type: 'string',
        placeholder: 'e.g. employee, users',
      },
      {
        name: 'index_value',
        title: 'Index',
        type: 'string',
        placeholder: 'e.g. name, date, description',
      },
      {
        name: 'description',
        title: 'Description',
        type: 'string',
        placeholder: 'Search description',
      },
      {
        name: 'user_id',
        title: 'User',
        type: 'lookup_only',
        placeholder: 'Search user',
        resource: 'users',
        column: 'name',
      },
      {
        name: 'ip_address',
        title: 'IP Address',
        type: 'string',
        placeholder: 'Enter IP address',
      },
    ],
  },
  {
    title: 'Dates',
    filters: [
      {
        name: 'created_at',
        title: 'Created At',
        type: 'datetime',
        placeholder: 'Select date',
      },
    ],
  },
  {
    title: 'JSON Data',
    filters: [
      {
        name: 'old_values',
        title: 'Old Values (JSON)',
        type: 'string',
        placeholder: 'Search text in old values JSON',
      },
      {
        name: 'new_values',
        title: 'New Values (JSON)',
        type: 'string',
        placeholder: 'Search text in new values JSON',
      },
      {
        name: 'json_field',
        title: 'JSON Field Name',
        type: 'string',
        placeholder: 'e.g. email, status, amount',
      },
      {
        name: 'json_value',
        title: 'JSON Value',
        type: 'string',
        placeholder: 'Search value in old or new JSON',
      },
      {
        name: 'json_field_value',
        title: 'JSON Field + Value',
        type: 'string',
        placeholder: 'field:value (e.g. email:john@example.com)',
      },
    ],
  },
]

const formatAction = (action) => {
  if (!action) return '-'
  return action.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatModule = (type) => {
  if (!type) return '-'
  return type.replace(/_/g, ' ').replace(/-/g, ' ')
}

const actionBadgeClass = (action) => {
  const base = 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full'
  const map = {
    create: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    update: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    delete: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    approve: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    bulk_import: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    printing: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    review: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
  }
  return `${base} ${map[action] || 'bg-gray-100 text-gray-800'}`
}

const handleCollectionUpdate = () => { }

defineExpose({ setData })
</script>
