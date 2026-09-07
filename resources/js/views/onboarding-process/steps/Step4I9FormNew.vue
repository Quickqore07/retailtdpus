<template>
    <div class="wizard-form-container">
        <Panel :divider="true">

            <!-- Step Navigation Tabs -->
            <div class="wizard-tabs border-b border-gray-200 dark:border-gray-700 -mt-3 md:-mt-4 mb-4">
                <div class="flex overflow-x-auto">
                    <button
                        v-for="(step, index) in steps"
                        :key="index"
                        @click="goToStep(index)"
                        :disabled="!canNavigateToStep(index)"
                        :class="getTabClasses(index)"
                        class="wizard-tab flex-shrink-0 px-6 py-3 text-sm font-medium transition-colors relative"
                        type="button"
                    >
                        {{ step.label }}
                        <span v-if="currentStep === index" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></span>
                    </button>
                </div>
            </div>


            <!-- Step Content -->
            <form @submit.prevent="handleNext" class="wizard-content space-y-6">
                <!-- Loading State -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading your information...</p>
                    </div>
                </div>

                <!-- I9/W4 Submitted Message -->
              

                <!-- Step 1: Profile Information -->
                <div v-show="currentStep === 0 && !loading" class="step-content">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Type -->
                        <div class="col-span-full">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="formData.type"
                                required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <!-- <option value="">Select type</option> -->
                                <option value="i9">Employee/W-2 (W-4 & I-9)</option>
                                <!-- <option value="1099">Contractor/1099</option> -->
                            </select>
                        </div>

                        <!-- Email -->
                        <div class="col-span-full">
                            <Input
                                v-model="formData.email"
                                label="Email"
                                type="email"
                                placeholder="Enter email address"
                                :required="true"
                                icon-left="mail"
                            />
                        </div>

                        <!-- Legal Name -->
                        <Input
                            v-model="formData.first_name"
                            label="Legal Name - First"
                            placeholder="First"
                            :required="true"
                            icon-left="user"
                        />
                        <Input
                            v-model="formData.middle_name"
                            label="Legal Name - Middle"
                            placeholder="Middle"
                            icon-left="user"
                        />
                        <Input
                            v-model="formData.last_name"
                            label="Legal Name - Last"
                            placeholder="Last"
                            :required="true"
                            icon-left="user"
                        />
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Enter full legal name, including middle name (as it appears on passport, social security card, etc.), otherwise paperwork may be rejected.
                    </p>

                    <!-- Address Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="col-span-full">
                            <Input
                                v-model="formData.street"
                                label="Address"
                                placeholder="Street"
                                icon-left="map-pin"
                            />
                        </div>
                        <div class="col-span-full">
                            <Input
                                v-model="formData.apt"
                                placeholder="Apt, Unit, Suite, etc. (optional)"
                            />
                        </div>
                        <Input
                            v-model="formData.city"
                            placeholder="City"
                            icon-left="map"
                        />
                        <Input
                            v-model="formData.state"
                            placeholder="State"
                            icon-left="map"
                        />
                        <Input
                            v-model="formData.zip"
                            placeholder="Zip"
                            icon-left="map-pin"
                        />
                        <Input
                            v-model="formData.country"
                            placeholder="Country"
                            icon-left="globe"
                        />
                    </div>

                    <!-- Additional Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-700 dark:text-gray-300 text-sm">📞</span>
                            <Input
                                v-model="formData.phone"
                                placeholder="Phone"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Gender
                            </label>
                            <select
                                v-model="formData.gender"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <Input
                            v-model="formData.birthdate"
                            label="Birthdate"
                            type="date"
                            icon-left="calendar"
                        />
                        <Input
                            v-model="formData.ssn"
                            label="SSN"
                            placeholder="XXX-XX-XXXX"
                            icon-left="shield"
                        />
                        <div class="col-span-full">
                            <Input
                                v-model="formData.preferred_name"
                                label="Preferred Name"
                                placeholder="Enter preferred name"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                The name that will be used day-to-day to address this person
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Groups -->
                <div v-show="currentStep === 1 && !loading" class="step-content">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Workgroup
                            </label>
                            <input
                                v-model="formData.workgroup_name"
                                type="text"
                                disabled
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-100 dark:text-gray-500 bg-gray-100 text-gray-500 shadow-sm cursor-not-allowed"
                                placeholder="Workgroup from employee data"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                This is the workgroup associated with the employee and cannot be changed.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Current Employment -->
                <div v-show="currentStep === 2 && !loading" class="step-content">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Job Title
                            </label>
                            <Input
                                v-model="formData.job_title"
                                placeholder="Enter job title"
                                icon-left="briefcase"
                            />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Employment Type
                                </label>
                                <select
                                    v-model="formData.employment_ype"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Select type</option>
                                    <option value="full-time">Full-time</option>
                                    <option value="part-time">Part-time</option>
                                    <option value="contract">Contract</option>
                                    <option value="intern">Intern</option>
                                </select>
                            </div>
                            <Input
                                v-model="formData.startDate"
                                label="Start Date"
                                type="date"
                                icon-left="calendar"
                            />
                        </div>
                        <!-- <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Manager
                            </label>
                            <select
                                v-model="formData.manager"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select manager</option>
                                <option value="manager1">John Doe</option>
                                <option value="manager2">Jane Smith</option>
                                <option value="manager3">Bob Johnson</option>
                            </select>
                        </div> -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Input
                                v-model="formData.salary"
                                label="Salary"
                                type="number"
                                placeholder="Enter salary"
                                icon-left="dollar"
                            />
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Salary Period
                                </label>
                                <select
                                    v-model="formData.salary_period"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="hourly">Hourly</option>
                                    <option value="annually">Annually</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        v-if="currentStep > 0"
                        variant="outline-secondary"
                        size="md"
                        @click="handlePrevious"
                        type="button"
                    >
                        Previous
                    </Button>
                    <div v-else></div>

                    <div class="flex items-center gap-3">
                        <Button
                            variant="outline-secondary"
                            size="md"
                            @click="closeWizard"
                            type="button"
                        >
                            Cancel
                        </Button>
                        <Button
                            v-if="currentStep < steps.length - 1"
                            variant="primary"
                            size="md"
                            type="submit"
                        >
                            Next
                        </Button>
                        <Button
                            v-else
                            variant="primary"
                            size="md"
                            @click="handleFinish"
                            type="button"
                        >
                            Finish
                        </Button>
                    </div>
                </div>
            </form>
        </Panel>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import { isSSNValid, formatSSN } from '@/utils/ssn'
import axios from 'axios'

const props = defineProps({
    onboardingId: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['close'])

const steps = ref([
    { label: 'Profile Information', id: 'profile' },
    { label: 'Groups', id: 'groups' },
    { label: 'Current Employment', id: 'employment' }
])

const currentStep = ref(0)
const loading = ref(false)
const formData = reactive({
    // Profile Information
    type: 'i9',
    email: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    street: '',
    apt: '',
    city: '',
    state: '',
    zip: '',
    country: '',
    phone: '',
    gender: '',
    birthdate: '',
    ssn: '',
    preferred_name: '',
    
    // Groups
    workgroupId: null,
    workgroup_name: '',
    
    // Employment
    job_title: '',
    employment_ype: '',
    startDate: '',
    manager: '',
    salary: '',
    salary_period: 'annually'
})

// Fetch onboarding data on component mount
onMounted(async () => {
    await fetchOnboardingData()
})

async function fetchOnboardingData() {
    try {
        loading.value = true
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const { data: res } = await axios.get('/onboarding-process/i9-w4-data', {
            params: { onboardingId: props.onboardingId },
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })
        
        // Populate form data from response
        formData.type = res.type ?? 'i9'
        formData.email = res.email ?? ''
        formData.first_name = res.first_name ?? res.first_name ?? ''
        formData.middle_name = res.middle_name ?? res.middle_name ?? ''
        formData.last_name = res.last_name ?? res.last_name ?? ''
        formData.street = res.street ?? ''
        formData.apt = res.apt ?? ''
        formData.city = res.city ?? ''
        formData.state = res.state ?? ''
        formData.zip = res.zip ?? ''
        formData.country = res.country ?? ''
        formData.phone = res.phone ?? ''
        formData.gender = res.gender ?? ''
        formData.birthdate = res.birthdate ?? ''
        formData.ssn = res.ssn ?? ''
        formData.preferred_name = res.preferred_name ?? res.preferred_name ?? ''
        formData.workgroupId = res.workgroupId ?? res.workgroup_id ?? null
        formData.workgroup_name = res.workgroup_name ?? res.workgroup_name ?? ''
        formData.job_title = res.job_title ?? ''
        formData.employment_ype = res.employment_ype ?? res.employment_type ?? ''
        formData.startDate = res.startDate ?? res.start_date ?? ''
        formData.manager = res.manager ?? ''
        formData.salary = res.salary ?? ''
        formData.salary_period = res.salary_period ?? res.salary_period ?? 'annually'
    } catch (error) {
        console.error(error)
        alert('Failed to load data: ' + (error.response?.data?.message || error.message))
    } finally {
        loading.value = false
    }
}

const visitedSteps = ref([0])

const canNavigateToStep = (stepIndex) => {
    return visitedSteps.value.includes(stepIndex)
}

const goToStep = (stepIndex) => {
    if (canNavigateToStep(stepIndex)) {
        currentStep.value = stepIndex
    }
}

const getTabClasses = (stepIndex) => {
    const isActive = currentStep.value === stepIndex
    const isVisited = visitedSteps.value.includes(stepIndex)
    const isDisabled = !isVisited
    
    let classes = []
    
    if (isActive) {
        classes.push('text-blue-600 dark:text-blue-400 border-b-2 border-blue-600')
    } else if (isVisited) {
        classes.push('text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer')
    } else {
        classes.push('text-gray-400 dark:text-gray-600 cursor-not-allowed')
    }
    
    return classes.join(' ')
}

const validateCurrentStep = () => {
    if (currentStep.value === 0) {
        // Validate Profile Information
        if (!formData.type) {
            alert('Please select a type')
            return false
        }
        if (!formData.email) {
            alert('Please enter an email address')
            return false
        }
        if (!formData.first_name || !formData.last_name) {
            alert('Please enter first and last name')
            return false
        }
        if (formData.ssn && !isSSNValid(formData.ssn)) {
            alert('Please enter a valid 9-digit SSN (e.g. XXX-XX-XXXX or 9 digits).')
            return false
        }
    }
    return true
}

const handleNext = () => {
    if (!validateCurrentStep()) {
        return
    }
    
    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++
        if (!visitedSteps.value.includes(currentStep.value)) {
            visitedSteps.value.push(currentStep.value)
        }
    }
}

const handlePrevious = () => {
    if (currentStep.value > 0) {
        currentStep.value--
    }
}

const handleFinish = async () => {
    if (!validateCurrentStep()) {
        return
    }
    if (formData.ssn && !isSSNValid(formData.ssn)) {
        alert('Please enter a valid 9-digit SSN (e.g. XXX-XX-XXXX or 9 digits).')
        return
    }

    // Submit the form data to the backend
    try {
        loading.value = true
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        
        const payload = {
            onboardingId: props.onboardingId,
            type: formData.type,
            email: formData.email,
            first_name: formData.first_name,
            middle_name: formData.middle_name,
            last_name: formData.last_name,
            street: formData.street,
            apt: formData.apt,
            city: formData.city,
            state: formData.state,
            zip: formData.zip,
            country: formData.country,
            phone: formData.phone,
            gender: formData.gender,
            birthdate: formData.birthdate,
            ssn: formData.ssn ? formatSSN(formData.ssn) || formData.ssn : '',
            preferred_name: formData.preferred_name,
            workgroup_id: formData.workgroupId,
            workgroup_name: formData.workgroup_name,
            job_title: formData.job_title,
            employment_type: formData.employment_ype,
            start_date: formData.startDate,
            manager: formData.manager,
            salary: formData.salary,
            salary_period: formData.salary_period
        }
        
        const { data: res } = await axios.post('/onboarding-process/save-i9-w4', payload, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })
        
        console.log('Form submitted successfully:', res)
        alert('Staff member wizard completed successfully!')
        
        // Reset form
        resetForm()
        
        // Close the wizard
        emit('close')
    } catch (error) {
        console.error('Error submitting form:', error)
        alert('Failed to save data: ' + (error.response?.data?.message || error.message))
    } finally {
        loading.value = false
    }
}

const closeWizard = () => {
    if (confirm('Are you sure you want to close? Any unsaved changes will be lost.')) {
        resetForm()
        emit('close')
    }
}

const resetForm = () => {
    // Reset form data
    Object.keys(formData).forEach(key => {
        if (key === 'salary_period') {
            formData[key] = 'annually'
        } else if (key === 'type') {
            formData[key] = 'i9'
        } else if (key === 'workgroupId') {
            formData[key] = null
        } else {
            formData[key] = ''
        }
    })
    
    // Reset wizard state
    currentStep.value = 0
    visitedSteps.value = [0]
}
</script>

<style scoped>
.wizard-form-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem;
}

.wizard-tabs {
    margin-left: -1rem;
    margin-right: -1rem;
    padding-left: 1rem;
    padding-right: 1rem;
}

@media (min-width: 768px) {
    .wizard-tabs {
        margin-left: -1.5rem;
        margin-right: -1.5rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
}

.wizard-tab {
    white-space: nowrap;
}

.wizard-tab:disabled {
    cursor: not-allowed;
}

.step-content {
    min-height: 300px;
}

/* Custom scrollbar for tab navigation */
.wizard-tabs .flex::-webkit-scrollbar {
    height: 4px;
}

.wizard-tabs .flex::-webkit-scrollbar-track {
    background: transparent;
}

.wizard-tabs .flex::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 2px;
}

.dark .wizard-tabs .flex::-webkit-scrollbar-thumb {
    background: #4a5568;
}

/* Input and select styling consistency */
select,
input[type="text"],
input[type="email"],
input[type="tel"],
input[type="date"],
input[type="number"] {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

select:focus,
input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6;
}
</style>
