<template>
  <div class="employee-confirmation-index">
    <Modal
      v-model="addEmployeeModalVisible"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Add Employees"
      :loading="addEmployeeLoading"
      @close="closeAddEmployeeModal"
      @confirm="submitAddEmployee"
    >
      <template #header>
        <div class="flex items-center justify-between w-full gap-4">
          <h5 class="text-xl font-bold !mb-0">Add Employee</h5>
          <Button
            v-if="access.includes('create')"
            @click="openCreateEmployeeModal"
            variant="outline-secondary"
            size="sm"
            icon-left="plus"
          >
            Add New Employee
          </Button>
        </div>
      </template>
      <div class="space-y-4">
        <DynamicDropdown
          v-model="selectedCompany"
          label="Company"
          resource="companies"
          display-name="name"
          placeholder="Select company"
          :required="true"
          :error="addEmployeeErrors.company_id"
          :remove-null-option="true"
        />
        <DynamicDropdown
          :key="employeesDropdownKey"
          v-model="selectedEmployees"
          label="Employees"
          resource="employee-confirmation-employees"
          display-name="pos_name"
          placeholder="Select employees"
          :required="true"
          :disabled="!selectedCompany?.id"
          :params="{ company_id: selectedCompany?.id || '' }"
          :error="addEmployeeErrors.employee_ids"
          :remove-null-option="true"
          multiple
        />
        <p
          v-if="!selectedCompany?.id"
          class="text-xs text-gray-500 dark:text-gray-400 !mb-0"
        >
          Select a company to load employees who are not yet in this list.
        </p>
      </div>
    </Modal>

    <CreateEmployeeModal
      v-model="createEmployeeModalVisible"
      :default-company="selectedCompany"
      @created="handleEmployeeCreated"
    />

    <EditConfirmationModal
      v-model="editModalVisible"
      :item="editingItem"
      :initial-view="editInitialView"
      @saved="handleEditSaved"
      @update:model-value="onEditModalVisibilityChange"
    />

    <ViewConfirmationModal
      v-model="viewModalVisible"
      :item="viewingItem"
      @update:model-value="onViewModalVisibilityChange"
    />

    <PendingDocsUploadModal
      v-model="i9UploadModalVisible"
      :employee="i9UploadEmployee"
      i9-only
      @uploaded="onI9Uploaded"
    />

    <Modal
      v-model="unsignedI9UploadModalVisible"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Upload"
      :loading="unsignedI9UploadLoading"
      @close="closeUnsignedI9UploadModal"
      @confirm="submitUnsignedI9Upload"
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0">Upload I-9 Without Signatures</h5>
      </template>
      <div class="space-y-4">
        <p class="!mb-0 text-sm text-gray-600 dark:text-gray-300">
          Upload an I-9 without signatures for
          <span class="font-semibold">{{ unsignedI9UploadItem?.employee?.pos_name || 'this employee' }}</span>.
          This will set HR status to authorised and status to verified.
        </p>
        <Input
          type="file"
          label="I-9 Document"
          placeholder="Select I-9 document"
          icon-left="files"
          :error="unsignedI9UploadError"
          :disabled="unsignedI9UploadLoading"
          @change="onUnsignedI9FileChange"
        />
      </div>
    </Modal>

    <Modal
      v-model="markLeftModalVisible"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Mark as Left / Terminated"
      :loading="markLeftLoading"
      @close="closeMarkLeftModal"
      @confirm="submitMarkLeft"
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0">Left / Terminated</h5>
      </template>
      <div class="space-y-4">
        <p class="!mb-0 text-sm text-gray-600 dark:text-gray-300">
          Mark
          <span class="font-semibold">{{ markLeftItem?.employee?.pos_name || 'this employee' }}</span>
          as left / terminated for
          <span class="font-semibold">{{ markLeftItem?.company?.name || 'this company' }}</span>.
        </p>
        <Textarea
          v-model="markLeftNote"
          label="Note"
          placeholder="Add an optional note"
          :rows="4"
          :disabled="markLeftLoading"
        />
      </div>
    </Modal>

    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
      <button
        v-for="tab in statusTabs"
        :key="tab.id"
        type="button"
        class="rounded-lg border bg-white p-4 text-left shadow-sm transition-all dark:bg-gray-800"
        :class="activeTab === tab.id
          ? 'border-primary ring-1 ring-primary/20 dark:border-emerald-400 dark:ring-emerald-400/20'
          : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'"
        @click="activeTab = tab.id"
      >
        <div class="mb-2 flex items-center gap-2">
          <span
            class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
            :class="tab.dotClass"
          />
          <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
            {{ tab.label }}
          </span>
        </div>
        <div class="text-3xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
          {{ statusCounts[tab.id] ?? 0 }}
        </div>
        <p
          class="mt-2 !mb-0 text-xs"
          :class="activeTab === tab.id
            ? 'font-semibold text-primary dark:text-emerald-400'
            : 'text-gray-500 dark:text-gray-400'"
        >
          <template v-if="activeTab === tab.id">Viewing this list</template>
          <template v-else>{{ tab.description }}</template>
        </p>
      </button>
    </div>

    <Filterable
      ref="filterableRef"
      title="Manual I9"
      url="onboarding/employee-confirmation"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      :extra-params="extraParams"
      :show-search="true"
      @update:data="handleDataUpdate"
    >
      <template #extra>
        <Button
          icon-left="download"
          icon-size="sm"
          variant="outline-secondary"
          size="sm"
          :loading="isExporting"
          :disabled="isExporting"
          @click="exportToExcel"
        >
          {{ isExporting ? 'Exporting...' : 'Export Excel' }}
        </Button>
        <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          @click="openAddEmployeeModal"
        >
          Add Employee
        </Button>
      </template>
      <template #extra-controls>
        <div
          v-if="can('manual-i9', 'regional-director') || can('manual-i9', 'area-manager')"
          class="flex flex-wrap items-end gap-2"
        >
          <div v-if="can('manual-i9', 'regional-director')" class="min-w-[220px]">
            <DynamicDropdown
              v-model="filters.regionalDirector"
              resource="users?role=Regional Director"
              display-name="name"
              placeholder="Select Regional Director"
              icon-left="user"
              label="Regional Director"
              @change="onRegionalDirectorChange"
            />
          </div>
          <div v-if="can('manual-i9', 'area-manager')" class="min-w-[220px]">
            <DynamicDropdown
              v-model="filters.areaManager"
              resource="users?role=Area Manager"
              display-name="name"
              placeholder="Select Area Manager"
              icon-left="user"
              label="Area Manager"
              @change="onAreaManagerChange"
            />
          </div>
        </div>
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Employee</Th>
          <Th>Store</Th>
          <Th>Status</Th>
          <Th>HR Status</Th>
          <Th>I-9 Choice</Th>
          <Th>Approved At</Th>
          <Th>Created At</Th>
          <Th>I-9 Form</Th>
          <Th>Uploaded I9</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>
      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">
            <a :href="`/employee/${item.employee?.id}`" target="_blank">
              {{ item.employee?.pos_name || 'N/A' }}
              <p class="!mb-0"><small>{{ item.employee?.employee_id || 'N/A' }}</small></p>
            </a>
          </Td>
          <Td color="secondary">{{ item.company?.store_number || 'N/A' }} - {{ item.company?.name || 'N/A' }}</Td>
          <Td color="secondary">
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="getStatusClass(item.status)"
            >
              {{ formatStatus(item.status) }}
            </span>
          </Td>
          <Td color="secondary">
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="getHrStatusClass(item.hr_status)"
            >
              {{ formatHrStatus(item.hr_status) }}
            </span>
          </Td>
          <Td color="secondary">{{ item.i9_choice || '-' }}</Td>
          <Td color="secondary">{{ item.approved_at ? formatDateTime(item.approved_at) : '-' }}</Td>
          <Td color="secondary">{{ item.created_at ? formatDateTime(item.created_at) : '-' }}</Td>
          <Td color="secondary">
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="item.has_i9_form
                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'"
            >
              {{ item.has_i9_form ? 'Uploaded' : 'Pending' }}
            </span>
          </Td>
          <Td color="secondary">
            <a
              v-if="getI9Document(item)?.document_path_url"
              :href="documentUrl(getI9Document(item).document_path_url)"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              title="Download uploaded I-9"
            >
              <SvgIcon name="download" size="sm" />
              {{ renderDocumentName(getI9Document(item).document_name) || 'I-9 Form' }}
            </a>
            <Button
              v-else-if="isAuthorised(item) && !item.has_i9_form"
              type="button"
              variant="secondary"
              size="sm"
              icon-left="upload"
              @click="openI9UploadModal(item)"
            >
              Upload
            </Button>
            <span v-else>-</span>
          </Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <Button
                v-if="item.status == 'verified' && !item.has_i9_form"
                @click="handleDownloadI9(item)"
                :loading="i9DownloadLoadingId === item.id"
                icon-left="download"
                icon-size="sm"
                variant="outline-secondary"
                size="sm"
                title="Download I-9"
              >
                Download I-9
              </Button>
              <Button
                v-if="access.includes('approve') && !item.has_i9_form && !item.left_terminate && item.hr_status != 'authorised'"
                @click="openUnsignedI9UploadModal(item)"
                icon-left="upload"
                icon-size="sm"
                variant="outline-secondary"
                size="sm"
                title="Upload I-9 without signatures"
              >
                Upload I-9 (no sign)
              </Button>
              <IconMenuDropdown title="Actions">
                <template #default="{ close }">
                  <button
                    v-if="access.includes('update') && !isAuthorised(item) && item.status != 'verified'"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="
                      openEditModal(item);
                      close()
                    "
                  >
                    <SvgIcon name="edit" size="sm" />
                    Edit
                  </button>
                  <button
                    v-if="isAuthorised(item)"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="
                      openViewModal(item);
                      close()
                    "
                  >
                    <SvgIcon name="eye" size="sm" />
                    View
                  </button>
                  <!-- <button
                    v-if="access.includes('documents-upload')"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="
                      openEditModal(item, 'documents');
                      close()
                    "
                  >
                    <SvgIcon name="upload" size="sm" />
                    Document Upload
                  </button> -->
                  <router-link
                    v-if="can('employee', 'documents-show') && item.employee_id"
                    :to="`/employee/documents/${item.employee_id}`"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    @click="close()"
                  >
                    <SvgIcon name="files" size="sm" />
                    Documents
                  </router-link>
                  <button
                    v-if="access.includes('approve') && (item.hr_status === 'authorised' || item.hr_status === 'incorrect_i9')"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                    role="menuitem"
                    :disabled="actionLoadingId === item.id"
                    @click="
                      handleUnauthorize(item);
                      close()
                    "
                  >
                    <SvgIcon name="x-circle" size="sm" />
                    Unauthorise
                  </button>
                  <button
                    v-if="access.includes('approve') && canMarkIncorrectI9(item)"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20"
                    role="menuitem"
                    :disabled="actionLoadingId === item.id"
                    @click="
                      handleIncorrectI9(item);
                      close()
                    "
                  >
                    <SvgIcon name="x-circle" size="sm" />
                    Incorrect I9
                  </button>
                  <button
                    v-if="access.includes('approve') && item.status != 'authorized'"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                    role="menuitem"
                    :disabled="actionLoadingId === item.id"
                    @click="
                      handleAuthorize(item);
                      close()
                    "
                  >
                    <SvgIcon name="check" size="sm" />
                    Authorize
                  </button>
                  <button
                    v-if="access.includes('left') && item.left_terminate"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                    role="menuitem"
                    :disabled="actionLoadingId === item.id"
                    @click="
                      handleRemoveLeft(item);
                      close()
                    "
                  >
                    <SvgIcon name="arrow-left" size="sm" />
                    Remove from Left / Terminated
                  </button>
                  <button
                    v-if="access.includes('left') && !item.left_terminate"
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                    role="menuitem"
                    :disabled="actionLoadingId === item.id"
                    @click="
                      openMarkLeftModal(item);
                      close()
                    "
                  >
                    <SvgIcon name="x-circle" size="sm" />
                    Left / Terminated
                  </button>
                </template>
              </IconMenuDropdown>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Filterable from '@/components/filterable/filterable.vue'
import { useIndexable } from '@/composables/useIndexable'
import SvgIcon from '@/components/SvgIcon.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import Button from '@/components/ui/button.vue'
import Modal from '@/components/common/Modal.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'
import Input from '@/components/ui/input.vue'
import Textarea from '@/components/ui/textarea.vue'
import CreateEmployeeModal from './CreateEmployeeModal.vue'
import EditConfirmationModal from './EditConfirmationModal.vue'
import ViewConfirmationModal from './ViewConfirmationModal.vue'
import PendingDocsUploadModal from '@/views/employee/components/PendingDocsUploadModal.vue'
import api, { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import { formatDateTime } from '@/utils/date'
import { assignValidatedFile } from '@/utils/documentUpload'

const message = useMessage()
const { can } = usePermission()
const route = useRoute()
const resource = route.meta?.resource || 'employee-confirmation'
const { filterableRef, setData, access } = useIndexable(resource, 'manual-i9')

const statusTabs = [
  {
    id: 'total',
    label: 'Total Employees',
    description: 'All employees in confirmation',
    dotClass: 'bg-gray-600',
  },
  {
    id: 'currently_working',
    label: 'Currently Working',
    description: 'Verified + in-process (excludes those who left)',
    dotClass: 'bg-blue-500',
  },
  {
    id: 'verified',
    label: 'Verified Employee',
    description: 'Work authorization confirmed',
    dotClass: 'bg-emerald-500',
  },
  {
    id: 'unverified',
    label: 'Unverified Employee',
    description: 'Awaiting status / documents / review',
    dotClass: 'bg-amber-500',
  },
  {
    id: 'left',
    label: 'Left / Terminated',
    description: 'No longer employed at this company',
    dotClass: 'bg-red-500',
  },
  {
    id: 'pending_i9_upload',
    label: 'Pending I-9 Upload',
    description: 'Authorised but I-9 form not uploaded',
    dotClass: 'bg-purple-500',
  },
  {
    id: 'incorrect_i9',
    label: 'Incorrect I9',
    description: 'Authorised and verified with incorrect I-9',
    dotClass: 'bg-orange-500',
  },
]

const activeTab = ref('total')
const filters = ref({
  regionalDirector: null,
  areaManager: null,
})
const extraParams = computed(() => {
  const params = { status: activeTab.value }
  const userId = filters.value.regionalDirector?.id || filters.value.areaManager?.id

  if (userId) {
    params.user_id = userId
  }

  return params
})
const statusCounts = ref({
  total: 0,
  currently_working: 0,
  verified: 0,
  unverified: 0,
  left: 0,
  pending_i9_upload: 0,
  incorrect_i9: 0,
})

const addEmployeeModalVisible = ref(false)
const addEmployeeLoading = ref(false)
const createEmployeeModalVisible = ref(false)
const editModalVisible = ref(false)
const editingItem = ref(null)
const editInitialView = ref('status')
const viewModalVisible = ref(false)
const viewingItem = ref(null)
const selectedCompany = ref(null)
const selectedEmployees = ref([])
const addEmployeeErrors = ref({})
const actionLoadingId = ref(null)
const employeesDropdownKey = ref('no-company')
const markLeftModalVisible = ref(false)
const markLeftItem = ref(null)
const markLeftNote = ref('')
const markLeftLoading = ref(false)
const i9DownloadLoadingId = ref(null)
const isExporting = ref(false)
const i9UploadModalVisible = ref(false)
const i9UploadEmployee = ref(null)
const unsignedI9UploadModalVisible = ref(false)
const unsignedI9UploadItem = ref(null)
const unsignedI9UploadFile = ref(null)
const unsignedI9UploadError = ref(null)
const unsignedI9UploadLoading = ref(false)

const getI9Document = (item) => {
  if (item?.i9_document) {
    return item.i9_document
  }
  const docs = item?.employee?.employee_documents || []
  return docs.find((document) => document.document_type === 'i9_form') || null
}

const documentUrl = (path) => {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }
  return `/storage/${path}`
}

const renderDocumentName = (name) => {
  if (!name) return null
  const transformedName = String(name).replaceAll('_', ' ')
  return transformedName.charAt(0).toUpperCase() + transformedName.slice(1)
}

const openI9UploadModal = (item) => {
  i9UploadEmployee.value = {
    ...(item?.employee || {}),
    id: item?.employee_id || item?.employee?.id,
    employee_documents: item?.employee?.employee_documents || (item?.i9_document ? [item.i9_document] : []),
  }
  i9UploadModalVisible.value = true
}

const onI9Uploaded = () => {
  i9UploadModalVisible.value = false
  i9UploadEmployee.value = null
  filterableRef.value?.fetch()
}

const openUnsignedI9UploadModal = (item) => {
  unsignedI9UploadItem.value = item
  unsignedI9UploadFile.value = null
  unsignedI9UploadError.value = null
  unsignedI9UploadModalVisible.value = true
}

const closeUnsignedI9UploadModal = () => {
  unsignedI9UploadModalVisible.value = false
  unsignedI9UploadItem.value = null
  unsignedI9UploadFile.value = null
  unsignedI9UploadError.value = null
}

const onUnsignedI9FileChange = (event) => {
  const input = event?.target
  const file = input?.files?.[0] || null
  assignValidatedFile(file, (selectedFile) => {
    unsignedI9UploadFile.value = selectedFile
    unsignedI9UploadError.value = null
  }, {
    onError: (error) => {
      unsignedI9UploadError.value = error
      unsignedI9UploadFile.value = null
    },
    input,
  })
}

const submitUnsignedI9Upload = async () => {
  const item = unsignedI9UploadItem.value
  if (!item?.id) return

  if (!unsignedI9UploadFile.value) {
    unsignedI9UploadError.value = 'Please select an I-9 document.'
    return
  }

  unsignedI9UploadLoading.value = true
  try {
    const payload = new FormData()
    payload.append('file', unsignedI9UploadFile.value)
    const response = await useRequest(
      'post',
      `onboarding/employee-confirmation/${item.id}/upload-i9-without-signatures`,
      payload,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    if (response?.saved) {
      message.success(response.message || 'I-9 without signatures uploaded successfully.')
      closeUnsignedI9UploadModal()
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to upload I-9 without signatures.')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to upload I-9 without signatures.')
  } finally {
    unsignedI9UploadLoading.value = false
  }
}

const handleDataUpdate = (response) => {
  const counts = response?.status_counts ?? response?.data?.status_counts
  if (counts) {
    statusCounts.value = { ...statusCounts.value, ...counts }
  }
}

const exportToExcel = async () => {
  if (isExporting.value) {
    return
  }

  isExporting.value = true
  try {
    const currentParams = filterableRef.value?.getCurrentParams?.() || {}
    const response = await useRequest('get', 'onboarding/employee-confirmation/export', null, {
      params: {
        ...currentParams,
        ...extraParams.value,
      },
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `manual_i9_export_${activeTab.value}_${new Date().toISOString().slice(0, 10)}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    message.success('Exported to Excel successfully')
  } catch (error) {
    let errorMessage = 'Failed to export data'
    const data = error?.response?.data
    if (data instanceof Blob) {
      try {
        const parsed = JSON.parse(await data.text())
        errorMessage = parsed.message || errorMessage
      } catch (_) {
        // keep default message
      }
    } else if (data?.message) {
      errorMessage = data.message
    }
    message.error(errorMessage)
  } finally {
    isExporting.value = false
  }
}

const onRegionalDirectorChange = () => {
  filters.value.areaManager = null
}

const onAreaManagerChange = () => {
  filters.value.regionalDirector = null
}

const openAddEmployeeModal = () => {
  selectedCompany.value = null
  selectedEmployees.value = []
  addEmployeeErrors.value = {}
  employeesDropdownKey.value = 'no-company'
  addEmployeeModalVisible.value = true
}

const closeAddEmployeeModal = () => {
  addEmployeeModalVisible.value = false
  selectedCompany.value = null
  selectedEmployees.value = []
  addEmployeeErrors.value = {}
}

const openCreateEmployeeModal = () => {
  createEmployeeModalVisible.value = true
}

const handleEmployeeCreated = () => {
  closeAddEmployeeModal()
  filterableRef.value?.fetch()
}

const openEditModal = (item, view = 'status') => {
  editingItem.value = item
  editInitialView.value = view
  editModalVisible.value = true
}

const openViewModal = (item) => {
  viewingItem.value = item
  viewModalVisible.value = true
}

const onEditModalVisibilityChange = (visible) => {
  if (!visible) {
    editingItem.value = null
    editInitialView.value = 'status'
  }
}

const onViewModalVisibilityChange = (visible) => {
  if (!visible) {
    viewingItem.value = null
  }
}

const handleEditSaved = (updated) => {
  if (updated) {
    editingItem.value = updated
  }
  filterableRef.value?.fetch()
}

const submitAddEmployee = async () => {
  addEmployeeErrors.value = {}

  if (!selectedCompany.value?.id) {
    addEmployeeErrors.value.company_id = 'Please select a company.'
    return
  }

  const employeeIds = (selectedEmployees.value || [])
    .map((employee) => employee.id)
    .filter((id) => id && id !== '0')

  if (!employeeIds.length) {
    addEmployeeErrors.value.employee_ids = 'Please select at least one employee.'
    return
  }

  addEmployeeLoading.value = true
  try {
    const response = await useRequest('post', 'onboarding/employee-confirmation', {
      company_id: selectedCompany.value.id,
      employee_ids: employeeIds,
    })

    if (response?.saved) {
      message.success(response.message || 'Employees added successfully.')
      closeAddEmployeeModal()
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to add employees.')
    }
  } catch (error) {
    const errors = error?.response?.data?.errors
    if (errors) {
      addEmployeeErrors.value = {
        company_id: errors.company_id?.[0],
        employee_ids: errors.employee_ids?.[0],
      }
    }
    message.error(error?.response?.data?.message || 'Failed to add employees.')
  } finally {
    addEmployeeLoading.value = false
  }
}

const handleAuthorize = async (item) => {
  const employeeName = item.employee?.pos_name || 'this employee'
  const companyName = item.company?.name || 'this company'
  const confirmed = confirm(
    `Authorize ${employeeName} for ${companyName}?\n\nThis will mark the employee as authorized for work.`
  )

  if (!confirmed) {
    return
  }

  actionLoadingId.value = item.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${item.id}/authorize`)
    if (response?.saved) {
      message.success(response.message || 'Employee authorized successfully.')
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to authorize employee.')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to authorize employee.')
  } finally {
    actionLoadingId.value = null
  }
}

const handleUnauthorize = async (item) => {
  actionLoadingId.value = item.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${item.id}/unauthorize`)
    if (response?.saved) {
      message.success(response.message || 'Employee unauthorised successfully.')
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to unauthorise employee.')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to unauthorise employee.')
  } finally {
    actionLoadingId.value = null
  }
}

const canMarkIncorrectI9 = (item) =>
  item?.hr_status === 'authorised' && item?.status === 'verified' && !item?.left_terminate

const handleIncorrectI9 = async (item) => {
  const employeeName = item.employee?.pos_name || 'this employee'
  const confirmed = confirm(
    `Mark ${employeeName} as Incorrect I9?\n\nThis can only be applied to authorised and verified employees.`
  )

  if (!confirmed) {
    return
  }

  actionLoadingId.value = item.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${item.id}/incorrect-i9`)
    if (response?.saved) {
      message.success(response.message || 'Employee marked as Incorrect I9.')
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to mark Incorrect I9.')
    }
  } catch (error) {
    message.error(
      error?.response?.data?.errors?.hr_status?.[0]
        || error?.response?.data?.message
        || 'Failed to mark Incorrect I9.'
    )
  } finally {
    actionLoadingId.value = null
  }
}

const isAuthorised = (item) => item?.status === 'verified'

const handleDownloadI9 = async (item) => {
  i9DownloadLoadingId.value = item.id
  try {
    const response = await api.get(`onboarding/employee-confirmation/${item.id}/download-i9`, {
      responseType: 'blob',
    })
    const contentType = response.headers?.['content-type'] || 'application/pdf'
    const blob = new Blob([response.data], { type: contentType })
    const url = URL.createObjectURL(blob)
    const employeeIdentifier = item.employee?.employee_id || item.employee_id || item.id
    const disposition = response.headers?.['content-disposition'] || ''
    const matchedName = disposition.match(/filename="?([^"]+)"?/i)?.[1]
    const extension = matchedName?.includes('.')
      ? matchedName.slice(matchedName.lastIndexOf('.'))
      : '.pdf'
    const link = document.createElement('a')
    link.href = url
    link.download = matchedName || `I9-Form-${employeeIdentifier}-${new Date().toISOString().slice(0, 10)}${extension}`
    link.click()
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to download I-9.')
  } finally {
    i9DownloadLoadingId.value = null
  }
}

const handleRemoveLeft = async (item) => {
  const employeeName = item.employee?.pos_name || 'this employee'
  const companyName = item.company?.name || 'this company'
  const confirmed = confirm(
    `Remove ${employeeName} from left / terminated status for ${companyName}?\n\nThey will appear again in the active confirmation lists for this company.`
  )

  if (!confirmed) {
    return
  }

  actionLoadingId.value = item.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${item.id}/remove-left`)
    if (response?.saved) {
      message.success(response.message || 'Employee removed from left / terminated.')
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to update employee.')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to update employee.')
  } finally {
    actionLoadingId.value = null
  }
}

const openMarkLeftModal = (item) => {
  markLeftItem.value = item
  markLeftNote.value = ''
  markLeftModalVisible.value = true
}

const closeMarkLeftModal = () => {
  markLeftModalVisible.value = false
  markLeftItem.value = null
  markLeftNote.value = ''
}

const submitMarkLeft = async () => {
  const item = markLeftItem.value
  if (!item?.id) {
    return
  }

  markLeftLoading.value = true
  actionLoadingId.value = item.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${item.id}/left`, {
      left_note: markLeftNote.value.trim() || null,
    })
    if (response?.saved) {
      message.success(response.message || 'Employee marked as left.')
      closeMarkLeftModal()
      filterableRef.value?.fetch()
    } else {
      message.error(response?.message || 'Failed to update employee.')
    }
  } catch (error) {
    message.error(error?.response?.data?.message || 'Failed to update employee.')
  } finally {
    markLeftLoading.value = false
    actionLoadingId.value = null
  }
}

const sortableColumns = [
  { value: 'employee_name', label: 'Employee' },
  { value: 'company_name', label: 'Company' },
  { value: 'created_at', label: 'Created At' },
  { value: 'i9_uploaded', label: 'I-9 Uploaded' },
]
const hrStatusLabels = {
  document_uploaded: 'Document Uploaded',
  reviewed: 'Reviewed',
  authorised: 'Authorised',
  pending: 'Pending',
  rejected: 'Rejected',
  tnc: 'TNC',
  incorrect_i9: 'Incorrect I9',
}

const filterGroups = [
  {
    title: 'Basic Information',
    filters: [
      {
        name: 'employee.id',
        title: 'Employee',
        type: 'lookup_only',
        resource: 'employee',
        column: 'pos_name',
        placeholder: 'Select employee',
      },
      {
        name: 'company.id',
        title: 'Company',
        type: 'lookup_only',
        resource: 'companies',
        column: 'name',
        placeholder: 'Select company',
      },
      {
        name: 'employeeConfirmation.hr_status',
        title: 'HR Status',
        type: 'dropdown',
        column: 'label',
        options: Object.keys(hrStatusLabels).map((key) => ({ id: key, label: hrStatusLabels[key] })),
        placeholder: 'Select HR Status',
      },
    ],
  },
]

const statusLabels = {
  total: 'Total',
  currently_working: 'Currently Working',
  verified: 'Verified',
  unverified: 'Unverified',
  left: 'Left / Terminated',
  pending_i9_upload: 'Pending I-9 Upload',
  incorrect_i9: 'Incorrect I9',
}



const formatStatus = (status) => statusLabels[status] || status || 'N/A'
const formatHrStatus = (status) => hrStatusLabels[status] || status || 'N/A'
const formatApprovedAt = (value) => (value ? formatDateTime(value) : 'N/A')

const getStatusClass = (status) => {
  const map = {
    currently_working: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    verified: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    unverified: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    left: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
  }
  return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const getHrStatusClass = (status) => {
  const map = {
    document_uploaded: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    reviewed: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
    authorised: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    tnc: 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-300',
    incorrect_i9: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
  }
  return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

watch(selectedCompany, (company, previous) => {
  if (company?.id !== previous?.id) {
    selectedEmployees.value = []
    addEmployeeErrors.value = {}
    employeesDropdownKey.value = company?.id ? `company-${company.id}` : 'no-company'
  }
})

watch(
  () => route.path,
  () => {
    if (filterableRef.value) {
      filterableRef.value.fetch()
    }
  },
  { immediate: false }
)

defineExpose({
  setData,
})
</script>
