<template>
    <div class="form-panel step1-welcome">
        <h5 class="mb-4 welcome-title">Welcome message</h5>
        
        <div class="form-inner mb-3">
            <p>Dear <b>{{ applicantFirstName }}</b>,</p>
            <p>
                Welcome to <b>{{ companyName }}</b>! We are thrilled to have you join our team and look
                forward to working with you.
            </p>
            <p>
                We believe that your skills, expertise, and experience will be valuable assets to our
                organization.
            </p>
            <p>
                We are committed to creating a positive and engaging work environment that supports our
                employees to grow and achieve their professional goals.
            </p>
            <p>
                We are all here to support you as you transition into your new role. Do not hesitate to
                call on any of us should you have questions or comments.
            </p>
            <p>
                We are excited to have you on board and look forward to seeing all the great things you
                will accomplish here at <b>{{ companyName }}</b>.
            </p>
            <p>
                Before we proceed with the next steps of your onboarding process, we kindly encourage you
                to review a few important documents, including the Employee Handbook and other essential
                materials that outline the terms and conditions of your employment with us.
            </p>
            <p>
                Please take some time to carefully go through these documents. Once you've reviewed
                everything, we ask that you digitally acknowledge and approve your agreement to the terms
                and conditions. This will allow us to move forward with the onboarding process.
            </p>
            <div class="ltr_detail">
                <p>Best regards,</p>
                <p><b>{{ companyName }}</b></p>
            </div>
        </div>
        <p v-if="submitError" class="mb-2 text-sm text-red-600">{{ submitError }}</p>
        <div class="step-actions">
            <Button variant="primary" @click="save" :loading="submitting" class="next btn btn-primary welcome-next">Next</Button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from '@/components/ui/button.vue'
import axios from 'axios'

const props = defineProps({
    applicantFirstName: { type: String, default: '' },
    companyName: { type: String, default: '' },
    goToNextStep: { type: Function, default: () => {} },
    onboardingId: { type: String, default: '' },
})
const submitting = ref(false)
const submitError = ref('')

async function save() {
    submitting.value = true
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        await axios.post('/update-onboarding-process', {
            onboarding_id: props.onboardingId
        }, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,  
        })
        props.goToNextStep()
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to save. Please try again.'
        submitError.value = msg
    } finally {
        submitting.value = false
    }
}
</script>

<style scoped>
.welcome-title {
    color: #bf162f;
}

.form-inner p {
    margin-bottom: 0.75rem;
}

.ltr_detail {
    margin-top: 1rem;
}

.step-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.next {
    text-decoration: none;
    padding: 0.5rem 1.25rem;
    border-radius: 6px;
    font-weight: 500;
}

.next:hover {
    color: #fff;
    opacity: 0.9;
}
</style>
