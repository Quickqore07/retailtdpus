<template>
    <div class="app-settings">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 
                        class="font-bold !mb-0 select-none" 
                        @click="handleHeaderClick"
                    >
                        Application Settings
                    </h5>
                </div>
            </template>

            <div v-if="loading" class="flex items-center justify-center py-8">
                <Spinner size="md" text="Loading settings..." />
            </div>

            <form v-else @submit.prevent="handleSave" class="space-y-6">
                <!-- General Settings (Password Protected) -->
                <div v-if="isGeneralSettingsAuthenticated && settings.length > 0" class="space-y-4">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        General Settings (Protected)
                    </h6>
                    
                    <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
                        <div 
                            v-for="setting in settings" 
                            :key="setting.id"
                            class="setting-item"
                        >
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ setting.display_key }}
                            </label>
                            <div class="flex gap-2">
                                <Input 
                                    :type="setting.value_type ? setting.value_type : 'text'"
                                    v-model="setting.value" 
                                    class="flex-1"
                                    placeholder="Value"
                                    :min="setting.min_value"
                                    :max="setting.max_value"
                                />
                            </div>
                        </div>
                    </div>

                    <Button variant="secondary" size="md" @click="setCompaniesCacheAction">
                        Sync Companies
                    </Button>
                </div>

                <!-- Settings Items (if needed) -->
                <div  class="space-y-4">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Report Settings
                    </h6>
                    
                    <div >
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <Th>
                                        Report Type
                                    </Th>
                                    <Th>
                                        Condition
                                    </Th>
                                    <Th>
                                        Value
                                    </Th>
                                    <Th>
                                        Color
                                    </Th>
                                    <Th>
                                        Actions
                                    </Th>

                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="(item, index) in settingsItems" :key="index">
                                    <Td>
                                        <DynamicDropdown v-model="item.report_type" :custom-options="reportTypeOptions" display-name="name" placeholder="Select Report Type" />
                                    </Td>
                                    <Td>
                                        <DynamicDropdown v-model="item.difference_condition" :custom-options="conditionOptions" display-name="name" placeholder="Select Condition" />
                                    </Td>
                                    <Td>
                                        <div class="flex gap-2 w-full">
                                            <Input 
                                                v-model="item.difference_value_1" 
                                                placeholder="Value"
                                                type="number"
                                                class="min-w-[100px] flex-1"
                                            />
                                            <div v-if="item.difference_condition?.id === 'between'" class="flex items-center justify-center">
                                                <span class="text-gray-500 dark:text-gray-400">To</span>
                                            </div>
                                            <Input 
                                                v-if="item.difference_condition?.id === 'between'"
                                                v-model="item.difference_value_2" 
                                                placeholder="Value"
                                                type="number"
                                                class="min-w-[100px] flex-1"
                                            />
                                    </div>

                                    </Td>
                                    <Td>
                                        <Input 
                                            v-model="item.difference_color" 
                                            type="color"
                                            placeholder="Color"
                                            class="min-w-[80px]"
                                        />
                                    </Td>
                                    <Td>
                                        <Button variant="outline-danger" size="sm" icon-left="trash" @click="()=>deleteSettingsItem(index)">
                                            Delete
                                        </Button>
                                    </Td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Button variant="primary" size="md" @click="addSettingsItem">
                                <SvgIcon name="plus" size="sm" />
                                Add Settings Item
                            </Button>
                        </div>
                    </div>
                </div>


                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button variant="outline-secondary" size="md" @click="fetchSettings" type="button">
                        Reset
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving">
                        Update Settings
                    </Button>
                </div>
            </form>
        </Panel>

        <!-- Password Modal -->
        <Modal
            v-model="showPasswordModal"
            title="General Settings Access"
            size="sm"
            :show-footer="true"
            :show-cancel="true"
            :show-confirm="true"
            cancel-text="Cancel"
            confirm-text="Submit"
            @confirm="handlePasswordSubmit"
            @close="closePasswordModal"
            :loading="isVerifyingPassword"
        >
            <div class="space-y-4">
                <div v-if="passwordError" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ passwordError }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Enter Password
                    </label>
                    <Input 
                        v-model="password" 
                        type="password" 
                        placeholder="Enter password"
                        @keyup.enter="handlePasswordSubmit"
                        autofocus
                    />
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import Modal from '@/components/common/Modal.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import SvgIcon from '@/components/SvgIcon.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'
import { useRouter } from 'vue-router'
const { can } = usePermission()
const message = useMessage()
const router = useRouter()

const settings = ref([])
const settingsItems = ref([])
const loading = ref(false)
const isSaving = ref(false)

// Password protection state
const headerClickCount = ref(0)
const showPasswordModal = ref(false)
const password = ref('')
const passwordAttempts = ref(0)
const passwordError = ref('')
const isVerifyingPassword = ref(false)
const isGeneralSettingsAuthenticated = ref(false)

let clickTimeout = null

const reportTypeOptions = ref([
    {
        id: 'ideal-cost-purchase-difference-report',
        name: 'Ideal Cost & Purchase Difference'
    },
    {
        id: 'ideal-cost-summary-report',
        name: 'Ideal Cost Summary'
    },
])
const conditionOptions = ref([
    {
        id: 'less-than',
        name: 'Less Than'
    },
    {
        id: 'greater-than',
        name: 'Greater Than'
    },
    {   
        id: 'between',
        name: 'Between'
    }
])

// Handle header clicks for password modal trigger
const handleHeaderClick = () => {
    if (isGeneralSettingsAuthenticated.value) {
        return
    }
    
    headerClickCount.value++
    
    if (clickTimeout) {
        clearTimeout(clickTimeout)
    }
    
    clickTimeout = setTimeout(() => {
        headerClickCount.value = 0
    }, 2000)
    
    if (headerClickCount.value >= 5) {
        showPasswordModal.value = true
        headerClickCount.value = 0
    }
}

// Handle password submission
const handlePasswordSubmit = async () => {
    if (!password.value) {
        passwordError.value = 'Please enter a password'
        return
    }
    
    isVerifyingPassword.value = true
    passwordError.value = ''
    
    try {
        const response = await useRequest('post', 'settings/verify-password', {
            password: password.value
        })
        
        if (response.success) {
            passwordAttempts.value = response.attempts
            
            if (response.authenticated) {
                isGeneralSettingsAuthenticated.value = true
                message.success('Access granted to general settings')
                showPasswordModal.value = false
                password.value = ''
                passwordAttempts.value = 0
                await fetchGeneralSettings()
            } else {
                passwordError.value = response.message || 'Incorrect password. Please try again.'
                password.value = ''
            }
        } else {
            passwordError.value = response.message || 'Failed to verify password'
        }
    } catch (error) {
        console.error('Error verifying password:', error)
        passwordError.value = error.response?.data?.message || 'An error occurred'
    } finally {
        isVerifyingPassword.value = false
    }
}

// Close password modal and reset
const closePasswordModal = () => {
    showPasswordModal.value = false
    password.value = ''
    passwordError.value = ''
}

// Fetch general settings after authentication
const fetchGeneralSettings = async () => {
    try {
        const response = await useRequest('get', 'settings/general')
        settings.value = response.settings || []
    } catch (error) {
        console.error('Error fetching general settings:', error)
        message.error('Error loading general settings')
    }
}

const fetchSettings = async () => {
    loading.value = true
    try {
        const response = await useRequest('get', 'settings')
        
        // Only set general settings if authenticated
        if (response.settings && isGeneralSettingsAuthenticated.value) {
            settings.value = response.settings || []
        } else {
            settings.value = []
        }
        
        settingsItems.value = response.settingsItems || []
        settingsItems.value.forEach(item => {
            item.report_type = reportTypeOptions.value.find(option => option.id === item.report_type)
            item.difference_condition = conditionOptions.value.find(option => option.id === item.difference_condition)
        })
    } catch (error) {
        console.error('Error fetching settings:', error)
        message.error('Error loading settings')
    } finally {
        loading.value = false
    }
}


const handleSave = async () => {
    
    const nonvalidSettingsItems = settingsItems.value.filter(s =>  !s.difference_value_1 || !s.difference_color);
    if(nonvalidSettingsItems.length > 0){
        message.error('Please fill all the required fields')
        return
    }
    isSaving.value = true
    try {
        const payload = {
            settingsItems: settingsItems.value.map(s => ({
                report_type: s.report_type.id,
                difference_condition: s.difference_condition.id,
                difference_value_1: s.difference_value_1,
                difference_value_2: s.difference_value_2 ?? 0,
                difference_color: s.difference_color
            }))
        }
        
        // Only include general settings if authenticated
        if (isGeneralSettingsAuthenticated.value && settings.value.length > 0) {
            payload.settings = settings.value.map(s => ({
                key: s.key,
                value: s.value,
                value_type: s.value_type,
                min_value: s.min_value,
                max_value: s.max_value,
            }))
        }
        
        const response = await useRequest('post', 'settings', payload)
        message.success(response.message || 'Settings updated successfully')
        await fetchSettings()
    } catch (error) {
        console.error('Error saving settings:', error)
        message.error(error.response?.data?.message || 'Failed to update settings')
    } finally {
        isSaving.value = false
    }
}

const deleteSettingsItem = (index) => {
    settingsItems.value.splice(index, 1)
}
const addSettingsItem = () => {
    settingsItems.value.push({
        report_type: reportTypeOptions.value[0],
        difference_condition: conditionOptions.value[0],
        difference_value_1: '',
        difference_value_2: '',
        difference_color: '#000000'
    })
}

const setCompaniesCacheAction = async () => {

    const response = await useRequest('get', 'set-companies-cache')
    if(response.success){
        message.success(response.message)
    }else{
        message.error(response.message)
    }
}
onMounted(() => {
    if(!can('app-settings', 'update')){
        message.error('You are not authorized to access this page')
        router.push('/')
    }
    fetchSettings()
})
</script>

<style scoped>

textarea:focus,
select:focus {
    outline: none !important;
    outline-offset: 0 !important;
}

@media (max-width: 640px) {
    .app-settings {
        padding: 0.5rem;
    }
}
</style>
