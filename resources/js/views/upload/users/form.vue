<template>
  <div v-if="show" class="upload-user-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Upload User' : 'Create New Upload User' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Name -->
          <Input
            v-model="form.name"
            label="Name"
            placeholder="Enter full name"
            :required="true"
            :error="errors.name ? errors.name[0] : null"
            icon-left="user"
          />

          <!-- Username -->
          <Input
            v-model="form.username"
            label="Username"
            placeholder="Enter username"
            :required="true"
            :error="errors.username ? errors.username[0] : null"
            icon-left="user"
          />

          <!-- Password -->
          <Input
            v-model="form.current_password"
            label="Password"
            type="password"
            :placeholder="mode === 'edit' ? 'Leave blank to keep current' : 'Enter password'"
            :required="mode === 'create'"
            :error="errors.current_password ? errors.current_password[0] : null"
            icon-left="lock"
            :help-text="mode === 'edit' ? 'Leave blank to keep current password. This will be encrypted.' : 'This will be encrypted and stored securely.'"
          />

          <!-- Office -->
          <DynamicDropdown
            v-model="form.office"
            label="Office"
            resource="offices"
            display-name="name"
            placeholder="Select an office"
            :error="errors.office_id ? errors.office_id[0] : null"
          />

          <!-- Checkout Time -->
          <Input
            v-model="form.checkout_time"
            label="Checkout Time"
            type="time"
            :error="errors.checkout_time ? errors.checkout_time[0] : null"
          />
        </div>

        <!-- Internal (TDPUS) Companies by Workgroup -->
        <div class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h6 class="text-base font-semibold text-gray-800 dark:text-white !mb-0">
              Internal Companies (TDPUS)
            </h6>
            <Button variant="outline-danger" size="md" @click="clearInternalCompanies" custom-class="max-w-[200px] h-auto shrink-0" type="button">
              Clear all internal companies
            </Button>
          </div>
          <div v-if="workgroupsLoading" class="flex items-center justify-center py-6">
            <Spinner size="sm" text="Loading workgroups..." />
          </div>
          <div v-else class="flex flex-wrap gap-4">
            <DynamicDropdown
              v-for="wg in workgroups"
              :key="'internal-' + wg.id"
              :label="wg.company_count != null ? `${wg.name} (${wg.company_count} stores)` : wg.name"
              v-model="internalCompaniesByWorkgroup[wg.id]"
              resource="companies"
              :params="{ workgroup_id: wg.id }"
              display-name="name"
              placeholder="Select companies"
              :required="false"
              :removeNullOption="true"
              multiple
              :error="errors.companies ? errors.companies[0] : null"
            />
          </div>
        </div>

        <!-- Folder Access -->
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
          <DynamicDropdown
            v-model="form.folder_access"
            label="Folder Access"
            resource="upload-folders"
            display-name="name"
            placeholder="Select folders"
            multiple
            :error="errors.folder_access ? errors.folder_access[0] : null"
          />
        </div>

        <!-- Special Permissions Section (Upload Portal Only) -->
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
          <h6 class="text-base font-semibold text-gray-800 dark:text-white !mb-4">
            Upload Portal Permissions
          </h6>
          <div v-if="spPermissionsLoading" class="flex items-center justify-center py-8">
            <Spinner size="sm" text="Loading permissions..." />
          </div>
          <div v-else-if="form.sp_permission && form.sp_permission.length > 0" class="space-y-6">
            <div
              v-for="(permission, index) in form.sp_permission"
              :key="permission.name"
              class="permission-group p-4 bg-gray-50 dark:bg-gray-900 rounded-lg"
            >
              <div class="flex items-center justify-between mb-3">
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 capitalize">
                  {{ permission.name?.replace(/-/g, ' ') }}
                </h6>
                <button
                  type="button"
                  @click="toggleAllSPActions(index)"
                  class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                >
                  {{ isAllSPActionsSelected(index) ? 'Deselect All' : 'Select All' }}
                </button>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                <label
                  v-for="(value, action) in permission.actions"
                  :key="action"
                  class="flex items-center space-x-2 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    v-model="form.sp_permission[index].actions[action]"
                    :true-value="1"
                    :false-value="0"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">
                    {{ action.replace('_', ' ') }}
                  </span>
                </label>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
            No special permissions available
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button variant="primary" size="md" type="submit" :loading="isSaving" v-if="mode === 'edit' ? access.includes('update') : access.includes('create')">
            {{ mode === 'edit' ? 'Update User' : 'Create User' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'
import { useRequest } from '@/services/api'

const route = useRoute()
const resource = route.meta?.resource || 'upload/users'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'upload/users', 'upload-user')

// Special permissions state
const spPermissionsLoading = ref(false)

// Internal (TDPUS) companies workgroups
const workgroups = ref([])
const workgroupsLoading = ref(false)
const internalCompaniesByWorkgroup = reactive({})

// Internal Companies Functions
const ensureInternalWorkgroupKeys = () => {
  for (const wg of workgroups.value) {
    if (!(wg.id in internalCompaniesByWorkgroup)) {
      internalCompaniesByWorkgroup[wg.id] = []
    }
  }
}

const distributeInternalCompanies = () => {
  const list = form.value.company_access
  for (const wg of workgroups.value) {
    internalCompaniesByWorkgroup[wg.id] = []
  }
  if (!Array.isArray(list) || list.length === 0) return
  for (const c of list) {
    const wgid = c.workgroup_id
    if (wgid == null) continue
    if (!(wgid in internalCompaniesByWorkgroup)) {
      internalCompaniesByWorkgroup[wgid] = []
    }
    internalCompaniesByWorkgroup[wgid].push({ id: c.id, name: c.name })
  }
}

const mergedInternalCompanyIds = () => {
  const ids = []
  for (const wg of workgroups.value) {
    const selected = internalCompaniesByWorkgroup[wg.id]
    if (!Array.isArray(selected)) continue
    selected.forEach((c) => ids.push(c.id))
  }
  return [...new Set(ids)]
}

const fetchInternalWorkgroups = async () => {
  workgroupsLoading.value = true
  try {
    const res = await axios.get('/api/search/workgroups', {
      params: { query: '', column: 'name', all: true },
    })
    workgroups.value = res.data?.collection || []
    ensureInternalWorkgroupKeys()
    distributeInternalCompanies()
  } catch (e) {
    console.error('Error loading workgroups:', e)
    workgroups.value = []
  } finally {
    workgroupsLoading.value = false
  }
}

const clearInternalCompanies = () => {
  for (const wg of workgroups.value) {
    internalCompaniesByWorkgroup[wg.id] = []
  }
  form.value.companies = []
}

// Special Permissions Functions
const fetchSPPermissions = async () => {
  if (mode.value !== 'create') {
    return
  }
  spPermissionsLoading.value = true
  try {
    const response = await useRequest('get', 'upload/users/sp-permissions')
    const schema = response.sp_permissions || []
    if (!form.value.sp_permission || form.value.sp_permission.length === 0) {
      form.value.sp_permission = schema.map(permission => ({
        name: permission.name,
        actions: Object.keys(permission.actions || {}).reduce((acc, action) => {
          acc[action] = 0
          return acc
        }, {})
      }))
    }
  } catch (error) {
    console.error('Error fetching special permissions:', error)
  } finally {
    spPermissionsLoading.value = false
  }
}

const toggleAllSPActions = (permissionIndex) => {
  const permission = form.value.sp_permission[permissionIndex]
  const allSelected = isAllSPActionsSelected(permissionIndex)
  Object.keys(permission.actions).forEach(action => {
    permission.actions[action] = allSelected ? 0 : 1
  })
}

const isAllSPActionsSelected = (permissionIndex) => {
  const permission = form.value.sp_permission[permissionIndex]
  return permission?.actions && Object.values(permission.actions).every(value => value === 1)
}

// Watch for form data changes
watch(
  () => form.value.companies,
  () => {
    if (show.value && workgroups.value.length) {
      distributeInternalCompanies()
    }
  },
  { deep: true }
)

watch(() => show.value, (newValue) => {
  if (newValue) {
    fetchInternalWorkgroups()
    fetchSPPermissions()
  }
})

const handleSave = () => {
  const obj = {
    ...form.value,
    office_id: form.value.office?.id ?? null,
    companies: mergedInternalCompanyIds().map(id => ({ id })),
    folder_access: form.value.folder_access ? form.value.folder_access.map(folder => folder.id) : []
  }
  save(obj)
}

defineExpose({
  setData
})
</script>
