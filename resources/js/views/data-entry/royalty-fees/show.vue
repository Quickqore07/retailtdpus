<template>
  <div v-if="show" class="royalty-fee-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Royalty Fee Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/royalty-fees" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/data-entry/royalty-fees/${model.id}/edit`"
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

      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <Label label="Type" :value="model.type || '-'" />
        <Label label="Company" :value="model.company?.name || '-'" />
        <Label label="Invoice Number" :value="model.invoice_number || '-'" />
        <Label label="Invoice Date" :value="formatDate(model.invoice_date)" />
        <Label label="Due Date" :value="formatDate(model.due_date)" />
        <Label label="Amount" :value="`$${formatAmount(model.amount)}`" />
        <Label label="Invoice PDF">
          <a
            v-if="model.invoice_pdf_url"
            :href="model.invoice_pdf_url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
          >
            {{ model.invoice_pdf_name || 'View PDF' }}
          </a>
          <span v-else>-</span>
        </Label>
        <Label label="Created By" :value="model.created_by?.name || model.createdBy?.name || '-'" />
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <Label label="Updated By" :value="model.updated_by?.name || model.updatedBy?.name || '-'" />
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>

      <div v-if="model.description" class="mt-6">
        <Label label="Description" :value="model.description" />
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading royalty fee details..." centered />
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

const route = useRoute()
const resource = route.meta?.resource || 'data-entry/royalty-fees'

const { model, show, setData, removeDB, access } = useShowable(resource, 'royalty-fees')

const formatAmount = (value) => Number(value || 0).toFixed(2)

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
