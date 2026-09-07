<template>
  <div class="other-daily-sales-index">
    <Filterable
      ref="filterableRef"
      title="Daily Sales"
      url="data-entry/daily-sales"
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
          to="/data-entry/daily-sales/create"
        >
          New Daily Sale
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Company</Th>
          <Th>Date</Th>
          <Th>Total</Th>
          <Th>Sales</Th>
          <Th>Tax</Th>
          <Th>Cash</Th>
          <Th>Total Cash Reconciliation</Th>
          <Th>Cash Due</Th>
          <Th>Bank Deposits</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">{{ item.company?.name }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.total) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.sales) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.tax) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.cash) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.total_payment) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.cash_due) }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(item.bank_deposits) }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/data-entry/daily-sales/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/data-entry/daily-sales/${item.id}/edit`"
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

const resource = 'data-entry/daily-sales'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'daily-sales')

const sortableColumns = [
  { value: 'date', label: 'Date' },
  { value: 'sales', label: 'Sales' },
  { value: 'tax', label: 'Tax' },
  { value: 'total', label: 'Total' },
  { value: 'cash', label: 'Cash' },
  { value: 'total_payment', label: 'Total Cash Reconciliation' },
  { value: 'cash_due', label: 'Cash Due' },
  { value: 'created_at', label: 'Created At' },
]

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'date',
        title: 'Date',
        type: 'datetime',
        placeholder: 'Select date',
      },
      {
        name: 'sales',
        title: 'Sales',
        type: 'numeric',
        placeholder: 'Enter sales',
      },
      {
        name: 'total',
        title: 'Total',
        type: 'numeric',
        placeholder: 'Enter total',
      },
      {
        name: 'total_payment',
        title: 'Total Cash Reconciliation',
        type: 'numeric',
        placeholder: 'Enter total payment',
      },
      {
        name: 'cash_due',
        title: 'Cash Due',
        type: 'numeric',
        placeholder: 'Enter cash due',
      },
    ],
  },
]

const formatAmount = (value) => {
  const amount = Number(value || 0)
  return amount.toFixed(2)
}

const handleCollectionUpdate = () => {}

const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

defineExpose({ setData })
</script>
