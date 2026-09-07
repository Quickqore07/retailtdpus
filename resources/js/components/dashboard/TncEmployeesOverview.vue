<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 mb-6 sm:mb-8">
    <div class="flex items-center justify-between gap-3 mb-3">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-white">TNC Employees</h4>
      <span class="text-sm text-gray-500 dark:text-gray-400">Total: {{ collection.total || rows.length }}</span>
    </div>

    <Input
      v-model="searchInput"
      placeholder="Search employee / ID / email"
      :disabled="loading"
      icon-left="search"
      class="mb-2"
    />

    <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">
      Loading TNC employees...
    </div>

    <div v-else-if="rows.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
      No TNC employees found.
    </div>

    <div class="overflow-x-auto" v-else>
      <table class="min-w-[700px] text-sm text-left text-gray-600 dark:text-gray-300">
        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
          <tr>
            <th class="py-2 pr-4">Employee ID</th>
            <th class="py-2 pr-4">Employee</th>
            <th class="py-2 pr-4">Email</th>
            <th class="py-2 pr-4">Company</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in rows"
            :key="item.id"
            class="border-b border-gray-100 dark:border-gray-700 last:border-b-0"
          >
            <td class="py-2 pr-4">{{ item.employee_id || '-' }}</td>
            <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">{{ item.pos_name || '-' }}</td>
            <td class="py-2 pr-4">{{ item.email || '-' }}</td>
            <td class="py-2 pr-4">{{ item.company?.name || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700" v-if="rows?.length">
      <Pagination
        :collection="collection"
        :loading="loading"
        :limit="collection.per_page || 25"
        @page-change="onPageChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Input from '@/components/ui/input.vue'
import Pagination from '@/components/ui/pagination.vue'

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  collection: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      from: 0,
      to: 0,
      total: 0,
      per_page: 25,
      has_prev: false,
      has_next: false,
    }),
  },
})

const emit = defineEmits(['page-change', 'search-change'])
const searchInput = ref('')
let searchTimer = null

watch(
  () => searchInput.value,
  (value) => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
      emit('search-change', value || '')
    }, 600)
  }
)

function onPageChange(page, limit) {
  emit('page-change', page, limit)
}
</script>
