<template>
  <div v-if="show" class="bank-rule-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Bank Rule Details</h5>
          <div class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/settings/bank-rules"
            />
            <Button
              v-if="access.includes('update')"
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              :to="`/settings/bank-rules/${model.id}/edit`"
            />
            <Button
              v-if="access.includes('delete')"
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              @click="handleDelete"
            />
          </div>
        </div>
      </template>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <Label label="Name" :value="model.name" />
        <Label label="Ledger" :value="model.ledger?.ledger_details?.code ? `${model.ledger?.ledger_details?.code} - ${model.ledger?.name}` : model.ledger?.name ?? 'Both'" />
        <Label label="Condition" :value="model.condition" />
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
        <Label label="Created By" :value="model.created_by?.name ?? 'N/A'" />
        <Label label="Updated By" :value="model.updated_by?.name ?? 'N/A'" />
      </div>

      <div v-if="model.conditions?.length" class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 font-semibold">
          Conditions
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="text-left px-4 py-3">Condition</th>
              <th class="text-left px-4 py-3">Value</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(c, i) in model.conditions"
              :key="i"
              class="border-t border-gray-100 dark:border-gray-700"
            >
              <td class="px-4 py-3">{{ c.condition_type }}</td>
              <td class="px-4 py-3">{{ c.value }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading bank rule..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDateTime as formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'settings/bank-rules'

const { model, show, setData, removeDB, access } = useShowable(resource, 'bank-rule')

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

<style scoped>
@media (max-width: 640px) {
  .bank-rule-show {
    padding: 1rem;
  }
}
</style>
