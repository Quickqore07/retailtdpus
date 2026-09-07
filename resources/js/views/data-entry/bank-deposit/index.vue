<template>
  <div class="bank-deposit-index">
    <Filterable
      ref="filterableRef"
      title="Bank Deposits"
      url="data-entry/bank-deposits"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
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
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Company</Th>
          <Th>Cash Bag</Th>
          <Th>Deposit Amount</Th>
          <Th>Shortage Amount</Th>
          <Th>Difference</Th>
          <Th>Deposit Date</Th>
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
          <Td color="secondary">{{ item.deposite_date ? formatDate(item.deposite_date) : '-' }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/data-entry/bank-deposits/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/data-entry/bank-deposits/${item.id}/edit`"
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
import { ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import SvgIcon from '@/components/SvgIcon.vue'
import { useRequest } from '@/services/api'

const resource = 'data-entry/bank-deposits'
const { filterableRef, setData, access } = useIndexable(resource, 'bank-deposit')
const message = useMessage()
const isDownloading = ref(false)

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

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/data-entry/bank-deposits-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'bank_deposits_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Bank deposits exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export bank deposits')
  } finally {
    isDownloading.value = false
  }
}

defineExpose({
  setData
})
</script>
