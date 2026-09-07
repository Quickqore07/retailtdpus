<template>
    <div class="step-digital-signature max-h-[calc(100vh-100px)] overflow-y-auto">
        <h5 class="mb-4" style="color: #bf162f">Digital Signature Fields</h5>

        <form @submit.prevent="handleSubmit">
            <div class="form-inner mb-3">
                <div class="form-wrapper">
                    <div class="signature-table">
                        <div class="signature-row">
                            <div class="signature-col">
                                <Input
                                    v-model="formData.digital_fname"
                                    label="Employee Full Name"
                                    type="text"
                                    readonly
                                    required
                                    placeholder="Full Name"
                                />
                            </div>

                            <div class="signature-col">
                                <Input
                                    v-model="formData.digital_lname"
                                    label="Signature"
                                    type="text"
                                    required
                                    placeholder="Type Your Signature"
                                />
                            </div>

                            <div class="signature-col">
                                <Input
                                    v-model="formData.digital_date"
                                    label="Date"
                                    type="text"
                                    readonly
                                    required
                                    placeholder="Date"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!isFormSubmitted" class="workbright-consent mb-4">
                <h6 class="mb-2 text-base font-semibold text-gray-900">One step left! Please check your email.</h6>
                <p class="!mb-1 text-sm text-gray-700">
                    Thank you for submitting your profile. To finish your onboarding, please check your inbox for an email from WorkBright.
                </p>
                <p class="!mb-3 text-sm text-gray-700">
                    You must click the link in that email to complete your I-9 and W-4 verification. This is the final requirement to get you officially started!
                </p>

                <div class="flex items-start gap-2">
                    <input
                        id="workbrightI9W4EmailConsent"
                        v-model="formData.workbright_email_consent"
                        type="checkbox"
                        class="workbright-consent-input mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-[#bf162f] focus:ring-[#bf162f]"
                    />
                    <label for="workbrightI9W4EmailConsent" class="text-sm text-gray-700">
                        I understand that I must check my email and complete my I-9/W-4 forms via WorkBright to finish my onboarding.
                    </label>
                </div>
            </div>

            <div v-if="errorMessage" class="alert alert-danger">
                {{ errorMessage }}
            </div>

            <div v-if="successMessage" class="alert alert-success">
                {{ successMessage }}
            </div>

            <div class="flex_between">
                <Button variant="secondary" @click="goToBackStep">Back</Button>
                <Button 
                    v-if="isFormSubmitted"
                    variant="secondary"
                    disabled
                >
                    Form Submitted
                </Button>
                <Button 
                    v-else
                    type="submit" 
                    :loading="saving || redirectingToWorkBright"
                    :disabled="redirectingToWorkBright || !formData.workbright_email_consent"
                >
                    {{ redirectingToWorkBright ? 'Redirecting...' : 'Finish' }}
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
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
const redirectingToWorkBright = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const onboardingStatus = ref('')

const formData = reactive({
    digital_fname: '',
    digital_lname: '',
    digital_date: new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }).replace(/\//g, '-'),
    workbright_email_consent: false,
})

const isFormSubmitted = computed(() => {
    return onboardingStatus.value === '2' || onboardingStatus.value === '4'
})

async function fetchDigitalSignatureData() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const { data: res } = await axios.get('/onboarding-process/digital-signature-data', {
            params: { onboardingId: props.onboardingId },
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        if (res.employee_full_name) {
            formData.digital_fname = res.employee_full_name
        }

        if (res.status) {
            onboardingStatus.value = res.status
        }

        if (res.form) {
            if (res.form.digital_lname) {
                formData.digital_lname = res.form.digital_lname
            }
            if (res.form.digital_date) {
                formData.digital_date = res.form.digital_date
            }
        }
    } catch (err) {
        console.error('Failed to fetch digital signature data:', err)
    }
}

async function handleSubmit() {
    errorMessage.value = ''
    successMessage.value = ''

    if (!formData.digital_lname.trim()) {
        errorMessage.value = 'Signature is required.'
        return
    }

    if (!formData.workbright_email_consent) {
        errorMessage.value = 'Please confirm that you are okay with receiving email from WorkBright for your I-9 and W-4 forms.'
        return
    }

    saving.value = true

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const payload = {
            onboardingId: props.onboardingId,
            digital_fname: formData.digital_fname,
            digital_lname: formData.digital_lname,
            digital_date: formData.digital_date,
        }

        const response = await axios.post('/onboarding-process/save-digital-signature', payload, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        successMessage.value = 'Digital signature saved successfully!'

        const redirectUrl = response?.data?.redirect
        if (redirectUrl) {
            redirectingToWorkBright.value = true
            successMessage.value = 'Digital signature saved. Redirecting to WorkBright...'
            setTimeout(() => {
                window.location.assign(redirectUrl)
            }, 300)
            return
        }

        setTimeout(() => {
            props.goToNextStep()
        }, 500)
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            err.response?.data?.error ||
            'Failed to save digital signature. Please try again.'
        errorMessage.value = msg
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchDigitalSignatureData()
})
</script>

<style scoped>
.step-digital-signature {
    padding: 1rem 0;
}

.form-wrapper {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fff;
}

.signature-table {
    width: 100%;
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

.alert-success {
    background-color: #d1e7dd;
    border: 1px solid #badbcc;
    color: #0f5132;
}

.signature-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.signature-col {
    padding: 0;
}

.flex_between {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .signature-row {
        grid-template-columns: 1fr;
    }

    .form-wrapper {
        padding: 1rem;
    }
}
</style>
