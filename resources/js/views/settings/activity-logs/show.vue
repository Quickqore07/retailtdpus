<template>
  <div v-if="show" class="activity-log-show min-w-0 w-full">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Activity Log Details</h5>
          <Button
            icon-left="arrow-left"
            icon-size="sm"
            variant="secondary"
            size="xs"
            to="/settings/activity-logs"
          />
        </div>
      </template>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <Label label="Action">
          <span :class="actionBadgeClass(model.action)">
            {{ formatAction(model.action) }}
          </span>
        </Label>
        <Label label="Module" :value="formatModule(model.auditable_type)" />
        <Label label="Index" :value="model.index_value || '-'" />
        <Label label="User" :value="model.user?.name || model.user?.username || 'System'" />
        <Label label="Email" :value="model.user?.email || '-'" />
        <Label label="IP Address" :value="model.ip_address || '-'" />
        <Label label="Date" :value="formatDate(model.created_at)" />
      </div>

      <div v-if="model.description" class="mt-6">
        <Label label="Description" :value="model.description" />
      </div>

      <div v-if="model.user_agent" class="mt-4">
        <Label label="User Agent" :value="model.user_agent" />
      </div>
    </Panel>

    <Panel
      v-if="hasOnlyChanged"
      title="Changed Values"
      :divider="true"
      class="mt-6"
    >
      <div v-if="onlyChangedRows.length" class="overflow-x-auto mb-4">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Old Value</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Value</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="row in onlyChangedRows"
              :key="`changed-${row.field}`"
              class="hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ row.field }}</td>
              <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 whitespace-pre-wrap break-all">{{ row.oldValue }}</td>
              <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400 whitespace-pre-wrap break-all">{{ row.newValue }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-for="tableField in tableFieldsWithDiffs"
        :key="`changed-table-${tableField.field}`"
        class="mb-4 last:mb-0"
      >
        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
          {{ tableField.label || tableField.field }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th
                  v-for="col in tableKeyColumns(tableField)"
                  :key="`ck-${col.field}`"
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  {{ col.label || col.field }}
                </th>
                <template v-for="col in tableValueColumns(tableField)" :key="`cv-${col.field}`">
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Old {{ col.label || col.field }}
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    New {{ col.label || col.field }}
                  </th>
                </template>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr
                v-for="(row, index) in tableDiffRows(tableField)"
                :key="`cd-${index}`"
                class="hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <td
                  v-for="col in tableKeyColumns(tableField)"
                  :key="col.field"
                  class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-all"
                >
                  {{
                    formatTableCell(
                      row.identity[col.field] ?? row.newRow?.[col.field] ?? row.oldRow?.[col.field],
                      col
                    )
                  }}
                </td>
                <template v-for="col in tableValueColumns(tableField)" :key="`cv-${col.field}`">
                  <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 whitespace-pre-wrap break-all">
                    {{ row.oldRow ? formatTableCell(row.oldRow[col.field], col) : '-' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400 whitespace-pre-wrap break-all">
                    {{ row.newRow ? formatTableCell(row.newRow[col.field], col) : '-' }}
                  </td>
                </template>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Panel>

    <Panel
      v-if="hasChanges"
      title="Changes"
      :divider="true"
      class="mt-6"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Old Value</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Value</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="row in changeRows" :key="row.field" class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ row.field }}</td>
              <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 whitespace-pre-wrap break-all">{{ row.oldValue }}</td>
              <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400 whitespace-pre-wrap break-all">{{ row.newValue }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Panel>

    <template v-for="tableField in tableFields" :key="tableField.field">
      <Panel
        v-if="hasTableRows(tableField, 'old')"
        :title="tableFieldTitle(tableField, 'old')"
        :divider="true"
        class="mt-6"
      >
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th
                  v-for="col in tableField.columns"
                  :key="col.field"
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  {{ col.label || col.field }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr
                v-for="(row, index) in tableRows(tableField, 'old')"
                :key="`old-${index}`"
                class="hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <td
                  v-for="col in tableField.columns"
                  :key="col.field"
                  class="px-4 py-3 text-sm text-red-600 dark:text-red-400 whitespace-pre-wrap break-all"
                >
                  {{ formatTableCell(row[col.field], col) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Panel>

      <Panel
        v-if="hasTableRows(tableField, 'new')"
        :title="tableFieldTitle(tableField, 'new')"
        :divider="true"
        class="mt-6"
      >
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th
                  v-for="col in tableField.columns"
                  :key="col.field"
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  {{ col.label || col.field }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr
                v-for="(row, index) in tableRows(tableField, 'new')"
                :key="`new-${index}`"
                class="hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <td
                  v-for="col in tableField.columns"
                  :key="col.field"
                  class="px-4 py-3 text-sm whitespace-pre-wrap break-all"
                  :class="model.action === 'update' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'"
                >
                  {{ formatTableCell(row[col.field], col) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Panel>
    </template>

    <div v-if="showRawData" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <Panel v-if="displayOldValues && Object.keys(displayOldValues).length" title="Old Values (Raw)" :divider="true">
        <pre class="text-xs overflow-x-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">{{ formatJson(displayOldValues) }}</pre>
      </Panel>
      <Panel v-if="displayNewValues && Object.keys(displayNewValues).length" title="New Values (Raw)" :divider="true">
        <pre class="text-xs overflow-x-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">{{ formatJson(displayNewValues) }}</pre>
      </Panel>
    </div>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading activity log..." centered />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDateTime as formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'settings/activity-logs'

const { model, show, setData, payload } = useShowable(resource, 'activity-log')

const activityFields = computed(() => payload.value?.activity_fields || null)
const displayOldValues = computed(() => payload.value?.display_old_values ?? model.value?.old_values ?? null)
const displayNewValues = computed(() => payload.value?.display_new_values ?? model.value?.new_values ?? null)

const formatAction = (action) => {
  if (!action) return '-'
  return action.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatModule = (type) => {
  if (!type) return '-'
  return type.replace(/_/g, ' ').replace(/-/g, ' ')
}

const actionBadgeClass = (action) => {
  const base = 'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full'
  const map = {
    create: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    update: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    delete: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    approve: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    bulk_import: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    printing: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    review: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
  }
  return `${base} ${map[action] || 'bg-gray-100 text-gray-800'}`
}

const formatJson = (value) => JSON.stringify(value, null, 2)

const formatValue = (value) => {
  if (value === null || value === undefined || value === '') return '-'
  if (Array.isArray(value)) return value.join(', ')
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

const fieldLabels = computed(() => {
  if (!activityFields.value?.length) return {}
  return Object.fromEntries(activityFields.value.map((f) => [f.field, f.label || f.field]))
})

const tableFields = computed(() => {
  if (!activityFields.value?.length) return []
  return activityFields.value.filter(
    (f) => f.type === 'table' && Array.isArray(f.columns) && f.columns.length
  )
})

const changeRows = computed(() => {
  if (!model.value) return []

  const oldValues = displayOldValues.value || model.value.old_values || {}
  const newValues = displayNewValues.value || model.value.new_values || {}
  const tableFieldKeys = new Set(tableFields.value.map((f) => f.field))

  if (activityFields.value?.length) {
    return activityFields.value
      .filter((f) => !tableFieldKeys.has(f.field) && (f.field in oldValues || f.field in newValues))
      .map((f) => ({
        field: f.label || f.field,
        oldValue: formatValue(oldValues[f.field]),
        newValue: formatValue(newValues[f.field]),
      }))
  }

  const fields = new Set([...Object.keys(oldValues), ...Object.keys(newValues)])

  return [...fields]
    .filter((field) => !tableFieldKeys.has(field))
    .map((field) => ({
      field: fieldLabels.value[field] || field,
      oldValue: formatValue(oldValues[field]),
      newValue: formatValue(newValues[field]),
    }))
})

const onlyChangedRows = computed(() => {
  if (model.value?.action !== 'update') return []
  return changeRows.value.filter((row) => row.oldValue !== row.newValue)
})

const tableRows = (tableField, side) => {
  const values = side === 'old'
    ? displayOldValues.value || model.value?.old_values || {}
    : displayNewValues.value || model.value?.new_values || {}
  const rows = values?.[tableField.field]
  return Array.isArray(rows) ? rows : []
}

const hasTableRows = (tableField, side) => tableRows(tableField, side).length > 0

const rowMatchKey = (row, tableField) => {
  if (!row || typeof row !== 'object') return JSON.stringify(row)
  if (row.id != null && row.id !== '') return `id:${row.id}`

  const dateCol = (tableField.columns || []).find((c) => c.type === 'date')
  if (dateCol && row[dateCol.field] != null && row[dateCol.field] !== '') {
    return `date:${String(row[dateCol.field])}`
  }

  const firstCol = tableField.columns?.[0]?.field
  if (firstCol && row[firstCol] != null && row[firstCol] !== '') {
    return `col:${String(row[firstCol])}`
  }

  return JSON.stringify(row)
}

const comparableRow = (row, tableField) => {
  const out = {}
  ;(tableField.columns || []).forEach((col) => {
    out[col.field] = row?.[col.field] ?? null
  })
  return out
}

const rowsEqual = (a, b, tableField) =>
  JSON.stringify(comparableRow(a, tableField)) === JSON.stringify(comparableRow(b, tableField))

const tableDiffRows = (tableField) => {
  const oldRows = tableRows(tableField, 'old')
  const newRows = tableRows(tableField, 'new')
  const oldMap = new Map()
  const newMap = new Map()

  oldRows.forEach((row) => oldMap.set(rowMatchKey(row, tableField), row))
  newRows.forEach((row) => newMap.set(rowMatchKey(row, tableField), row))

  const keys = [...new Set([...oldMap.keys(), ...newMap.keys()])]
  const diffs = []

  keys.forEach((key) => {
    const oldRow = oldMap.get(key) || null
    const newRow = newMap.get(key) || null
    if (rowsEqual(oldRow, newRow, tableField)) return

    const identity = {}
    ;(tableField.columns || []).forEach((col) => {
      identity[col.field] = newRow?.[col.field] ?? oldRow?.[col.field] ?? null
    })

    diffs.push({ key, oldRow, newRow, identity })
  })

  return diffs
}

const tableFieldsWithDiffs = computed(() => {
  if (model.value?.action !== 'update') return []
  return tableFields.value.filter((f) => tableDiffRows(f).length > 0)
})

const hasOnlyChanged = computed(() => onlyChangedRows.value.length > 0 || tableFieldsWithDiffs.value.length > 0)
const hasChanges = computed(() => changeRows.value.length > 0)

const showRawData = computed(() => {
  if (activityFields.value?.length) return false
  if (!model.value) return false
  const oldVals = displayOldValues.value || model.value.old_values
  const newVals = displayNewValues.value || model.value.new_values
  const hasOld = oldVals && Object.keys(oldVals).length
  const hasNew = newVals && Object.keys(newVals).length
  return hasOld || hasNew
})

const tableKeyColumns = (tableField) => {
  const columns = tableField.columns || []
  if (!columns.length) return []
  const dateCol = columns.find((c) => c.type === 'date')
  return [dateCol || columns[0]]
}

const tableValueColumns = (tableField) => {
  const keyFields = new Set(tableKeyColumns(tableField).map((c) => c.field))
  const valueCols = (tableField.columns || []).filter((c) => !keyFields.has(c.field))
  return valueCols.length ? valueCols : tableField.columns || []
}

const tableFieldTitle = (tableField, side) => {
  const label = tableField.label || tableField.field
  if (side === 'old' && model.value?.action === 'update') {
    return `${label} (Old Value)`
  }
  if (side === 'new' && model.value?.action === 'update' && hasTableRows(tableField, 'old')) {
    return `${label} (New Value)`
  }
  return label
}

const formatTableCell = (value, col) => {
  if (value === null || value === undefined || value === '') return '-'
  if (col?.type === 'date' && value) {
    return formatDate(value)
  }
  if (col?.type === 'number' && value !== '' && !Number.isNaN(Number(value))) {
    return Number(value).toLocaleString()
  }
  if (col?.type === 'array' && Array.isArray(value)) {
    return value.join(', ')
  }
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

defineExpose({ setData })
</script>
