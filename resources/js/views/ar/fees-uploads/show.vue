<template>
  <div v-if="show" class="fees-upload-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Fees Upload Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/ar/fees-uploads" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/ar/fees-uploads/${model.id}/edit`"
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

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <Label label="Date" :value="formatDate(model.date)" />
        <Label label="Company" :value="model.company?.name || 'N/A'" />
      </div>
      
      <h5 class="text-xl font-bold mb-4">Payment Details</h5>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <Label label="DDD Cash" :value="`$${formatAmount(model.ddd_cash)}`" />
        <Label label="EZ Cater" :value="`$${formatAmount(model.ez_cater)}`" />
        <Label label="Meal Deal" :value="`$${formatAmount(model.meal_deal)}`" />
        <Label label="Visa" :value="`$${formatAmount(model.visa)}`" />
        <Label label="Amex" :value="`$${formatAmount(model.amex)}`" />
        <Label label="Doordash" :value="`$${formatAmount(model.doordash)}`" />
        <Label label="DDC Doordash" :value="`$${formatAmount(model.ddc_doordash)}`" />
        <Label label="Uber" :value="`$${formatAmount(model.uber)}`" />
        <Label label="Grubhub" :value="`$${formatAmount(model.grubhub)}`" />
        <Label label="Total Amount" :value="`$${formatAmount(model.total_amount)}`" value-weight="bold" />
      </div>

      <h5 class="text-xl font-bold mb-4">Audit Information</h5>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <!-- <Label label="Created By" :value="model.created_by?.name || 'N/A'" /> -->
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <!-- <Label label="Updated By" :value="model.updated_by?.name || 'N/A'" /> -->
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading fees upload details..." centered />
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
const resource = route.meta?.resource || 'ar/fees-uploads'

const { model, show, setData, removeDB, access } = useShowable(resource, 'fees-upload')

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
