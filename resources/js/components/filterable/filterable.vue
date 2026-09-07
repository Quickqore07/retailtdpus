<template>
  <div class="filterable space-y-3">
    <!-- Filters Panel -->
    <Panel padding="none">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <span class="font-medium text-base">{{ title || 'Users' }} match</span>
          <select v-model="query.filter_match" class="filterable-select">
            <option value="and">All</option>
            <option value="or">Any</option>
          </select>
          <span class="hidden sm:inline">of the following:</span>
        </div>
        <div class="flex items-center gap-2">
          <slot name="extra"></slot>
        </div>
      </div>

      <!-- Filter Rows -->
      <div class="space-y-3">
        <div v-for="(filter, index) in filterCandidates" :key="index"
          class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-start gap-2">
          <!-- Column Select -->
          <div class="flex-1 sm:min-w-[180px]">
            <select v-model="filter.column" @change="onColumnSelect(filter, index, $event)"
              class="filterable-select w-full">
              <option value="">Select a filter</option>
              <optgroup v-for="group in filterGroups" :key="group.title" :label="group.title">
                <option v-for="option in group.filters" :key="option.name" :value="JSON.stringify(option)">
                  {{ option.title || option.name }}
                </option>
              </optgroup>
            </select>
            <p v-if="errors[`f.${index}.column`]" class="mt-1 text-xs text-red-600 dark:text-red-400">
              {{ errors[`f.${index}.column`][0] }}
            </p>
          </div>

          <!-- Operator Select -->
          <div v-if="filter.columnObj" class="flex-1 sm:min-w-[130px]">
            <select v-model="filter.operator" @change="onOperatorSelect(filter, index, $event)"
              class="filterable-select w-full">
              <option v-for="op in getOperators(filter.columnObj)" :key="op.name" :value="JSON.stringify(op)">
                {{ op.label || op.name }}
              </option>
            </select>
            <p v-if="errors[`f.${index}.operator`]" class="mt-1 text-xs text-red-600 dark:text-red-400">
              {{ errors[`f.${index}.operator`][0] }}
            </p>
          </div>

          <!-- Filter Value Input -->
          <div v-if="filter.columnObj && filter.operatorObj" class="flex-1 sm:min-w-[180px]">
            <Input v-if="filter.operatorObj.component === 'single'" v-model="filter.query_1" type="text"
              :placeholder="filter.placeholder || 'Enter value'" size="sm"
              :error="errors[`f.${index}.query_1`] ? errors[`f.${index}.query_1`][0] : null" block />
            <Input v-else-if="filter.operatorObj.component === 'number'" v-model="filter.query_1" type="number"
              :placeholder="filter.placeholder || 'Enter number'" size="sm"
              :error="errors[`f.${index}.query_1`] ? errors[`f.${index}.query_1`][0] : null" block />
            <select v-else-if="filter.operatorObj.component === 'dropdown'" v-model="filter.query_1"
              class="filterable-select w-full">
              <option value="">{{ filter.placeholder || 'Select value' }}</option>
              <option v-for="option in filter.columnObj.options" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>

            <DynamicDropdown v-else-if="filter.operatorObj.component === 'lookup'" v-model="filter.query_1"
              :resource="filter.columnObj.resource" :display-name="filter.columnObj.column || 'name'"
              :placeholder="filter.placeholder || 'Select value'" :custom-options="filter.columnObj.options"
              :removable="true" :error="errors[`f.${index}.query_1`] ? errors[`f.${index}.query_1`][0] : null" />
            <div v-else-if="filter.operatorObj.component === 'dual'" class="flex gap-2">
              <Input v-model="filter.query_1" type="number" placeholder="From" size="sm"
                :error="errors[`f.${index}.query_1`] ? errors[`f.${index}.query_1`][0] : null" block />
              <Input v-model="filter.query_2" type="number" placeholder="To" size="sm"
                :error="errors[`f.${index}.query_2`] ? errors[`f.${index}.query_2`][0] : null" block />
            </div>
            <template v-else-if="filter.operatorObj.component === 'datetime_1'">
              <div class="flex gap-2">
                <div class="filters-query_1 w-full">
                  <input type="number" class="form-input" placeholder="Enter number" v-model="filter.query_1" />
                  <small class="error-control" v-if="errors[`f.${index}.query_1`]">
                    {{ errors[`f.${index}.query_1`][0] }}
                  </small>
                </div>
                <div class="filters-query_2 w-full">
                  <select class="form-input" placeholder="Select value" v-model="filter.query_2">
                    <option value="hours">Hours</option>
                    <option value="days">Days</option>
                    <option value="months">Months</option>
                    <option value="years">Years</option>
                  </select>
                  <small class="error-control" v-if="errors[`f.${index}.query_2`]">
                    {{ errors[`f.${index}.query_2`][0] }}
                  </small>
                </div>
              </div>
            </template>
            <template v-else-if="filter.operatorObj.component === 'datetime_2'">
              <div class="filters-query_2">
                <select class="form-input" v-model="filter.query_1">
                  <option value="yesterday">Yesterday</option>
                  <option value="today">Today</option>
                  <option value="tomorrow">Tomorrow</option>
                  <option value="last_month">Last Month</option>
                  <option value="this_month">This Month</option>
                  <option value="next_month">Next Month</option>
                  <option value="last_year">Last Year</option>
                  <option value="this_year">This Year</option>
                  <option value="next_year">Next Year</option>
                </select>
                <small class="error-control" v-if="errors[`f.${index}.query_1`]">
                  {{ errors[`f.${index}.query_1`][0] }}
                </small>
              </div>
            </template>
            <template v-else-if="filter.operatorObj.component === 'datetime_3'">
              <div class="filters-query_1">
                <input type="date" class="form-input" :placeholder="filter.placeholder" v-model="filter.query_1" />
                <small class="error-control" v-if="errors[`f.${index}.query_1`]">
                  {{ errors[`f.${index}.query_1`][0] }}
                </small>
              </div>
            </template>
            <template v-else-if="filter.operatorObj.component === 'datetime_4'">
              <div class="flex gap-2">

                <div class="filters-query_1 w-full">
                  <input type="number" class="form-input" :placeholder="filter.placeholder" v-model="filter.query_1" />
                  <small class="error-control" v-if="errors[`f.${index}.query_1`]">
                    {{ errors[`f.${index}.query_1`][0] }}
                  </small>
                </div>
                <div class="filters-query_2 w-full">
                  <select class="form-input" v-model="filter.query_2">
                    <option value="hours">Hours ago</option>
                    <option value="days">Days ago</option>
                    <option value="months">Months ago</option>
                    <option value="years">Years ago</option>
                  </select>
                  <small class="error-control" v-if="errors[`f.${index}.query_2`]">
                    {{ errors[`f.${index}.query_2`][0] }}
                  </small>
                </div>
              </div>

            </template>
            <template v-else-if="filter.operatorObj.component === 'datetime_5'">
              <div class="flex gap-2">
              <div class="filters-query_1 w-full">
                <input type="date" class="form-input" :placeholder="filter.placeholder" v-model="filter.query_1">
                  <small class="error-control" v-if="errors[`f.${index}.query_1`]">
                    {{ errors[`f.${index}.query_1`][0] }}
                  </small>
              </div>
              <div class="filters-query_2 w-full">
                <input type="date" class="form-input" :placeholder="filter.placeholder" v-model="filter.query_2">
                  <small class="error-control" v-if="errors[`f.${index}.query_2`]">
                      {{ errors[`f.${index}.query_2`][0] }}
                    </small>
                  </div>
              </div>
            </template>
          </div>

          <!-- Remove Filter Button -->
          <Button @click="removeFilter(index)" :disabled="loading" variant="danger" :icon-left="'trash'" icon-only
            size="sm" class="sm:self-start" />
        </div>
      </div>

      <!-- Filter Controls -->
      <div v-if="appliedFilters.length > 0 || filterCandidates.length > 0"
        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap items-center gap-2">
          <Button @click="addFilter" :disabled="loading" variant="outline" size="xs" :icon-left="'plus'" icon-size="xs">
            Add Filter
          </Button>
          <Button v-if="appliedFilters.length > 0" @click="resetFilters" :disabled="loading" variant="outline" size="xs"
            :icon-left="'refresh'" icon-size="xs">
            Reset
          </Button>
        </div>
        <Button @click="applyFilters" :disabled="loading" variant="outline" size="xs" :icon-left="'filter'"
          icon-size="xs" class="sm:ml-auto">
          Apply Filters
        </Button>
      </div>
    </Panel>

    <!-- Sorting and Controls -->
    <Panel>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 sm:hidden">Sort Options</span>
        <div class="flex items-center gap-2" v-if="totalValues.length > 0">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total: {{ totalValues.map(item => item.value).join(', ') }}</span>
        </div>
        <slot name="extra-controls"></slot>
        <div class="flex items-center gap-2 sm:ml-auto">
          <Input v-if="showSearch" v-model="query.search" @input="handleSearchInput" type="text" placeholder="Search" size="sm" block />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 hidden sm:inline">Order By</span>
          <select v-model="query.sort_column" @change="handleSort" :disabled="loading"
            class="filterable-select disabled:opacity-50 disabled:cursor-not-allowed flex-1 sm:flex-initial">
            <option v-for="col in sortableColumns" :key="col.value" :value="col.value">
              {{ col.label }}
            </option>
          </select>
          <Button @click="toggleSortDirection" :disabled="loading" variant="outline"
            :icon-left="query.sort_direction === 'desc' ? 'chevron-down' : 'chevron-up'" icon-only size="sm" />
        </div>
      </div>
    </Panel>

    <!-- Data Table -->
    <Panel padding="none" custom-class="overflow-hidden">
      <div v-if="loading" class="p-12 text-center">
        <Spinner size="md" text="Loading..." centered />
      </div>
      <div v-else>
        <div class="overflow-x-auto min-h-[400px]">
          <table class="table-wrapper">
            <thead class="table-head">
              <slot name="heading"></slot>
            </thead>
            <tbody class="table-body">
              <template v-if="collection.data && collection.data.length > 0">
                <slot v-for="(item, index) in collection.data" :key="item.id || index" :item="item" :index="index">
                </slot>
              </template>
              <tr v-else>
                <td colspan="100%" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                  No results found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="collection.data && collection.data.length > 0"
          class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <select v-model="query.limit" @change="updateLimit" :disabled="loading"
              class="filterable-select disabled:opacity-50 disabled:cursor-not-allowed">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
              Showing {{ collection.from || 0 }}-{{ collection.to || 0 }} of {{ collection.total || 0 }} entries
            </span>
          </div>
          <div class="flex items-center justify-center sm:justify-end gap-2 flex-wrap">
            <Button @click="prevPage" :disabled="!collection.prev_page_url || loading" variant="outline" size="xs">
              Prev
            </Button>
            <span class="px-2 text-sm text-gray-700 dark:text-gray-300">
              {{ collection.current_page || 1 }} / {{ collection.last_page || 1 }}
            </span>
            <Button @click="nextPage" :disabled="!collection.next_page_url || loading" variant="outline" size="xs">
              Next
            </Button>
            <div class="flex items-center gap-2 ml-2">
              <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">Go to:</span>
              <input 
                v-model.number="jumpToPageInput" 
                @keyup.enter="jumpToPage"
                type="number" 
                min="1" 
                :max="collection.last_page || 1"
                :disabled="loading"
                placeholder="Page"
                class="jump-to-page-input"
              />
              <Button @click="jumpToPage" :disabled="loading" variant="outline" size="xs">
                Go
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Panel>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRequest } from '@/services/api'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Panel from '@/components/ui/panel.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useMessage, useLoadingBar } from '@/composables/useMessage'
import { useRouter, useRoute } from 'vue-router'
const props = defineProps({
  title: {
    type: String,
    default: 'Filters'
  },
  url: {
    type: String,
    required: true
  },
  sortable: {
    type: Array,
    default: () => []
  },
  filterGroups: {
    type: Array,
    default: () => []
  },
  totalValues: {
    type: Array,
    default: () => []
  },
  showSearch: {
    type: Boolean,
    default: false
  },
  extraParams: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:data', 'update:collection'])
const message = useMessage()
const loadingBar = useLoadingBar()
const router = useRouter()
const route = useRoute()
// State
const loading = ref(false)
const collection = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  per_page: 25,
  prev_page_url: null,
  next_page_url: null
})
const allData = ref({})
const errors = ref({})
const query = ref({
  sort_column: 'id',
  sort_direction: 'desc',
  filter_match: 'and',
  search: '',
  limit: 25,
  page: 1
})
const filterCandidates = ref([])
const appliedFilters = ref([])
const jumpToPageInput = ref(null)
const SEARCH_DEBOUNCE_MS = 500
let searchDebounceTimeout = null

// Computed
const sortableColumns = computed(() => {
  if (!props.sortable || props.sortable.length === 0) {
    return [{ value: 'id', label: 'ID' }, { value: 'created_at', label: 'Created At' }]
  }
  return props.sortable.map(item => {
    if (typeof item === 'object') {
      return { value: item.value || item.name, label: item.label || item.title || item.value || item.name }
    }
    return { value: item, label: item }
  })
})

// Methods
const onColumnSelect = (filter, index, event) => {
  const value = event.target.value
  filter.column = value // Update v-model value
  if (!value) {
    filter.columnObj = null
    filter.operator = ''
    filter.operatorObj = null
    filter.query_1 = null
    filter.query_2 = null
    return
  }

  try {
    const columnObj = JSON.parse(value)
    filter.columnObj = columnObj

    // Set default operator based on column type
    const operators = getOperators(columnObj)
    if (operators.length > 0) {
      filter.operator = JSON.stringify(operators[0])
      filter.operatorObj = operators[0]
    } else {
      filter.operator = ''
      filter.operatorObj = null
    }

    // Reset query values
    filter.query_1 = null
    filter.query_2 = null
    filter.placeholder = columnObj.placeholder || 'Enter value'


    switch (columnObj.type) {
        case "numeric":
          filter.operator = JSON.stringify(availableOperators()[6]);  
          filter.operatorObj = availableOperators()[6];  
          filter.query_1 = null;
          filter.query_2 = null;
          break;
        case "lookup":
        case "lookup_only":
          filter.operator = JSON.stringify(availableOperators()[11]); 
          filter.operatorObj = availableOperators()[11];
          filter.query_1 = [];
          filter.query_2 = null;
          break;

        case "static_lookup":
          filter.operator = JSON.stringify(availableOperators()[21]);
          filter.operatorObj = availableOperators()[21];
          filter.query_1 = [];
          filter.query_2 = null;
          break;
        case "string":
          filter.operator = JSON.stringify(availableOperators()[8]);
          filter.operatorObj = availableOperators()[8];
          filter.query_1 = [];
          filter.query_2 = null;
          break;
        case "toggle":
          filter.operator = JSON.stringify(availableOperators()[23]);
          filter.operatorObj = availableOperators()[23];
          filter.query_1 = 1;
          filter.query_2 = null;
          break;
        case "datetime":
          filter.operator = JSON.stringify(availableOperators()[13]);
          filter.operatorObj = availableOperators()[13];
          filter.query_1 = 28;
          filter.query_2 = "days";
          break;
      }
  } catch (e) {
    console.error('Error parsing column:', e)
    filter.columnObj = null
  }
}

const onOperatorSelect = (filter, index, event) => {
  const value = event.target.value
  filter.operator = value // Update v-model value
  if (!value) {
    filter.operatorObj = null
    filter.query_1 = null
    filter.query_2 = null
    return
  }

  try {
    const operatorObj = JSON.parse(value)
    filter.operatorObj = operatorObj
    filter.query_1 = null
    filter.query_2 = null
  } catch (e) {
    console.error('Error parsing operator:', e)
    filter.operatorObj = null
  }
}

const availableOperators = () => {
  return [
    { name: "equal_to", label: "Equals", parent: ["numeric", "string"], component: "single" },
    { name: "not_equal_to", label: "Not Equals", parent: ["numeric", "string"], component: "single" },
    { name: "less_than", label: "Less Than", parent: ["numeric"], component: "single" },
    { name: "greater_than", label: "Greater Than", parent: ["numeric"], component: "single" },
    { name: "less_than_or_equal_to", label: "Less Than or Equal To", parent: ["numeric"], component: "single" },
    { name: "greater_than_or_equal_to", label: "Greater Than or Equal To", parent: ["numeric"], component: "single" },
    { name: "between", label: "Between", parent: ["numeric"], component: "dual" },
    { name: "not_between", label: "Not Between", parent: ["numeric"], component: "dual" },
    { name: "contains", label: "Contains", parent: ["string", "lookup"], component: "single" },
    { name: "starts_with", label: "Starts With", parent: ["string", "lookup"], component: "single" },
    { name: "ends_with", label: "Ends With", parent: ["string", "lookup"], component: "single" },
    { name: "includes", label: "Includes", parent: ["lookup", "lookup_only", "dropdown"], component: "lookup" },
    { name: "not_includes", label: "Not Includes", parent: ["lookup", "lookup_only", "dropdown"], component: "lookup" },
    { name: "in_the_past", label: "In The Past", parent: ["datetime"], component: "datetime_1" },
    { name: "in_the_next", label: "In The Next", parent: ["datetime"], component: "datetime_1" },
    { name: "over", label: "Over", parent: ["datetime"], component: "datetime_4" }, // same as in_the_past
    { name: "between_date", label: "Between Date", parent: ["datetime"], component: "datetime_5" },
    { name: "in_the_peroid", label: "In The Peroid", parent: ["datetime"], component: "datetime_2" },
    { name: "equal_to_date", label: "Equal To Date", parent: ["datetime"], component: "datetime_3" },
    {
      name: "is_empty",
      label: "Is Empty",
      parent: ["date", "numeric", "string", "datetime", "lookup"],
      component: "none",
    },
    {
      name: "is_not_empty",
      label: "Is Not Empty",
      parent: ["date", "numeric", "string", "datetime", "lookup"],
      component: "none",
    },
    { name: "includes", label: "Includes", parent: ["static_lookup"], component: "static_lookup" },
    { name: "not_includes", label: "Not Includes", parent: ["static_lookup"], component: "static_lookup" },
    { name: "toggle", label: "Toggle", parent: ["toggle"], component: "toggle" },
  ]
}

const getOperators = (column) => {
  if (!column || !column.type) return []

  const operators = availableOperators()
  return operators.filter(op => op.parent.includes(column.type))
}

const addFilter = () => {
  filterCandidates.value.push({
    column: '',
    columnObj: null,
    operator: '',
    operatorObj: null,
    query_1: null,
    query_2: null,
    placeholder: ''
  })
}

const removeFilter = (index) => {
  filterCandidates.value.splice(index, 1)
  if (filterCandidates.value.length === 0) {
    addFilter()
  }
}

const applyFilters = () => {
  appliedFilters.value = JSON.parse(JSON.stringify(filterCandidates.value))
  query.value.page = 1
  saveFiltersToStorage()
  fetch()
}

const resetFilters = () => {
  appliedFilters.value = []
  filterCandidates.value = []
  query.value.page = 1
  clearFiltersFromStorage()
  addFilter()
  fetch()
}

const buildQuery = () => {
  const params = {
    page: query.value.page,
    limit: query.value.limit,
    sort_column: query.value.sort_column,
    sort_direction: query.value.sort_direction,
    filter_match: query.value.filter_match,
    search: query.value.search
  }

  // Add filters
  appliedFilters.value.forEach((filter, index) => {
    if (filter.columnObj && filter.operatorObj) {
      params[`f[${index}][column]`] = filter.columnObj.name || filter.columnObj.value
      params[`f[${index}][operator]`] = filter.operatorObj.name

      // For lookup and lookup_only types, extract the ID from the selected object
      if ((filter.operatorObj.component === 'lookup' || filter.operatorObj.component === 'lookup_only') && filter.query_1 && typeof filter.query_1 === 'object') {
        params[`f[${index}][query_1]`] = filter.query_1.id
      } else {
        params[`f[${index}][query_1]`] = filter.query_1
      }

      if (filter.query_2 !== null && filter.query_2 !== undefined) {
        params[`f[${index}][query_2]`] = filter.query_2
      }
    }
  })

  return { ...params, ...props.extraParams }
}

const fetch = async () => {
  loadingBar.start()
  errors.value = {}

  try {
    const params = buildQuery()
    const response = await useRequest('get', `${props.url}`, undefined, { params })

    if (response.collection) {
      collection.value = response.collection
      query.value.page = response.collection.current_page || 1
      query.value.limit = response.collection.per_page || 25
    } else if (response.data?.collection) {
      collection.value = response.data.collection
      query.value.page = response.data.collection.current_page || 1
      query.value.limit = response.data.collection.per_page || 25
    }

    emit('update:data', response)
    emit('update:collection', collection.value)
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    message.error(error.response?.data?.message || 'An error occurred while fetching data')

    if (error.response?.status === 403) {
      router.go(-1)
    }
  } finally {
    loadingBar.finish()
  }
}

const setData = (response) => {
  if (response.data?.collection) {
    collection.value = response.data.collection
    query.value.page = response.data.collection.current_page || 1
    query.value.limit = response.data.collection.per_page || 25
  } else if (response.collection) {
    collection.value = response.collection
    query.value.page = response.collection.current_page || 1
    query.value.limit = response.collection.per_page || 25
  }
  emit('update:data', response)
  emit('update:collection', collection.value)
}

const setAllData = (data) => {
  allData.value = data || {}
}

const nextPage = () => {
  if (collection.value.next_page_url) {
    query.value.page += 1
    saveFiltersToStorage()
    fetch()
  }
}

const prevPage = () => {
  if (collection.value.prev_page_url) {
    query.value.page -= 1
    saveFiltersToStorage()
    fetch()
  }
}

const updateLimit = (event) => {
  query.value.limit = Number(event.target.value)
  query.value.page = 1
  saveFiltersToStorage()
  fetch()
}

const handleSort = () => {
  query.value.sort_direction = 'asc'
  query.value.page = 1
  saveFiltersToStorage()
  fetch()
}

const toggleSortDirection = () => {
  query.value.sort_direction = query.value.sort_direction === 'desc' ? 'asc' : 'desc'
  saveFiltersToStorage()
  fetch()
}

const handleSearchInput = () => {
  if (searchDebounceTimeout) {
    clearTimeout(searchDebounceTimeout)
  }

  searchDebounceTimeout = setTimeout(() => {
    query.value.page = 1
    saveFiltersToStorage()
    fetch()
  }, SEARCH_DEBOUNCE_MS)
}

const jumpToPage = () => {
  const targetPage = Number(jumpToPageInput.value)
  const lastPage = collection.value.last_page || 1
  
  if (!targetPage || targetPage < 1) {
    message.error('Please enter a valid page number')
    return
  }
  
  if (targetPage > lastPage) {
    message.error(`Page number cannot exceed ${lastPage}`)
    return
  }
  
  query.value.page = targetPage
  saveFiltersToStorage()
  fetch()
  jumpToPageInput.value = null // Clear input after jump
}

// Generate a unique storage key based on the current route
const getStorageKey = () => {
  if (Object.keys(route.query).length > 0) {
    return `filterable_${route.path}_${Object.keys(route.query).join('_')}_${Object.values(route.query).join('_')}`
  }
  return `filterable_${route.path}`
}

// Save filters to localStorage
const saveFiltersToStorage = () => {
  const filterState = {
    filters: appliedFilters.value,
    sort_column: query.value.sort_column,
    sort_direction: query.value.sort_direction,
    filter_match: query.value.filter_match,
    search: query.value.search,
    limit: query.value.limit,
    page: query.value.page,
    timestamp: Date.now() // Add timestamp for expiration check
  }
  
  try {
    localStorage.setItem(getStorageKey(), JSON.stringify(filterState))
  } catch (e) {
    console.error('Error saving filters to localStorage:', e)
  }
}

// Clear filters from localStorage
const clearFiltersFromStorage = () => {
  try {
    localStorage.removeItem(getStorageKey())
  } catch (e) {
    console.error('Error clearing filters from localStorage:', e)
  }
}

// Restore filters from localStorage
const restoreFiltersFromStorage = () => {
  try {
    const savedState = localStorage.getItem(getStorageKey())
    if (savedState) {
      const filterState = JSON.parse(savedState)
      
      // Check if the saved state has expired (1 hour = 3600000 milliseconds)
      const ONE_HOUR = 3600000
      if (filterState.timestamp && (Date.now() - filterState.timestamp > ONE_HOUR)) {
        // Data has expired, clear it and return
        clearFiltersFromStorage()
        return
      }
      
      if (filterState.filters && Array.isArray(filterState.filters) && filterState.filters.length > 0) {
        appliedFilters.value = filterState.filters
        filterCandidates.value = JSON.parse(JSON.stringify(filterState.filters))
      }
      
      if (filterState.sort_column) {
        query.value.sort_column = filterState.sort_column
      }
      
      if (filterState.sort_direction) {
        query.value.sort_direction = filterState.sort_direction
      }
      
      if (filterState.filter_match) {
        query.value.filter_match = filterState.filter_match
      }

      if (typeof filterState.search === 'string') {
        query.value.search = filterState.search
      }
      
      if (filterState.limit) {
        query.value.limit = Number(filterState.limit)
      }
      
      if (filterState.page) {
        query.value.page = Number(filterState.page)
      }
    }
  } catch (e) {
    console.error('Error restoring filters from localStorage:', e)
  }
}

// Initialize
onMounted(() => {
  if (props.sortable && props.sortable.length > 0) {
    const firstSortable = typeof props.sortable[0] === 'object'
      ? (props.sortable[0].value || props.sortable[0].name)
      : props.sortable[0]
    query.value.sort_column = firstSortable || 'id'
  }
  
  // Restore filters from localStorage if available
  restoreFiltersFromStorage()
  
  // Add a filter if none exist
  if (filterCandidates.value.length === 0) {
    addFilter()
  }
  
  fetch()
})

watch(
  () => props.extraParams,
  () => {
    query.value.page = 1
    fetch()
  },
  { deep: true }
)

onUnmounted(() => {
  if (searchDebounceTimeout) {
    clearTimeout(searchDebounceTimeout)
  }
})

// Get current params for export
const getCurrentParams = () => {
  return buildQuery()
}

// Expose methods for parent components
defineExpose({
  fetch,
  setData,
  setAllData,
  getCurrentParams,
  collection,
  query,
  allData
})
</script>

<style scoped>
.filterable-select {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background-color: #ffffff;
  color: #374151;
  outline: none;
  transition: all 0.15s ease-in-out;
}

.filterable-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filterable-select:hover {
  border-color: #9ca3af;
}

.dark .filterable-select {
  background-color: #1f2937;
  border-color: #374151;
  color: #e5e7eb;
}

.dark .filterable-select:focus {
  border-color: #3b82f6;
}

.dark .filterable-select:hover {
  border-color: #4b5563;
}

/* Jump to Page Input */
.jump-to-page-input {
  width: 70px;
  padding: 0.375rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background-color: #ffffff;
  color: #374151;
  outline: none;
  transition: all 0.15s ease-in-out;
  text-align: center;
}

.jump-to-page-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.jump-to-page-input:hover {
  border-color: #9ca3af;
}

.jump-to-page-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dark .jump-to-page-input {
  background-color: #1f2937;
  border-color: #374151;
  color: #e5e7eb;
}

.dark .jump-to-page-input:focus {
  border-color: #3b82f6;
}

.dark .jump-to-page-input:hover {
  border-color: #4b5563;
}

/* Remove number input arrows */
.jump-to-page-input::-webkit-inner-spin-button,
.jump-to-page-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.jump-to-page-input[type=number] {
  -moz-appearance: textfield;
}

/* Mobile Responsive Improvements */
@media (max-width: 640px) {
  .filterable {
    padding: 0;
  }

  .filterable-select {
    font-size: 0.8125rem;
    padding: 0.5rem;
  }

  /* Stack filter items vertically on mobile */
  .filterable :deep(.space-y-3) {
    gap: 1rem;
  }

  /* Ensure table scrolls horizontally on mobile */
  .overflow-x-auto {
    -webkit-overflow-scrolling: touch;
  }

  /* Adjust table text size on mobile */
  table {
    font-size: 0.875rem;
  }

  table th,
  table td {
    padding: 0.5rem 0.75rem;
  }
}

/* Tablet Responsive */
@media (min-width: 641px) and (max-width: 1024px) {
  .filterable-select {
    font-size: 0.875rem;
  }
}
</style>
