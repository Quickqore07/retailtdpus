<template>
  <div v-if="show" class="pj-calendar-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">PJ Calendar Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs" to="/settings/pj-calendars" />
            <Button
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/settings/pj-calendars/${model.id}/edit`"
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

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <Label label="Year" :value="model.year" />
        <Label label="Created At" :value="formatDateTime(model.created_at)" />
        <Label label="Updated At" :value="formatDateTime(model.updated_at)" />
      </div>

      <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="text-left px-4 py-3 w-14">No</th>
              <th class="text-left px-4 py-3">Label</th>
              <th class="text-left px-4 py-3">Weeks</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in model.items || []"
              :key="item.id || index"
              class="border-t border-gray-100 dark:border-gray-700 align-top"
            >
              <td class="px-4 py-3">{{ index + 1 }}</td>
              <td class="px-4 py-3 font-medium">{{ item.label }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="weekLabel in formatWeekLabelsForModel(item.weeks)"
                    :key="weekLabel"
                    class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-xs"
                  >
                    {{ weekLabel }}
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading PJ calendar details..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDateTime } from '@/utils/date'
import { formatWeekLabels } from '@/utils/pjCalendarWeeks'

const route = useRoute()
const resource = route.meta?.resource || 'settings/pj-calendars'

const { model, show, setData, removeDB, access } = useShowable(resource, 'pj-calendar')

const formatWeekLabelsForModel = (weeks) => formatWeekLabels(weeks, model.value?.year)

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
  .pj-calendar-show {
    padding: 1rem;
  }
}
</style>
