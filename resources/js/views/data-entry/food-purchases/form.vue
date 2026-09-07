<template>
  <div v-if="show" class="food-purchase-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Food Purchase' : 'Create New Food Purchase' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Input
            v-model="form.date"
            type="date"
            label="Date"
            :required="true"
            :error="errors.date ? errors.date[0] : null"
          />
          <Input
            :model-value="totals.total_amount"
            label="Total Amount"
            disabled
          />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold !mb-0">Purchase Items</h6>
            <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addItem">
              Add Row
            </Button>
          </div>
          <div class="overflow-x-auto max-h-[600px]">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 !z-[101]">
                <tr>
                  <Th class="text-left px-3 py-3 w-14">No</Th>
                  <th class="text-left px-3 py-3 min-w-[300px]">Company</th>
                  <Th class="text-left px-3 py-3 min-w-[170px]">Food Product Purchase</Th>
                  <Th class="text-left px-3 py-3 min-w-[150px]">Paper Supply</Th>
                  <Th class="text-left px-3 py-3 min-w-[170px]">Smallware Supplies</Th>
                  <Th class="text-left px-3 py-3 min-w-[170px]">Cleaning Supplies</Th>
                  <Th class="text-left px-3 py-3 min-w-[120px]">Pepsi</Th>
                  <Th class="text-left px-3 py-3 min-w-[130px]">Row Total</Th>
                  <Th class="text-left px-3 py-3 w-16">Actions</Th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in form.items"
                  :key="`item-row-${index}`"
                  class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                  <Td class="px-3 py-3">{{ index + 1 }}</Td>
                  <Td class="px-3 py-3">
                    <DynamicDropdown
                      v-model="item.company"
                      resource="companies"
                      display-name="name"
                      placeholder="Select company"
                      :required="true"
                      :error="getItemError(index, 'company_id')"
                      @change="(value) => onCompanyChange(index, value)"
                    />
                  </Td>
                    <Td class="px-3 py-3">
                    <Input
                      v-model="item.food_product_purchase"
                      type="number"
                      step="0.01"
                      min="0"
                      :error="getItemError(index, 'food_product_purchase')"
                    />
                  </Td>
                    <Td class="px-3 py-3">
                    <Input
                      v-model="item.paper_supplies"
                      type="number"
                      step="0.01"
                      min="0"
                      :error="getItemError(index, 'paper_supplies')"
                    />
                  </Td>
                  <Td class="px-3 py-3">
                    <Input
                      v-model="item.smallware_supplies"
                      type="number"
                      step="0.01"
                      min="0"
                      :error="getItemError(index, 'smallware_supplies')"
                    />
                  </Td>
                    <Td class="px-3 py-3">
                    <Input
                      v-model="item.cleaning_supplies"
                      type="number"
                      step="0.01"
                      min="0"
                      :error="getItemError(index, 'cleaning_supplies')"
                    />
                  </Td>
                  <Td class="px-3 py-3">
                    <Input
                      v-model="item.pepsi"
                      type="number"
                      step="0.01"
                      min="0"
                      :error="getItemError(index, 'pepsi')"
                    />
                  </Td>
                  <Td class="px-3 py-3">
                    <Input :model-value="formatAmount(rowTotal(item))" readonly />
                  </Td>
                  <Td class="px-3 py-3">
                    <Button
                      type="button"
                      variant="outline-danger"
                      size="sm"
                      icon-left="trash"
                      @click="removeItem(index)"
                    />
                  </Td>
                </tr>
                <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                  <Td >Total</Td>
                  <Td >-</Td>
                  <Td class="!px-8">{{ formatAmount(totals.food_product_purchase) }}</Td>
                  <Td class="!px-8">{{ formatAmount(totals.paper_supplies) }}</Td>
                  <Td class="!px-8">{{ formatAmount(totals.smallware_supplies) }}</Td>
                  <Td class="!px-8">{{ formatAmount(totals.cleaning_supplies) }}</Td>
                  <Td class="!px-8">{{ formatAmount(totals.pepsi) }}</Td>
                  <Td class="!px-8">{{ formatAmount(totals.total_amount) }}</Td>
                  <Td class="!px-8"></Td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="errors.items" class="px-4 py-2 text-xs text-red-600">
            {{ errors.items[0] }}
          </div>
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
            {{ mode === 'edit' ? 'Update Food Purchase' : 'Create Food Purchase' }}
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
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/food-purchases'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'data-entry/food-purchases',
  'food-purchase'
)

const createEmptyItem = () => ({
  company_id: null,
  company: null,
  food_product_purchase: 0,
  paper_supplies: 0,
  smallware_supplies: 0,
  cleaning_supplies: 0,
  pepsi: 0,
  total_amount: 0
})

const ensureFormShape = () => {
  if (!form.value) return

  if (!Array.isArray(form.value.items) || !form.value.items.length) {
    form.value.items = [createEmptyItem()]
    return
  }

  form.value.items = form.value.items.map((item) => ({
    ...createEmptyItem(),
    ...item
  }))
}

watch(
  () => form.value,
  () => {
    ensureFormShape()
  },
  { immediate: true }
)

const rowTotal = (item) => {
  return (
    Number(item?.food_product_purchase || 0) +
    Number(item?.paper_supplies || 0) +
    Number(item?.smallware_supplies || 0) +
    Number(item?.cleaning_supplies || 0) +
    Number(item?.pepsi || 0)
  )
}
const totals = computed(() => {
  return {
    food_product_purchase: form.value.items.reduce((sum, item) => sum + Number(item.food_product_purchase || 0), 0),
    paper_supplies: form.value.items.reduce((sum, item) => sum + Number(item.paper_supplies || 0), 0),
    smallware_supplies: form.value.items.reduce((sum, item) => sum + Number(item.smallware_supplies || 0), 0),
    cleaning_supplies: form.value.items.reduce((sum, item) => sum + Number(item.cleaning_supplies || 0), 0),
    pepsi: form.value.items.reduce((sum, item) => sum + Number(item.pepsi || 0), 0),
    total_amount: formatAmount(form.value.items.reduce((sum, item) => sum + Number(rowTotal(item) || 0), 0))
  }
})

const formatAmount = (value) => Number(value || 0).toFixed(2)

const onCompanyChange = (index, value) => {
  const row = form.value.items[index]
  row.company = value || null
  row.company_id = value?.id || null
}

const addItem = () => {
  form.value.items.push(createEmptyItem())
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const getItemError = (index, field) => {
  return errors.value?.[`items.${index}.${field}`]?.[0] || null
}

const handleSave = async () => {
  const items = (form.value.items || [])
    .filter((item) => item?.company?.id || item?.company_id)
    .map((item) => ({
      company_id: item.company?.id || item.company_id,
      food_product_purchase: Number(item.food_product_purchase || 0),
      paper_supplies: Number(item.paper_supplies || 0),
      smallware_supplies: Number(item.smallware_supplies || 0),
      cleaning_supplies: Number(item.cleaning_supplies || 0),
      pepsi: Number(item.pepsi || 0)
    }))

  const payload = {
    date: form.value.date,
    total_amount: totals.value.total_amount,
    items
  }

  await save(payload)
}

defineExpose({
  setData
})
</script>
