<template>
    <div v-if="show" class="fund-requirement-form">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Fund Requirement' : 'Create New Fund Requirement' }}
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
                            v-model="form.label" 
                            label="Label" 
                            placeholder="Enter label" 
                            :required="true"
                            :error="errors.label ? errors.label[0] : null" 
                            icon-left="tag"
                        />

                        <DynamicDropdown
                            label="Type"
                            v-model="form.type"
                            :custom-options="typeOptions"
                            placeholder="Select type"
                            :required="true"
                            :error="errors.type ? errors.type[0] : null"
                            icon-left="list"
                            @change="handleTypeChange"
                        />

                        <DynamicDropdown
                            label="Condition Type"
                            v-model="form.condition_type"
                            :custom-options="conditionTypeOptions"
                            placeholder="Select condition type"
                            :required="true"
                            :error="errors.condition_type ? errors.condition_type[0] : null"
                            @change="handleConditionChange"

                            icon-left="calendar"
                        />

                        <Input 
                            v-model="form.condition_value" 
                            :label="renderConditionValueLabel" 
                            type="date"
                            :required="true"
                            :error="errors.condition_value ? errors.condition_value[0] : null" 
                            icon-left="calendar"
                        />

                        <Input 
                            v-if="form.type?.id === 'fixed'"
                            v-model="form.amount" 
                            label="Amount ($)" 
                            placeholder="0.00" 
                            type="number"
                            step="0.01"
                            min="0"
                            :required="form.type?.id === 'fixed'"
                            :error="errors.amount ? errors.amount[0] : null" 
                            icon-left="dollar"
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

                <!-- Company Wise Selection -->
                <div v-if="form.type?.id === 'company wise'" class="border border-gray-200 dark:border-gray-700 rounded-md">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h6 class="font-semibold !mb-0">Company Amounts</h6>
                        <div class="flex items-center gap-2">
                            <div v-for="workgroup in workgroups" :key="workgroup.id" class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="workgroup.selected"
                                    :true-value="true"
                                    :false-value="false"
                                    @change="handleWorkgroupChange(workgroup)"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ workgroup.name }}</span>
                            </div>
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <Th class="text-left px-3 py-3 w-14">No</Th>
                                <th class="text-left px-3 py-3 min-w-[300px]">Company</th>
                                <Th class="text-left px-3 py-3 min-w-[150px]">Amount ($)</Th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(company, index) in filteredCompanies"
                                :key="`company-row-${index}`"
                                class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <Td class="px-3 py-3">{{ index + 1 }}</Td>
                                <Td class="px-3 py-3">
                                    {{ company.company_name }}
                                </Td>
                                <Td class="px-3 py-3">
                                    <Input
                                        v-model="company.amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        :error="errors[`companies.${index}.amount`] ? errors[`companies.${index}.amount`][0] : null"
                                    />
                                </Td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                                <Td colspan="2" class="!px-3">Total</Td>
                                <Td class="!px-3 font-semibold">{{ formatCurrency(calculateTotal()) }}</Td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="errors.companies" class="px-4 py-2 text-xs text-red-600">
                        {{ errors.companies[0] }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <Button variant="outline-secondary" size="md" @click="cancel" type="button">
                        Cancel
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving" 
                        v-if="mode === 'create' ? access.includes('create') : access.includes('update')">
                        {{ mode === 'edit' ? 'Update Fund Requirement' : 'Create Fund Requirement' }}
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
import { computed, watch, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import { useRequest } from '@/services/api'

const route = useRoute()
const resource = route.meta?.resource || 'settings/fund-requirements'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/fund-requirements', 'fund-requirement')

const typeOptions = [
    { id: 'fixed', name: 'Fixed' },
    { id: 'company wise', name: 'Company Wise' }
]

const conditionTypeOptions = [
    { id: 'monthly', name: 'Monthly' },
    { id: 'weekly', name: 'Weekly' },
    { id: 'bi-weekly', name: 'Bi-Weekly' }
]

const workgroups = ref([])

const fetchWorkgroups = async () => {
    const res = await useRequest('get', 'search/workgroups')
    workgroups.value = res?.collection || []

    if(mode.value === 'edit') {
        workgroups.value.forEach(w => {
            w.selected = form.value.companies?.some(c => c.workgroup_id === w.id)
        })
    }else{
        workgroups.value.forEach(w => {
            w.selected = true
        })
    }
}

onMounted(() => {
    fetchWorkgroups()
})

const filteredCompanies = computed(() => {
    return form.value.companies?.filter(c => workgroups.value.some(w => w.selected === true && w.id === c.workgroup_id))
})

const handleTypeChange = () => {
    if (form.value.type?.id === 'fixed') {
        form.value.companies = []
    } else {
        form.value.amount = null
        if (!form.value.companies || form.value.companies.length === 0) {
            form.value.companies = []
        }
    }
}

const calculateTotal = () => {
    if (!form.value.companies || form.value.companies.length === 0) {
        return 0
    }
    return form.value.companies.reduce((sum, company) => {
        const amount = parseFloat(company.amount) || 0
        return sum + amount
    }, 0)
}

const formatCurrency = (value) => {
    if (!value) return '0.00'
    return parseFloat(value).toFixed(2)
}

const renderConditionValueLabel = computed(() => {
    return form.value.condition_type?.id === 'monthly' ? 'Day of Month' : form.value.condition_type?.id === 'weekly' ? 'Start Date' : form.value.condition_type?.id === 'bi-weekly' ? 'Start Date' : 'N/A'
})

const handleConditionChange = () => {
   form.value.condition_value =null
}

const handleWorkgroupChange = (workgroup) => {
    console.log(workgroup)
}

const handleSave = () => {
    const obj = {
        ...form.value,
        type: form.value.type?.id,
        condition_type: form.value.condition_type?.id,
        companies: form.value.type?.id === 'company wise' 
            ? form.value.companies?.map(c => ({
                company_id: c.company_id,
                company_name: c.company_name,
                amount: c.amount,
            }))
            : []
    }
    save(obj)
}

// Watch form data to initialize dropdown values
watch(() => form.value, (newVal) => {
    if (newVal.type && typeof newVal.type === 'string') {
        form.value.type = typeOptions.find(option => option.id === newVal.type)
    }
    if (newVal.condition_type && typeof newVal.condition_type === 'string') {
        form.value.condition_type = conditionTypeOptions.find(option => option.id === newVal.condition_type)
    }

    
    // Format companies data when editing
    if (newVal.companies && Array.isArray(newVal.companies) && newVal.companies.length > 0) {
        form.value.companies = newVal.companies.map(c => ({
            company_id: c.company_id,
            company_name: c.company_name,
            amount: c.amount || '',
            workgroup_id: c.workgroup_id
        }))
    }
})

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .fund-requirement-form {
        padding: 1rem;
    }
}
</style>
