<template>
  <div class="food-purchases-index">
    <Filterable
      ref="filterableRef"
      title="Food Purchases"
      url="data-entry/food-purchases"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
    >
      <template #extra>
        <Button
          v-if="access.includes('index')"
          icon-left="download"
          icon-size="sm"
          variant="secondary"
          size="sm"
          @click="downloadExport"
          :disabled="isDownloading"
        >
          {{ isDownloading ? 'Downloading...' : 'Export' }}
        </Button>
        <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/data-entry/food-purchases/create"
        >
          New Food Purchase
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Companies</Th>
          <Th>Total Amount</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td weight="medium" color="primary">{{ item.items_count || 0 }}</Td>
          <Td weight="medium" color="primary">${{ formatAmount(totalAmount(item)) }}</Td>
          <Td color="secondary">{{ formatDate(item.created_at) }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/data-entry/food-purchases/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/data-entry/food-purchases/${item.id}/edit`"
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
import { ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { useRequest } from '@/services/api'

const { filterableRef, setData, removeDB, access } = useIndexable('data-entry/food-purchases', 'food-purchase')
const message = useMessage()
const isDownloading = ref(false)

const sortableColumns = [
  { value: 'date', label: 'Date' },
  { value: 'total_amount', label: 'Total Amount' },
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
        name: 'total_amount',
        title: 'Total Amount',
        type: 'text',
        placeholder: 'Enter amount'
      }
    ]
  }
]

const handleCollectionUpdate = (collection) => {
  collection.data.forEach(item => {
    item.items_count = new Set(item.items.map(item => item.company_id)).size
  })
}

const formatAmount = (value) => {
  const n = Number(value || 0)
  return n.toFixed(2)
}

const totalAmount = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.total_amount || 0), 0)
}

const handleDelete = async (id) => {
  const success = await removeDB('data-entry/food-purchases', id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/data-entry/food-purchases-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'food_purchases_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Food purchases exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export food purchases')
  } finally {
    isDownloading.value = false
  }
}

defineExpose({
  setData
})
</script>
