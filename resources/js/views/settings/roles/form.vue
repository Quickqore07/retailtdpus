<template>
    <div v-if="show" class="role-form">
        <!-- Form Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ formTitle }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Role Name -->
                <div v-if="!isNotificationOnlyEdit" class="grid gap-4  md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-3    ">
                    <Input 
                        v-model="form.name" 
                        label="Role Name" 
                        placeholder="Enter role name" 
                        :required="true"
                        :error="errors.name ? errors.name[0] : null" 
                    />
                    <div class='flex items-end justify-between gap-4'>
                        <DynamicDropdown 
                            label="Companies"
                            v-model="form.companies" 
                            resource="companies" 
                            display-name="name" 
                            placeholder="Select companies" 
                            :required="false"
                            :removeNullOption="true"
                            multiple
                            :error="errors.companies ? errors.companies[0] : null" 
                        />
                        <Button variant="outline-danger" size="md" @click="clearCompanies" custom-class="max-w-[150px] h-auto" type="button">
                            Clear Companies
                        </Button>
                    </div>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-3">
                    <Input
                        v-model="form.name"
                        label="Role Name"
                        :disabled="true"
                    />
                </div>
                <div v-if="!isNotificationOnlyEdit" class="grid gap-4  md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-3 ">

                    <DynamicDropdown 
                    label="Folder Access"
                    v-model="form.folder_access" 
                    resource="upload-folders" 
                    display-name="name" 
                    placeholder="Select folders" 
                    :required="false"
                    :removeNullOption="true"
                    multiple
                    :error="errors.folder_access ? errors.folder_access[0] : null" 
                    />
                </div>

                <!-- Permissions Section -->
                <div v-if="!isNotificationOnlyEdit">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white !mb-4">
                        Permissions
                    </h6>
                    
                    <div v-if="permissionsLoading" class="flex items-center justify-center py-8">
                        <Spinner size="sm" text="Loading permissions..." />
                    </div>

                    <div v-else-if="availablePermissions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        <div 
                            v-for="permissionIndex in visiblePermissionIndexes" 
                            :key="form.permissions[permissionIndex].name"
                            class="permission-group p-4 bg-gray-50 dark:bg-gray-900 rounded-lg col-span-1"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 capitalize">
                                    {{ form.permissions[permissionIndex].name?.replace(/-/g, ' ') }}
                                </h6>
                                <button
                                    type="button"
                                    @click="toggleAllActions(permissionIndex)"
                                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                >
                                    {{ isAllActionsSelected(permissionIndex) ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>
                            
                            <div class="flex flex-wrap gap-6">
                                <label 
                                    v-for="(value, action) in form.permissions[permissionIndex].actions" 
                                    :key="action"
                                    class="flex items-center space-x-2 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.permissions[permissionIndex].actions[action]"
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

                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No permissions available
                    </div>

                    <p v-if="errors.permissions" class="text-xs text-red-600 dark:text-red-400 mt-2">
                        {{ errors.permissions[0] }}
                    </p>
                </div>

                <!-- Notification permissions -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white !mb-4">
                        Notification permissions
                    </h6>
                    <div v-if="permissionsLoading" class="flex items-center justify-center py-4">
                        <Spinner size="sm" text="Loading..." />
                    </div>
                    <div
                        v-else
                        class="permission-group p-4 bg-gray-50 dark:bg-gray-900 rounded-lg flex flex-wrap gap-6"
                    >
                        <label
                            v-for="item in notificationPermissionTypes"
                            :key="item.key"
                            class="flex items-center space-x-2 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                v-model="form.notification_permissions"
                                :value="item.key"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ item.label }}
                            </span>
                        </label>
                    </div>
                    <p v-if="errors.notification_permissions" class="text-xs text-red-600 dark:text-red-400 mt-2">
                        {{ errors.notification_permissions[0] }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button variant="outline-secondary" size="md" @click="cancel" type="button">
                        Cancel
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving" v-if="access.includes('create')">
                        {{ submitButtonLabel }}
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
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import { useRequest } from '@/services/api'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/roles'

// Use the useFormable composable
const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/roles','role')

const isNotificationOnlyEdit = computed(() => Boolean(form.value?.notification_only_edit))

const formTitle = computed(() => {
    if (isNotificationOnlyEdit.value) {
        return 'Edit Admin Notification Access'
    }
    return mode.value === 'edit' ? 'Edit Role' : 'Create New Role'
})

const submitButtonLabel = computed(() => {
    if (isNotificationOnlyEdit.value) {
        return 'Update Notifications'
    }
    return mode.value === 'edit' ? 'Update Role' : 'Create Role'
})

// Permissions state
const availablePermissions = ref([])
const notificationPermissionTypes = ref([])
const permissionsLoading = ref(false)

const visiblePermissionIndexes = computed(() => {
    const permissions = form.value?.permissions || []
    return permissions.map((_, index) => index)
})

// Fetch available permissions
const fetchPermissions = async () => {
    permissionsLoading.value = true
    try {
        const response = await useRequest('get', 'settings/roles/permissions')
        availablePermissions.value = response.permissions || []
        notificationPermissionTypes.value = response.notification_permissions_schema || []
        if (!Array.isArray(form.value.notification_permissions)) {
            form.value.notification_permissions = []
        }
        // If creating new role, initialize permissions
        if (mode.value === 'create' && (!form.value?.permissions || form.value.permissions?.length === 0)) {
            form.value.permissions = availablePermissions.value.map(permission => ({
                name: permission.name,
                actions: Object.keys(permission.actions).reduce((acc, action) => {
                    acc[action] = 0
                    return acc
                }, {})
            }))
        }
    } catch (error) {
        console.error('Error fetching permissions:', error)
    } finally {
        permissionsLoading.value = false
    }
}

// Toggle all actions for a permission group
const toggleAllActions = (permissionIndex) => {
    const permission = form.value.permissions[permissionIndex]
    const allSelected = isAllActionsSelected(permissionIndex)
    
    Object.keys(permission.actions).forEach(action => {
        permission.actions[action] = allSelected ? 0 : 1
    })
}
const clearCompanies = () => {
    form.value.companies = []
}

// Check if all actions are selected for a permission group
const isAllActionsSelected = (permissionIndex) => {
    const permission = form.value.permissions[permissionIndex]
    return Object.values(permission.actions).every(value => value === 1)
}

// Watch for form changes
watch(() => show.value, (newValue) => {
    if (newValue) {
        fetchPermissions()
    }
})

// Fetch permissions on mount
onMounted(() => {
    if (show.value) {
        fetchPermissions()
    }
})

const handleSave = async () => {
    try {
        const obj = isNotificationOnlyEdit.value
            ? { notification_permissions: form.value.notification_permissions ?? [] }
            : {
                ...form.value,
                permissions: form.value.permissions || [],
                companies: form.value.companies.map(company => company.id),
                folder_access: form.value.folder_access ? form.value.folder_access.map(folder => folder.id) : []
            }
        await save(obj)
    } catch (error) {
        console.error('Error saving role:', error)
    }
}
// Expose setData for useFormable route guards
defineExpose({
    setData
})
</script>

<style scoped>
textarea:focus,
select:focus {
    outline: none !important;
    outline-offset: 0 !important;
}


@media (max-width: 640px) {
    .role-form {
        padding: 1rem;
    }
}
</style>

