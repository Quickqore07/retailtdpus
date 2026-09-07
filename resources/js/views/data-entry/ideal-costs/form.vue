<template>
  <div v-if="show" class="ideal-cost-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Ideal Cost' : 'Create New Ideal Cost' }}
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
          <Input
            :model-value="totals.total_cost"
            label="Total Cost"
            disabled
          />
          <Input
            :model-value="totals.total_mileage"
            label="Total Mileage"
            disabled
          />
          <Input
            :model-value="totals.total_delivery"
            label="Total Delivery"
            disabled
          />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold !mb-0">Ideal Cost Items</h6>
            <!-- <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addItem">
              Add Row
            </Button> -->
          </div>

          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <Th class="text-left px-3 py-3 w-14">No</Th>
                <th class="text-left px-3 py-3 min-w-[280px]">Company</th>
                <Th class="text-left px-3 py-3 min-w-[170px]">Ideal Cost</Th>
                <Th class="text-left px-3 py-3 min-w-[150px]">Mileage</Th>
                <Th class="text-left px-3 py-3 min-w-[170px]">Delivery</Th>
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
                    :disabled="true"
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
                    v-model="item.ideal_cost"
                    type="number"
                    step="0.01"
                    min="0"
                    :error="getItemError(index, 'ideal_cost')"
                  />
                </Td>
                <Td class="px-3 py-3">
                  <Input
                    v-model="item.mileage"
                    type="number"
                    step="0.01"
                    min="0"
                    :error="getItemError(index, 'mileage')"
                  />
                </Td>
                <Td class="px-3 py-3">
                  <Input
                    v-model="item.delivery"
                    type="number"
                    step="0.01"
                    min="0"
                    :error="getItemError(index, 'delivery')"
                  />
                </Td>
                <Td class="px-3 py-3">
                  <!-- <Button
                    type="button"
                    variant="outline-danger"
                    size="sm"
                    icon-left="trash"
                    @click="removeItem(index)"
                    :disabled="form.items.length === 1"
                  /> -->
                </Td>
              </tr>
              <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                <Td>Total</Td>
                <Td>-</Td>
                <Td class="!px-8">{{ totals.total_cost }}</Td>
                <Td class="!px-8">{{ totals.total_mileage }}</Td>
                <Td class="!px-8">{{ totals.total_delivery }}</Td>
                <Td class="!px-8"></Td>
              </tr>
            </tbody>
          </table>
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
            {{ mode === 'edit' ? 'Update Ideal Cost' : 'Create Ideal Cost' }}
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
const resource = route.meta?.resource || 'data-entry/ideal-costs'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'data-entry/ideal-costs',
  'ideal-cost'
)

const createEmptyItem = () => ({
  company_id: null,
  company: null,
  ideal_cost: 0,
  mileage: 0,
  delivery: 0
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

const formatAmount = (value) => Number(value || 0).toFixed(2)

const totals = computed(() => {
  return {
    total_cost: formatAmount(form.value.items.reduce((sum, item) => sum + Number(item.ideal_cost || 0), 0)),
    total_mileage: formatAmount(form.value.items.reduce((sum, item) => sum + Number(item.mileage || 0), 0)),
    total_delivery: formatAmount(form.value.items.reduce((sum, item) => sum + Number(item.delivery || 0), 0))
  }
})

const onCompanyChange = (index, value) => {
  const row = form.value.items[index]
  row.company = value || null
  row.company_id = value?.id || null
}

const addItem = () => {
  form.value.items.push(createEmptyItem())
}

const removeItem = (index) => {
  if (form.value.items.length === 1) return
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
      ideal_cost: Number(item.ideal_cost || 0),
      mileage: Number(item.mileage || 0),
      delivery: Number(item.delivery || 0)
    }))

  const payload = {
    date: form.value.date,
    total_cost: Number(totals.value.total_cost),
    total_mileage: Number(totals.value.total_mileage),
    total_delivery: Number(totals.value.total_delivery),
    items
  }

  await save(payload)
}

defineExpose({
  setData
})
</script>
