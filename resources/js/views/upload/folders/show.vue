<template>
  <div v-if="show" class="upload-folder-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Upload Folder Details</h5>
          <div class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/upload/folders"
            />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              :to="`/upload/folders/${model.id}/edit`"
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

        <!-- Created At -->
        <Label label="Created At" :value="formatDate(model.created_at)" />

        <!-- Updated At -->
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading folder details..." centered />
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
const resource = route.meta?.resource || 'upload/folders'

const { model, show, setData, removeDB, access } = useShowable(resource, 'upload-folder')

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
