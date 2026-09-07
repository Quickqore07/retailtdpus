<template>
  <div v-if="show" class="wc-entry-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">WC Entry Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/data-entry/wc-entries" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/data-entry/wc-entries/${model.id}/edit`"
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
        <Label label="Year" :value="String(model.year || '-')" />
        <Label label="Period (Week)" :value="formatPeriodFromEow(model.eow)" />
        <Label label="EOW" :value="formatDate(model.eow)" />
        <Label label="Company" :value="model.company?.name || '-'" />
        <Label label="Driver Pay" :value="`$${formatAmount(model.driver_pay)}`" />
        <Label label="Non Driver Pay" :value="`$${formatAmount(model.non_driver_pay)}`" />
        <Label label="Total Pay" :value="`$${formatAmount(model.total_pay)}`" />
        <Label label="Created By" :value="model.created_by?.name || model.createdBy?.name || '-'" />
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <Label label="Updated By" :value="model.updated_by?.name || model.updatedBy?.name || '-'" />
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading WC entry details..." centered />
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
const resource = route.meta?.resource || 'data-entry/wc-entries'

const { model, show, setData, removeDB, access } = useShowable(resource, 'wc-entry')

const formatAmount = (value) => Number(value || 0).toFixed(2)

const formatPeriodFromEow = (eow) => {
  if (!eow) return '-'
  const date = new Date(`${eow}T00:00:00`)
  if (Number.isNaN(date.getTime())) return '-'
  const week = getWeekNumber(date)
  return `W${String(week).padStart(2, '0')}`
}

const getWeekNumber = (date) => {
  const utcDate = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
  const dayNum = utcDate.getUTCDay() || 7
  utcDate.setUTCDate(utcDate.getUTCDate() + 4 - dayNum)
  const yearStart = new Date(Date.UTC(utcDate.getUTCFullYear(), 0, 1))
  return Math.ceil((((utcDate - yearStart) / 86400000) + 1) / 7)
}

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
