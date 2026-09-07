<template>
  <div v-if="show" class="bank-entry-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Bank Entry' : 'Create New Bank Entry' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input
            v-model="form.date"
            type="date"
            label="Date"
            :required="true"
            :error="errors.date ? errors.date[0] : null"
          />

          <DynamicDropdown
            label="Company"
            v-model="form.company"
            resource="companies"
            display-name="name"
            :required="true"
            :error="errors.company_id ? errors.company_id[0] : null"
            placeholder="Select company"
          />

          <DynamicDropdown
            label="Bank"
            v-model="form.bank_item"
            resource="banks"
            display-name="name"
            :required="true"
            :error="errors.bank ? errors.bank[0] : null"
            placeholder="Select bank ledger"
          />

          <Input :model-value="formattedTotal" label="Total Amount" readonly />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold !mb-0">Entry Items</h6>
          </div>

          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="text-left px-4 py-3 w-16">No</th>
                <th class="text-left px-4 py-3">Ledger</th>
                <th class="text-left px-4 py-3">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in form.items"
                :key="`item-row-${index}`"
                class="border-t border-gray-100 dark:border-gray-700"
              >
                <td class="px-4 py-3">{{ index + 1 }}</td>
                <td class="px-4 py-3 min-w-[220px]">
                  <DynamicDropdown
                    v-model="item.ledger"
                    disabled
                    resource="ledgers"
                    :params="{ type: 'pj' }"
                    display-name="name"
                    placeholder="Select ledger"
                    @change="(value) => onLedgerChange(index, value)"
                  />
                </td>
                <td class="px-4 py-3 min-w-[160px]">
                  <Input
                    v-model="item.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    :required="true"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button
            variant="primary"
            size="md"
            type="submit"
            :loading="isSaving"
            v-if="mode === 'create' ? access.includes('create') : access.includes('update')"
          >
            {{ mode === 'edit' ? 'Update Bank Entry' : 'Create Bank Entry' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/bank-entries'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'data-entry/bank-entries',
  'bank-entry'
)

const createEmptyItem = () => ({
  ledger_id: null,
  ledger: null,
  amount: ''
})

const ensureFormShape = () => {
  if (!form.value) return

  const currentItems = Array.isArray(form.value.items) ? form.value.items : []
  const normalizedItems = currentItems.map((item) => ({
    ...createEmptyItem(),
    ...item
  }))

  if (!Array.isArray(form.value.items) || normalizedItems.length !== currentItems.length) {
    form.value.items = normalizedItems
  }

  if (!form.value.company && form.value.company_id) {
    form.value.company = null
  }

  if (!form.value.bank_item && form.value.bank_ledger) {
    form.value.bank_item = form.value.bank_ledger
  }
}

watch(
  () => form.value,
  () => {
    ensureFormShape()
  },
  { immediate: true }
)

const totalAmount = computed(() => {
  return (form.value.items || []).reduce((sum, item) => {
    if (!item?.ledger?.id && !item?.ledger_id) return sum
    return sum + Number(item.amount || 0)
  }, 0)
})

const formattedTotal = computed(() => `$${formatAmount(totalAmount.value)}`)

watch(totalAmount, (value) => {
  form.value.total_amount = Number(value.toFixed(2))
})

const formatAmount = (value) => Number(value || 0).toFixed(2)

const onLedgerChange = (index, value) => {
  const row = form.value.items[index]
  row.ledger = value || null
  row.ledger_id = value?.id || null
}

const handleSave = async () => {
  const items = (form.value.items || [])
    .filter((item) => item?.ledger?.id || item?.ledger_id)
    .map((item) => ({
      ledger_id: item.ledger?.id || item.ledger_id,
      amount: Number(item.amount || 0)
    }))

  const payload = {
    company_id: form.value.company?.id || form.value.company_id,
    date: form.value.date,
    bank: form.value.bank_item?.id || form.value.bank || null,
    total_amount: Number(totalAmount.value.toFixed(2)),
    items
  }

  await save(payload)
}

defineExpose({
  setData
})
</script>
