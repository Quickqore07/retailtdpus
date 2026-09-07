<template>
    <div class="pending-applications-index" >
      <!-- Send Email Modal -->
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
      <Modal
        v-model="rejectModalVisible"
        size="md"
        :show-footer="true"
        :show-cancel="true"
        :show-confirm="true"
        cancel-text="Cancel"
        confirm-text="Reject Application"
        @close="closeRejectModal"
        @confirm="confirmRejectApplication"
        :loading="rejectLoading"
      >
        <template #header>
          <h5 class="text-xl font-bold !mb-0">Reject Application</h5>
        </template>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Reject Reason</label>
          <textarea
            v-model="rejectReason"
            rows="4"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"
            placeholder="Enter reject reason"
          />
          <p v-if="rejectReasonError" class="text-sm text-red-600">{{ rejectReasonError }}</p>
        </div>
      </Modal>

      <Filterable
        ref="filterableRef"
        title="Pending Applications"
        url="onboarding/pending-applications"
        :sortable="sortableColumns"
        :filter-groups="filterGroups"
        @update:collection="handleCollectionUpdate"
      >
        <template #heading>
          <tr>
            <Th>No</Th>
            <Th>Onboarding #</Th>
            <Th>Company</Th>
            <Th>Applicant Name</Th>
            <Th>Email</Th>
            <Th>Status</Th>
            <Th>Employee ID</Th>
            <Th>Submitted At</Th>
            <Th align="right">Actions</Th>
          </tr>
        </template>
        <template #default="{ item , index }">
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
            <Td color="default">
              {{ index + 1 }}
            </Td>
            <Td color="secondary" weight="medium">
              {{ item.onboarding_number || 'N/A' }}
            </Td>
            <Td color="secondary">
              {{ item.company?.name || 'N/A' }}
            </Td>
            <Td weight="medium" color="primary">
                <a  target="_blank" :href="`/employee/${item.employee?.id}`">{{ item.employee?.pos_name || 'N/A' }}</a>
            </Td>
            <Td color="secondary">
              {{ item.applicant_email || 'N/A' }}
            </Td>
            <Td color="secondary">
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getProcessStatusClass(item.process_id)"
              >
                {{ getProcessStatusLabel(item.process_id) }}
              </span>
            </Td>
            <Td color="secondary">
              {{ item.employee?.employee_id || 'Pending' }}
            </Td>
            <Td color="secondary">
              {{ formatDate(item.submit_application_date || item.created_at) }}
            </Td>
            <Td align="right" weight="medium">
              <div class="flex items-center justify-end gap-2">
                
                <router-link
                  v-if="access.includes('show')"
                  :to="`/onboarding/i9-review/${item.id}`"
                  class="text-blue-600 hover:text-blue-900 transition-colors"
                  title="View Application"
                >
                  <SvgIcon name="eye" size="lg" />
                </router-link>
                <IconMenuDropdown
                  v-if="item.employee_id"
                  title="Actions"
                >
                  <template #default="{ close }">
                    <button
                    v-if="role === 'admin'"
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 disabled:opacity-50"
                      role="menuitem"
                      :disabled="moveToEmployeeLoading"
                      @click="moveToEmployee(item.id)"
                    >
                      <SvgIcon name="user" size="sm" />
                      Move to employee
                    </button>
                    <button
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                      role="menuitem"
                      @click="
                        openSendEmailModal(item.employee_id);
                        close()
                      "
                    >
                      <SvgIcon name="mail" size="sm" />
                      Send Email
                    </button>
                    <button
                      v-if="access.includes('reject')"
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:text-red-400 dark:hover:bg-gray-700"
                      role="menuitem"
                      @click="
                        openRejectModal(item.id);
                        close()
                      "
                    >
                      <SvgIcon name="x-circle" size="sm" />
                      Reject Application
                    </button>
                  </template>
                </IconMenuDropdown>
                <!-- <button
                  v-if="access.includes('delete')"
                  @click="handleDelete(item.id)"
                  class="text-red-600 hover:text-red-900 transition-colors"
                  title="Delete"
                >
                  <SvgIcon name="trash" size="lg" />
                </button> -->
  
              </div>
            </Td>
          </tr>
        </template>
      </Filterable>
    </div>
  </template>
  
  <script setup>
  import { useRoute } from 'vue-router'
  import Filterable from '@/components/filterable/filterable.vue'
  import { useIndexable } from '@/composables/useIndexable'
  import SvgIcon from '@/components/SvgIcon.vue'
  import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
  import Td from '@/components/ui/td.vue'
  import Th from '@/components/ui/th.vue'
  import { formatDate } from '@/utils/date'
  import { useRequest } from '@/services/api'
  import { useMessage } from '@/composables/useMessage'
  import { watch, ref, computed } from 'vue'
  import Modal from '@/components/common/Modal.vue'
  import Spinner from '@/components/ui/spinner.vue'
  import Input from '@/components/ui/input.vue'
  import EmployeeShow from '@/views/employee/show.vue'
  import { useAuthStore } from '@/stores/auth'
  const message = useMessage()
  const sendEmailLoading = ref(false)
  const sendEmailModalVisible = ref(false)
  const sendEmailModalEmployee = ref(null)
  const sendEmailModalLoading = ref(false)
  const sendEmailSelectedId = ref(null)
  const sendEmailFormEmail = ref('')
  const sendEmailEmailError = ref(null)
  const rejectLoading = ref(false)
  const rejectModalVisible = ref(false)
  const rejectSelectedId = ref(null)
  const rejectReason = ref('')
  const rejectReasonError = ref(null)
  const moveToEmployeeLoading = ref(false)
  const authStore = useAuthStore()

  const role = computed(() => authStore.user?.role?.name?.toLowerCase() || '')
  const moveToEmployee = async (onboardingId) => {
    if (!onboardingId || moveToEmployeeLoading.value) return
    moveToEmployeeLoading.value = true
    try {
      await useRequest('post', `onboarding/pending-applications/${onboardingId}/move-to-employee`)
      message.success('Employee updated successfully')
      if (filterableRef.value) filterableRef.value.fetch()
    } catch (error) {
      message.error(error?.response?.data?.message || 'Failed to move application to employee')
    } finally {
      moveToEmployeeLoading.value = false
    }
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
        employee_type: 'New',
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

  const openRejectModal = (onboardingId) => {
    rejectSelectedId.value = onboardingId
    rejectReason.value = ''
    rejectReasonError.value = null
    rejectModalVisible.value = true
  }

  const closeRejectModal = () => {
    rejectModalVisible.value = false
    rejectSelectedId.value = null
    rejectReason.value = ''
    rejectReasonError.value = null
  }

  const confirmRejectApplication = async () => {
    if (!rejectSelectedId.value) return
    const reason = rejectReason.value?.trim() ?? ''
    rejectReasonError.value = null
    if (!reason) {
      rejectReasonError.value = 'Reject reason is required'
      return
    }
    rejectLoading.value = true
    try {
      await useRequest('post', `/onboarding/pending-applications/${rejectSelectedId.value}/reject`, {
        reason,
      })
      message.success('Application rejected successfully')
      closeRejectModal()
      if (filterableRef.value) filterableRef.value.fetch()
    } catch (error) {
      message.error(error?.response?.data?.message || 'Failed to reject application')
    } finally {
      rejectLoading.value = false
    }
  }

  const route = useRoute()
  const resource = route.meta?.resource || 'pending-applications'
  
  // Use the useIndexable composable
  const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'pending-applications')
  // Sortable columns configuration
  const sortableColumns = [
    { value: 'created_at', label: 'Created At' },
    { value: 'applicant_first_name', label: 'First Name' },
    { value: 'onboarding_number', label: 'Onboarding #' },
    { value: 'applicant_last_name', label: 'Last Name' },
    { value: 'applicant_email', label: 'Email' },
    { value: 'company_id', label: 'Company' },
    { value: 'process_id', label: 'Process Status' },
    { value: 'submit_application_date', label: 'Submitted At' },
  ]

  // Helper function to get process status label
  const getProcessStatusLabel = (processId) => {
    const statusMap = {
      0: 'Mail sent',
      1: 'Mail sent',
      2: 'Handbook Signed',
      3: 'Personal Info',
      4: 'I-9 Form',
      5: 'W-4 Form',
      6: 'Benefits Enrollment',
      7: 'Direct Deposit',
      8: 'Emergency Contact',
      9: 'Final Submission',
      10: 'Waiting for I9 and W4',
    }
    return statusMap[processId] || 'Unknown'
  }

  // Helper function to get process status class
  const getProcessStatusClass = (processId) => {
    if (processId >= 7) {
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    } else if (processId >= 4) {
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    } else if (processId >= 2) {
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  
  
  
  
  // Filter groups configuration
  const filterGroups = [
    {
      title: 'Basic Information',
      filters: [
        {
          name: 'applicant_first_name',
          title: 'First Name',
          type: 'string',
          placeholder: 'Enter first name'
        },
        {
          name: 'applicant_last_name',
          title: 'Last Name',
          type: 'string',
          placeholder: 'Enter last name'
        },
        {
          name: 'applicant_email',
          title: 'Email',
          type: 'string',
          placeholder: 'Enter email'
        },
        {
          name: 'onboarding_number',
          title: 'Onboarding #',
          type: 'string',
          placeholder: 'Enter onboarding number'
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
    // {
    //   title: 'Process Status',
    //   filters: [
    //     {
    //       name: 'process_id',
    //       title: 'Process Step',
    //       type: 'dropdown',
    //       placeholder: 'Select process step',
    //       column: 'label',
    //       options: [
    //         { id: 1, label: 'Started' },
    //         { id: 2, label: 'Handbook Signed' },
    //         { id: 3, label: 'Personal Info' },
    //         { id: 4, label: 'I-9 Form' },
    //         { id: 5, label: 'W-4 Form' },
    //         { id: 6, label: 'Direct Deposit' },
    //         { id: 7, label: 'Completed' },
    //       ]
    //     },
    //   ]
    // },
    {
      title: 'Dates',
      filters: [
        {
          name: 'submit_application_date',
          title: 'Submitted At',
          type: 'datetime',
          placeholder: 'Select submission date'
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
  
  // Watch for route changes and update employee type
  watch(() => route.path, (newPath) => {
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  }, { immediate: false })
  // Expose setData for useIndexable route guards
  defineExpose({
    setData
  })
  </script>
  
  