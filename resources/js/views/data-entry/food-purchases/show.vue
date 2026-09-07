<template>
  <div v-if="show" class="food-purchase-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Food Purchase Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/food-purchases" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/data-entry/food-purchases/${model.id}/edit`"
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

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <Label label="Date" :value="formatDate(model.date)" />
        <Label label="Total Amount" :value="`$${formatAmount(totalAmount(model))}`" />
        <Label label="No. of Companies" :value="model.items?.length || 0" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
        <table class="text-sm w-full">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th class="text-left px-4 py-3">Company</Th>
              <Th class="text-right px-4 py-3">Food Product Purchase</Th>
              <Th class="text-right px-4 py-3">Paper Supply</Th>
              <Th class="text-right px-4 py-3">Smallware Supplies</Th>
              <Th class="text-right px-4 py-3">Cleaning Supplies</Th>
              <Th class="text-right px-4 py-3">Pepsi</Th>
              <Th class="text-right px-4 py-3">Total</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!model.items?.length">
              <Td colspan="7" class="px-4 py-6 text-center text-gray-500">No items found.</Td>
            </tr>
            <tr
              v-for="item in model.items"
              :key="item.id"
              class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <Td class="px-4 py-3">
                {{ item.company?.name || item.company?.name || 'N/A' }}
              </Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.food_product_purchase) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.paper_supplies) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.smallware_supplies) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.cleaning_supplies) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.pepsi) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.total_amount) }}</Td>
            </tr>
            <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
              <Td class="px-4 py-3">Total</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totals.food_product_purchase) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totals.paper_supplies) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totals.smallware_supplies) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totals.cleaning_supplies) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totals.pepsi) }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(totals.total_amount) }}</Td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading food purchase details..." centered />
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
import { computed } from 'vue'
import Th from '@/components/ui/th.vue'

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/food-purchases'

const { model, show, setData, removeDB, access } = useShowable(resource, 'food-purchase')

const formatAmount = (value) => Number(value || 0).toFixed(2)

const totalAmount = (item) => {
  return item.items.reduce((sum, row) => sum + Number(row.total_amount || 0), 0)
}

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}
const totals = computed(() => {
  const items = model.value?.items ?? []
  return {
    food_product_purchase: items.reduce((sum, item) => sum + Number(item.food_product_purchase || 0), 0),
    paper_supplies: items.reduce((sum, item) => sum + Number(item.paper_supplies || 0), 0),
    smallware_supplies: items.reduce((sum, item) => sum + Number(item.smallware_supplies || 0), 0),
    cleaning_supplies: items.reduce((sum, item) => sum + Number(item.cleaning_supplies || 0), 0),
    pepsi: items.reduce((sum, item) => sum + Number(item.pepsi || 0), 0),
    total_amount: totalAmount(model.value)
  }
})


defineExpose({
  setData
})
</script>
