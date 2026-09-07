<template>
    <div v-if="show" class="state-form">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold">
                        {{ mode === 'edit' ? 'Edit State' : 'Create New State' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <Input 
                        v-model="form.name" 
                        label="Name" 
                        placeholder="Enter state name" 
                        :required="true"
                        :error="errors.name ? errors.name[0] : null" 
                        icon-left="map"
                    />

                    <!-- Active Status -->
                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                            Status
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.active"
                                :true-value="true"
                                :false-value="false"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Active
                            </span>
                        </label>
                        <p v-if="errors.active" class="text-xs text-red-600 dark:text-red-400 mt-1">
                            {{ errors.active[0] }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <Button variant="outline-secondary" size="md" @click="cancel" type="button">
                        Cancel
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving" 
                        v-if="mode === 'create' ? access.includes('create') : access.includes('update')">
                        {{ mode === 'edit' ? 'Update State' : 'Create State' }}
                    </Button>
                </div>
            </form>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading form..." centered />
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/states'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/states', 'state')

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .state-form {
        padding: 1rem;
    }
}
</style>

