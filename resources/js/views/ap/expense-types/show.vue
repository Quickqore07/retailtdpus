<template>
  <div v-if="show" class="expense-type-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Expense Type Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/ap/expense-types" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/ap/expense-types/${model.id}/edit`"
            />
            <Button
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              @click="handleDelete"
              v-if="access.includes('delete')"
            />
          </div>
        </div>
      </template>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Label label="Name" :value="model.name" />
        <Label label="Amount Label" :value="model.amount_label" />
        <Label label="Other Amount Label" :value="model.other_amount_label" />
        <Label label="Show Other Amount" :value="model.show_other_amount ? 'Yes' : 'No'" />
        <Label label="Status" :value="model.active ? 'Active' : 'Inactive'" />
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading expense type details..." centered />
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
const resource = route.meta?.resource || 'ap/expense-types'

const { model, show, setData, removeDB, access } = useShowable(resource, 'expense-type')

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

defineExpose({ setData })
</script>
