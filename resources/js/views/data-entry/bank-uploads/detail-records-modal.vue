<template>
  <Modal v-model="isOpen" size="5xl" title="Bank Upload Details" @update:model-value="handleClose">
    <template #title>
      <div class="flex flex-col gap-1">
        <span class="text-lg font-semibold">Bank Upload Details</span>
        <span v-if="selectedRecord" class="text-sm font-normal text-gray-600 dark:text-gray-400">
          {{ formatDate(selectedRecord.date) }} - {{ selectedRecord.bank?.name }}
        </span>
      </div>
    </template>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else-if="records.length === 0" class="text-center py-12 text-gray-500">
        No records found
      </div>

      <div v-else>
        <div class="mb-4">
          <Input
            v-model="searchQuery"
            name="search"
            placeholder="Search by account number, account name, or description..."
          />
        </div>

        <div v-if="filteredRecords.length === 0" class="text-center py-12 text-gray-500">
          No records match your search
        </div>

        <div v-else class="overflow-x-auto max-h-[500px] overflow-y-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th>No</Th>
              <Th>Account Number</Th>
              <Th>Account Name</Th>
              <!-- <Th>Company</Th> -->
              <Th>Description</Th>
              <Th>Debit</Th>
              <Th>Credit</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="(record, index) in filteredRecords"
              :key="index"
              class="hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td>{{ index + 1 }}</Td>
              <Td>{{ record.account_number }}</Td>
              <Td>{{ record.account_name || 'N/A' }}</Td>
              <!-- <Td>{{ record.company?.name || 'N/A' }}</Td> -->
              <Td class="max-w-[300px] truncate" :title="record.description">{{ record.description || 'N/A' }}</Td>
              <Td class="text-red-600">
                {{ record.amount < 0 ? formatCurrency(Math.abs(record.amount)) : '-' }}
              </Td>
              <Td class="text-green-600">
                {{ record.amount > 0 ? formatCurrency(record.amount) : '-' }}
              </Td>
              <Td>
                <div  class="flex gap-2">
                  <Button
                    v-if="can('bank-upload', 'update')" 
                    icon-left="edit"
                    icon-size="sm"
                    variant="secondary"
                    size="sm"
                    @click="handleEditOpeningBalance(record)"
                  >
                    Edit
                  </Button>
                  <Button
                  v-if="can('bank-upload', 'delete') && record.is_opening_balance"
                    icon-left="trash"
                    icon-size="sm"
                    variant="danger"
                    size="sm"
                    @click="handleDeleteOpeningBalance(record)"
                  >
                    Delete
                  </Button>
                </div>
              </Td>
            </tr>
          </tbody>
          <tfoot v-if="filteredRecords.length > 0" class="bg-gray-50 dark:bg-gray-800 font-semibold">
            <tr>
              <Td colspan="4" class="text-right">Total:</Td>
              <Td class="text-red-600">{{ formatCurrency(totalDebit) }}</Td>
              <Td class="text-green-600">{{ formatCurrency(totalCredit) }}</Td>
              <Td></Td>
            </tr>
          </tfoot>
        </table>
        </div>
      </div>

      <div class="flex gap-2 mt-2">
        <Button 
          variant="secondary" 
          @click="handleExport"
          :disabled="loading || records.length === 0"
        >
          Export to Excel
        </Button>
        <Button variant="secondary" @click="handleClose">Close</Button>
      </div>
  </Modal>

  <Modal 
    v-model="showEditModal" 
    size="md" 
    :title="isEditingOpeningBalance ? 'Edit Opening Balance' : 'Edit Record'"
  >
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Date
        </label>
        <Input
          :model-value="editingRecord?.date"
          name="date"
          type="date"
          disabled
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Bank
        </label>
        <Input
          :model-value="editingRecord?.bank?.name || 'N/A'"
          name="bank"
          disabled
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Amount <span class="text-red-500">*</span>
        </label>
        <Input
          v-model="editAmount"
          :disabled="!isEditingOpeningBalance"
          name="amount"
          type="number"
          step="0.01"
          placeholder="Enter Amount"
          :error="editAmountError"
        />
      </div>

      <div v-if="!isEditingOpeningBalance">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Description
        </label>
        <Input
          v-model="editDescription"
          name="description"
          placeholder="Enter Description"
          :disabled="isEditingOpeningBalance"
        />
      </div>

      <div class="flex gap-2 justify-end mt-6">
        <Button variant="secondary" @click="handleCancelEdit">
          Cancel
        </Button>
        <Button 
          variant="primary" 
          @click="handleSaveEdit"
          :disabled="isSaving"
        >
          {{ isSaving ? 'Updating...' : 'Update' }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import Input from '@/components/ui/input.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  records: {
    type: Array,
    default: () => []
  },
  selectedRecord: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'refresh'])
const message = useMessage()
const searchQuery = ref('')
const showEditModal = ref(false)
const editingRecord = ref(null)
const editAmount = ref('')
const editAmountError = ref('')
const editDescription = ref('')
const isSaving = ref(false)
const { can } = usePermission()
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const filteredRecords = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.records
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  
  return props.records.filter(record => {
    const accountNumber = (record.account_number || '').toLowerCase()
    const accountName = (record.account_name || '').toLowerCase()
    const description = (record.description || '').toLowerCase()
    
    return accountNumber.includes(query) || 
           accountName.includes(query) || 
           description.includes(query)
  })
})

const totalDebit = computed(() => {
  return filteredRecords.value.reduce((sum, record) => {
    return sum + (record.amount < 0 ? Math.abs(record.amount) : 0)
  }, 0)
})

const totalCredit = computed(() => {
  return filteredRecords.value.reduce((sum, record) => {
    return sum + (record.amount > 0 ? Math.abs(record.amount) : 0)
  }, 0)
})

const handleClose = () => {
  searchQuery.value = ''
  isOpen.value = false
}

const isOpeningBalance = (record) => {
  return record.description?.toLowerCase() === 'opening balance'
}

const isEditingOpeningBalance = computed(() => {
  return isOpeningBalance(editingRecord.value || {})
})

const handleEditOpeningBalance = (record) => {
  editingRecord.value = record
  editAmount.value = record.amount
  editDescription.value = record.description || ''
  editAmountError.value = ''
  showEditModal.value = true
}

const handleCancelEdit = () => {
  showEditModal.value = false
  editingRecord.value = null
  editAmount.value = ''
  editDescription.value = ''
  editAmountError.value = ''
}

const handleSaveEdit = async () => {
  if (!editAmount.value || parseFloat(editAmount.value) === 0) {
    editAmountError.value = 'Amount is required and must not be zero'
    return
  }

  isSaving.value = true
  editAmountError.value = ''

  try {
    let response
      response = await useRequest('put', `/data-entry/bank-uploads/update/${editingRecord.value.id}`, {
        amount: editAmount.value,
        description: editDescription.value
      })
      message.success(response.message || 'Record updated successfully')

    handleCancelEdit()
    emit('refresh')
  } catch (error) {
    if (error.response?.data?.errors?.amount) {
      editAmountError.value = error.response.data.errors.amount[0]
    }
    message.error(error.response?.data?.message || 'Failed to update record')
  } finally {
    isSaving.value = false
  }
}

const handleDeleteOpeningBalance = async (record) => {
  if (!confirm('Are you sure you want to delete this record?')) {
    return
  }

  try {
    const response = await useRequest('delete', `/data-entry/bank-uploads/delete/${record.id}`)
    message.success(response.message || 'Record deleted successfully')
    emit('refresh')
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete record')
  }
}

const handleExport = () => {
  if (!props.selectedRecord || props.records.length === 0) return
  
  try {
    // Import XLSX dynamically
    import('xlsx').then((XLSX) => {
      const bankName = props.selectedRecord.bank?.name || 'Bank'
      const date = formatDate(props.selectedRecord.date)
      
      // Prepare data for Excel
      const excelData = []
      
      // Add title rows
      excelData.push(['Bank Upload Details'])
      excelData.push([`${bankName} - ${date}`])
      excelData.push([]) // Empty row
      
      // Add headers
      excelData.push(['No', 'Account Number', 'Account Name', 'Description', 'Debit', 'Credit'])
      
      // Add data rows
      props.records.forEach((record, index) => {
        const debit = record.amount < 0 ? Math.abs(record.amount) : ''
        const credit = record.amount > 0 ? record.amount : ''
        
        excelData.push([
          index + 1,
          record.account_number,
          record.account_name || 'N/A',
          record.description || 'N/A',
          debit,
          credit
        ])
      })
      
      // Add totals row
      excelData.push([
        '',
        '',
        '',
        'Total:',
        totalDebit.value,
        totalCredit.value
      ])
      
      // Create worksheet
      const ws = XLSX.utils.aoa_to_sheet(excelData)
      
      // Set column widths
      ws['!cols'] = [
        { wch: 8 },  // No
        { wch: 20 }, // Account Number
        { wch: 25 }, // Account Name
        { wch: 40 }, // Description
        { wch: 15 }, // Debit
        { wch: 15 }  // Credit
      ]
      
      // Merge title cells
      ws['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: 5 } }, // Title row
        { s: { r: 1, c: 0 }, e: { r: 1, c: 5 } }  // Subtitle row
      ]
      
      // Create workbook
      const wb = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(wb, ws, 'Bank Upload Details')
      
      // Generate file name
      const fileName = `bank-upload-${props.selectedRecord.date}-${bankName}.xlsx`
      
      // Download file
      XLSX.writeFile(wb, fileName)
    })
  } catch (error) {
    console.error('Export error:', error)
    alert('Failed to export data. Please try again.')
  }
}
</script>
