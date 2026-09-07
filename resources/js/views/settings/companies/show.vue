<template>
    <div v-if="show" class="company-show space-y-3">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">Company Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                        to="/settings/companies">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
                        :to="`/settings/companies/${model.id}/edit`">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete')">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Basic Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Label label="Name" :value="model.name" />
                        <Label label="Store Number" :value="model.store_number" />
                        <Label label="Workgroup" :value="model.workgroup?.name" />
                        <Label label="Payroll ID" :value="model.payroll_id" />
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
                    </div>
                </div>

                <!-- Location Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Location Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <Label label="State" :value="model.state?.name" />
                        <Label label="Region" :value="model.region?.name" />
                        <Label label="Area" :value="model.area?.name" />
                        <Label label="County" :value="model.county?.name" />
                        <Label label="Address" :value="model.address" />
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Contact Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Label label="Email">
                            <a v-if="model.email" :href="`mailto:${model.email}`"
                                class="text-blue-600 hover:text-blue-800 hover:underline">
                                {{ model.email }}
                            </a>
                            <span v-else class="text-gray-400">-</span>
                        </Label>
                        <Label label="Contact Person" :value="model.contact_person" />
                        <Label label="Contact Number" :value="model.contact_number" />
                        <Label label="Website">
                            <a v-if="model.website" :href="model.website" target="_blank"
                                class="text-blue-600 hover:text-blue-800 hover:underline">
                                {{ model.website }}
                            </a>
                            <span v-else class="text-gray-400">-</span>
                        </Label>
                    </div>
                </div>

                <!-- Tax & Payroll Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Tax & Payroll Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Label label="EIN" :value="model.employer_identification_number" />
                        <Label label="Payroll Start Date" :value="formatDate(model.payroll_start_date)" />
                        <Label label="Payroll Frequency" :value="model.payroll_frequency" />
                        <Label label="Trash Frequency" :value="model.trash_frequency" />
                        <Label label="Tax" :value="model.tax" />
                        <Label label="ST Number" :value="model.st_number" />
                        <Label label="PIN" :value="model.pin" />
                        <Label label="Payroll Percentage" :value="model.payroll_percentage ? `${model.payroll_percentage}%` : '-'" />
                        <Label label="Sales Tax Percentage" :value="model.sales_tax_percentage ? `${model.sales_tax_percentage}%` : '-'" />
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Timestamps
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <Label label="Created At" :value="formatDate(model.created_at)" />
                        <Label label="Updated At" :value="formatDate(model.updated_at)" />
                    </div>
                </div>
            </div>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading company details..." centered />
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
const resource = route.meta?.resource || 'companies'

const { model, show, setData, removeDB, access } = useShowable(resource, 'company')

const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .company-show {
        padding: 1rem;
    }
}
</style>

