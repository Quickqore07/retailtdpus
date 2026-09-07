<template>
  <div v-if="show" class="bank-category-rule-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !m-0">Bank Category Rule Details</h5>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
                <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                to="/settings/bank-category-rules">
                </Button>
                <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
                :to="`/settings/bank-category-rules/${model.id}/edit`">
                </Button>
                <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete')">
                </Button>
            </div>
          </div>
        </div>
      </template>

      <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Label label="Name" :value="model.name" />
          <Label label="Category Label" :value="model.label" />
          <Label label="Condition" :value="model.condition" />
          <Label label="Created At" :value="formatDate(model.created_at)" />
          <Label label="Updated At" :value="formatDate(model.updated_at)" />
          <Label label="Created By" :value="model.created_by?.name ?? 'N/A'" />
          <Label label="Updated By" :value="model.updated_by?.name ?? 'N/A'" />
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h6 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Conditions</h6>
          
          <div v-if="model.conditions && model.conditions.length > 0" class="space-y-3">
            <div
              v-for="(condition, index) in model.conditions"
              :key="condition.id"
              class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
            >
              <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full font-semibold text-sm">
                {{ index + 1 }}
              </div>
              <div class="flex-1 space-y-1">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ condition.condition_type }}</span>
                </div>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ condition.value }}</p>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <SvgIcon name="files" size="2xl" class="text-gray-400 dark:text-gray-600 mx-auto mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">No conditions defined</p>
          </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
          <div class="flex items-start gap-2">
            <SvgIcon name="info" size="md" class="text-blue-600 dark:text-blue-400 mt-0.5" />
            <div class="text-sm text-blue-800 dark:text-blue-300">
              <p class="font-medium mb-1">How this rule works:</p>
              <p>
                When a bank transaction description 
                <strong>{{ model.condition === 'All' ? 'matches ALL' : 'matches ANY' }}</strong> 
                of the conditions above, it will be automatically categorized as 
                <strong>"{{ model.label }}"</strong> in the bank report.
              </p>
            </div>
          </div>
        </div>
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Label from '@/components/ui/label.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/bank-category-rules'

const { model, show, access } = useShowable(resource, 'bank-category-rule')

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
@media (max-width: 640px) {
  .bank-category-rule-show {
    padding: 1rem;
  }
}
</style>
