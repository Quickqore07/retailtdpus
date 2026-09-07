<template>
  <div v-if="show" class="upload-workgroup-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Upload Workgroup Details</h5>
          <div class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/upload/workgroups"
            />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              :to="`/upload/workgroups/${model.id}/edit`"
              v-if="access.includes('update')"
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
        <!-- Name -->
        <Label label="Name" :value="model.name" />

        <!-- Status -->
        <Label label="Status">
          <span
            :class="[
              'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
              model.active
                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
            ]"
          >
            <SvgIcon :name="model.active ? 'check' : 'x'" size="xs" class="mr-1" />
            {{ model.active ? 'Active' : 'Inactive' }}
          </span>
        </Label>

        <!-- Created At -->
        <Label label="Created At" :value="formatDate(model.created_at)" />

        <!-- Updated At -->
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading workgroup details..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { formatDateTime as formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'upload/workgroups'

const { model, show, setData, removeDB, access } = useShowable(resource, 'upload-workgroup')

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
