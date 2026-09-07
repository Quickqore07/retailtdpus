<template>
  <div v-if="show" class="vendor-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Vendor Details</h5>
          <div class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/ap/vendors"
            />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/ap/vendors/${model.id}/edit`"
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

      <div class="space-y-6">
        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            Contact Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Code" :value="model.code || '-'" />
            <Label label="Name" :value="model.name" />
            <Label label="Email" :value="model.email || '-'" />
            <Label label="Mobile Number" :value="model.mobile || '-'" />
            <Label label="Fax" :value="model.fax || '-'" />
            <Label label="Credit Days" :value="formatCreditDays(model.credit_days)" />
          </div>
        </div>

        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            Address
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Address Line 1" :value="model.address_line_1 || '-'" />
            <Label label="Address Line 2" :value="model.address_line_2 || '-'" />
            <Label label="Address Line 3" :value="model.address_line_3 || '-'" />
            <Label label="City" :value="model.city || '-'" />
            <Label label="State" :value="model.state || '-'" />
            <Label label="Country" :value="model.country || '-'" />
          </div>
        </div>

        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
            Audit
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Created By" :value="model.created_by?.name || '-'" />
            <Label label="Updated By" :value="model.updated_by?.name || '-'" />
            <Label label="Created At" :value="formatDate(model.created_at)" />
            <Label label="Updated At" :value="formatDate(model.updated_at)" />
          </div>
        </div>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading vendor details..." centered />
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
const resource = route.meta?.resource || 'ap/vendors'

const { model, show, setData, removeDB, access } = useShowable(resource, 'vendor')

const formatCreditDays = (value) => {
  if (value === null || value === undefined || value === '') return '-'
  return `${value} day${Number(value) === 1 ? '' : 's'}`
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

<style scoped>
@media (max-width: 640px) {
  .vendor-show {
    padding: 1rem;
  }
}
</style>
