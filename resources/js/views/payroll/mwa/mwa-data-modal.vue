<template>
  <Modal
    :model-value="modelValue"
    title="MWA Entries"
    size="5xl"
    :show-footer="false"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="space-y-4">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-4">
          <div class="text-sm text-gray-600 dark:text-gray-300">
            <span class="font-medium">EOW:</span> {{ selectedRecord?.eow ? formatDate(selectedRecord.eow) : '-' }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-300">
            <span class="font-medium">Total Entries:</span> {{ totalEntries }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-300">
            <span class="font-medium">Total Amount:</span> {{ formatCurrency(totalAmount) }}
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Input
            v-model="search"
            type="text"
            placeholder="Filter entries..."
            class="md:max-w-sm"
          />
          <Button
            v-if="access.includes('create')"
            icon-left="plus"
            icon-size="sm"
            variant="primary"
            size="sm"
            @click="handleAdd"
          >
            Add
          </Button>
        </div>
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto max-h-[60vh]">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10">
            <tr>
              <Th>No</Th>
              <Th>Employee</Th>
              <Th>Company</Th>
              <Th>Role</Th>
              <Th>Amount</Th>
              <Th>Created At</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <Td colspan="7" class="text-center py-6">Loading MWA entries...</Td>
            </tr>
            <tr v-else-if="filteredRows.length === 0">
              <Td colspan="7" class="text-center py-6">No MWA entries found.</Td>
            </tr>
            <tr
              v-for="(row, index) in filteredRows"
              :key="row.id"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td>{{ index + 1 }}</Td>
              <Td>
                <div class="flex flex-col">
                  <span class="font-medium">{{ row.employee?.pos_name || 'N/A' }}</span>
                  <span class="text-xs text-gray-500">{{ row.employee?.employee_id || '' }}</span>
                </div>
              </Td>
              <Td>
                <div class="flex flex-col">
                  <span class="font-medium">{{ row.company?.name || 'N/A' }}</span>
                  <span class="text-xs text-gray-500">{{ row.company?.store_number || '' }}</span>
                </div>
              </Td>
              <Td>{{ row.role?.name || 'N/A' }}</Td>
              <Td>{{ formatCurrency(row.amount) }}</Td>
              <Td>{{ formatDate(row.created_at) }}</Td>
              <Td>
                <div class="flex gap-2">
                  <Button
                    v-if="access.includes('update')"
                    icon-left="edit"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    @click="handleEdit(row)"
                  >
                  </Button>
                  <Button
                    v-if="access.includes('delete')"
                    icon-left="trash"
                    icon-size="sm"
                    variant="danger"
                    size="sm"
                    @click="handleDelete(row)"
                    :loading="deletingIds.includes(row.id)"
                  >
                  </Button>
                </div>
              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <MwaFormModal
      v-model="showFormModal"
      :title="formModalTitle"
      :mwa-entry="selectedEntry"
      :is-edit-mode="isEditMode"
      :is-view-mode="isViewMode"
      :default-eow="selectedRecord?.eow"
      @saved="handleSaved"
    />
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import MwaFormModal from './mwa-form-modal.vue'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { useMessage } from '@/composables/useMessage'
import { useRequest } from '@/services/api'

const message = useMessage()
const resource = 'payroll/mwa'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  rows: {
    type: Array,
    default: () => []
  },
  selectedRecord: {
    type: Object,
    default: null
  },
  totalEntries: {
    type: Number,
    default: 0
  },
  totalAmount: {
    type: Number,
    default: 0
  },
  access: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const search = ref('')
const debouncedSearch = ref('')
const showFormModal = ref(false)
const selectedEntry = ref(null)
const isEditMode = ref(false)
const isViewMode = ref(false)
const deletingIds = ref([])
let debounceTimer = null

const formModalTitle = computed(() => {
  if (isViewMode.value) return 'View MWA Entry'
  if (isEditMode.value) return 'Edit MWA Entry'
  return 'Add MWA Entry'
})

watch(search, (newValue) => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  
  debounceTimer = setTimeout(() => {
    debouncedSearch.value = newValue
  }, 300)
})

watch(() => props.modelValue, (value) => {
  if (!value) {
    search.value = ''
    debouncedSearch.value = ''
  }
})

const filteredRows = computed(() => {
  const keyword = debouncedSearch.value.trim().toLowerCase()
  if (!keyword) {
    return props.rows
  }

  return props.rows.filter((row) => {
    const employeeName = String(row.employee?.pos_name || '').toLowerCase()
    const employeeId = String(row.employee?.employee_id || '').toLowerCase()
    const companyName = String(row.company?.name || '').toLowerCase()
    const storeNumber = String(row.company?.store_number || '').toLowerCase()
    const roleName = String(row.role?.name || '').toLowerCase()
    const amount = String(row.amount || '').toLowerCase()

    return [
      employeeName,
      employeeId,
      companyName,
      storeNumber,
      roleName,
      amount
    ].some((value) => value.includes(keyword))
  })
})

const handleAdd = () => {
  selectedEntry.value = null
  isEditMode.value = false
  isViewMode.value = false
  showFormModal.value = true
}
const handleEdit = (row) => {
  selectedEntry.value = { ...row }
  isEditMode.value = true
  isViewMode.value = false
  showFormModal.value = true
}

const handleDelete = async (row) => {
  if (!confirm('Are you sure you want to delete this MWA entry? This action cannot be undone.')) {
    return
  }

  deletingIds.value.push(row.id)

  try {
    const response = await useRequest('delete', `/${resource}/${row.id}`)
    
    if (response.deleted) {
      message.success(response.message || 'MWA entry deleted successfully')
      emit('refresh')
    }
  } catch (error) {
    console.error(error)
    message.error(error.response?.data?.message || 'Failed to delete MWA entry')
  } finally {
    deletingIds.value = deletingIds.value.filter(id => id !== row.id)
  }
}

const handleSaved = () => {
  showFormModal.value = false
  emit('refresh')
}
</script>
