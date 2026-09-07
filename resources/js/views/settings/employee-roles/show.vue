<template>
    <div v-if="show" class="employee-role-show">
        <!-- Role Information Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">Employee Role Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                        to="/settings/employee-roles">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
                        :to="`/settings/employee-roles/${model.id}/edit`">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete')">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-3  lg:grid-cols-5 gap-6">

                <!-- Name -->
                <Label label="Role Name" :value="model.name" />

                <!-- Code -->
                <Label label="Role Code" :value="model.code" />

                <!-- Status -->
                <Label label="Status">
                    <span
                        :class="[
                            'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
                            model.active 
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                        ]"
                    >
                        <SvgIcon 
                            :name="model.active ? 'check' : 'x'" 
                            size="xs" 
                            class="mr-1" 
                        />
                        {{ model.active ? 'Active' : 'Inactive' }}
                    </span>
                </Label>
                <Label label="Tipped">
                    <span
                        :class="[
                            'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
                            model.tipped 
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                        ]"
                    >
                        <SvgIcon 
                            :name="model.tipped ? 'check' : 'x'" 
                            size="xs" 
                            class="mr-1" 
                        />
                        {{ model.tipped ? 'Tipped' : 'Not Tipped' }}
                    </span>
                </Label>

                <!-- Created At -->
                <Label label="Created At" :value="formatDate(model.created_at)" />

                <!-- Updated At -->
                <Label label="Updated At" :value="formatDate(model.updated_at)" />
            </div>
            <div class="mt-6"> 
                <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                    Sub Roles
                </h6>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div v-for="(subRole, index) in model.sub_roles" :key="index">
                        <Label :label="`Sub Role ${index + 1}`" :value="subRole.code" />
                    </div>
                </div>

            </div>
        </Panel>
    </div>

    <!-- Loading State -->
    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading employee role details..." centered />
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

const route = useRoute()
const resource = route.meta?.resource || 'settings/employee-roles'

// Use the useShowable composable
const { model, show, setData, removeDB, access } = useShowable(resource, 'employee-role')

// Handle delete action
const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}

// Expose setData for useShowable route guards
defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .employee-role-show {
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

