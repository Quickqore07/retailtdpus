<template>
  <div class="royalty-fees-index">
    <Filterable ref="filterableRef" title="Royalty Fees" url="data-entry/royalty-fees" :sortable="sortableColumns"
      :filter-groups="filterGroups">
      <template #extra>
        <Button v-if="access.includes('create')" icon-left="upload" icon-size="sm" variant="secondary" size="sm"
          @click="showImportModal = true">
          PDF Import
        </Button>
        <Button v-if="access.includes('create')" icon-left="upload" icon-size="sm" variant="secondary" size="sm"
          @click="showExcelImportModal = true">
          Excel Import
        </Button>
        <Button v-if="access.includes('index')" icon-left="download" icon-size="sm" variant="secondary" size="sm"
          @click="downloadExport" :disabled="isDownloading">
          {{ isDownloading ? 'Downloading...' : 'Export' }}
        </Button>
        <Button v-if="access.includes('create')" icon-left="plus" icon-size="sm" variant="primary" size="sm"
          to="/data-entry/royalty-fees/create">
          New Royalty Fee
        </Button>
      </template>

      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Type</Th>
          <Th>Company</Th>
          <Th>Invoice Number</Th>
          <Th>Invoice Date</Th>
          <Th>Due Date</Th>
          <Th>Amount</Th>
          <Th>Invoice PDF</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td>{{ index + 1 }}</Td>
          <Td>{{ item.type || '-' }}</Td>
          <Td>{{ item.company?.name || '-' }}</Td>
          <Td>{{ item.invoice_number || '-' }}</Td>
          <Td>{{ formatDate(item.invoice_date) }}</Td>
          <Td>{{ formatDate(item.due_date) }}</Td>
          <Td>${{ formatAmount(item.amount) }}</Td>
          <Td>
            <a v-if="item.invoice_pdf_url" :href="item.invoice_pdf_url" target="_blank" rel="noopener noreferrer"
              class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
              View PDF
            </a>
            <span v-else>-</span>
          </Td>
          <Td>{{ formatDate(item.created_at) }}</Td>
          <Td align="right">
            <div class="flex items-center justify-end gap-2">
              <router-link v-if="access.includes('show')" :to="`/data-entry/royalty-fees/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors" title="View">
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link v-if="access.includes('update')" :to="`/data-entry/royalty-fees/${item.id}/edit`"
                class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                <SvgIcon name="edit" size="lg" />
              </router-link>
              <button v-if="access.includes('delete')" @click="handleDelete(item.id)"
                class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                <SvgIcon name="trash" size="lg" />
              </button>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>

    <FileUploadModal v-model="showImportModal" title="Import Royalty Fee PDFs" size="lg" :allow-multiple="true"
      :max-files="14" :upload-url="`/${resource}/import-pdf`" :timeout="600000" accepted-formats=".pdf,application/pdf"
      :accepted-extensions="['pdf']" :accepted-mime-types="['application/pdf']" format-text="PDF"
      :instructions="importInstructions" @upload-success="handleImportSuccess" @upload-error="handleImportError" />

    <FileUploadModal v-model="showExcelImportModal" title="Import Royalty Fees from Excel" size="lg"
      :upload-url="`/${resource}/import-excel`"
      accepted-formats=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
      :accepted-extensions="['xlsx', 'xls', 'csv']" format-text="XLSX, XLS, CSV" :instructions="excelImportInstructions"
      :templates="[{ name: 'royalty-fees-template.xlsx', label: 'Royalty Fees Template' }]"
      :allow-download-template="access.includes('create')" @upload-success="handleExcelImportSuccess"
      @upload-error="handleImportError">
    </FileUploadModal>

    <MessageModal v-model="showMessagesModal" title="Import Messages" :messages="importMessages" />
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
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import MessageModal from '@/components/common/MessageModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'
import { useRequest } from '@/services/api'

const resource = 'data-entry/royalty-fees'
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'royalty-fees')
const message = useMessage()
const isDownloading = ref(false)
const showImportModal = ref(false)
const showExcelImportModal = ref(false)
const showMessagesModal = ref(false)
const importMessages = ref([])

const importInstructions = [
  'Upload up to 14 invoice PDFs at once',
  'Type is set from Purchase Order No: Roy… = Royalty, Mktg… = Advertisement',
  'If Purchase Order is missing, Marketing fund = Advertisement and Royalties = Royalty',
  UPLOAD_MAX_SIZE_NOTE,
]

const excelImportInstructions = [
  'Use the same columns as the exported file (Created At is ignored)',
  'Required: Type, Company, Invoice Date, Due Date, Amount',
  'Optional: Invoice Number, Description',
  'Company can be store number or "store number - company name"',
  'Type must be Advertisement or Royalty',
  UPLOAD_MAX_SIZE_NOTE,
]

const feeTypeOptions = [
  { id: 'Advertisement', name: 'Advertisement' },
  { id: 'Royalty', name: 'Royalty' },
]

const sortableColumns = [
  { value: 'royalty_fees.created_at', label: 'Created At' },
  { value: 'royalty_fees.type', label: 'Type' },
  { value: 'company.store_number', label: 'Company' },
  { value: 'royalty_fees.invoice_number', label: 'Invoice Number' },
  { value: 'royalty_fees.invoice_date', label: 'Invoice Date' },
  { value: 'royalty_fees.due_date', label: 'Due Date' },
  { value: 'royalty_fees.amount', label: 'Amount' },
]

const filterGroups = [
  {
    title: 'Royalty Fee Filters',
    filters: [
      {
        name: 'type',
        title: 'Type',
        type: 'lookup_only',
        options: feeTypeOptions,
        placeholder: 'Select type'
      },
      {
        name: 'company_id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company'
      },
      {
        name: 'invoice_number',
        title: 'Invoice Number',
        type: 'text',
        placeholder: 'Enter invoice number'
      },
      {
        name: 'invoice_date',
        title: 'Invoice Date',
        type: 'datetime',
        placeholder: 'Select invoice date'
      },
      {
        name: 'due_date',
        title: 'Due Date',
        type: 'datetime',
        placeholder: 'Select due date'
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

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    const response = await useRequest('get', '/data-entry/royalty-fees-export', null, {
      params: currentParams,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'royalty_fees_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Royalty fees exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export royalty fees')
  } finally {
    isDownloading.value = false
  }
}

const handleImportSuccess = (response) => {
  if (response?.messages?.length) {
    importMessages.value = response.messages
    showMessagesModal.value = true
  }
  message.success(response?.message || 'PDF imported successfully')
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const handleExcelImportSuccess = (response) => {
  if (response?.messages?.length) {
    importMessages.value = response.messages
    showMessagesModal.value = true
  }
  message.success(response?.message || 'Excel imported successfully')
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const handleImportError = (error) => {
  const data = error.response?.data
  if (data?.messages?.length) {
    importMessages.value = data.messages
    showMessagesModal.value = true
  }
}

defineExpose({
  setData
})
</script>
