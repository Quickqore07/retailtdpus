<template>
  <Modal 
    v-model="isOpen" 
    :title="title" 
    size="xl"
    :show-footer="true"
  >
    <div class="space-y-6">
      <!-- Success Summary -->
      <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <SvgIcon name="check-circle" size="lg" class="text-green-600 dark:text-green-400 mt-0.5" />
          <div class="flex-1">
            <p class="font-medium text-green-800 dark:text-green-300">{{ result.message }}</p>
            <p class="text-sm text-green-700 dark:text-green-400 mt-1">
              Total rows processed: <span class="font-semibold">{{ result.rows_processed }}</span>
              <span v-if="result.files_processed > 1"> · Files uploaded: <span class="font-semibold">{{ result.files_processed }}</span></span>
            </p>
          </div>
        </div>
      </div>

      <!-- New Employees Section -->
      <div v-if="result.new_employees && result.new_employees.length > 0" class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            New Employees Added ({{ result.new_employees.length }})
          </h3>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <div class="max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    #
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Employee ID
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Name
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr 
                  v-for="(employee, index) in result.new_employees" 
                  :key="index"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ index + 1 }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ employee.employee_id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                    {{ employee.name }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Existing Employees Section -->
      <div v-if="result.existing_employees && result.existing_employees.length > 0" class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Existing Employees Found ({{ result.existing_employees.length }})
          </h3>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <div class="max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    #
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Employee ID
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Name
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr 
                  v-for="(employee, index) in result.existing_employees" 
                  :key="index"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ index + 1 }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ employee.employee_id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                    {{ employee.name }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Missing Companies Warning -->
      <div v-if="result.missing_companies && result.missing_companies.length > 0" class="space-y-3">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <SvgIcon name="alert" size="lg" class="text-yellow-600 dark:text-yellow-400 mt-0.5" />
            <div class="flex-1">
              <p class="font-medium text-yellow-800 dark:text-yellow-300">
                Missing Companies ({{ result.missing_companies.length }})
              </p>
              <ul class="mt-2 text-sm text-yellow-700 dark:text-yellow-400 list-disc list-inside">
                <li v-for="(company, index) in result.missing_companies" :key="index">
                  {{ company }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- No New Employees Message -->
      <div v-if="(!result.new_employees || result.new_employees.length === 0) && (!result.existing_employees || result.existing_employees.length === 0)" class="text-center py-8">
        <div class="flex justify-center mb-4">
          <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
            <SvgIcon name="info" size="xl" class="text-blue-600 dark:text-blue-400" />
          </div>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
          No new or existing employees were processed in this upload.
        </p>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <Button 
        variant="primary" 
        size="sm"
        @click="handleClose"
      >
        Close
      </Button>
    </template>
  </Modal>
</template>

<script setup>
import { computed } from 'vue'
import Modal from './Modal.vue'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Upload Results'
  },
  result: {
    type: Object,
    default: () => ({
      message: '',
      uploaded: false,
      rows_processed: 0,
      missing_companies: [],
      new_employees: [],
      existing_employees: []
    })
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const handleClose = () => {
  isOpen.value = false
  emit('close')
}
</script>

