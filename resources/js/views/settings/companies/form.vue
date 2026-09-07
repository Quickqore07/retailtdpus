<template>
    <div v-if="show" class="company-form">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Company' : 'Create New Company' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Basic Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Input 
                            v-model="form.name" 
                            label="Company Name" 
                            placeholder="Enter company name" 
                            :required="true"
                            :error="errors.name ? errors.name[0] : null" 
                            icon-left="building"
                        />

                        <Input 
                            v-model="form.store_number" 
                            label="Company Store Number" 
                            placeholder="Enter company store number" 
                            :required="true"
                            :error="errors.store_number ? errors.store_number[0] : null" 
                            icon-left="hash"
                        />

                        <Input 
                            v-model="form.password" 
                            label="Password" 
                            type="password"
                            placeholder="Enter password" 
                            :error="errors.password ? errors.password[0] : null" 
                            icon-left="lock"
                        />

                        <!-- Workgroup -->
                            <DynamicDropdown
                                label="Workgroup"
                                v-model="form.workgroup"
                                resource="workgroups"
                                display-name="name"
                                placeholder="Select a workgroup"
                                :required="true"
                                :error="errors.workgroup_id ? errors.workgroup_id[0] : null"
                                icon-left="building"
                            />

                        <Input 
                            v-model="form.payroll_id" 
                            label="Payroll ID" 
                            placeholder="Enter payroll ID" 
                            :error="errors.payroll_id ? errors.payroll_id[0] : null" 
                            icon-left="hash"
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
                        </div>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Location Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- State -->
                            <DynamicDropdown
                                label="State"
                                v-model="form.state"
                                resource="states"
                                display-name="name"
                                placeholder="Select a state"
                                :required="true"
                                :error="errors.state_id ? errors.state_id[0] : null"
                                icon-left="map"
                                @change="handleStateChange($event)" 
                            />

                        <!-- Region -->
                            <DynamicDropdown
                                label="Region"
                                v-model="form.region"
                                :resource="`regions?${form.state?.id ? 'state_id=' + form.state?.id : ''}`"
                                display-name="name"
                                placeholder="Select a region"
                                :error="errors.region_id ? errors.region_id[0] : null"
                                icon-left="map"
                                @change="handleRegionChange($event)" 
                            />

                        <!-- Area -->
                            <DynamicDropdown
                                label="Area"
                                v-model="form.area"
                                :resource="`areas?${form.region?.id ? 'region_id=' + form.region?.id : ''} &${form.state?.id ? 'state_id=' + form.state?.id : ''}`"
                                display-name="name"
                                placeholder="Select an area"
                                :error="errors.area_id ? errors.area_id[0] : null"
                                icon-left="map"
                            />

                            <DynamicDropdown
                                label="County"
                                v-model="form.county"
                                :resource="`counties?${form.state?.id ? 'state_id=' + form.state?.id : ''}`"
                                display-name="name"
                                placeholder="Select a county"
                                :error="errors.county_id ? errors.county_id[0] : null"
                                icon-left="map"
                            />

                        <Input 
                            v-model="form.address" 
                            label="Address" 
                            placeholder="Enter address" 
                            :error="errors.address ? errors.address[0] : null" 
                            icon-left="map-pin"
                        />
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Contact Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Input 
                            v-model="form.email" 
                            label="Email" 
                            type="email"
                            placeholder="Enter email address" 
                            :error="errors.email ? errors.email[0] : null" 
                            icon-left="mail"
                        />

                        <Input 
                            v-model="form.contact_person" 
                            label="Contact Person" 
                            placeholder="Enter contact person name" 
                            :error="errors.contact_person ? errors.contact_person[0] : null" 
                            icon-left="user"
                        />

                        <Input 
                            v-model="form.contact_number" 
                            label="Contact Number" 
                            type="tel"
                            placeholder="Enter contact number" 
                            :error="errors.contact_number ? errors.contact_number[0] : null" 
                            icon-left="phone"
                        />

                        <Input 
                            v-model="form.website" 
                            label="Website" 
                            placeholder="Enter website URL" 
                            :error="errors.website ? errors.website[0] : null" 
                            icon-left="globe"
                        />
                    </div>
                </div>

                <!-- Tax & Payroll Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Tax & Payroll Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Input 
                            v-model="form.employer_identification_number" 
                            label="Employer Identification Number (EIN)" 
                            placeholder="Enter EIN" 
                            :error="errors.employer_identification_number ? errors.employer_identification_number[0] : null" 
                            icon-left="hash"
                        />

                        <Input 
                            v-model="form.payroll_start_date" 
                            label="Payroll Start Date" 
                            type="date"
                            :error="errors.payroll_start_date ? errors.payroll_start_date[0] : null" 
                            icon-left="calendar"
                        />

                        <!-- Payroll Frequency -->
                            <DynamicDropdown
                                label="Payroll Frequency"
                                v-model="form.payroll_frequency_obj"
                                :custom-options="payrollFrequencies"
                                display-name="name"
                                placeholder="Select payroll frequency"
                                :removable="true"
                                :searchable="false"
                            />

                        <!-- Trash Frequency -->
                            <DynamicDropdown
                                label="Trash Frequency"
                                v-model="form.trash_frequency_obj"
                                :custom-options="trashFrequencies"
                                display-name="name"
                                placeholder="Select trash frequency"
                                :removable="true"
                                :searchable="false"
                            />

                        <!-- Tax -->
                            <DynamicDropdown
                                label="Tax"
                                v-model="form.tax_obj"
                                :custom-options="taxOptions"
                                display-name="name"
                                placeholder="Select tax option"
                                :removable="true"
                                :searchable="false"
                            />

                        <Input 
                            v-model="form.st_number" 
                            label="ST Number" 
                            placeholder="Enter ST number" 
                            :error="errors.st_number ? errors.st_number[0] : null" 
                            icon-left="hash"
                        />

                        <Input 
                            v-model="form.pin" 
                            label="PIN" 
                            placeholder="Enter PIN" 
                            :error="errors.pin ? errors.pin[0] : null" 
                            icon-left="key"
                        />

                        <Input 
                            v-model="form.payroll_percentage" 
                            label="Payroll Percentage" 
                            type="number"
                            min="0"
                            max="100"
                            placeholder="Enter payroll percentage" 
                            :error="errors.payroll_percentage ? errors.payroll_percentage[0] : null" 
                            icon-left="percent"
                        />

                        <Input 
                            v-model="form.sales_tax_percentage" 
                            label="Sales Tax Percentage" 
                            type="number"
                            min="0"
                            max="100"
                            placeholder="Enter sales tax percentage" 
                            :error="errors.sales_tax_percentage ? errors.sales_tax_percentage[0] : null" 
                            icon-left="percent"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button variant="outline-secondary" size="md" @click="cancel" type="button">
                        Cancel
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving" 
                        v-if="mode === 'create' ? access.includes('create') : access.includes('update')">
                        {{ mode === 'edit' ? 'Update Company' : 'Create Company' }}
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
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/companies'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/companies', 'company')

const payrollFrequencies = [
    { id: 'Weekly', name: 'Weekly' },
    { id: 'Bi-Weekly', name: 'Bi-Weekly' }
]

const trashFrequencies = [
    { id: '1', name: 'One in a Week' },
    { id: '2', name: 'Two in a Week' },
    { id: '3', name: 'Three in a Week' }
]

const taxOptions = [
    { id: 'Monthly', name: 'Monthly' },
    { id: 'Quarterly', name: 'Quarterly' },
    { id: 'No Tax', name: 'No Tax' }
]
watch(() => form.value.area, (newArea) => {
    if (newArea) {
        if (!form.value.region?.id) {
            form.value.region = newArea.region
        }
        if (!form.value.state?.id) {
            form.value.state = newArea.region.state
        }
    }
})

const handleStateChange = (newState) => {
    form.value.region = null
    form.value.area = null
}
const handleRegionChange = (newRegion) => {
    form.value.area = null
}
// Initialize dropdown objects from string values on edit
watch(() => form.value, (newVal) => {
    if (newVal.payroll_frequency && !newVal.payroll_frequency_obj) {
        newVal.payroll_frequency_obj = payrollFrequencies.find(f => f.id === newVal.payroll_frequency)
    }
    if (newVal.trash_frequency && !newVal.trash_frequency_obj) {
        newVal.trash_frequency_obj = trashFrequencies.find(f => f.id === newVal.trash_frequency)
    }
    if (newVal.tax && !newVal.tax_obj) {
        newVal.tax_obj = taxOptions.find(t => t.id === newVal.tax)
    }
}, { deep: true })

const handleSave = () => {
    const obj = {
        ...form.value,
        payroll_frequency: form.value.payroll_frequency_obj?.id,
        trash_frequency: form.value.trash_frequency_obj?.id,
        tax: form.value.tax_obj?.id,
        state_id: form.value.state?.id,
        region_id: form.value.region?.id,
        area_id: form.value.area?.id,
        workgroup_id: form.value.workgroup?.id,
        county_id: form.value.county?.id
    }
    save(obj)
}

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .company-form {
        padding: 1rem;
    }
}
</style>

