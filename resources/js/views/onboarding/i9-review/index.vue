<template>
    <div class="i9-review-index" >
  
      <Filterable
        ref="filterableRef"
        title="I-9 Review"
        url="onboarding/i9-review"
        :sortable="sortableColumns"
        :filter-groups="filterGroups"
        @update:collection="handleCollectionUpdate"
      >
        <template #heading>
          <tr>
            <Th>No</Th>
            <Th>Onboarding #</Th>
            <Th>Company</Th>
            <Th>Employee Name</Th>
            <Th>Email</Th>
            <Th>Contact</Th>
            <Th>I-9 Status</Th>
            <Th>Final Status</Th>
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
                <a  target="_blank" :href="`/employee/${item.employee?.id}`">{{ item.employee?.pos_name }}</a>
            </Td>
            <Td color="secondary">
              {{ item.applicant_email || 'N/A' }}
            </Td>
            <Td color="secondary">
              {{ item.applicant_contact_number || 'N/A' }}
            </Td>
            <Td color="secondary">
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getI9StatusClass(item)"
              >
                {{ getI9StatusLabel(item) }}
              </span>

              <div class="flex items-center gap-2" >
                <span v-if="!item.i9_completed && !item.i9_approved" class="text-red-500 text-xs">I9 Pending</span>
                <span v-if="!item.w4_completed && !item.w4_approved" class="text-red-500 text-xs">W4 Pending</span>
                <span v-if="item.i9_completed && !item.i9_approved" class="text-red-500 text-xs">I9 Completed</span>
                <span v-if="item.w4_completed && !item.w4_approved" class="text-red-500 text-xs">W4 Completed</span>
                <span v-if="item.i9_rejected" class="text-red-500 text-xs">I9 Rejected</span>
                <span v-if="item.w4_rejected" class="text-red-500 text-xs">W4 Rejected</span>
                <span v-if="item.i9_approved" class="text-green-500 text-xs">I9 Approved</span>
                <span v-if="item.w4_approved" class="text-green-500 text-xs">W4 Approved</span>
              </div>
            </Td>
            <Td color="secondary">
              <div v-if="(item.status === 'form_submitted' || item.status === 'i9_submitted') && item.i9_approved && item.w4_approved" class="text-red-500 ">Internal Review Pending</div>
                <div v-else-if="item.document_approved && (!item.i9_approved && !item.w4_approved)" class="text-red-500 ">I9 and W4 Pending</div>
                <div v-else>
                  {{ item.final_status || 'N/A' }}
                </div>
            </Td>
            <Td color="secondary">
              {{ item.employee?.employee_id || 'Pending' }}
            </Td>
            <Td color="secondary">
              {{ formatDate(item.submit_application_date || item.created_at) }}
            </Td>
            <Td align="right" weight="medium">
              <div class="flex items-center justify-end gap-2">
                <Button
                  v-if="access.includes('show')"
                  size="xs"
                  variant="secondary"
                  @click="openStatusModal(item)"
                >
                  Update Status
                </Button>

                <router-link
                  v-if="access.includes('show')"
                  :to="`/onboarding/i9-review/${item.id}`"
                  class="text-blue-600 hover:text-blue-900 transition-colors"
                  title="Review I-9"
                >
                  <SvgIcon name="eye" size="lg" />
                </router-link>
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

      <Modal
        v-model="statusModalOpen"
        title="Update Status"
        size="md"
        :show-footer="false"
      >
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="statusForm.action"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
            >
              <option value="" disabled>Select status</option>
              <option value="i9_approve">I9 Approve</option>
              <option value="w4_approve">W4 Approve</option>
              <option value="i9_w4_approve">I9 and W4 Both Approve</option>
              <option value="i9_reject">I9 Reject</option>
              <option value="w4_reject">W4 Reject</option>
              <option value="i9_w4_reject">I9 and W4 Both Reject</option>
              <option value="verified">Verified</option>
            </select>
          </div>

          <div v-if="requiresRejectReason">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reject Reason</label>
            <textarea
              v-model="statusForm.reason"
              rows="3"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
              placeholder="Enter reject reason"
            />
          </div>

          <div class="flex items-center justify-end gap-2">
            <Button variant="outline" @click="closeStatusModal">Cancel</Button>
            <Button variant="primary" :loading="statusSubmitting" @click="submitStatusUpdate">
              Update Status
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  </template>
  
  <script setup>
  import { useRoute } from 'vue-router'
  import Filterable from '@/components/filterable/filterable.vue'
  import { useIndexable } from '@/composables/useIndexable'
  import Button from '@/components/ui/button.vue'
  import Modal from '@/components/common/Modal.vue'
  import SvgIcon from '@/components/SvgIcon.vue'
  import Td from '@/components/ui/td.vue'
  import Th from '@/components/ui/th.vue'
  import { formatDate } from '@/utils/date'
  import { useRequest } from '@/services/api'
  import { useMessage } from '@/composables/useMessage'
  import { watch, ref, computed } from 'vue'
  
  const message = useMessage()
  
  const route = useRoute()
  const resource = route.meta?.resource || 'i9-review'
  
  const { filterableRef, setData, removeDB, access } = useIndexable(resource, 'i9-review')
  const statusModalOpen = ref(false)
  const statusSubmitting = ref(false)
  const selectedOnboardingId = ref(null)
  const statusForm = ref({
    action: '',
    reason: '',
  })
  const rejectActions = ['i9_reject', 'w4_reject', 'i9_w4_reject']
  const requiresRejectReason = computed(() => rejectActions.includes(statusForm.value.action))
  
  const sortableColumns = [
    { value: 'created_at', label: 'Created At' },
    { value: 'onboarding_number', label: 'Onboarding #' },
    { value: 'applicant_first_name', label: 'First Name' },
    { value: 'applicant_last_name', label: 'Last Name' },
    { value: 'applicant_email', label: 'Email' },
    { value: 'company_id', label: 'Company' },
    { value: 'process_id', label: 'Process Status' },
    { value: 'submit_application_date', label: 'Submitted At' },
  ]

  const getFullName = (item) => {
    const parts = [
      item.applicant_first_name,
      item.applicant_middle_initial,
      item.applicant_last_name
    ].filter(Boolean)
    return parts.length > 0 ? parts.join(' ') : 'N/A'
  }

  const getI9StatusLabel = (item) => {
    switch(item.status) {
      case 'verified':
        return 'Verified'
      case 'form_submitted':
        return 'Form Submitted'
      case 'pending':
        return 'Pending'
      case 'i9_submission_requested':
        return 'I-9 Submission Requested'
      case 'w4_submission_requested':
        return 'W-4 Submission Requested'
      case 'i9_and_w4_submission_requested':
        return 'I-9 and W-4 Submission Requested'
      default:
        return 'Not Submitted'
      case 'document_approved':
        return 'Document Approved'
      case 'i9_submitted':
        return 'I-9 Submitted'
    }
  }

  const getI9StatusClass = (item) => {
    if (item.verified_i9 && item.verified_i9.status === 'approved' || item.document_approved) {
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    } else if (item.verified_i9 && item.verified_i9.status === 'rejected') {
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    } else if (item.form_i9) {
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  
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
    {
      title: 'Process Status',
      filters: [
        {
          name: 'process_id',
          title: 'Process Step',
          type: 'dropdown',
          placeholder: 'Select process step',
          column: 'label',
          options: [
            { id: 4, label: 'I-9 Form' },
            { id: 5, label: 'W-4 Form' },
            { id: 6, label: 'Direct Deposit' },
            { id: 7, label: 'Completed' },
          ]
        },
      ]
    },
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
  
  const handleCollectionUpdate = (collection) => {
    
  }

  const openStatusModal = (item) => {
    selectedOnboardingId.value = item.id
    statusForm.value = {
      action: '',
      reason: '',
    }
    statusModalOpen.value = true
  }

  const closeStatusModal = () => {
    statusModalOpen.value = false
    statusForm.value = {
      action: '',
      reason: '',
    }
    selectedOnboardingId.value = null
  }

  const submitStatusUpdate = async () => {
    if (!selectedOnboardingId.value) return

    if (!statusForm.value.action) {
      message.error('Please select a status.')
      return
    }

    if (requiresRejectReason.value && !statusForm.value.reason?.trim()) {
      message.error('Please enter reject reason.')
      return
    }

    statusSubmitting.value = true
    try {
      await useRequest('post', `/onboarding/i9-review/${selectedOnboardingId.value}/update-status`, {
        action: statusForm.value.action,
        reason: statusForm.value.reason || null,
      })
      message.success('Status updated successfully.')
      closeStatusModal()
      if (filterableRef.value) {
        filterableRef.value.fetch()
      }
    } catch (error) {
      message.error(error.response?.data?.message || 'Failed to update status.')
    } finally {
      statusSubmitting.value = false
    }
  }
  
  const handleDelete = async (id) => {
    const success = await removeDB(resource, id)
    if (success && filterableRef.value) {
      filterableRef.value.fetch()
    }
  }
  
  watch(() => route.path, (newPath) => {
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  }, { immediate: false })
  
  defineExpose({
    setData
  })
  </script>
  
  