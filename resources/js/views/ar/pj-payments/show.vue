<template>
  <div v-if="show" class="pj-payment-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">PJ Payment Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/ar/pj-payments" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/ar/pj-payments/${model.id}/edit`"
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

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <Label label="Date" :value="formatDate(model.date)" />
        <Label
          label="Company"
          :value="model.company?.name || model.company?.name || 'N/A'"
        />
        <Label label="Total Amount" :value="`$${formatAmount(model.total_amount)}`" />
        <Label label="No. of Items" :value="model.items?.length || 0" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
        <table class="text-sm">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <Th class="w-[20px]"></Th>
              <Th class="text-left px-4 py-3">Ledger</Th>
              <Th class="text-left px-4 py-3">Name</Th>
              <Th class="text-right px-4 py-3">Amount</Th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!model.items?.length">
              <Td colspan="4" class="px-4 py-6 text-center text-gray-500">No items found.</Td>
            </tr>
            <tr v-for="item in sortedItems" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
              <Td >
                <SvgIcon name="check-circle" v-if="item.bank_child_amount?.settled" size="sm" :color="item.bank_child_amount?.manual_settlement ? 'orange' : 'green'" />
              </Td>
              <Td class="px-4 py-3">
                {{  `${item.ledger?.ledger_details?.code} - ${item.ledger?.name}`}}</Td>
              <Td class="px-4 py-3">{{ item.name }}</Td>
              <Td class="px-4 py-3 text-right">${{ formatAmount(item.amount) }}</Td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading PJ payment details..." centered />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate } from '@/utils/date'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'

const route = useRoute()
const resource = route.meta?.resource || 'ar/pj-payments'

const { model, show, setData, removeDB, access } = useShowable(resource, 'pj-payment')

const formatAmount = (value) => Number(value || 0).toFixed(2)
const sortedItems = computed(() =>
  [...(model.value?.items || [])].sort((a, b) => Number(b?.amount || 0) - Number(a?.amount || 0))
)

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

defineExpose({
  setData
})
</script>
