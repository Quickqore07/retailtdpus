<template>
    <div v-if="show" class="role-show">
        <!-- Role Information Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">Role Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                        to="/settings/roles">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update') && !model.not_editable"
                        :to="`/settings/roles/${model.id}/edit`">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete') && !model.not_deletable">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Name -->
                <Label label="Role Name" :value="model.name" />

                <!-- Users Count -->
                <Label label="Assigned Users">
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                        {{ model.users_count || 0 }} {{ model.users_count === 1 ? 'user' : 'users' }}
                    </span>
                </Label>

                <!-- Created At -->
                <Label label="Created At" :value="formatDate(model.created_at)" />

                <!-- Updated At -->
                <Label label="Updated At" :value="formatDate(model.updated_at)" />
            </div>
        </Panel>

        <!-- Folder Access Panel -->
        <Panel title="Folder Access" :divider="true" class="mt-6" v-if="model.folders && model.folders.length > 0">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="folder in model.folders"
                    :key="folder.id"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-lg"
                >
                    {{ folder.name }}
                </span>
            </div>
        </Panel>

        <!-- Permissions Panel -->
        <Panel title="Permissions" :divider="true" class="mt-6" v-if="!isAdminRole">
            <div v-if="model.permissions && model.permissions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <div 
                    v-for="permission in model.permissions" 
                    :key="permission.name"
                    class="permission-group  p-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 capitalize !mb-3">
                        {{ permission.name }}
                    </h6>
                    
                    <div class="flex flex-wrap gap-6">
                        <span 
                            v-for="(value, action) in permission.actions" 
                            :key="action"
                            :class="[
                                'inline-flex items-center px-3 py-2 text-xs font-medium rounded-full max-w-fit',
                                value === 1 
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                            ]"
                        >
                            <SvgIcon 
                                :name="value === 1 ? 'check' : 'x'" 
                                size="xs" 
                                class="mr-1" 
                            />
                            {{ action.replace('_', ' ').replace('-', ' ') }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                No permissions assigned to this role
            </div>
        </Panel>

        <!-- Notification permissions -->
        <Panel title="Notification permissions" :divider="true" class="mt-6">
            <div v-if="notificationSchemaLoading" class="flex items-center justify-center py-8">
                <Spinner size="sm" text="Loading notification types..." />
            </div>
            <div
                v-else-if="notificationPermissionTypes.length > 0"
                class="flex flex-wrap gap-6 "
            >
                <span
                    v-for="item in notificationPermissionTypes"
                    :key="item.key"
                    :class="[
                        'inline-flex items-center px-3 py-2 text-xs font-medium rounded-full max-w-fit',
                        hasNotificationKey(item.key)
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                    ]"
                >
                    <SvgIcon
                        :name="hasNotificationKey(item.key) ? 'check' : 'x'"
                        size="xs"
                        class="mr-1"
                    />
                    {{ item.label }}
                </span>
            </div>
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                No notification types configured
            </div>
        </Panel>
    </div>

    <!-- Loading State -->
    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading role details..." centered />
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { formatDateTime as formatDate } from '@/utils/date'
import { useRequest } from '@/services/api'
import { ref, computed, onMounted } from 'vue'
const route = useRoute()
const resource = route.meta?.resource || 'roles'

// Use the useShowable composable
const { model, show, setData, removeDB, access } = useShowable(resource,'role')

// Handle delete action
const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}
const allCompanies = ref([])
const notificationPermissionTypes = ref([])
const notificationSchemaLoading = ref(false)

const roleNotificationKeys = computed(() => {
    const n = model.value?.notification_permissions
    return Array.isArray(n) ? n : []
})

const isAdminRole = computed(() => {
    const name = model.value?.name
    return typeof name === 'string' && name.toLowerCase() === 'admin'
})

const hasNotificationKey = (key) => roleNotificationKeys.value.includes(key)

const fetchAllCompanies = async () => {
    try {
        const response = await useRequest('get', '/search/companies?query=&column=name')
        console.log(response)
        allCompanies.value = response.collection
    } catch (error) {
        console.error('Error fetching all companies:', error)
        allCompanies.value = []
    }
}

const fetchNotificationSchema = async () => {
    notificationSchemaLoading.value = true
    try {
        const response = await useRequest('get', 'settings/roles/permissions')
        notificationPermissionTypes.value = response.notification_permissions_schema || []
    } catch (error) {
        console.error('Error fetching notification permission schema:', error)
        notificationPermissionTypes.value = []
    } finally {
        notificationSchemaLoading.value = false
    }
}

onMounted(() => {
    fetchAllCompanies()
    fetchNotificationSchema()
})
</script>

<style scoped>
.permission-group {
    transition: all 0.2s ease;
}

@media (max-width: 640px) {
    .role-show {
        padding: 1rem;
    }

    .mb-6 {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }

    .mb-6>div:last-child {
        width: 100%;
    }

    .mb-6 .flex.items-center.gap-2 {
        width: 100%;
        flex-wrap: wrap;
    }

    .mb-6 .flex.items-center.gap-2>* {
        flex: 1;
        min-width: fit-content;
    }
}
</style>

