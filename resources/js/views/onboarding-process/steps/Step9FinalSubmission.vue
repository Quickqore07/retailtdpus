<template>
    <div class="step-final-submission max-h-[calc(100vh-100px)] overflow-y-auto">
        <h5 class="mb-4" style="color: #bf162f">Final Submission & Employee Declaration</h5>

        <form @submit.prevent="handleSubmit">
            <div class="form-inner mb-3">
                <div class="form-wrapper">
                    <div class="review-notice">
                        <p class="notice-text">
                            Before submitting your onboarding forms, please carefully review all information you have provided.
                        </p>
                    </div>

                    <!-- Declaration Checkboxes -->
                    <div class="declarations-section">
                        <div class="declaration-item">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.check_confirmation"
                                    type="checkbox"
                                    required
                                    class="checkbox-input"
                                />
                                <span class="checkbox-text">
                                    I confirm that I have completed all required onboarding steps, including personal profile, Form I-9, Federal and State W-4, and Direct Deposit details (if applicable).
                                </span>
                            </label>
                        </div>

                        <div class="declaration-item">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.certification_complete"
                                    type="checkbox"
                                    class="checkbox-input"
                                />
                                <span class="checkbox-text">
                                    I certify that all information provided is true, complete, and accurate to the best of my knowledge.
                                </span>
                            </label>
                        </div>

                        <div class="declaration-item">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.work_authorization_confirmed"
                                    type="checkbox"
                                    class="checkbox-input"
                                />
                                <span class="checkbox-text">
                                    I further confirm that I am legally authorized to work in the United States and have submitted valid documentation for employment eligibility verification.
                                </span>
                            </label>
                        </div>

                        <div class="declaration-item">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.false_info_acknowledgment"
                                    type="checkbox"
                                    class="checkbox-input"
                                />
                                <span class="checkbox-text">
                                    I understand that providing false or misleading information may result in disqualification or termination.
                                </span>
                            </label>
                        </div>

                        <div class="declaration-item">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.electronic_signature_consent"
                                    type="checkbox"
                                    class="checkbox-input"
                                />
                                <span class="checkbox-text">
                                    I consent to the use of my electronic signature as my legal acknowledgment of the information provided above.
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.signature"
                                    label="Electronic Signature (Type Your Full Name)"
                                    placeholder="Type Your Full Name"
                                    required
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.signature_date"
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

            <div v-if="successMessage" class="alert alert-success">
                {{ successMessage }}
            </div>

            <div class="flex_between">
                <Button variant="secondary" @click="goToBackStep">Back</Button>
                <Button type="submit" :loading="saving" :disabled="!allChecksConfirmed">
                    Submit Final Onboarding
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
const errorMessage = ref('')
const successMessage = ref('')
const applicantName = ref('')

const formData = reactive({
    check_confirmation: false,
    certification_complete: false,
    work_authorization_confirmed: false,
    false_info_acknowledgment: false,
    electronic_signature_consent: false,
    signature: '',
    signature_date: new Date().toISOString().split('T')[0],
})

const allChecksConfirmed = computed(() =>
    formData.check_confirmation &&
    formData.certification_complete &&
    formData.work_authorization_confirmed &&
    formData.false_info_acknowledgment &&
    formData.electronic_signature_consent,
)

async function fetchFinalSubmissionData() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const { data: res } = await axios.get('/onboarding-process/final-submission-data', {
            params: { onboardingId: props.onboardingId },
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        if (res.applicant_name) {
            applicantName.value = res.applicant_name
        }

        if (res.form) {
            Object.assign(formData, res.form)
        }
    } catch (err) {
        console.error('Failed to fetch final submission data:', err)
    }
}

async function handleSubmit() {
    errorMessage.value = ''
    successMessage.value = ''

    if (!allChecksConfirmed.value) {
        errorMessage.value = 'Please review and confirm all declaration checkboxes before submitting.'
        return
    }

    saving.value = true

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const payload = {
            onboardingId: props.onboardingId,
            ...formData,
        }

        const response = await axios.post('/onboarding-process/save-final-submission', payload, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        successMessage.value = 'Onboarding completed successfully! Thank you for completing the onboarding process.'
        
        setTimeout(() => {
            props.goToNextStep()
        }, 500)
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            err.response?.data?.error ||
            'Failed to submit final submission. Please try again.'
        errorMessage.value = msg
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchFinalSubmissionData()
})
</script>

<style scoped>
.step-final-submission {
    padding: 1rem 0;
}

.form-wrapper {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fff;
}

.review-notice {
    text-align: center;
    color: #bf162f;
    margin-bottom: 2rem;
    padding: 1rem;
    background: #fff5f5;
    border: 1px solid #ffcdd2;
    border-radius: 6px;
}

.notice-text {
    font-weight: 600;
    margin: 0;
    font-size: 15px;
}

.declarations-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #fafbfc;
}

.declaration-item {
    margin-bottom: 1.25rem;
    padding: 1rem;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.declaration-item:last-child {
    margin-bottom: 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    margin: 0;
}

.checkbox-input {
    margin-top: 0.25rem;
    cursor: pointer;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.checkbox-text {
    flex: 1;
    line-height: 1.6;
    font-size: 14px;
    color: #333;
    user-select: none;
}

.signature-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #dee2e6;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-col {
    display: flex;
    flex-direction: column;
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

    .form-wrapper {
        padding: 1rem;
    }

    .declarations-section {
        padding: 1rem;
    }

    .declaration-item {
        padding: 0.75rem;
    }
}
</style>
