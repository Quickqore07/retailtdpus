<template>
  <div class="employee-inactive-index">
    <Filterable
      ref="filterableRef"
      title="Employee Inactive"
      url="payroll/employee-inactive"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
    >
      <template #extra>
        <div class="flex items-center gap-2">
          <Button 
            v-if="access.includes('delete') && selectedIds.length > 0"
            icon-left="trash" 
            icon-size="sm" 
            variant="danger" 
            size="sm" 
            @click="handleMultiDelete"
          >
            Delete Selected ({{ selectedIds.length }})
          </Button>
          <Button 
            v-if="access.includes('create')"
            icon-left="plus" 
            icon-size="sm" 
            variant="primary" 
            size="sm" 
            to="/payroll/employee-inactive/create"
          >
            New Entry
          </Button>
        </div>
      </template>
      
      <template #heading>
        <tr>
          <Th v-if="access.includes('delete')">
            <input 
              type="checkbox" 
              :checked="isAllSelected"
              @change="toggleSelectAll"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
            />
          </Th>
          <Th>No</Th>
          <Th>Employee ID</Th>
          <Th>Employee Name</Th>
          <Th>Company</Th>
          <Th>From Date</Th>
          <Th>To Date</Th>
          <Th>Duration (Days)</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td v-if="access.includes('delete')" color="default">
            <input 
              type="checkbox" 
              :checked="selectedIds.includes(item.id)"
              @change="toggleSelect(item.id)"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
            />
          </Td>
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td color="secondary">
            {{ item.employee?.employee_id || 'N/A' }}
          </Td>
          <Td weight="medium" color="primary">
            <a :href="`/employee/${item.employee?.id}`" target="_blank">
              {{ item.employee?.pos_name || 'N/A' }}
            </a>
          </Td>
          <Td color="secondary">
            {{ item.company?.store_number ? item.company?.store_number + ' - ' + item.company?.name : item.company?.name || 'N/A' }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.from_date) }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.to_date) }}
          </Td>
          <Td color="secondary">
            {{ calculateDuration(item.from_date, item.to_date) }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/payroll/employee-inactive/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/payroll/employee-inactive/${item.id}/edit`"
                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                title="Edit"
              >
                <SvgIcon name="edit" size="lg" />
              </router-link>
              <button
                v-if="access.includes('delete')"
                @click="handleDelete(item.id)"
                class="text-red-600 hover:text-red-900 transition-colors"
                title="Delete"
              >
                <SvgIcon name="trash" size="lg" />
              </button>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'payroll/employee-inactive'

// Use the useIndexable composable
const { filterableRef, setData, removeDB, removeMultipleDB, access } = useIndexable(resource, 'employee-inactive')

// Multi-select state
const selectedIds = ref([])
const collectionData = ref([])

// Computed property to check if all items are selected
const isAllSelected = computed(() => {
  return collectionData.value.length > 0 && 
         selectedIds.value.length === collectionData.value.length
})

// Sortable columns configuration
const sortableColumns = [
  { value: 'employee_id', label: 'Employee' },
  { value: 'company_id', label: 'Company' },
  { value: 'from_date', label: 'From Date' },
  { value: 'to_date', label: 'To Date' },
  { value: 'created_at', label: 'Created At' },
  { value: 'updated_at', label: 'Updated At' },
]

// Filter groups configuration
const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'employee_id',
        title: 'Employee',
        type: 'lookup_only',
        placeholder: 'Enter employee',
        resource: 'employees',
        column: 'pos_name',
      },
      {
        name: 'company_id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company'
      },
    ]
  },
  {
    title: 'Dates',
    filters: [
      {
        name: 'from_date',
        title: 'From Date',
        type: 'datetime',
        placeholder: 'Select from date'
      },
      {
        name: 'to_date',
        title: 'To Date',
        type: 'datetime',
        placeholder: 'Select to date'
      },
      {
        name: 'created_at',
        title: 'Created At',
        type: 'datetime',
        placeholder: 'Select date'
      },
      {
        name: 'updated_at',
        title: 'Updated At',
        type: 'datetime',
        placeholder: 'Select date'
      }
    ]
  }
]

// Calculate duration in days
const calculateDuration = (fromDate, toDate) => {
  if (!fromDate || !toDate) return 'N/A'
  const from = new Date(fromDate)
  const to = new Date(toDate)
  const diffTime = Math.abs(to - from)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return `${diffDays} days`
}

// Handle collection update from filterable component
const handleCollectionUpdate = (collection) => {
  collectionData.value = collection?.data || []
  // Clear selections when collection changes
  selectedIds.value = []
}

// Multi-select functions
const toggleSelect = (id) => {
  const index = selectedIds.value.indexOf(id)
  if (index > -1) {
    selectedIds.value.splice(index, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = collectionData.value.map(item => item.id)
  }
}

// Handle single delete action
const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
    // Remove from selected if it was selected
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    }
  }
}

// Handle multiple delete action
const handleMultiDelete = async () => {
  if (selectedIds.value.length === 0) {
    return
  }
  
  const success = await removeMultipleDB(resource, selectedIds.value)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
    selectedIds.value = []
  }
}

// Expose setData for useIndexable route guards
defineExpose({
  setData
})
</script>
