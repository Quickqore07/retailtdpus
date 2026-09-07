<template>
  <div v-if="show" class="bank-rule-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !m-0">
            {{ mode === 'edit' ? 'Edit Bank Rule' : 'Create Bank Rules' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            v-model="form.name"
            label="Name"
            placeholder="Rule name"
            :required="true"
            :error="errors.name ? errors.name[0] : null"
          />
          <DynamicDropdown
            label="Ledger"
            v-model="form.ledger"
            resource="ledgers"
            display-name="name"
            placeholder="Select Ledger"
            :error="errors.ledger_id ? errors.ledger_id[0] : null"
            allow-clear
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="text-sm text-gray-600 dark:text-gray-400">When a transaction meets</span>
          <select
            v-model="form.condition"
            class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1.5 text-gray-900 dark:text-gray-100"
          >
            <option value="All">All</option>
            <option value="some">Some</option>
          </select>
          <span class="text-sm text-gray-600 dark:text-gray-400">of this condition</span>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="text-left px-4 py-3 w-40">Condition</th>
                <th class="text-left px-4 py-3">Value</th>
                <th class="w-16"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in form.conditions"
                :key="`cond-${index}`"
                class="border-t border-gray-100 dark:border-gray-700"
              >
                <td class="px-4 py-3">
                  <select
                    v-model="row.condition_type"
                    class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                  >
                    <option v-for="opt in conditionTypeOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                </td>
                <td class="px-4 py-3">
                  <Input
                    v-model="row.value"
                    placeholder="Value"
                    :error="errors[`conditions.${index}.value`] ? errors[`conditions.${index}.value`][0] : null"
                  />
                </td>
                <td class="px-4 py-3">
                  <Button
                    variant="outline-danger"
                    size="sm"
                    class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm"
                    @click="removeCondition(index)"
                  >
                  <SvgIcon name="trash" size="sm" class="text-red-500" />
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            <Button type="button" variant="primary" size="sm" @click="addCondition">
              Add more
            </Button>
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
            {{ mode === 'edit' ? 'Update Bank Rule' : 'Create Bank Rule' }}
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
import { watch, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import SvgIcon from '@/components/SvgIcon.vue'


const route = useRoute()
const resource = route.meta?.resource || 'settings/bank-rules'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(
  resource,
  'settings/bank-rules',
  'bank-rule'
)

const conditionTypeOptions = ref([
  { value: 'Matches', label: 'Matches' },
  { value: 'Contains', label: 'Contains' },
  { value: 'Starts With', label: 'Starts With' },
  { value: 'Ends With', label: 'Ends With' },
  { value: 'Is Equal To', label: 'Is Equal To' }
])

const ensureConditions = () => {
  if (!form.value?.conditions?.length) {
    form.value.conditions = [{ condition_type: 'Matches', value: '' }]
  }
}

watch(
  () => form.value,
  () => ensureConditions(),
  { immediate: true, deep: true }
)

onMounted(() => {
  ensureConditions()
})

const addCondition = () => {
  if (!form.value.conditions) form.value.conditions = []
  form.value.conditions.push({ condition_type: 'Matches', value: '' })
}

const removeCondition = (index) => {
  form.value.conditions.splice(index, 1)
  if (form.value.conditions.length === 0) {
    form.value.conditions.push({ condition_type: 'Matches', value: '' })
  }
}

const handleSave = () => {
  const payload = {
    name: form.value.name,
    ledger_id: form.value.ledger?.id ?? form.value.ledger_id ?? null,
    condition: form.value.condition || 'All',
    conditions: (form.value.conditions || []).map((c) => ({
      condition_type: c.condition_type || 'Matches',
      value: c.value ?? ''
    }))
  }
  save(payload)
}

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .bank-rule-form {
    padding: 1rem;
  }
}
</style>
