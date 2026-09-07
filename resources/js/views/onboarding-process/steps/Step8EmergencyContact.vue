<template>
    <div class="step-emergency-contact max-h-[calc(100vh-100px)] overflow-y-auto">
        <h5 class="mb-4" style="color: #bf162f">Emergency Contact Information Form</h5>

        <div class="important-notice">
            <p class="notice-text">
                This information will be extremely important in the event of an accident or medical emergency.
            </p>
            <p class="notice-text">
                Please be sure to sign and date this form
            </p>
        </div>

        <form @submit.prevent="handleSubmit">
            <div class="form-inner mb-3">
                <div class="form-wrapper">
                    <!-- Employee Information Section -->
                    <div class="section-block">
                        <h6 class="section-title">Employee Information</h6>
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="profile.e_emp_name"
                                    label="Last Name"
                                    
                                    readonly
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="profile.e_emp_first_name"
                                    label="First Name"
                                    readonly
                                />
                            </div>
                            <div class="form-col-small">
                                <Input
                                    v-model="profile.e_emp_middle_initial"
                                    label="MI"
                                    readonly
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="profile.e_home_phone"
                                    label="Phone: Home"
                                    readonly
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="profile.e_cell_phone"
                                    label="Phone: Cell"
                                    readonly
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col-full">
                                <Input
                                    v-model="profile.e_home_email"
                                    label="Home Email Address"
                                    readonly
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="profile.e_address_street"
                                    label="Address: Street"
                                    readonly
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="profile.e_address_city"
                                    label="City"
                                    readonly
                                />
                            </div>
                            <div class="form-col-small">
                                <Input
                                    v-model="profile.e_address_state"
                                    label="State"
                                    readonly
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Primary Emergency Contact Section -->
                    <div class="section-block">
                        <h6 class="section-title">Primary Emergency Contact</h6>
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact_last_name"
                                    label="Last Name"
                                    placeholder="Last Name"
                                    required
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact_first_name"
                                    label="First Name"
                                    placeholder="First Name"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col-full">
                                <Input
                                    v-model="formData.e_emergency_contact_relationship"
                                    label="Relationship"
                                    placeholder="Relationship"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact_phone_home"
                                    label="Phone: Home"
                                    placeholder="Home Phone"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact_phone_cell"
                                    label="Phone: Cell"
                                    placeholder="Cell Phone"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact_phone_work"
                                    label="Phone: Work"
                                    placeholder="Work Phone"
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col-full">
                                <Input
                                    v-model="formData.e_emergency_contact_email"
                                    label="Email Address"
                                    placeholder="Email Address"
                                    type="email"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Secondary Emergency Contact Section -->
                    <div class="section-block">
                        <h6 class="section-title">Secondary Emergency Contact</h6>
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact2_last_name"
                                    label="Last Name"
                                    placeholder="Last Name"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact2_first_name"
                                    label="First Name"
                                    placeholder="First Name"
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col-full">
                                <Input
                                    v-model="formData.e_emergency_contact2_relationship"
                                    label="Relationship"
                                    placeholder="Relationship"
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact2_phone_home"
                                    label="Phone: Home"
                                    placeholder="Home Phone"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact2_phone_cell"
                                    label="Phone: Cell"
                                    placeholder="Cell Phone"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_emergency_contact2_phone_work"
                                    label="Phone: Work"
                                    placeholder="Work Phone"
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col-full">
                                <Input
                                    v-model="formData.e_emergency_contact2_email"
                                    label="Email Address"
                                    placeholder="Email Address"
                                    type="email"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Hospital & Insurance Section -->
                    <div class="section-block">
                        <h6 class="section-title">Preferred Local Hospital</h6>
                        <div class="form-row">
                            <div class="form-col-full">
                                <Input
                                    v-model="formData.e_emergency_contact2_hospital"
                                    label="Hospital Name"
                                    placeholder="Preferred Hospital Name"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="section-block">
                        <h6 class="section-title">Insurance Information</h6>
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_insurance_company"
                                    label="Company"
                                    placeholder="Insurance Company Name"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_insurance_policy_number"
                                    label="Policy #"
                                    placeholder="Policy Number"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_signature"
                                    label="Signature"
                                    placeholder="Type Your Full Name"
                                    required
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.e_signature_date"
                                    label="Date"
                                    type="date"
                                    readonly
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="errorMessage" class="alert alert-danger">
                {{ errorMessage }}
            </div>

            <div class="flex_between">
                <Button variant="secondary" @click="goToBackStep">Back</Button>
                <Button type="submit" :loading="saving">
                    Save & Next
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'

const props = defineProps({
    onboardingId: {
        type: String,
        required: true,
    },
    goToNextStep: {
        type: Function,
        required: true,
    },
    goToBackStep: {
        type: Function,
        required: true,
    },
})

const saving = ref(false)
const errorMessage = ref('')

const profile = reactive({
    e_emp_name: '',
    e_emp_first_name: '',
    e_emp_middle_initial: '',
    e_home_phone: '',
    e_cell_phone: '',
    e_home_email: '',
    e_address_street: '',
    e_address_city: '',
    e_address_state: '',
})

const formData = reactive({
    e_emergency_contact_first_name: '',
    e_emergency_contact_last_name: '',
    e_emergency_contact_relationship: '',
    e_emergency_contact_phone_home: '',
    e_emergency_contact_phone_cell: '',
    e_emergency_contact_phone_work: '',
    e_emergency_contact_email: '',
    e_emergency_contact2_first_name: '',
    e_emergency_contact2_last_name: '',
    e_emergency_contact2_relationship: '',
    e_emergency_contact2_phone_home: '',
    e_emergency_contact2_phone_cell: '',
    e_emergency_contact2_phone_work: '',
    e_emergency_contact2_email: '',
    e_emergency_contact2_hospital: '',
    e_insurance_company: '',
    e_insurance_policy_number: '',
    e_signature: '',
    e_signature_date: new Date().toISOString().split('T')[0],
})

async function fetchEmergencyContactData() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const { data: res } = await axios.get('/onboarding-process/emergency-contact-data', {
            params: { onboardingId: props.onboardingId },
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        if (res.profile) {
            Object.assign(profile, res.profile)
        }

        if (res.form) {
            Object.assign(formData, res.form)
        }
    } catch (err) {
        console.error('Failed to fetch emergency contact data:', err)
    }
}

async function handleSubmit() {
    errorMessage.value = ''

    const hasPrimaryEmergencyHomePhone =
        typeof formData.e_emergency_contact_phone_home === 'string' &&
        formData.e_emergency_contact_phone_home.trim().length > 0
    const hasPrimaryEmergencyCellPhone =
        typeof formData.e_emergency_contact_phone_cell === 'string' &&
        formData.e_emergency_contact_phone_cell.trim().length > 0

    if (!hasPrimaryEmergencyHomePhone && !hasPrimaryEmergencyCellPhone) {
        errorMessage.value = 'Phone: Home or Phone: Cell is required for Primary Emergency Contact.'
        return
    }

    saving.value = true

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const payload = {
            onboardingId: props.onboardingId,
            ...formData,
        }

        await axios.post('/onboarding-process/save-emergency-contact', payload, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        props.goToNextStep()
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            err.response?.data?.error ||
            'Failed to save emergency contact information. Please try again.'
        errorMessage.value = msg
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchEmergencyContactData()
})
</script>

<style scoped>
.step-emergency-contact {
    padding: 1rem 0;
}

.important-notice {
    text-align: center;
    color: #bf162f;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #fff5f5;
    border: 1px solid #ffcdd2;
    border-radius: 6px;
}

.notice-text {
    font-weight: 600;
    margin: 0.25rem 0;
}

.form-wrapper {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fff;
}

.section-block {
    margin-bottom: 2rem;
    padding: 1.25rem;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #fafbfc;
}

.section-title {
    font-weight: 600;
    font-size: 16px;
    color: #bf162f;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #bf162f;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-col {
    display: flex;
    flex-direction: column;
}

.form-col-small {
    display: flex;
    flex-direction: column;
    grid-column: span 1;
    max-width: 100px;
}

.form-col-full {
    grid-column: span 2;
}

.signature-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #dee2e6;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c2c7;
    color: #842029;
}

.flex_between {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .form-col-full {
        grid-column: span 1;
    }

    .form-col-small {
        max-width: 100%;
    }

    .form-wrapper {
        padding: 1rem;
    }
}
</style>
