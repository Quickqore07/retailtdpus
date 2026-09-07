<template>
  <div v-if="show" class="upload-user-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Upload User Details</h5>
          <div class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/upload/users"
            />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              :to="`/upload/users/${model.id}/edit`"
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

        <!-- Username -->
        <Label label="Username" :value="model.username" />

        <!-- View Password (Plain Text) -->
        <Label label="Password">
          <span v-if="model.current_password" class="text-gray-900 dark:text-gray-200 font-mono">
            {{ model.current_password }}
          </span>
          <span v-else class="text-gray-400">Not set</span>
        </Label>

        <!-- Office -->
        <Label label="Office" :value="model.office?.name || '-'" />

        <!-- Checkout Time -->
        <Label label="Checkout Time" :value="formatCheckoutTime(model.checkout_time)" />

        <!-- Created At -->
        <Label label="Created At" :value="formatDate(model.created_at)" />

        <!-- Updated At -->
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>

    <!-- Internal (TDPUS) Companies -->
    <Panel
      title="Internal Companies (TDPUS)"
      :divider="true"
      class="mt-6"
      v-if="model.companies_by_workgroup && model.companies_by_workgroup.length > 0"
    >
      <div class="flex gap-6 flex-wrap">
        <div
          v-for="group in model.companies_by_workgroup"
          :key="group.workgroup_id"
          class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden min-w-[280px]"
        >
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
            {{ group.workgroup_name }}
          </h6>
          <ul class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[320px] overflow-y-auto">
            <li
              v-for="company in group.companies_array"
              :key="company.id"
              class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300"
            >
              {{ company.name }}
            </li>
          </ul>
        </div>
      </div>
    </Panel>

  
    <!-- Folder Access -->
    <Panel title="Folder Access" :divider="true" class="mt-6" v-if="model.folder_access && model.folder_access.length > 0">
      <div class="flex flex-wrap gap-2">
        <span
          v-for="(folder, index) in model.folder_access"
          :key="index"
          class="inline-flex items-center px-3 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full"
        >
          {{ folder?.name }}
        </span>
      </div>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading user details..." centered />
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
const resource = route.meta?.resource || 'upload/users'

const { model, show, setData, removeDB, access } = useShowable(resource, 'upload-user')

const formatCheckoutTime = (value) => {
  if (!value) return '-'
  return String(value).slice(0, 5)
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
