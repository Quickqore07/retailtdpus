<template>
    <div v-if="show" class="office-form">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Office' : 'Create New Office' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Input
                        v-model="form.name"
                        label="Office Name"
                        placeholder="Enter office name"
                        :required="true"
                        :error="errors.name ? errors.name[0] : null"
                    />

                    <Input
                        v-model="form.latitude"
                        label="Latitude"
                        type="number"
                        step="any"
                        placeholder="e.g. 38.8901"
                        :required="true"
                        :error="errors.latitude ? errors.latitude[0] : null"
                    />

                    <Input
                        v-model="form.longitude"
                        label="Longitude"
                        type="number"
                        step="any"
                        placeholder="e.g. -77.0364"
                        :required="true"
                        :error="errors.longitude ? errors.longitude[0] : null"
                    />

                    <Input
                        v-model="form.authorized_radius"
                        label="Authorized Radius (meters)"
                        type="number"
                        min="1"
                        placeholder="e.g. 300"
                        :required="true"
                        :error="errors.authorized_radius ? errors.authorized_radius[0] : null"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-0">
                            Timezone <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.timezone"
                            class="w-full min-h-[33px] px-3 py-1.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-900/50 border rounded-md border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                            required
                        >
                            <option v-for="tz in timezoneOptions" :key="tz" :value="tz">
                                {{ tz }}
                            </option>
                        </select>
                        <p v-if="errors.timezone" class="text-xs text-red-600 dark:text-red-400 mt-1">
                            {{ errors.timezone[0] }}
                        </p>
                    </div>

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
                    <Button
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="isSaving"
                        v-if="mode === 'create' ? access.includes('create') : access.includes('update')"
                    >
                        {{ mode === 'edit' ? 'Update Office' : 'Create Office' }}
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
const resource = route.meta?.resource || 'settings/offices'

const timezoneOptions = [
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Phoenix',
    'America/Los_Angeles',
    'America/Anchorage',
    'Pacific/Honolulu',
    'UTC',
]

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/offices', 'office')

defineExpose({
    setData
})
</script>
