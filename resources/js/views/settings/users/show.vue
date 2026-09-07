<template>
    <div v-if="show" class="user-show">
        <!-- Header -->

        <!-- User Information Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">User Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                        to="/settings/users">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs"
                        :to="`/settings/users/${model.id}/edit`" v-if="access.includes('update')">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="!model.not_deletable && access.includes('delete')">
                        </Button>
                        <Button icon-left="lock" icon-size="sm" variant="secondary" size="xs" @click="handleResetPassword" v-if="model.email && access.includes('update')" title="Send password reset email">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Name -->
                <Label label="Name" :value="model.name" />

                <!-- Username -->
                <Label label="Username" :value="model.username" />

                <!-- Email -->
                <Label label="Email For OTP">
                    <a v-if="model.email" :href="`mailto:${model.email}`"
                        class="text-blue-600 hover:text-blue-800 hover:underline">
                        {{ model.email }}
                    </a>
                    <span v-else class="text-gray-400">-</span>
                </Label>

                <!-- Current Password -->
                <Label label="Current Password" :value="model.current_password" v-if="can('user', 'password')" />

                <!-- Phone -->
                <Label label="Phone For OTP" :value="model.phone" />

                <!-- Role -->
                <Label label="Role">
                    <span v-if="model.role"
                        class="inline-flex items-center px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                        {{ model.role.name || model.role.title || '-' }}
                    </span>
                    <span v-else class="text-gray-400">-</span>
                </Label>

                <!-- Office -->
                <Label label="Office" :value="model.office?.name || '-'" />

                <!-- Checkout Time -->
                <Label label="Checkout Time" :value="formatCheckoutTime(model.checkout_time)" />

                <!-- Created At -->
                <Label label="Created At" :value="formatDate(model.created_at)" />

                <!-- Updated At -->
                <Label label="Updated At" :value="formatDate(model.updated_at)" />
            </div>
        </Panel>
        <Panel
            title="Companies"
            :divider="true"
            class="mt-6"
            v-if="model.companies_by_workgroup && model.companies_by_workgroup.length > 0"
        >
            <div class="space-y-6 flex gap-6 flex-wrap">
                <div
                    v-for="group in model.companies_by_workgroup"
                    :key="group.workgroup_id + '-' + group.type"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden min-w-[280px]"
                >
                    <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <span>{{ group.workgroup_name }}</span>
                        <span 
                            :class="[
                                'text-xs px-2 py-1 rounded-full font-medium',
                                group.type === 'external' 
                                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300'
                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                            ]"
                        >
                            {{ group.type === 'external' ? 'External' : 'TDPUS' }}
                        </span>
                    </h6>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[320px] overflow-y-auto">
                        <li
                            v-for="company in group.companies_array"
                            :key="company.id"
                            class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300"
                        >
                            {{ company.name }}
                        </li>
                    </ul>
                </div>
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

        <!-- Special Permissions Panel (non-admin only) -->
        <Panel title="Special Permissions" :divider="true" class="mt-6" v-if="model.role && model.role.name !== 'admin'">
            <div v-if="model.sp_permission && model.sp_permission.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div
                    v-for="permission in model.sp_permission"
                    :key="permission.name"
                    class="permission-group p-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 capitalize !mb-3">
                        {{ (permission.name || '').replace(/-/g, ' ') }}
                    </h6>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
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
                            {{ (action || '').replace('_', ' ') }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                No special permissions assigned
            </div>
        </Panel>
    </div>

    <!-- Loading State -->
    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading user details..." centered />
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
import axios from 'axios'
import { useMessage } from '@/composables/useMessage'
import { formatDateTime as formatDate } from '@/utils/date'
import { useSPPermission } from '@/composables/useSPPermission'

const route = useRoute()
const resource = route.meta?.resource || 'users'

const formatCheckoutTime = (value) => {
    if (!value) return '-'
    return String(value).slice(0, 5)
}
const message = useMessage()
const { can } = useSPPermission()

// Use the useShowable composable
const { model, show, setData, removeDB, access } = useShowable(resource,'user')

// Handle delete action
const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}

// Handle password reset
const handleResetPassword = async () => {
    if (!model.value?.email) {
        message.error('User does not have an email address')
        return
    }

    if (!confirm(`Send password reset email to ${model.value.email}?`)) {
        return
    }

    try {
        const response = await axios.post('/forgot', {
            email: model.value.email
        })

        if (response.data?.success || response.data?.message) {
            message.success(response.data.message || 'Password reset email sent successfully')
        }
    } catch (error) {
        console.error('Error sending password reset:', error)
        const errorMessage = error.response?.data?.message || 'Failed to send password reset email'
        message.error(errorMessage)
    }
}

// Expose setData for useShowable route guards
defineExpose({
    setData
})
</script>

<style scoped>
.permission-group {
    transition: all 0.2s ease;
}

@media (max-width: 640px) {
    .user-show {
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
