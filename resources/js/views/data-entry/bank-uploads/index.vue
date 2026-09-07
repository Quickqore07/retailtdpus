<template>
  <div class="bank-upload-index">
    <Filterable
      ref="filterableRef"
      title="Bank Upload"
      url="data-entry/bank-uploads"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
    >
      <template #extra>
        <Button
          v-if="access.includes('opening-balance')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          @click="showOpeningBalanceModal = true"
        >
          Add Opening Balance
        </Button>
        <Button
          v-if="access.includes('upload')"
          icon-left="upload"
          icon-size="sm"
          variant="secondary"
          size="sm"
          @click="showUploadModal = true"
        >
          Import File
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Date</Th>
          <Th>Bank</Th>
          <Th>Total Debit</Th>
          <Th>Total Credit</Th>
          <Th>Transaction Count</Th>
          <Th>Upload Date</Th>
          <Th>Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td>{{ index + 1 }}</Td>
          <Td>{{ formatDate(item.date) }}</Td>
          <Td>{{ item.bank?.name || 'N/A' }}</Td>
          <Td class="text-red-600">{{ formatCurrency(item.total_debit) }}</Td>
          <Td class="text-green-600">{{ formatCurrency(item.total_credit) }}</Td>
          <Td>{{ item.transaction_count }}</Td>
          <Td>{{ formatDate(item.created_at) }}</Td>
          <Td>
            <div class="flex flex-wrap items-center gap-2">
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
              <Button
                v-if="access.includes('delete')"
                icon-left="trash"
                icon-size="sm"
                variant="danger"
                size="sm"
                @click="handleDeleteGroup(item)"
              >
                Delete
              </Button>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>

    <FileUploadModal
      v-model="showUploadModal"
      title="Import Bank Transaction Sheet"
      size="lg"
      :upload-url="`/${resource}/upload`"
      :instructions="uploadInstructions"
      format-text="CSV, XLSX, XLS"
      allow-multiple
      @upload-success="handleUploadSuccess"
      @upload-error="handleUploadError"
    />

    <MessageModal v-model="showMessagesModal" title="Messages" :messages="messages" />
    
    <DetailRecordsModal
      v-model="showDetailModal"
      :loading="isDetailLoading"
      :records="detailRecords"
      :selected-record="selectedRecord"
      @refresh="handleRefreshDetailRecords"
    />

    <Modal v-model="showOpeningBalanceModal" size="md" title="Add Opening Balance">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Date <span class="text-red-500">*</span>
          </label>
          <Input
            v-model="openingBalance.date"
            name="date"
            type="date"
            placeholder="Select Date"
            :error="openingBalanceErrors.date"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Bank <span class="text-red-500">*</span>
          </label>
          <DynamicDropdown
            v-model="openingBalance.bank"
            name="bank_id"
            placeholder="Select Bank"
            resource="banks"
            :params="{ type: 'bank' }"
            display-name="ledger_name"
            :error="openingBalanceErrors.bank_id"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Amount <span class="text-red-500">*</span>
          </label>
          <Input
            v-model="openingBalance.amount"
            name="amount"
            type="number"
            step="0.01"
            placeholder="Enter Amount"
            :error="openingBalanceErrors.amount"
          />
        </div>

        <div class="flex gap-2 justify-end mt-6">
          <Button variant="secondary" @click="handleCancelOpeningBalance">
            Cancel
          </Button>
          <Button 
            variant="primary" 
            @click="handleSaveOpeningBalance"
            :disabled="isSavingOpeningBalance"
          >
            {{ isSavingOpeningBalance ? 'Saving...' : 'Save' }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Filterable from '@/components/filterable/filterable.vue'
import Button from '@/components/ui/button.vue'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import MessageModal from '@/components/common/MessageModal.vue'
import DetailRecordsModal from './detail-records-modal.vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useIndexable } from '@/composables/useIndexable'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { useRequest } from '@/services/api'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'

const resource = 'data-entry/bank-uploads'
const { filterableRef, setData, access } = useIndexable(resource, 'bank-upload')
const message = useMessage()
const showUploadModal = ref(false)
const showMessagesModal = ref(false)
const showDetailModal = ref(false)
const isDetailLoading = ref(false)
const messages = ref([])
const detailRecords = ref([])
const selectedRecord = ref(null)

const showOpeningBalanceModal = ref(false)
const isSavingOpeningBalance = ref(false)
const bankOptions = ref([])
const openingBalance = ref({
  date: '',
  bank: null,
  amount: ''
})
const openingBalanceErrors = ref({
  date: '',
  bank_id: '',
  amount: ''
})

const uploadInstructions = [
  'Required columns: Date, Account Number, Description',
  'Amount columns: Credit Amount (positive) or Debit Amount (negative) or Amount',
  'Credit amounts will be stored as positive values',
  'Debit amounts will be stored as negative values',
  'Data will be grouped by account number and description',
  'Supported formats: CSV, Excel (.xlsx, .xls)',
  UPLOAD_MAX_SIZE_NOTE,
  'After upload you will be asked to confirm the dates found in the sheet before import',
]

const sortableColumns = [
  { value: 'created_at', label: 'Upload Date' },
  { value: 'date', label: 'Transaction Date' },
  { value: 'total_debit', label: 'Total Debit' },
  { value: 'total_credit', label: 'Total Credit' },
  { value: 'transaction_count', label: 'Transaction Count' },
]

const filterGroups = [
  {
    title: 'Bank Upload Filters',
    filters: [
      {
        name: 'date',
        title: 'Transaction Date',
        type: 'datetime',
        placeholder: 'Select Transaction Date'
      },
      {
        name: 'created_at',
        title: 'Upload Date',
        type: 'datetime',
        placeholder: 'Select Upload Date'
      },
      {
        name: 'total_debit',
        title: 'Total Debit',
        type: 'text',
        placeholder: 'Enter Total Debit'
      },
      {
        name: 'total_credit',
        title: 'Total Credit',
        type: 'text',
        placeholder: 'Enter Total Credit'
      }
    ]
  }
]

const handleUploadSuccess = (response) => {
  message.success(response.message || 'File uploaded successfully')
  messages.value = response.messages || []
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
  if (messages.value.length > 0) {
    showMessagesModal.value = true
  }
}

const handleUploadError = (error) => {
  // message.error(error.response?.data?.message || 'Upload failed. Please try again.')
}

const handleDeleteGroup = async (item) => {
  if (!item?.date) {
    message.error('Unable to delete this upload group.')
    return
  }

  if (!confirm('Delete all transactions for this date and bank? This cannot be undone.')) {
    return
  }

  try {
    const response = await useRequest('post', `/${resource}/delete-group`, {
      date: item.date,
      bank_id: item.bank_id ?? 0
    })
    if (response.deleted) {
      message.success(response.message || 'Deleted successfully')
      if (filterableRef.value) {
        filterableRef.value.fetch()
      }
      const sameGroup =
        selectedRecord.value &&
        selectedRecord.value.date === item.date &&
        (selectedRecord.value.bank_id ?? 0) === (item.bank_id ?? 0)
      if (showDetailModal.value && sameGroup) {
        showDetailModal.value = false
        selectedRecord.value = null
        detailRecords.value = []
      }
    } else {
      message.error(response.message || 'Failed to delete')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete')
  }
}

const handleView = async (item) => {
  if (!item?.date) {
    message.error('Unable to load detail records.')
    return
  }

  selectedRecord.value = item
  showDetailModal.value = true
  isDetailLoading.value = true

  try {
    const response = await useRequest('post', `/${resource}/detail-records`, {
      date: item.date,
      bank_id: item.bank_id ?? 0
    })
    detailRecords.value = response.records || []
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load detail records')
    detailRecords.value = []
  } finally {
    isDetailLoading.value = false
  }
}

const handleRefreshDetailRecords = async () => {
  if (!selectedRecord.value) return
  
  isDetailLoading.value = true
  try {
    const response = await useRequest('post', `/${resource}/detail-records`, {
      date: selectedRecord.value.date,
      bank_id: selectedRecord.value.bank_id ?? 0
    })
    detailRecords.value = response.records || []
    
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to refresh detail records')
  } finally {
    isDetailLoading.value = false
  }
}

const fetchBanks = async () => {
  try {
    const response = await useRequest('get', '/settings/ledgers')
    bankOptions.value = response.collection || []
  } catch (error) {
    message.error('Failed to load banks')
  }
}

const validateOpeningBalance = () => {
  let isValid = true
  openingBalanceErrors.value = {
    date: '',
    bank_id: '',
    amount: ''
  }

  if (!openingBalance.value.date) {
    openingBalanceErrors.value.date = 'Date is required'
    isValid = false
  }

  if (!openingBalance.value.bank || !openingBalance.value.bank.id) {
    openingBalanceErrors.value.bank_id = 'Bank is required'
    isValid = false
  }

  if (!openingBalance.value.amount || parseFloat(openingBalance.value.amount) === 0) {
    openingBalanceErrors.value.amount = 'Amount is required and must not be zero'
    isValid = false
  }

  return isValid
}

const handleSaveOpeningBalance = async () => {
  if (!validateOpeningBalance()) {
    return
  }

  isSavingOpeningBalance.value = true

  try {
    const response = await useRequest('post', `/${resource}/store-opening-balance`, {
      date: openingBalance.value.date,
      bank_id: openingBalance.value.bank?.id || '',
      amount: openingBalance.value.amount
    })

    message.success(response.message || 'Opening balance saved successfully')
    handleCancelOpeningBalance()
    
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  } catch (error) {
    const errors = error.response?.data?.errors
    if (errors) {
      openingBalanceErrors.value = {
        date: errors.date?.[0] || '',
        bank_id: errors.bank_id?.[0] || '',
        amount: errors.amount?.[0] || ''
      }
    }
    message.error(error.response?.data?.message || 'Failed to save Opening Balance')
  } finally {
    isSavingOpeningBalance.value = false
  }
}

const handleCancelOpeningBalance = () => {
  showOpeningBalanceModal.value = false
  openingBalance.value = {
    date: '',
    bank: null,
    amount: ''
  }
  openingBalanceErrors.value = {
    date: '',
    bank_id: '',
    amount: ''
  }
}

onMounted(() => {
  fetchBanks()
})

defineExpose({
  setData
})
</script>
