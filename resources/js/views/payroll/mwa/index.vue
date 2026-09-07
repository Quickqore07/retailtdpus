<template>
  <div class="mwa-index">
    <Filterable
      ref="filterableRef"
      title="Minimum Wage Adjustments (MWA)"
      url="payroll/mwa"
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
          @click="handleCreate"
        >
          Create MWA Entry
        </Button>
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>EOW</Th>
          <Th>Total Entries</Th>
          <Th>Total Amount</Th>
          <Th>Created At</Th>
          <Th>Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td>{{ index + 1 }}</Td>
          <Td>{{ formatDate(item.eow) }}</Td>
          <Td>{{ item.total_entries || 0 }}</Td>
          <Td>{{ formatCurrency(item.total_amount) }}</Td>
          <Td>{{ formatDate(item.created_at) }}</Td>
          <Td>
            <Button
              v-if="access.includes('show')"
              icon-left="eye"
              icon-size="sm"
              variant="primary"
              size="sm"
              @click="handleView(item)"
            >
              View
            </Button>
          </Td>
        </tr>
      </template>
    </Filterable>

    <MwaDataModal
      v-model="showMwaDataModal"
      :loading="isMwaDataLoading"
      :rows="mwaRows"
      :selected-record="selectedRecord"
      :total-entries="totalEntries"
      :total-amount="totalAmount"
      :access="access"
      @refresh="handleView(selectedRecord)"
    />

    <MwaFormModal
      v-model="showMwaFormModal"
      @saved="handleFormSaved"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import MwaDataModal from './mwa-data-modal.vue'
import MwaFormModal from './mwa-form-modal.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { useRequest } from '@/services/api'

const resource = 'payroll/mwa'
const { filterableRef, setData, access } = useIndexable(resource, 'mwa')
const message = useMessage()
const showMwaDataModal = ref(false)
const showMwaFormModal = ref(false)
const isMwaDataLoading = ref(false)
const mwaRows = ref([])
const selectedRecord = ref(null)
const totalEntries = ref(0)
const totalAmount = ref(0)
const sortableColumns = [
  { value: 'eow', label: 'EOW' },
  { value: 'total_entries', label: 'Total Entries' },
  { value: 'total_amount', label: 'Total Amount' },
  { value: 'created_at', label: 'Created At' }
]

const filterGroups = [
  {
    title: 'MWA Filters',
    filters: [
      {
        name: 'eow',
        title: 'End of Week',
        type: 'date',
        placeholder: 'Select EOW'
      },
      {
        name: 'created_at',
        title: 'Created At',
        type: 'datetime',
        placeholder: 'Select Created At'
      },
      {
        name: 'total_entries',
        title: 'Total Entries',
        type: 'text',
        placeholder: 'Enter Total Entries'
      },
      {
        name: 'total_amount',
        title: 'Total Amount',
        type: 'text',
        placeholder: 'Enter Total Amount'
      }
    ]
  }
]

const handleView = async (item) => {
  if (!item?.eow) {
    message.error('Unable to load MWA data.')
    return
  }

  selectedRecord.value = item
  showMwaDataModal.value = true
  isMwaDataLoading.value = true

  try {
    const response = await useRequest('post', `/${resource}/entries`, {
      eow: item.eow
    })
    mwaRows.value = response.data || []
    totalEntries.value = response.total_entries || 0
    totalAmount.value = response.total_amount || 0
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load MWA entries')
    mwaRows.value = []
  } finally {
    isMwaDataLoading.value = false
  }
}

const handleCreate = () => {
  showMwaFormModal.value = true
}

const handleFormSaved = () => {
  if (filterableRef.value) {
    filterableRef.value.fetchData()
  }
}

defineExpose({
  setData
})
</script>
