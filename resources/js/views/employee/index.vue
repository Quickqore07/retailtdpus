<template>
  <div class="employees-index" >
    <Filterable
      ref="filterableRef"
      :key="filterableUrl"
      :title="listTitle"
      :url="filterableUrl"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      @update:collection="handleCollectionUpdate"
      :show-search="true"
    >
    <template #extra>
      <div class="flex items-center gap-2">
        <Button
          v-if="access.includes('index')"
          icon-left="download"
          icon-size="sm"
          variant="secondary"
          size="sm"
          :disabled="isDownloading"
          @click="downloadExport"
        >
          {{ isDownloading ? 'Downloading...' : 'Export' }}
        </Button>
        <Button 
          v-if="access.includes('create')"
          icon-left="plus" 
          icon-size="sm" 
          variant="primary" 
          size="sm" 
          :to="createEmployeePath"
        >
          New Employee
        </Button>
      </div>
    </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Company</Th>
          <Th>Employee ID</Th>
          <Th>Name</Th>
          <Th v-if="employeeType=='New' ||  employeeType=='Existing' || isPendingI9W4List">Onboarding Status</Th>
          <Th v-if="isPendingI9W4List">I-9 (Workbright)</Th>
          <Th v-if="isPendingI9W4List">W-4 (Workbright)</Th>
          <Th v-if="isPendingI9W4List">I-9</Th>
          <Th v-if="isPendingI9W4List">W-4</Th>
          <Th v-if="isMissingProfilePictureList">Profile photo</Th>
          <Th>Hire Date</Th>
          <Th>Created At</Th>
          <Th v-if="isPendingI9W4List">Documents Count</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item , index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td color="secondary">
            <div class="flex flex-wrap gap-2 max-w-[300px]  whitespace-normal break-words ">
              {{ renderUniqueCompanies([...item.employee_rates, ...item.employee_rates_requests]) }} 
            </div>
          </Td>
          <Td color="secondary">
            {{ [item.employee_id, ...(item.aliases || []).map(a => a.alias_employee_id)].filter(Boolean).join(' / ') }}
          </Td>
          <Td weight="medium" color="primary">
            {{ [item.pos_name, ...(item.aliases || []).map(a => a.alias_name)].filter(Boolean).join(' / ') }} <small v-if="item.rejected">(Rejected)</small> 
            <p class="text-red-500 text-sm !mb-0" v-if="item.rejected"> {{ item.rejection_reason }}</p>
          </Td>
          <Td color="secondary" v-if="employeeType=='New' ||  employeeType=='Existing' || isPendingI9W4List">
            {{ renderOnboardingStatus(item.onboarding_status) }}
          </Td>
          <Td v-if="isPendingI9W4List" color="secondary">
           <span class="text-amber-600" :class="workbrightI9StatusClass(item.onboarding_list)"> {{ renderI9StatusWorkbright(item.onboarding_list) }}</span>
          </Td>
          <Td v-if="isPendingI9W4List" color="secondary">
            <span class="text-amber-600" :class="workbrightW4StatusClass(item.onboarding_list)"> {{ renderW4StatusWorkbright(item.onboarding_list) }}</span>
          </Td>
          <Td v-if="isPendingI9W4List" color="secondary">
            <span
              :class="item.employee_documents.some(document => document.document_type === 'i9_form') ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500'"
            >
              {{ item.employee_documents.some(document => document.document_type === 'i9_form') ? 'Completed' : 'Pending' }}
            </span>
          </Td>
          <Td v-if="isPendingI9W4List" color="secondary">
            <span
              :class="item.employee_documents.some(document => document.document_type === 'w4_form') ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500'"
            >
              {{ item.employee_documents.some(document => document.document_type === 'w4_form') ? 'Completed' : 'Pending' }}
            </span>
          </Td>
          <Td v-if="isMissingProfilePictureList" color="secondary">
            <span
            :class="hasProfilePhoto(item) ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500'"
            >
            {{ hasProfilePhoto(item) ? 'Completed' : 'Pending' }}
          </span>
        </Td>
        <Td color="secondary">
          {{ formatDate(item.hire_date) }}
        </Td>
        <Td color="secondary">
          {{ formatDate(item.created_at) }}
        </Td>
        <Td v-if="isPendingI9W4List" color="secondary">
          {{ item.employee_documents.length }}
        </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <Button
                v-if="isPendingI9W4List || isMissingProfilePictureList"
                type="button"
                variant="secondary"
                size="sm"
                icon-left="upload"
                :disabled="item.has_i9_document && item.has_w4_document && hasProfilePhoto(item)"
                @click="openPendingDocsUploadModal(item)"
              >
                Upload
              </Button>
              <IconMenuDropdown title="Actions">
                <template #default="{ close }">
                  <button
                    v-if="can('employee', 'send-workbright-email') && item.onboarding_list && !item.onboarding_list.work_bright_employee_id"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left !text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="handleSendWorkbrightEmail(item.onboarding_list.id)"
                  >
                    <SvgIcon v-if="!workbrightEmailLoading" name="mail" size="sm" />
                    <Spinner v-if="workbrightEmailLoading" size="sm" />
                    Send Workbright email
                  </button>
                  <button
                    v-if="item.employee_id && !item.employee?.mail_sent && checkHasPayrollRate(item.employee_rates_requests,item.employee_rates) && employeeType !='Existing'"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left !text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="
                      openSendEmailModal(item.id);
                      close()
                    "
                  >
                    <SvgIcon name="mail" size="sm" />
                    Send onboarding email
                  </button>
                  <router-link
                    v-if="access.includes('show')"
                    :to="`/${path}/${item.id}`"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="close()"
                  >
                    <SvgIcon name="eye" size="sm" />
                    View
                  </router-link>
                  <router-link
                    v-if="access.includes('documents-show')"
                    :to="`/employee/documents/${item.id}`"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="close()"
                  >
                    <SvgIcon name="files" size="sm" />
                    Documents
                  </router-link>
                  <router-link
                    v-if="access.includes('update')"
                    :to="`/${path}/${item.id}/edit`"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="close()"
                  >
                    <SvgIcon name="edit" size="sm" />
                    Edit
                  </router-link>
                  <button
                    v-if="access.includes('update') && item.active && can('employee', 'inactive')"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                    role="menuitem"
                    @click="
                      handleInactive(item.id);
                      close()
                    "
                  >
                    <SvgIcon name="x-circle" size="sm" />
                    Inactive employee
                  </button>
                  <button
                    v-if="access.includes('delete')"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                    role="menuitem"
                    @click="
                      handleDelete(item.id);
                      close()
                    "
                  >
                    <SvgIcon name="trash" size="sm" />
                    Delete
                  </button>
                </template>
              </IconMenuDropdown>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
    <Modal
        v-model="sendEmailModalVisible"
        size="5xl"
        :show-footer="true"
        :show-cancel="true"
        :show-confirm="true"
        cancel-text="Cancel"
        confirm-text="Send Email"
        @close="closeSendEmailModal"
        @confirm="confirmSendEmail"
        :loading="sendEmailLoading"
      >
        <template #header>
          <h5 class="text-xl font-bold !mb-0">Preview Onboarding Email</h5>
        </template>
        <div v-if="sendEmailModalLoading" class="py-12 flex justify-center">
          <Spinner size="md" text="Loading employee details..." centered />
        </div>
        <div v-else-if="sendEmailModalEmployee" class="space-y-4">
          <Input
            v-model="sendEmailFormEmail"
            label="Send to email"
            type="email"
            placeholder="Enter email address"
            icon-left="mail"
            :error="sendEmailEmailError"
            
          />
          <div class="max-h-[70vh] overflow-y-auto">
            <EmployeeShow
              :embedded="true"
              :employee="sendEmailModalEmployee"
            />
          </div>
        </div>
      </Modal>
    <PendingDocsUploadModal
      v-model="pendingDocsModalVisible"
      :employee="pendingDocsEmployee"
      @uploaded="onPendingDocsUploaded"
    />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { formatDate } from '@/utils/date'
import { watch, ref, computed } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Spinner from '@/components/ui/spinner.vue'
import Input from '@/components/ui/input.vue'
import EmployeeShow from '@/views/employee/show.vue'
import PendingDocsUploadModal from '@/views/employee/components/PendingDocsUploadModal.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission' 

const { can } = usePermission()
const message = useMessage()
const sendEmailLoading = ref(false)
const workbrightEmailLoading = ref(false)
const isDownloading = ref(false)
const route = useRoute()
const resource = route.meta?.resource || 'employee'

function onboardingListPath(p) {
  if (p.includes('missing-profile-picture')) return 'onboarding/employee/missing-profile-picture'
  if (p.includes('pending-i9-w4')) return 'onboarding/employee/pending-i9-w4'
  if (p.includes('onboarding/employee/existing')) return 'onboarding/employee/existing'
  if (p.includes('onboarding/employee/new')) return 'onboarding/employee/new'
  return 'employee'
}

function employeeTypeFromPath(p) {
  if (p.includes('missing-profile-picture')) return 'MissingProfilePicture'
  if (p.includes('pending-i9-w4')) return 'PendingI9W4'
  if (p.includes('onboarding/employee/existing')) return 'Existing'
  if (p.includes('onboarding/employee/new')) return 'New'
  return 'Completed'
}

const employeeType = ref(employeeTypeFromPath(route.path))
const path = ref(onboardingListPath(route.path))
const isPendingI9W4List = computed(() => route.path.includes('pending-i9-w4'))
const isMissingProfilePictureList = computed(() => route.path.includes('missing-profile-picture'))

const listTitle = computed(() => {
  if (isPendingI9W4List.value) return 'Pending I-9 / W-4'
  if (isMissingProfilePictureList.value) return 'Missing profile photo'
  return 'Employees'
})

function hasProfilePhoto(item) {
  const pic = item?.profile_picture
  if (pic != null && String(pic).trim() !== '') return true
  return !!item?.has_profile_picture_document
}

const createEmployeePath = computed(() => {
  if (isPendingI9W4List.value || isMissingProfilePictureList.value) {
    return '/onboarding/employee/new/create'
  }
  if (path.value !== 'employee') return `/${path.value}/create`
  return '/employee/create'
})

// Computed URL that updates when employeeType changes
const filterableUrl = computed(() => `employee?employee_type=${employeeType.value}`)

// Use the useIndexable composable
const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'employee')

// Sortable columns configuration
const sortableColumns = [
  { value: 'created_at', label: 'Created At' },
  { value: 'hire_date', label: 'Hire Date' },
  { value: 'updated_at', label: 'Updated At' },
  { value: 'name', label: 'Name' },
  { value: 'code', label: 'Code' },
]

const sendEmailModalVisible = ref(false)
const sendEmailModalEmployee = ref(null)
const sendEmailModalLoading = ref(false)
const sendEmailSelectedId = ref(null)
const sendEmailFormEmail = ref('')
const sendEmailEmailError = ref(null)
const pendingDocsModalVisible = ref(false)
const pendingDocsEmployee = ref(null)

const closeSendEmailModal = () => {
    sendEmailModalVisible.value = false
    sendEmailModalEmployee.value = null
    sendEmailSelectedId.value = null
    sendEmailFormEmail.value = ''
    sendEmailEmailError.value = null
  }


  
  const confirmSendEmail = async () => {
    const id = sendEmailSelectedId.value
    if (!id) return
    const email = sendEmailFormEmail.value?.trim() ?? ''
    sendEmailEmailError.value = null
    if (!email) {
      sendEmailEmailError.value = 'Email is required'
      return
    }
    sendEmailLoading.value = true
    try {
      const success = await useRequest('post', 'onboarding/send-mail-to-employee', {
        employee_id: id,
        email,
      })
      if (success) {
        message.success('Onboarding email sent successfully')
        closeSendEmailModal()
        if (filterableRef.value) filterableRef.value.fetch()
      } else {
        message.error(success.message || 'Failed to send onboarding email')
      }
    } catch (error) {
      message.error(error?.response?.data?.message || 'Failed to send onboarding email')
    } finally {
      sendEmailLoading.value = false
    }
  }

const workbrightI9StatusClass = computed(() => {
  return (onboardingList) => {
    return onboardingList.i9_rejected ? 'text-red-600' : onboardingList.i9_approved ? 'text-green-600' : onboardingList.i9_completed ? 'text-green-600' : 'text-amber-600'
  }
})

const workbrightW4StatusClass = computed(() => {
  return (onboardingList) => {
    return onboardingList.w4_rejected ? 'text-red-600' : onboardingList.w4_approved ? 'text-green-600' : onboardingList.w4_completed ? 'text-green-600' : 'text-amber-600'
  }
})
const renderI9StatusWorkbright = computed(() => {
  return (onboardingList) => {
    if(onboardingList.i9_rejected){
      return 'Rejected'
    }
    if(onboardingList.i9_approved){
      return 'Approved'
    }
    if(onboardingList.i9_completed){
      return 'Completed'
    }
    return 'Pending'
  }
})

const renderW4StatusWorkbright = computed(() => {
  return (onboardingList) => {
    if(onboardingList.w4_rejected){
      return 'Rejected'
    }
    if(onboardingList.w4_approved){
      return 'Approved'
    }
    if(onboardingList.w4_completed){
      return 'Completed'
    }
    return 'Pending'
  }
})
const openPendingDocsUploadModal = (employee) => {
  pendingDocsEmployee.value = employee
  pendingDocsModalVisible.value = true
}

const onPendingDocsUploaded = () => {
  pendingDocsEmployee.value = null
  if (filterableRef.value) filterableRef.value.fetch()
}

const openSendEmailModal = async (employeeId) => {
    sendEmailSelectedId.value = employeeId
    sendEmailModalVisible.value = true
    sendEmailModalEmployee.value = null
    sendEmailFormEmail.value = ''
    sendEmailEmailError.value = null
    sendEmailModalLoading.value = true
    try {
      const res = await useRequest('get', `employee/${employeeId}`)
      const model = res?.data?.model || res?.model || res
      sendEmailModalEmployee.value = model
      sendEmailFormEmail.value = model?.email ?? ''
    } catch (error) {
      message.error('Failed to load employee details')
      sendEmailModalVisible.value = false
    } finally {
      sendEmailModalLoading.value = false
    }
  }

const renderUniqueCompanies = computed(() => {
  return (item) => {
    if (!item?.length) return 'N/A'

    const companies = item.map(rate => {
        const company = rate?.company
        if (!company) return null

        return company.name
      })
      .filter(Boolean)

    return [...new Set(companies)].join(', ')
  }
})

const renderOnboardingStatus = computed(() => {
  return (status) => {
    let str = status.replace(/_/g, ' ')
    return str.charAt(0).toUpperCase() + str.slice(1)
  }
})

const checkHasPayrollRate = computed(() => {
  return (employeeRatesRequests,employeeRates) => {
    if(employeeRates.some(rate => rate.rate_type.includes('Payroll')) || employeeRatesRequests.some(rate => rate.rate_type.includes('Payroll'))){
      return true
    }
    return false
  }
})

const checkRejectedRateRequest = computed(() => {
  return (employeeRatesRequests) => {
    return employeeRatesRequests.some(rate => rate.status === 'rejected')
  }
})

// Filter groups configuration
const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'pos_name',
        title: 'Name',
        type: 'string',
        placeholder: 'Enter employee name'
      },
      {
        name: 'ssn',
        title: 'SSN',
        type: 'string',
        placeholder: 'Enter employee SSN'
      },
      {
        name: 'employee_id',
        title: 'Employee ID',
        type: 'string',
        placeholder: 'Enter employee ID'
      },
      {
        name: 'company_id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company'
      },
     
      {
        name: 'active',
        title: 'Status',
        type: 'dropdown',
        placeholder: 'Select status',
        column: 'label',
        options: [
          { id: 1, label: 'Active' },
          { id: 0, label: 'Inactive' },
        ]
      },
      {
        name: 'hire_date',
        title: 'Hire Date',
        type: 'datetime',
        placeholder: 'Select hire date'
      },
    ]
  },
  // {
  //   title: 'Status',
  //   filters: [
  //     {
  //       name: 'active',
  //       title: 'Status',
  //       type: 'boolean',
  //       placeholder: 'Select status'
  //     }
  //   ]
  // },
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

const handleSendWorkbrightEmail = async (onboardingListId) => {
  workbrightEmailLoading.value = true
  try {
    const success = await useRequest('post', 'onboarding/send-workbright-email', {
      onboarding_list_id: onboardingListId
    })
    if (success) {
      message.success('Workbright email sent successfully')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to send Workbright email')
  } finally {
    workbrightEmailLoading.value = false
  }
}

const downloadExport = async () => {
  try {
    isDownloading.value = true
    const currentParams = filterableRef.value?.getCurrentParams() || {}
    currentParams.employee_type = employeeType.value
    const response = await useRequest('get', '/employee-export', null, {
      params: currentParams,
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'employees_export.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Employees exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    message.error('Failed to export employees')
  } finally {
    isDownloading.value = false
  }
}

// Handle delete action
const handleDelete = async (id) => {
  const success = await removeDB(resource, id)
  if (success && filterableRef.value) {
    filterableRef.value.fetch()
  }
}

const handleInactive = async (id) => {
  const confirmed = confirm('Are you sure you want to inactive this employee?')
  if (!confirmed) return

  try {
    const res = await useRequest('post', `employee/${id}/inactive`)
    if (res.inactive) {
      message.success(res.message || 'Employee inactivated successfully')
      if (filterableRef.value) filterableRef.value.fetch()
    } else {
      message.error(res.message || 'Failed to inactive employee')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to inactive employee')
  }
}

// Watch for route changes and update employee type
watch(() => route.path, (newPath) => {
  const newType = employeeTypeFromPath(newPath)
  const newPathBase = onboardingListPath(newPath)
  if (newType !== employeeType.value) {
    employeeType.value = newType
  }
  if (newPathBase !== path.value) {
    path.value = newPathBase
  }
}, { immediate: false })
// Expose setData for useIndexable route guards
defineExpose({
  setData
})
</script>

