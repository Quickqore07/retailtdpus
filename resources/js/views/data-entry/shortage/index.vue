<template>
  <div class="shortage-index">
    <Filterable
      ref="filterableRef"
      title="Shortages"
      url="data-entry/shortages"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
    >
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Company</Th>
          <Th>Cash Bag</Th>
          <Th>Deposit Amount</Th>
          <Th>Shortage Amount</Th>
          <Th>Difference</Th>
          <Th>Shortage Date</Th>
          <Th>Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td weight="medium" color="primary">{{ item.company?.name }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.cash_bag) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.bank_deposit_total) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.shortage_total) }}</Td>
          <Td weight="medium" :color="differenceColor(item.difference)">${{ formatAmount(item.difference) }}</Td>
          <Td color="secondary">{{ item.shortage_date ? formatDate(item.shortage_date) : '-' }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/data-entry/shortages/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/data-entry/shortages/${item.id}/edit`"
                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                title="Edit"
              >
                <SvgIcon name="edit" size="lg" />
              </router-link>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
  </div>
</template>

<script setup>
import Filterable from '@/components/filterable/filterable.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useIndexable } from '@/composables/useIndexable'
import { formatDate } from '@/utils/date'
import SvgIcon from '@/components/SvgIcon.vue'

const resource = 'data-entry/shortages'
const { filterableRef, setData, access } = useIndexable(resource, 'shortage')

const sortableColumns = [
  { value: 'date', label: 'Date' },
  { value: 'cash_bag', label: 'Cash Bag' },
  { value: 'created_at', label: 'Created At' }
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'date',
        title: 'Date',
        type: 'datetime',
        placeholder: 'Select date'
      },
      {
        name: 'cash_bag',
        title: 'Cash Bag',
        type: 'text',
        placeholder: 'Enter cash bag'
      }
    ]
  }
]

const formatAmount = (value) => {
  const n = Number(value || 0)
  return n.toFixed(2)
}

const differenceColor = (value) => {
  const n = Number(value || 0)
  if (n > 0) return 'success'
  if (n < 0) return 'danger'
  return 'primary'
}

defineExpose({
  setData
})
</script>
