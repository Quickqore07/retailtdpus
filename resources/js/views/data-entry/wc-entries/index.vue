<template>
  <div class="wc-entries-index">
    <Filterable
      ref="filterableRef"
      title="WC Entries"
      url="data-entry/wc-entries"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
    >
      <template #extra>
        <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/data-entry/wc-entries/create"
        >
          New WC Entry
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Year</Th>
          <Th>Period</Th>
          <Th>Company</Th>
          <Th>Driver Pay</Th>
          <Th>Non Driver Pay</Th>
          <Th>Total Pay</Th>
          <Th>Created At</Th>
          <Th>Updated At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td>{{ index + 1 }}</Td>
          <Td>{{ item.year }}</Td>
          <Td>{{ formatDate(item.eow) }}</Td>
          <Td>{{ item.company?.name || '-' }}</Td>
          <Td>${{ formatAmount(item.driver_pay) }}</Td>
          <Td>${{ formatAmount(item.non_driver_pay) }}</Td>
          <Td>${{ formatAmount(item.total_pay) }}</Td>
          <Td>{{ formatDate(item.created_at) }}</Td>
          <Td>{{ formatDate(item.updated_at) }}</Td>
          <Td align="right">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/data-entry/wc-entries/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/data-entry/wc-entries/${item.id}/edit`"
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
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useIndexable } from '@/composables/useIndexable'
import { formatDate } from '@/utils/date'

const resource = 'data-entry/wc-entries'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'wc-entry')

const sortableColumns = [
  { value: 'wc_entries.created_at', label: 'Created At' },
  { value: 'wc_entries.eow', label: 'EOW' },
  { value: 'wc_entries.year', label: 'Year' },
  { value: 'company.store_number', label: 'Company' },
  { value: 'wc_entries.total_pay', label: 'Total Pay' },
]

const filterGroups = [
  {
    title: 'WC Entry Filters',
    filters: [
      {
        name: 'year',
        title: 'Year',
        type: 'text',
        placeholder: 'Enter year'
      },
      {
        name: 'eow',
        title: 'End of Week',
        type: 'datetime',
        placeholder: 'Select date'
      },
      {
        name: 'company_id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company'
      }
    ]
  }
]

const formatAmount = (value) => Number(value || 0).toFixed(2)


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
