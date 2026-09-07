<template>
  <div v-if="show" class="ideal-cost-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Ideal Cost Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/ideal-costs" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/data-entry/ideal-costs/${model.id}/edit`"
            />
            <Button
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              v-if="access.includes('delete')"
              @click="handleDelete"
            />
          </div>
        </div>
      </template>

      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <Label label="Date" :value="formatDate(model.date)" />
        <Label label="Total Cost" :value="`$${formatAmount(totalCost(model))}`" />
        <Label label="Total Mileage" :value="formatAmount(totalMileage(model))" />
        <Label label="Total Delivery" :value="formatAmount(totalDelivery(model))" />
        <Label label="No. of Companies" :value="model.items?.length || 0" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
        <table class="text-sm w-full">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th class="text-left px-4 py-3">No.</Th>
              <Th class="text-left px-4 py-3">Company</Th>
              <Th class="text-right px-4 py-3">Ideal Cost</Th>
              <Th class="text-right px-4 py-3">Mileage</Th>
              <Th class="text-right px-4 py-3">Delivery</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!model.items?.length">
              <Td colspan="4" class="px-4 py-6 text-center text-gray-500">No items found.</Td>
            </tr>
            <tr
              v-for="(item, index) in model.items"
              :key="item.id"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td class="px-4 py-3">
                {{ index + 1 }}
              </Td>
              <Td class="px-4 py-3">
                {{ item.company?.name || 'N/A' }}
              </Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.ideal_cost) }}</Td>
              <Td class="px-4 py-3 text-right">{{ formatAmount(item.mileage) }}</Td>
              <Td class="px-4 py-3 text-right">{{ formatAmount(item.delivery) }}</Td>
            </tr>
            <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
              <Td class="px-4 py-3">Total</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totalCost(model)) }}</Td>
              <Td class="px-4 py-3 text-right">{{ formatAmount(totalMileage(model)) }}</Td>
              <Td class="px-4 py-3 text-right">{{ formatAmount(totalDelivery(model)) }}</Td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading ideal cost details..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate } from '@/utils/date'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/ideal-costs'

const { model, show, setData, removeDB, access } = useShowable(resource, 'ideal-cost')

const formatAmount = (value) => Number(value || 0).toFixed(2)

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

const totalCost = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.ideal_cost || 0), 0)
}
const totalMileage = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.mileage || 0), 0)
}
const totalDelivery = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.delivery || 0), 0)
}

defineExpose({
  setData
})
</script>
