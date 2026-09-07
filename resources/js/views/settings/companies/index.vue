<template>
  <div class="companies-index">
    <Filterable
      ref="filterableRef"
      title="Companies"
      url="settings/companies"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
      :show-search="true"
    >
    <template #extra>
      <div class="flex items-center gap-2">
        <Button 
          v-if="access.includes('create')"
          icon-left="download" 
          icon-size="sm" 
          variant="secondary" 
          size="sm" 
          @click="downloadCompanyData"
          :disabled="isDownloading"
        >
          {{ isDownloading ? 'Downloading...' : 'Export Companies' }}
        </Button>
        <Button 
          v-if="access.includes('create')"
          icon-left="upload" 
          icon-size="sm" 
          variant="secondary" 
          size="sm" 
          @click="showUploadModal = true"
        >
          Import Companies
        </Button>
        <Button 
          v-if="access.includes('create')"
          icon-left="plus" 
          icon-size="sm" 
          variant="primary" 
          size="sm" 
          to="/settings/companies/create"
        >
          New Company
        </Button>
      </div>
    </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Name</Th>
          <Th>Store Number</Th>
          <Th>Workgroup</Th>
          <Th>State</Th>
          <Th>County</Th>
          <Th>Region</Th>
          <Th>Area</Th>
          <Th>Status</Th>
          <Th>Created At</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item , index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td weight="medium" color="primary">
            {{ item.name }}
          </Td>
          <Td color="secondary" weight="medium">
            {{ item.store_number }}
          </Td>
          <Td color="secondary">
            {{ item.workgroup?.name || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.state?.name || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.county?.name || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.region?.name || '-' }}
          </Td>
          <Td color="secondary">
            {{ item.area?.name || '-' }}
          </Td>
          <Td color="secondary">
            <span
              :class="[
                'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
                item.active 
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                  : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
              ]"
            >
              <SvgIcon 
                :name="item.active ? 'check' : 'x'" 
                size="xs" 
                class="mr-1" 
              />
              {{ item.active ? 'Active' : 'Inactive' }}
            </span>
          </Td>
          <Td color="secondary">
            {{ formatDate(item.created_at) }}
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <router-link
                v-if="access.includes('show')"
                :to="`/settings/companies/${item.id}`"
                class="text-blue-600 hover:text-blue-900 transition-colors"
                title="View"
              >
                <SvgIcon name="eye" size="lg" />
              </router-link>
              <router-link
                v-if="access.includes('update')"
                :to="`/settings/companies/${item.id}/edit`"
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

    <!-- File Upload Modal -->
    <FileUploadModal
      v-model="showUploadModal"
      title="Import Companies"
      upload-url="/settings/companies-import"
      :accepted-formats="'.xlsx,.xls'"
      :accepted-mime-types="['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']"
      :accepted-extensions="['xlsx', 'xls']"
      :allow-multiple="false"
      :show-instructions="true"
      instructions-title="Import Guidelines:"
      :instructions="[
        'Supported formats: Excel (.xlsx, .xls)',
        UPLOAD_MAX_SIZE_NOTE,
        'Use the template to ensure correct column structure',
        'Existing companies will be updated based on store number'
      ]"
      upload-text="Click to upload or drag and drop"
      format-text="XLSX, XLS"
      success-title="Import Successful"
      error-title="Import Failed"
      :axios="axios"
      @upload-success="handleUploadSuccess"
      @upload-error="handleUploadError"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'
import { useMessage } from '@/composables/useMessage'
import axios from 'axios'
import FileUploadModal from '@/components/common/FileUploadModal.vue'
import { UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'

const route = useRoute()
const resource = route.meta?.resource || 'companies'

// Use the useIndexable composable
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'company')

// Import functionality
const message = useMessage()
const isDownloading = ref(false)
const showUploadModal = ref(false)

// Sortable columns configuration
const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
  { value: 'store_number', label: 'Store Number' },
]

// Filter groups configuration
const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'name',
        title: 'Name',
        type: 'string',
        placeholder: 'Enter name'
      },
      {
        name: 'store_number',
        title: 'Store Number',
        type: 'string',
        placeholder: 'Enter store number'
      },
      {
        name: 'workgroup_id',
        type: 'lookup_only',
        resource: 'workgroups',
        column: 'name',
        title: 'Workgroup'
      },
      {
        name: 'state_id',
        type: 'lookup_only',
        resource: 'states',
        column: 'name',
        title: 'State'
      },
      {
        name: 'region_id',
        type: 'lookup_only',
        resource: 'regions',
        column: 'name',
        title: 'Region'
      },
      {
        name: 'county_id',
        type: 'lookup_only',
        resource: 'counties',
        column: 'name',
        title: 'County'
      },
      {
        name: 'area_id',
        type: 'lookup_only',
        resource: 'areas',
        column: 'name',
        title: 'Area'
      },
      {
        name: 'active',
        title: 'Status',
        type: 'boolean',
        placeholder: 'Select status'
      }
    ]
  },
  {
    title: 'Contact Information',
    filters: [
      {
        name: 'email',
        title: 'Email',
        type: 'string',
        placeholder: 'Enter email'
      },
      {
        name: 'contact_person',
        title: 'Contact Person',
        type: 'string',
        placeholder: 'Enter contact person'
      }
    ]
  },
  {
    title: 'Dates',
    filters: [
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

// Handle collection update from filterable component
const handleCollectionUpdate = (collection) => {
  
}

// Handle delete action
const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

// Download company data
const downloadCompanyData = async () => {
  try {
    isDownloading.value = true
    const response = await axios.get('/api/settings/companies-export', {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    
    // Get filename from response headers or use default
    const contentDisposition = response.headers['content-disposition']
    let filename = 'companies_export.xlsx'
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
      if (filenameMatch) {
        filename = filenameMatch[1]
      }
    }
    
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    message.success('Companies exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export companies')
  } finally {
    isDownloading.value = false
  }
}

// Handle upload success
const handleUploadSuccess = (data) => {
  const { imported, updated, errors } = data
  
  // Show success message
  let messageText = `Import completed! ${imported} created, ${updated} updated`
  if (errors && errors.length > 0) {
    messageText += `\n\nErrors (${errors.length}):\n${errors.slice(0, 5).join('\n')}`
    if (errors.length > 5) {
      messageText += `\n... and ${errors.length - 5} more errors`
    }
    message.warning(messageText)
  } else {
    message.success(messageText)
  }

  // Refresh the list
  if (filterableRef.value) {
    filterableRef.value.fetch()
  }
  
  // Close modal
  showUploadModal.value = false
}

// Handle upload error
const handleUploadError = (error) => {
  console.error('Import error:', error)
  const errorMessage = error.response?.data?.message || 'Failed to import companies'
  message.error(errorMessage)
}

// Expose setData for useIndexable route guards
defineExpose({
  setData
})
</script>

