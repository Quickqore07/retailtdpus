<template>
    <div v-if="show" class="user-form">
        <!-- Form Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit User' : 'Create New User' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <Input v-model="form.name" label="Name" placeholder="Enter full name" :required="true"
                        :error="errors.name ? errors.name[0] : null" icon-left="user" />

                    <!-- Username -->
                    <Input v-model="form.username" label="Username" placeholder="Enter username" :required="true"
                        :error="errors.username ? errors.username[0] : null" icon-left="user" />

                    <!-- Email -->
                    <Input v-model="form.email" label="Email For OTP" type="email" :required="true" placeholder="Enter email address"
                        :error="errors.email ? errors.email[0] : null" icon-left="mail" />
                    <Input v-model="form.current_password" label="Current Password" type="password" placeholder="Enter current password" v-if="can('user', 'password')"
                        :error="errors.current_password ? errors.current_password[0] : null" icon-left="lock" />

                    <!-- Phone -->
                    <Input v-model="form.phone" label="Phone For OTP" type="tel" placeholder="Enter phone number"
                        :error="errors.phone ? errors.phone[0] : null" icon-left="phone" />

                    <!-- Role -->
                    <div class="flex flex-col">
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Role
                            <span class="text-red-500">*</span>
                        </label>
                        <DynamicDropdown
                            v-model="form.role"
                            resource="roles"
                            display-name="name"
                            placeholder="Select a role"
                            :disabled="form.role && form.role.name === 'admin' && mode != 'create'"
                            :removable="false"
                        />
                        <p v-if="errors.role_id" class="text-xs text-red-600 dark:text-red-400 mt-1">
                            {{ errors.role_id[0] }}
                        </p>
                    </div>

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

                <div v-if="form.role && form.role.name !== 'admin'" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Select companies per workgroup; all choices are saved together on the user.
                        </p>
                        <Button variant="outline-danger" size="md" @click="clearCompanies" custom-class="max-w-[180px] h-auto shrink-0" type="button">
                            Clear all companies
                        </Button>
                    </div>
                    <div v-if="workgroupsLoading" class="flex items-center justify-center py-6">
                        <Spinner size="sm" text="Loading workgroups..." />
                    </div>
                    <div v-else class="space-y-6">
                        <!-- TDPUS Workgroups -->
                        <div v-if="workgroups.length > 0">
                            <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-3">
                                TDPUS Companies
                            </h6>
                            <div class="flex flex-wrap gap-4">
                                <DynamicDropdown
                                    v-for="wg in workgroups"
                                    :key="wg.id"
                                    :label="wg.company_count != null ? `${wg.name} (${wg.company_count} stores)` : wg.name"
                                    v-model="companiesByWorkgroup[wg.id]"
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

                        <!-- External Workgroups -->
                        <div v-if="externalWorkgroups.length > 0" class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-3">
                                External Companies
                            </h6>
                            <div class="flex flex-wrap gap-4">
                                <DynamicDropdown
                                    v-for="wg in externalWorkgroups"
                                    :key="'ext-' + wg.id"
                                    :label="wg.name"
                                    v-model="externalCompaniesByWorkgroup[wg.id]"
                                    resource="upload-companies"
                                    :params="{ workgroup_id: wg.id }"
                                    display-name="name"
                                    placeholder="Select companies"
                                    :required="false"
                                    :removeNullOption="true"
                                    multiple
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Folder Access Section (non-admin only) -->
                <div v-if="form.role && form.role.name !== 'admin'" class="pt-4 border-t border-gray-200 dark:border-gray-700">
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

                <!-- Special Permissions Section (non-admin only) -->
                <div v-if="form.role && form.role.name !== 'admin'" class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white !mb-4">
                        Special Permissions
                    </h6>
                    <div v-if="spPermissionsLoading" class="flex items-center justify-center py-8">
                        <Spinner size="sm" text="Loading special permissions..." />
                    </div>
                    <div v-else-if="form.sp_permission && form.sp_permission.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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
import { useSPPermission } from '@/composables/useSPPermission'

const { can } = useSPPermission()
const route = useRoute()
const resource = route.meta?.resource || 'settings/users'

// Use the useFormable composable
const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/users','user')

// Special permissions state
const spPermissionsLoading = ref(false)

const workgroups = ref([])
const workgroupsLoading = ref(false)
const companiesByWorkgroup = reactive({})

const externalWorkgroups = ref([])
const externalCompaniesByWorkgroup = reactive({})

const ensureWorkgroupKeys = () => {
    for (const wg of workgroups.value) {
        if (!(wg.id in companiesByWorkgroup)) {
            companiesByWorkgroup[wg.id] = []
        }
    }
    for (const wg of externalWorkgroups.value) {
        if (!(wg.id in externalCompaniesByWorkgroup)) {
            externalCompaniesByWorkgroup[wg.id] = []
        }
    }
}

const distributeFormCompaniesByWorkgroup = () => {
    const list = form.value.companies
    for (const wg of workgroups.value) {
        companiesByWorkgroup[wg.id] = []
    }
    if (!Array.isArray(list) || list.length === 0) return
    for (const c of list) {
        const wgid = c.workgroup_id
        if (wgid == null) continue
        if (!(wgid in companiesByWorkgroup)) {
            companiesByWorkgroup[wgid] = []
        }
        companiesByWorkgroup[wgid].push({ id: c.id, name: c.name })
    }
}

const distributeFormExternalCompaniesByWorkgroup = () => {
    for (const wg of externalWorkgroups.value) {
        externalCompaniesByWorkgroup[wg.id] = []
    }
}

const mergedCompanyIds = () => {
    const ids = []
    for (const wg of workgroups.value) {
        const selected = companiesByWorkgroup[wg.id]
        if (!Array.isArray(selected)) continue
        selected.forEach((c) => ids.push(c.id))
    }
    return [...new Set(ids)]
}

const mergedExternalCompanyIds = () => {
    const ids = []
    for (const wg of externalWorkgroups.value) {
        const selected = externalCompaniesByWorkgroup[wg.id]
        if (!Array.isArray(selected)) continue
        selected.forEach((c) => ids.push(c.id))
    }
    return [...new Set(ids)]
}

const fetchWorkgroupsForCompanies = async () => {
    if (!form.value.role || form.value.role.name === 'admin') return
    workgroupsLoading.value = true
    try {
        const [tdpusRes, externalRes] = await Promise.all([
            axios.get('/api/search/workgroups', {
                params: { query: '', column: 'name' },
            }),
            axios.get('/api/search/upload-workgroups', {
                params: { query: '', column: 'name' },
            })
        ])
        workgroups.value = tdpusRes.data?.collection || []
        externalWorkgroups.value = externalRes.data?.collection || []
        ensureWorkgroupKeys()
        distributeFormCompaniesByWorkgroup()
        distributeFormExternalCompaniesByWorkgroup()
    } catch (e) {
        console.error('Error loading workgroups:', e)
        workgroups.value = []
        externalWorkgroups.value = []
    } finally {
        workgroupsLoading.value = false
    }
}

const fetchSPPermissions = async () => {
    if (mode.value === 'edit') return
    if (!form.value.role || form.value.role.name === 'admin') return
    spPermissionsLoading.value = true
    try {
        const response = await useRequest('get', 'settings/users/sp-permissions')
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

// Watch for selectedRole changes to update form.role_id
watch(() => form.value.role, (newRole) => {
    if (newRole) {
        form.value.role_id = newRole.id
        if (newRole.name !== 'admin') {
            if (mode.value === 'create') {
                fetchSPPermissions()
            }
            fetchWorkgroupsForCompanies()
        } else {
            workgroups.value = []
            externalWorkgroups.value = []
            Object.keys(companiesByWorkgroup).forEach((k) => delete companiesByWorkgroup[k])
            Object.keys(externalCompaniesByWorkgroup).forEach((k) => delete externalCompaniesByWorkgroup[k])
        }
    } else {
        form.value.role_id = ''
    }
}, { immediate: true })

watch(
    () => form.value.companies,
    () => {
        if (show.value && workgroups.value.length && form.value.role?.name !== 'admin') {
            distributeFormCompaniesByWorkgroup()
        }
    },
    { deep: true }
)

watch(
    () => form.value.external_companies,
    () => {
        if (show.value && externalWorkgroups.value.length && form.value.role?.name !== 'admin') {
            distributeFormExternalCompaniesByWorkgroup()
        }
    },
    { deep: true }
)

// Expose setData for useFormable route guards
defineExpose({
    setData
})

const handleSave = () => {
    const obj = {
        ...form.value,
        office_id: form.value.office?.id ?? null,
        companies: mergedCompanyIds(),
        external_companies: mergedExternalCompanyIds(),
        folder_access: form.value.folder_access ? form.value.folder_access.map(folder => folder.id) : []
    }
    save(obj)
}

const clearCompanies = () => {
    for (const wg of workgroups.value) {
        companiesByWorkgroup[wg.id] = []
    }
    for (const wg of externalWorkgroups.value) {
        externalCompaniesByWorkgroup[wg.id] = []
    }
    form.value.companies = []
    form.value.external_companies = []
}
</script>

<style scoped>
textarea:focus,
select:focus {
    outline: none !important;
    outline-offset: 0 !important;
}

@media (max-width: 640px) {
    .user-form {
        padding: 1rem;
    }
}
</style>

