<template>
  <div v-if="show" class="pandl-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit P&L Configuration' : 'Create P&L Configuration' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Input
            v-model="form.label"
            label="Configuration Label"
            placeholder="Enter configuration label"
            :required="true"
            :error="errors.label ? errors.label[0] : null"
          />
        </div>

        <div class="space-y-6">
          <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
            <h6 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Income Items</h6>
            <div class="space-y-4 flex flex-col gap-4">
              <div
                v-for="(item, index) in form.income_items"
                :key="`income-${index}`"
                class="border border-gray-100 dark:border-gray-600 rounded-md p-4 bg-gray-50 dark:bg-gray-800"
              >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                  <Input
                    v-model="item.label"
                    :label="`Income Label ${index + 1}`"
                    placeholder="e.g., Gross Sales"
                    :required="true"
                    :error="errors[`income_items.${index}.label`] ? errors[`income_items.${index}.label`][0] : null"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ledger Codes</label>
                  <div class="flex flex flex-wrap gap-2">
                    <div
                      v-for="(ledger, ledgerIndex) in item.ledgers"
                      :key="`income-ledger-${index}-${ledgerIndex}`"
                      class="flex items-center gap-2"
                    >
                      <Input
                        v-model="item.ledgers[ledgerIndex]"
                        placeholder="Enter ledger code"
                        :error="errors[`income_items.${index}.ledgers.${ledgerIndex}`] ? errors[`income_items.${index}.ledgers.${ledgerIndex}`][0] : null"
                      />
                      <Button
                        v-if="item.ledgers.length > 1"
                        variant="outline-danger"
                        size="sm"
                        @click="removeLedger(form.income_items[index].ledgers, ledgerIndex)"
                        type="button"
                      >
                        <SvgIcon name="trash" size="sm" class="text-red-500" />
                      </Button>
                    </div>
                  </div>

                  <Button
                    type="button"
                    variant="outline-primary"
                    size="sm"
                    @click="addLedger(form.income_items[index].ledgers)"
                  >
                    Add Ledger Code
                  </Button>
                </div>
                <div class="mt-3 flex justify-end">
                  <Button
                    v-if="form.income_items.length > 1"
                    variant="outline-danger"
                    size="sm"
                    @click="removeItem(form.income_items, index)"
                    type="button"
                  >
                    Remove Income Item
                  </Button>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <Button type="button" variant="primary" size="sm" @click="addItem(form.income_items)">
                Add Income Item
              </Button>
            </div>
          </div>

          <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
            <h6 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">COGS Items</h6>
            <div class="space-y-4">
              <div
                v-for="(item, index) in form.cogs_items"
                :key="`cogs-${index}`"
                class="border border-gray-100 dark:border-gray-600 rounded-md p-4 bg-gray-50 dark:bg-gray-800"
              >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                  <DynamicDropdown
                    v-model="item.cogs_type"
                    :label="`COGS Type ${index + 1}`"
                    :custom-options="cogsTypeOptions"
                    display-name="name"
                    placeholder="Select COGS Type"
                    :error="errors[`cogs_items.${index}.cogs_type`] ? errors[`cogs_items.${index}.cogs_type`][0] : null"
                  />
                  <Input
                    v-model="item.label"
                    :label="`COGS Label ${index + 1}`"
                    placeholder="e.g., Food Cost"
                    :required="true"
                    :error="errors[`cogs_items.${index}.label`] ? errors[`cogs_items.${index}.label`][0] : null"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ledger Codes</label>
                  <div class="flex flex flex-wrap gap-2">
                    <div
                      v-for="(ledger, ledgerIndex) in item.ledgers"
                      :key="`cogs-ledger-${index}-${ledgerIndex}`"
                      class="flex items-center gap-2"
                    >
                      <Input
                        v-model="item.ledgers[ledgerIndex]"
                        placeholder="Enter ledger code"
                        :error="errors[`cogs_items.${index}.ledgers.${ledgerIndex}`] ? errors[`cogs_items.${index}.ledgers.${ledgerIndex}`][0] : null"
                      />
                      <Button
                        v-if="item.ledgers.length > 1"
                        variant="outline-danger"
                        size="sm"
                        @click="removeLedger(form.cogs_items[index].ledgers, ledgerIndex)"
                        type="button"
                      >
                        <SvgIcon name="trash" size="sm" class="text-red-500" />
                      </Button>
                    </div>
                  </div>

                  <Button
                    type="button"
                    variant="outline-primary"
                    size="sm"
                    @click="addLedger(form.cogs_items[index].ledgers)"
                  >
                    Add Ledger Code
                  </Button>
                </div>
                <div class="mt-3 flex justify-end">
                  <Button
                    v-if="form.cogs_items.length > 1"
                    variant="outline-danger"
                    size="sm"
                    @click="removeItem(form.cogs_items, index, 'cogs')"
                    type="button"
                  >
                    Remove COGS Item
                  </Button>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <Button type="button" variant="primary" size="sm" @click="addItem(form.cogs_items, 'cogs')">
                Add COGS Item
              </Button>
            </div>
          </div>

          <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
            <h6 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Expense Items</h6>
            <div class="space-y-4">
              <div
                v-for="(item, index) in form.expense_items"
                :key="`expense-${index}`"
                class="border border-gray-100 dark:border-gray-600 rounded-md p-4 bg-gray-50 dark:bg-gray-800"
              >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                  <Input
                    v-model="item.label"
                    :label="`Expense Label ${index + 1}`"
                    placeholder="e.g., Operating Expenses"
                    :required="true"
                    :error="errors[`expense_items.${index}.label`] ? errors[`expense_items.${index}.label`][0] : null"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ledger Codes</label>

                  <div class="flex flex flex-wrap gap-2">

                    <div
                      v-for="(ledger, ledgerIndex) in item.ledgers"
                      :key="`expense-ledger-${index}-${ledgerIndex}`"
                      class="flex items-center gap-2"
                    >
                      <Input
                        v-model="item.ledgers[ledgerIndex]"
                        placeholder="Enter ledger code"
                        :error="errors[`expense_items.${index}.ledgers.${ledgerIndex}`] ? errors[`expense_items.${index}.ledgers.${ledgerIndex}`][0] : null"
                      />
                      <Button
                        v-if="item.ledgers.length > 1"
                        variant="outline-danger"
                        size="sm"
                        @click="removeLedger(form.expense_items[index].ledgers, ledgerIndex)"
                        type="button"
                      >
                        <SvgIcon name="trash" size="sm" class="text-red-500" />
                      </Button>
                    </div>
                  </div>

                  <Button
                    type="button"
                    variant="outline-primary"
                    size="sm"
                    @click="addLedger(form.expense_items[index].ledgers)"
                  >
                    Add Ledger Code
                  </Button>
                </div>
                <div class="mt-3 flex justify-end">
                  <Button
                    v-if="form.expense_items.length > 1"
                    variant="outline-danger"
                    size="sm"
                    @click="removeItem(form.expense_items, index)"
                    type="button"
                  >
                    Remove Expense Item
                  </Button>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <Button type="button" variant="primary" size="sm" @click="addItem(form.expense_items)">
                Add Expense Item
              </Button>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
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
            {{ mode === 'edit' ? 'Update P&L Configuration' : 'Create P&L Configuration' }}
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
import { watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/pandl'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'settings/pandl',
  'pandl'
)

const cogsTypeOptions = [
  { id: 'Food Purchase', name: 'Food Purchase' },
  { id: 'Labor Cost', name: 'Labor Cost' },
  { id: 'Franchise fee', name: 'Franchise fee' },
  { id: 'Retail', name: 'Retail' }
]

const ensureArrays = () => {
  if (!form.value?.income_items?.length) {
    form.value.income_items = [{ ledgers: [''], label: '' }]
  }
  if (!form.value?.cogs_items?.length) {
    form.value.cogs_items = [{ ledgers: [''], cogs_type: null, label: '' }]
  }
  if (!form.value?.expense_items?.length) {
    form.value.expense_items = [{ ledgers: [''], label: '' }]
  }
}

watch(
  () => form.value,
  () => ensureArrays(),
  { immediate: true, deep: true }
)

onMounted(() => {
  ensureArrays()
})

const addItem = (itemsArray, type = null) => {
  if (type === 'cogs') {
    itemsArray.push({ ledgers: [''], cogs_type: null, label: '' })
  } else {
    itemsArray.push({ ledgers: [''], label: '' })
  }
}

const removeItem = (itemsArray, index, type = null) => {
  itemsArray.splice(index, 1)
  if (itemsArray.length === 0) {
    if (type === 'cogs') {
      itemsArray.push({ ledgers: [''], cogs_type: null, label: '' })
    } else {
      itemsArray.push({ ledgers: [''], label: '' })
    }
  }
}

const addLedger = (ledgersArray) => {
  ledgersArray.push('')
}

const removeLedger = (ledgersArray, index) => {
  ledgersArray.splice(index, 1)
  if (ledgersArray.length === 0) {
    ledgersArray.push('')
  }
}

const handleSave = () => {
  const payload = {
    label: form.value.label,
    income_items: (form.value.income_items || []).map((item) => ({
      ledgers: item.ledgers || [''],
      label: item.label || ''
    })),
    cogs_items: (form.value.cogs_items || []).map((item) => ({
      ledgers: item.ledgers || [''],
      cogs_type: item.cogs_type?.name || item.cogs_type || '',
      label: item.label || ''
    })),
    expense_items: (form.value.expense_items || []).map((item) => ({
      ledgers: item.ledgers || [''],
      label: item.label || ''
    }))
  }
  save(payload)
}

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .pandl-form {
    padding: 1rem;
  }
}
</style>
