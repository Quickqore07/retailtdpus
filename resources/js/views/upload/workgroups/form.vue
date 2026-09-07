<template>
  <div v-if="show" class="upload-workgroup-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Upload Workgroup' : 'Create New Upload Workgroup' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Name -->
          <Input
            v-model="form.name"
            label="Name"
            placeholder="Enter workgroup name"
            :required="true"
            :error="errors.name ? errors.name[0] : null"
            icon-left="users"
          />

          <!-- Active Checkbox -->
          <div class="flex flex-col justify-end pb-1">
            <label class="flex items-center cursor-pointer">
              <input
                v-model="form.active"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
              />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Active
              </span>
            </label>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button variant="primary" size="md" type="submit" :loading="isSaving" v-if="access.includes('create')">
            {{ mode === 'edit' ? 'Update Workgroup' : 'Create Workgroup' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'upload/workgroups'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'upload/workgroups', 'upload-workgroup')

const handleSave = () => {
  save(form.value)
}

defineExpose({
  setData
})
</script>
