<template>
  <div v-if="show" class="pandl-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">P&L Configuration Details</h5>
          <div class="flex items-center gap-2">
            <Button
              icon-left="upload"
              icon-size="sm"
              variant="success"
              size="xs"
              @click="exportToExcel"
              :loading="exportLoading"
            >
              Export to Excel
            </Button>
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              to="/settings/pandl"
            />
            <Button
              v-if="access.includes('update')"
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              :to="`/settings/pandl/${model.id}/edit`"
            />
            <Button
              v-if="access.includes('delete')"
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              @click="handleDelete"
            />
          </div>
        </div>
      </template>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <Label label="Configuration Label" :value="model.label" />
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
        <Label label="Created By" :value="model.created_by?.name ?? 'N/A'" />
        <Label label="Updated By" :value="model.updated_by?.name ?? 'N/A'" />
      </div> 
      <div class="space-y-6">
        <div v-if="details?.income_items?.length" class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
          <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700 font-semibold text-blue-900 dark:text-blue-300">
            Income Items
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="text-left px-4 py-3 w-16">No</th>
                <th class="text-left px-4 py-3">Label</th>
                <th class="text-left px-4 py-3">Ledger Codes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, i) in details.income_items"
                :key="`income-${i}`"
                class="border-t border-gray-100 dark:border-gray-700"
              >
                <td class="px-4 py-3">{{ i + 1 }}</td>
                <td class="px-4 py-3 font-medium">{{ item.label }}</td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="(ledger, idx) in item.ledgers"
                      :key="`income-ledger-${i}-${idx}`"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                    >
                      {{ ledger }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="details?.cogs_items?.length" class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
          <div class="px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border-b border-gray-200 dark:border-gray-700 font-semibold text-yellow-900 dark:text-yellow-300">
            COGS Items
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="text-left px-4 py-3 w-16">No</th>
                <th class="text-left px-4 py-3">COGS Type</th>
                <th class="text-left px-4 py-3">Label</th>
                <th class="text-left px-4 py-3">Ledger Codes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, i) in details.cogs_items"
                :key="`cogs-${i}`"
                class="border-t border-gray-100 dark:border-gray-700"
              >
                <td class="px-4 py-3">{{ i + 1 }}</td>
                <td class="px-4 py-3">
                    {{ item.cogs_type }}
                </td>
                <td class="px-4 py-3 font-medium">{{ item.label }}</td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="(ledger, idx) in item.ledgers"
                      :key="`cogs-ledger-${i}-${idx}`"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300"
                    >
                      {{ ledger }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="details?.expense_items?.length" class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
          <div class="px-4 py-3 bg-red-50 dark:bg-red-900/20 border-b border-gray-200 dark:border-gray-700 font-semibold text-red-900 dark:text-red-300">
            Expense Items
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="text-left px-4 py-3 w-16">No</th>
                <th class="text-left px-4 py-3">Label</th>
                <th class="text-left px-4 py-3">Ledger Codes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, i) in details.expense_items"
                :key="`expense-${i}`"
                class="border-t border-gray-100 dark:border-gray-700"
              >
                <td class="px-4 py-3">{{ i + 1 }}</td>
                <td class="px-4 py-3 font-medium">{{ item.label }}</td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="(ledger, idx) in item.ledgers"
                      :key="`expense-ledger-${i}-${idx}`"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300"
                    >
                      {{ ledger }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading P&L configuration..." centered />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDateTime as formatDate } from '@/utils/date'
import { useMessage } from '@/composables/useMessage'

const route = useRoute()
const resource = route.meta?.resource || 'settings/pandl'
const message = useMessage()

const { model, show, setData, removeDB, access } = useShowable(resource, 'pandl')
const exportLoading = ref(false)

const details = computed(() => {
  if (!model.value) return null
  return model.value.ledgerDetails || {}
})

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

const exportToExcel = async () => {
  try {
    if (!model.value || !details.value) {
      message.error('No data to export')
      return
    }

    exportLoading.value = true

    await import('xlsx').then((XLSX) => {
      const wb = XLSX.utils.book_new()
      
      const summaryData = [
        ['P&L Configuration Details'],
        [],
        ['Configuration Label:', model.value.label || 'N/A'],
        ['Created At:', formatDate(model.value.created_at)],
        ['Updated At:', formatDate(model.value.updated_at)],
        ['Created By:', model.value.created_by?.name || 'N/A'],
        ['Updated By:', model.value.updated_by?.name || 'N/A'],
        ['Generated:', new Date().toLocaleString()],
        []
      ]
      
      if (details.value.income_items?.length) {
        summaryData.push(['Income Items'])
        summaryData.push(['No', 'Label', 'Ledger Codes'])
        details.value.income_items.forEach((item, i) => {
          summaryData.push([
            i + 1,
            item.label || '',
            (item.ledgers || []).join(', ')
          ])
        })
        summaryData.push([])
      }
      
      if (details.value.cogs_items?.length) {
        summaryData.push(['COGS Items'])
        summaryData.push(['No', 'COGS Type', 'Label', 'Ledger Codes'])
        details.value.cogs_items.forEach((item, i) => {
          summaryData.push([
            i + 1,
            item.cogs_type || '',
            item.label || '',
            (item.ledgers || []).join(', ')
          ])
        })
        summaryData.push([])
      }
      
      if (details.value.expense_items?.length) {
        summaryData.push(['Expense Items'])
        summaryData.push(['No', 'Label', 'Ledger Codes'])
        details.value.expense_items.forEach((item, i) => {
          summaryData.push([
            i + 1,
            item.label || '',
            (item.ledgers || []).join(', ')
          ])
        })
      }
      
      const ws = XLSX.utils.aoa_to_sheet(summaryData)
      
      ws['!cols'] = [
        { wch: 8 },
        { wch: 25 },
        { wch: 40 },
        { wch: 60 }
      ]
      
      XLSX.utils.book_append_sheet(wb, ws, 'Configuration')
      
      const filename = `PL_Configuration_${model.value.label?.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().getTime()}.xlsx`
      
      XLSX.writeFile(wb, filename)
      
      message.success('Configuration exported successfully!')
    }).catch((error) => {
      console.error('Failed to load XLSX library:', error)
      message.error('Failed to export configuration. Please try again.')
    })
  } catch (error) {
    console.error('Export error:', error)
    message.error('An error occurred while exporting the configuration')
  } finally {
    exportLoading.value = false
  }
}

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .pandl-show {
    padding: 1rem;
  }
}
</style>
