<template>
  <div v-if="show" class="pj-calendar-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit PJ Calendar' : 'Create New PJ Calendar' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Year
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model.number="form.year"
              class="w-full min-h-[36px] px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-md"
            >
              <option v-for="year in yearOptions" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
            <div v-if="errors.year?.[0]" class="text-xs text-red-600">{{ errors.year[0] }}</div>
          </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold !mb-0">Labels &amp; Weeks</h6>
            <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addItem">
              Add Row
            </Button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <Th class="text-left px-3 py-3 w-14">No</Th>
                  <Th class="text-left px-3 py-3 w-[180px]">Label</Th>
                  <Th class="text-left px-3 py-3 min-w-[320px]">Weeks</Th>
                  <Th class="text-left px-3 py-3 w-16">Actions</Th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in form.items"
                  :key="`pj-calendar-item-${index}`"
                  class="border-t border-gray-100 dark:border-gray-700 align-top"
                >
                  <Td class="px-3 py-3">{{ index + 1 }}</Td>
                  <Td class="px-3 py-3">
                    <Input
                      v-model="item.label"
                      placeholder="Enter label"
                      :required="true"
                      :error="getItemError(index, 'label')"
                    />
                  </Td>
                  <Td class="px-3 py-3">
                    <DynamicDropdown
                      :model-value="getSelectedWeekObjects(item)"
                      :custom-options="getAvailableWeekOptions(index)"
                      display-name="name"
                      placeholder="Select weeks"
                      search-placeholder="Search weeks..."
                      :searchable="true"
                      multiple
                      :error="getItemError(index, 'weeks')"
                      @update:model-value="setSelectedWeeks(item, $event)"
                    />
                  </Td>
                  <Td class="px-3 py-3">
                    <Button
                      type="button"
                      variant="outline-danger"
                      size="sm"
                      icon-left="trash"
                      :disabled="form.items.length === 1"
                      @click="removeItem(index)"
                    />
                  </Td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button
            variant="primary"
            size="md"
            type="submit"
            :loading="isSaving"
            v-if="mode === 'create' ? access.includes('create') : access.includes('update')"
          >
            {{ mode === 'edit' ? 'Update PJ Calendar' : 'Create PJ Calendar' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { generateWeekOptionsForYear, getYearOptions } from '@/utils/pjCalendarWeeks'

const route = useRoute()
const resource = route.meta?.resource || 'settings/pj-calendars'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'settings/pj-calendars',
  'pj-calendar'
)

const createEmptyItem = () => {
  return {
    label: 'Period ' + (form.value?.items?.length + 1),
    weeks: [],
  }
}

const yearOptions = computed(() => getYearOptions())

const weekOptions = computed(() => generateWeekOptionsForYear(Number(form.value.year || new Date().getFullYear())))

const addItem = () => {
  form.value.items.push(createEmptyItem())
}

const removeItem = (index) => {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1)
  }
}

const getItemError = (index, field) => {
  const key = `items.${index}.${field}`
  return errors.value[key] ? errors.value[key][0] : null
}

const weekOptionMap = computed(() =>
  Object.fromEntries(weekOptions.value.map((week) => [week.value, { id: week.value, name: week.label }]))
)

const getAvailableWeekOptions = (rowIndex) => {
  const takenWeeks = new Set()

  form.value.items.forEach((item, index) => {
    if (index === rowIndex) return
    ;(item.weeks || []).forEach((week) => takenWeeks.add(week))
  })

  return weekOptions.value
    .filter((week) => !takenWeeks.has(week.value))
    .map((week) => weekOptionMap.value[week.value])
}

const getSelectedWeekObjects = (item) =>
  (item.weeks || []).map((week) => weekOptionMap.value[week]).filter(Boolean)

const setSelectedWeeks = (item, selected) => {
  item.weeks = (selected || []).map((option) => option.id)
}

const pruneInvalidWeeks = () => {
  const validWeekValues = new Set(weekOptions.value.map((week) => week.value))

  form.value.items = (form.value.items || []).map((item) => ({
    ...item,
    weeks: (item.weeks || []).filter((week) => validWeekValues.has(week)),
  }))
}

const handleSave = () => {
  save({
    year: Number(form.value.year),
    items: (form.value.items || []).map((item) => ({
      label: item.label,
      weeks: [...(item.weeks || [])].sort(),
    })),
  })
}

watch(
  () => form.value?.year,
  (newYear, oldYear) => {
    if (!newYear || newYear === oldYear) return
    pruneInvalidWeeks()
  }
)

watch(
  () => form.value,
  (newVal) => {
    if (!newVal.items || newVal.items.length === 0) {
      form.value.items = [createEmptyItem()]
    }

    form.value.items = form.value.items.map((item) => ({
      ...item,
      weeks: Array.isArray(item.weeks) ? item.weeks : [],
    }))


  },
  { immediate: true }
)

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .pj-calendar-form {
    padding: 1rem;
  }
}
</style>
