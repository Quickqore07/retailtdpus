<template>
    <div v-if="model" class="company-group-show">
        <Panel>
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">{{ model.name }}</h5>
                    <div class="flex items-center gap-2">
                        <Button
                            icon-left="arrow-left"
                            icon-size="sm"
                            variant="secondary"
                            size="sm"
                            :to="`/settings/company-groups`"
                            >
                            </Button>
                        <Button 
                            v-if="access.includes('update')"
                            variant="primary" 
                            size="sm" 
                            @click="$router.push({name: 'settings.company-groups.edit', params: { id: model.id }})"
                            icon-left="edit"
                        >
                        </Button>
                        <Button
                            icon-left="trash"
                            icon-size="sm"
                            variant="danger"
                            size="sm"
                            @click="handleDelete"
                            v-if="access.includes('delete')"
                            >
                            </Button>
                    </div>
                </div>
            </template>

            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h6 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                        Group Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Group Name
                            </label>
                            <p class="text-base text-gray-900 dark:text-white">
                                {{ model.name }}
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Status
                            </label>
                            <span 
                                :class="[
                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                    model.active 
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                ]"
                            >
                                {{ model.active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Total Companies
                            </label>
                            <p class="text-base text-gray-900 dark:text-white">
                                {{ model.companies?.length || 0 }} companies
                            </p>
                        </div>
                    </div>

                    <div v-if="model.description" class="mt-4 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Description
                        </label>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ model.description }}
                        </p>
                    </div>
                </div>

                <!-- Companies List -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h6 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                        Companies in this Group ({{ model.companies?.length || 0 }})
                    </h6>
                    
                    <div v-if="model.companies_by_workgroup && model.companies_by_workgroup.length > 0" class="space-y-6 flex gap-6 flex-wrap">
                        <div
                            v-for="group in model.companies_by_workgroup"
                            :key="group.workgroup_id + '-' + group.type"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden min-w-[280px]"
                        >
                            <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <span>{{ group.workgroup_name }}</span>

                            </h6>
                            <ul class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[320px] overflow-y-auto">
                                <li
                                    v-for="company in group.companies_array"
                                    :key="company.id"
                                    class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors"
                                    @click="viewCompany(company.id)"
                                >
                                    {{ company.name }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <div class="text-gray-400 dark:text-gray-500 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h6 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            No Companies Assigned
                        </h6>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            This group doesn't have any companies assigned yet.
                        </p>
                        <Button 
                            v-if="access.includes('update')"
                            variant="primary" 
                            @click="$router.push({name: 'settings.company-groups.edit', params: { id: model.id }})"
                        >
                            Add Companies
                        </Button>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h6 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                        Timestamps
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Created At
                            </label>
                            <p class="text-base text-gray-900 dark:text-white">
                                {{ formatDateTime(model.created_at) }}
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Last Updated
                            </label>
                            <p class="text-base text-gray-900 dark:text-white">
                                {{ formatDateTime(model.updated_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading company group..." centered />
    </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import { formatDateTime } from '@/utils/date'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const router = useRouter()
const resource = route.meta?.resource || 'settings/company-groups'

const { model, access, removeDB } = useShowable(resource, 'company-group')

const viewCompany = (companyId) => {
    router.push({ name: 'settings.companies.show', params: { id: companyId } })
}

const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}
</script>