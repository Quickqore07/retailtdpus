<template>
  <div v-if="show" class="upload-company-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Upload Company' : 'Create New Upload Company' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Store Number -->
          <Input
            v-model="form.store_number"
            label="Store Number"
            placeholder="Enter store number"
            :required="true"
            :error="errors.store_number ? errors.store_number[0] : null"
            icon-left="hash"
          />

          <!-- Name -->
          <Input
            v-model="form.name"
            label="Name"
            placeholder="Enter company name"
            :required="true"
            :error="errors.name ? errors.name[0] : null"
            icon-left="building"
          />

          <!-- Workgroup -->
          <div class="flex flex-col">
            <label for="workgroup" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
              Workgroup
              <span class="text-red-500">*</span>
            </label>
            <DynamicDropdown
              v-model="form.workgroup"
              resource="upload-workgroups"
              display-name="name"
              placeholder="Select a workgroup"
              :removable="false"
            />
            <p v-if="errors.workgroup_id" class="text-xs text-red-600 dark:text-red-400 mt-1">
              {{ errors.workgroup_id[0] }}
            </p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button variant="primary" size="md" type="submit" :loading="isSaving" v-if="access.includes('create')">
            {{ mode === 'edit' ? 'Update Company' : 'Create Company' }}
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
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'upload/companies'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'upload/companies', 'upload-company')

watch(() => form.value.workgroup, (newWorkgroup) => {
  if (newWorkgroup) {
    form.value.workgroup_id = newWorkgroup.id
  } else {
    form.value.workgroup_id = ''
  }
})

const handleSave = () => {
  save(form.value)
}

defineExpose({
  setData
})
</script>
