<template>
    <div v-if="show" class="company-group-form">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Company Group' : 'Create New Company Group' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Basic Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input 
                            v-model="form.name" 
                            label="Group Name" 
                            placeholder="Enter group name" 
                            :required="true"
                            :error="errors.name ? errors.name[0] : null" 
                            icon-left="users"
                        />

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
                    
                    <div class="mt-4">
                        <Input 
                            v-model="form.description" 
                            label="Description" 
                            type="textarea"
                            placeholder="Enter group description (optional)" 
                            :error="errors.description ? errors.description[0] : null" 
                            rows="3"
                        />
                    </div>
                </div>

                <!-- Companies Selection -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <h6 class="text-base font-semibold text-gray-800 dark:text-white !mb-0">
                            Companies in this Group
                        </h6>
                        <Button 
                            variant="outline-danger" 
                            size="md" 
                            @click="clearAllCompanies" 
                            custom-class="max-w-[200px] h-auto shrink-0" 
                            type="button"
                            v-if="selectedCompanies.length > 0"
                        >
                            Clear all companies
                        </Button>
                    </div>
                    
                    <div class="space-y-4">
                        <div v-if="availableCompaniesLoading" class="flex items-center justify-center py-6">
                            <Spinner size="sm" text="Loading companies..." />
                        </div>
                        
                        <div v-else class="space-y-4">
                            <!-- Companies by Workgroup -->
                            <div v-if="workgroupsLoading" class="flex items-center justify-center py-6">
                                <Spinner size="sm" text="Loading workgroups..." />
                            </div>
                            <div v-else class="flex flex-wrap gap-4">
                                <DynamicDropdown
                                    v-for="wg in workgroups"
                                    :key="'workgroup-' + wg.id"
                                    :label="wg.company_count != null ? `${wg.name} (${wg.company_count} stores)` : wg.name"
                                    v-model="companiesByWorkgroup[wg.id]"
                                    resource="companies"
                                    :params="{ 
                                        workgroup_id: wg.id,
                                        search_for_group: true,
                                        group_id: mode === 'edit' ? form.id : null
                                    }"
                                    display-name="name"
                                    placeholder="Select companies"
                                    :required="false"
                                    :removeNullOption="true"
                                    multiple
                                    :error="errors.companies ? errors.companies[0] : null"
                                />
                            </div>
                        </div>
                        
                        <!-- Selected Companies Summary -->
                        <div v-if="selectedCompanies.length > 0" class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Selected Companies ({{ selectedCompanies.length }})
                            </h6>
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-for="company in selectedCompanies" 
                                    :key="company.id"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                >
                                   {{ company.name }}
                                    <button 
                                        @click="removeCompany(company.id)"
                                        type="button"
                                        class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-200 focus:text-blue-600"
                                    >
                                        <svg class="w-2 h-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                            <path stroke-linecap="round" stroke-width="1.5" d="m1 1 6 6m0-6L1 7" />
                                        </svg>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
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
                        {{ mode === 'edit' ? 'Update Group' : 'Create Group' }}
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
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/company-groups'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/company-groups', 'company-group')

// Companies and workgroups state
const workgroups = ref([])
const workgroupsLoading = ref(false)
const availableCompanies = ref([])
const availableCompaniesLoading = ref(false)
const companiesByWorkgroup = reactive({})

// Functions
const ensureWorkgroupKeys = () => {
    for (const wg of workgroups.value) {
        if (!(wg.id in companiesByWorkgroup)) {
            companiesByWorkgroup[wg.id] = []
        }
    }
}

const distributeCompanies = () => {
    const list = form.value.companies_details || []
    for (const wg of workgroups.value) {
        companiesByWorkgroup[wg.id] = []
    }
    if (!Array.isArray(list) || list.length === 0) return
    
    for (const c of list) {
        // Find company's workgroup
        if (c.workgroup_id) {
            const wgid = c.workgroup_id
            if (!(wgid in companiesByWorkgroup)) {
                companiesByWorkgroup[wgid] = []
            }
            companiesByWorkgroup[wgid].push({ id: c.id, name: c.name, store_number: c.store_number })
        }
    }

}

const selectedCompanies = computed(() => {
    const companies = []
    for (const wg of workgroups.value) {
        const selected = companiesByWorkgroup[wg.id]
        if (!Array.isArray(selected)) continue
        selected.forEach((c) => companies.push(c))
    }
    return companies
})

const mergedCompanyIds = () => {
    const ids = []
    for (const wg of workgroups.value) {
        const selected = companiesByWorkgroup[wg.id]
        if (!Array.isArray(selected)) continue
        selected.forEach((c) => ids.push(c.id))
    }
    return [...new Set(ids)]
}

const fetchWorkgroups = async () => {
    workgroupsLoading.value = true
    try {
        const res = await axios.get('/api/search/workgroups', {
            params: { query: '', column: 'name', all: true, internal: true },
        })
        workgroups.value = res.data?.collection || []
        ensureWorkgroupKeys()
        distributeCompanies()
    } catch (e) {
        console.error('Error loading workgroups:', e)
        workgroups.value = []
    } finally {
        workgroupsLoading.value = false
    }
}


const clearAllCompanies = () => {
    for (const wg of workgroups.value) {
        companiesByWorkgroup[wg.id] = []
    }
}

const removeCompany = (companyId) => {
    for (const wg of workgroups.value) {
        const index = companiesByWorkgroup[wg.id].findIndex(c => c.id === companyId)
        if (index !== -1) {
            companiesByWorkgroup[wg.id].splice(index, 1)
            break
        }
    }
}

// Watch for form data changes
watch(() => form.value.companies, () => {
    if (show.value && workgroups.value.length && form.value.companies_details.length) {
        distributeCompanies()
    }
}, { deep: true })

watch(() => show.value, (newValue) => {
    if (newValue) {
        fetchWorkgroups()
    }
})

onMounted(() => {
    if (show.value) {
        fetchWorkgroups()
    }
})

const handleSave = () => {
    const obj = {
        ...form.value,
        companies: mergedCompanyIds().map(id => ({ id }))
    }
    save(obj)
}

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .company-group-form {
        padding: 1rem;
    }
}
</style>