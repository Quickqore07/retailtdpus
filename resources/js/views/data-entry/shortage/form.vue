<template>
  <div v-if="show" class="shortage-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Manage Shortages</h5>
          <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/shortages" />
        </div>
      </template>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
        <Label label="Daily Sale Date" :value="formatDate(model?.daily_sale?.date)" />
        <Label label="Cash Bag"  >
          <span class="cursor-pointer !text-blue-500 !dark:text-blue-400" @click="clickCashBag">{{ formatCurrency(model?.daily_sale?.cash_bag) }}</span>
        </Label>
        <Label label="Total Deposits" :value="`$${formatAmount(model?.daily_sale?.bank_deposit_total)}`" />
        <Label label="Total Shortages" :value="`$${formatAmount(model?.daily_sale?.shortage_total)}`" />
        <Label label="Remaining" :value="`$${formatAmount(model?.daily_sale?.remaining_amount)}`" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4 mb-6" v-if="model?.daily_sale?.remaining_amount > 0">
        <h6 class="font-semibold mb-4">Enter Shortage</h6>
        <form @submit.prevent="createShortage" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input v-model="createForm.date" type="date" label="Date" :required="true" />
          <Input v-model="createForm.amount" type="number" min="0" step="0.01" label="Amount" :required="true" />
          <Input v-model="createForm.notes" type="text" label="Note" placeholder="Optional note" />
          <div class="flex items-end">
            <Button
              type="submit"
              variant="primary"
              icon-left="plus"
              :disabled="!access.includes('create')"
              :loading="isSaving"
            >
              Add Shortage
            </Button>
          </div>
        </form>
        <p v-if="formError" class="text-sm text-red-600 dark:text-red-400 mt-3">
          {{ formError }}
        </p>
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h6 class="font-semibold !mb-0">Shortages List</h6>
        </div>
        <table class="text-sm w-full">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th class="text-left px-4 py-3">No</Th>
              <Th class="text-left px-4 py-3">Date</Th>
              <Th class="text-right px-4 py-3">Amount</Th>
              <Th class="text-left px-4 py-3">Note</Th>
              <Th class="text-left px-4 py-3">Created By / Time</Th>
              <Th class="text-left px-4 py-3">Updated By / Time</Th>
              <Th class="text-right px-4 py-3">Action</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!shortages.length">
              <Td colspan="7" class="px-4 py-6 text-center text-gray-500">No shortages found.</Td>
            </tr>
            <tr
              v-for="(row, index) in shortages"
              :key="row.id || index"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td class="px-4 py-3">{{ index + 1 }}</Td>
              <Td class="px-4 py-3">{{ formatDate(row.date) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(row.amount) }}</Td>
              <Td class="px-4 py-3">{{ row.notes || '-' }}</Td>
              <Td class="px-4 py-3">
                <div>{{ row.created_by?.name || row.createdBy?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ formatDateTime(row.created_at) }}</div>
              </Td>
              <Td class="px-4 py-3">
                <div>{{ row.updated_by?.name || row.updatedBy?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ formatDateTime(row.updated_at) }}</div>
              </Td>
              <Td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">

                <Button
                  v-if="access.includes('update')"
                  size="xs"
                  variant="outline-primary"
                  icon-left="edit"
                  @click="openEditModal(row)"
                >
                  Edit
                </Button>
                <Button
                  v-if="access.includes('delete')"
                  size="xs"
                  variant="outline-danger"
                  icon-left="trash"
                  @click="deleteShortage(row.id)"
                >
                  Delete
                </Button>
              </div>

              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[320px]">
    <Spinner size="md" text="Loading shortages..." centered />
  </div>

  <Modal v-model="isEditModalOpen" title="Update Shortage" size="md" :show-footer="false">
    <form @submit.prevent="updateShortage" class="space-y-4">
      <Input v-model="editForm.date" type="date" label="Date" :required="true" />
      <Input v-model="editForm.amount" type="number" min="0" step="0.01" label="Amount" :required="true" />
      <Input v-model="editForm.notes" type="text" label="Note" placeholder="Optional note" />

      <p v-if="editFormError" class="text-sm text-red-600 dark:text-red-400">
        {{ editFormError }}
      </p>

      <div class="flex items-center justify-end gap-2 pt-2">
        <Button type="button" variant="outline-secondary" @click="isEditModalOpen = false">Cancel</Button>
        <Button type="submit" variant="primary" :loading="isUpdating">Update</Button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import Label from '@/components/ui/label.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Modal from '@/components/common/Modal.vue'
import { formatDate, formatDateTime } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
const route = useRoute()
const message = useMessage()
const { getAllowedActions } = usePermission()

const resource = 'data-entry/shortages'
const show = ref(false)
const isSaving = ref(false)
const isUpdating = ref(false)
const model = ref(null)
const formError = ref('')
const editFormError = ref('')

const createForm = ref({
  date: '',
  amount: 0,
  notes: ''
})

const isEditModalOpen = ref(false)
const editForm = ref({
  id: null,
  date: '',
  amount: 0,
  notes: ''
})

const access = computed(() => getAllowedActions('shortage'))
const shortages = computed(() => model.value?.shortages || [])
const totalShortages = computed(() =>
  shortages.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
)
const remainingAmount = computed(() => Number((model.value?.daily_sale?.remaining_amount || 0) - totalShortages.value).toFixed(2))

const formatAmount = (value) => Number(value || 0).toFixed(2)
const getModelFromResponse = (res) => res?.data?.model || res?.model || null

const setDefaultDate = () => {
  if (!createForm.value.date) {
    createForm.value.date = model.value?.daily_sale?.date || new Date().toISOString().slice(0, 10)
  }
}

const fetchShortages = async () => {
  try {
    const res = await useRequest('get', `${resource}/${route.params.id}`)
    model.value = getModelFromResponse(res)
    setDefaultDate()
    show.value = true
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load shortages')
  }
}

const clickCashBag = () => {
  window.open(`/data-entry/daily-sales/${model.value?.daily_sale?.id}`, '_blank')
}

const createShortage = async () => {
  formError.value = ''
  isSaving.value = true

  try {
    await useRequest('post', resource, {
      daily_sale_id: Number(route.params.id),
      date: createForm.value.date,
      amount: Number(createForm.value.amount || 0),
      notes: createForm.value.notes
    })
    message.success('Shortage added successfully')
    createForm.value.amount = 0
    createForm.value.notes = ''
    await fetchShortages()
  } catch (error) {
    formError.value = error.response?.data?.message || 'Failed to add shortage'
  } finally {
    isSaving.value = false
  }
}

const openEditModal = (row) => {
  editFormError.value = ''
  editForm.value = {
    id: row.id,
    date: row.date,
    amount: Number(row.amount || 0),
    notes: row.notes || ''
  }
  isEditModalOpen.value = true
}

const updateShortage = async () => {
  editFormError.value = ''
  isUpdating.value = true
  try {
    await useRequest('put', `${resource}/${route.params.id}`, {
      shortage_id: editForm.value.id,
      daily_sale_id: Number(route.params.id),
      date: editForm.value.date,
      amount: Number(editForm.value.amount || 0),
      notes: editForm.value.notes
    })
    message.success('Shortage updated successfully')
    isEditModalOpen.value = false
    await fetchShortages()
  } catch (error) {
    editFormError.value = error.response?.data?.message || 'Failed to update shortage'
  } finally {
    isUpdating.value = false
  }
}

const deleteShortage = async (id) => {
  try {
    if (confirm('Are you sure you want to delete this shortage?')) {
      await useRequest('delete', `${resource}/${id}`)
      message.success('Shortage deleted successfully')
      await fetchShortages()
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to delete shortage')
    }
}
onMounted(async () => {
  await fetchShortages()
})
</script>
