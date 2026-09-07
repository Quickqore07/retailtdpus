<template>
    <div class="onboarding-review-container">
        <div
            v-if="pdfDownloadKey"
            class="pdf-download-overlay"
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <div class="pdf-download-panel">
                <div class="spinner"></div>
                <p class="pdf-download-message">Preparing {{ pdfDownloadLabel }}…</p>
            </div>
        </div>

        <div v-if="loading" class="loading-container">
            <div class="spinner"></div>
            <p>Loading onboarding details...</p>
        </div>

        <div v-else-if="error" class="error-container">
            <p class="error-message">{{ error }}</p>
            <Button @click="fetchData">Retry</Button>
        </div>

        <div v-else class="onboarding-content">
            <!-- Header -->
            <div class="header-section">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-2xl font-bold !mb-0">Onboarding Review #{{ onboardingData.onboarding_number }}</h2>
                    <Button variant="outline" @click="router.go(-1)" class="btn-back">
                        <SvgIcon name="arrow-left" size="sm" />
                        Back to List
                    </Button>
                </div>

                <!-- Employee Info Card -->
                <!-- <div class="info-card">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="info-item">
                            <label>Employee Name</label>
                            <p class="font-semibold">{{ getFullName() }}</p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p>{{ onboardingData.applicant_email || 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <label>Contact</label>
                            <p>{{ onboardingData.applicant_contact_number || 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <label>Company</label>
                            <p>{{ onboardingData.company?.name || 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <label>Submitted Date</label>
                            <p>{{ formatDate(onboardingData.submit_application_date || onboardingData.created_at) }}</p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <span class="status-badge" :class="getStatusClass()">
                                {{ getStatusLabel() }}
                            </span>
                        </div>
                    </div>
                </div> -->
            </div>

            <!-- Multi-step Form Display -->
            <div class="multi-step-form">
                <div class="flex gap-6">
                    <!-- Sidebar -->
                    <div class="sidebar">
                        <ul class="step-list">
                            <li 
                                v-for="(step, index) in steps" 
                                :key="index"
                                :class="{ active: activeStep === step.id }"
                                @click="activeStep = step.id"
                            >
                                <span class="step-icon" :class="{ completed: isStepCompleted(index) }">
                                    <span v-if="isStepCompleted(index)">✓</span>
                                    <span v-else>{{ index + 1 }}</span>
                                </span>
                                <span class="step-title">{{ step.title }}</span>
                            </li>
                        </ul>

                        <!-- Document Preview Section -->
                        <div v-if="i9Documents.length > 0" class="document-preview mt-6">
                            <h4 class="font-semibold mb-3">Uploaded Documents</h4>
                            <div class="space-y-2">
                                <a 
                                    v-for="doc in i9Documents" 
                                    :key="doc.name"
                                    :href="doc.url" 
                                    target="_blank"
                                    class="document-link"
                                >
                                    <SvgIcon name="file-check" size="md" />
                                    {{ doc.name }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="form-content max-h-[calc(100vh-100px)] overflow-y-auto">
                        <!-- Step 1: Handbook Acknowledgment -->
                        <HandbookAcknowledgment 
                            v-if="activeStep === 0"
                            :data="onboardingData.handbook"
                            :readonly="true"
                        />

                        <!-- Step 2: I-9 Form -->
                        <I9FormDisplay
                            v-else-if="activeStep === 1"
                            :data="onboardingData.form_i9"
                            :onboarding-data="onboardingData"
                            :verified-data="onboardingData.verified_i9"
                            :readonly="false"
                        />

                        <!-- Step 3: W-4 Form -->
                        <W4FormDisplay
                            v-else-if="activeStep === 2"
                            :data="onboardingData.form_w4"
                            :onboarding-data="onboardingData"
                            :readonly="true"
                        />

                        <!-- Step 4: Direct Deposit -->
                        <DirectDepositDisplay
                            v-else-if="activeStep === 3"
                            :data="onboardingData.direct_deposit"
                            :onboarding-data="onboardingData"
                            :readonly="true"
                        />

                        <!-- Step 5: Emergency Contact -->
                        <EmergencyContactDisplay
                            v-else-if="activeStep === 4"
                            :data="onboardingData.emergency_contact"
                            :onboarding-data="onboardingData"
                            :readonly="true"
                        />

                        <!-- Step 6: Final Acknowledgment -->
                        <FinalAcknowledgment
                            :skipI9W4="skipI9W4"
                            v-else-if="activeStep === 5"
                            :data="onboardingData.final_acknowledgment"
                            :onboarding-data="onboardingData"
                            :approving="approving"
                            :pdf-download-key="pdfDownloadKey"
                            :readonly="true"
                            @approveForm="approveForm"
                            @rejectForm="rejectForm"
                            @printPdf="printI9Pdf"
                            @printW4Pdf="printW4Pdf"
                            @printBenefitPdf="printBenefitPdf"
                            @printHireFormPdf="printHireFormPdf"
                            @printDirectDepositPdf="printDirectDepositPdf"
                        />
                    </div>
                    
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <Button variant="outline" @click="previousStep" :disabled="activeStep === 0">
                        Previous Step
                    </Button>
                    <Button variant="primary" @click="nextStep" :disabled="activeStep === 5">
                        Next Step
                    </Button>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'

// Import form display components
import HandbookAcknowledgment from './components/HandbookAcknowledgment.vue'
import I9FormDisplay from './components/I9FormDisplay.vue'
import W4FormDisplay from './components/W4FormDisplay.vue'
import DirectDepositDisplay from './components/DirectDepositDisplay.vue'
import EmergencyContactDisplay from './components/EmergencyContactDisplay.vue'
import FinalAcknowledgment from './components/FinalAcknowledgment.vue'
import processDocs from '@/views/onboarding-process/docs.json'

const PDF_DOWNLOAD_LABELS = {
    i9: 'I-9 PDF',
    w4: 'W-4 PDF',
    benefit: 'Benefit PDF',
    hire: 'Hire Form PDF',
    'direct-deposit': 'Direct Deposit PDF',
}

const route = useRoute()
const message = useMessage()
const router = useRouter()
const loading = ref(true)
const approving = ref(false)
const error = ref(null)
const activeStep = ref(0)
const onboardingData = ref({})
const skipI9W4 = ref(false)
const pdfDownloadKey = ref(null)

const pdfDownloadLabel = computed(() => {
    const key = pdfDownloadKey.value
    return key ? (PDF_DOWNLOAD_LABELS[key] || 'PDF') : ''
})

const steps = ref([
    { title: 'Handbook Acknowledgment', key: 'handbook',id:0 },
    { title: 'I-9 Form (SS# /DOB/Work Authorization)', key: 'form_i9',id:1 },
    { title: 'W-4 / W-9 Form', key: 'form_w4',id:2 },
    { title: 'Direct Deposit Form', key: 'direct_deposit',id:3 },
    { title: 'Emergency Contact', key: 'emergency_contact',id:4 },
    { title: 'Final Acknowledgment', key: 'final_acknowledgment',id:5 }
])

const previousStep = () => {
    if (skipI9W4.value && activeStep.value === 2 ) {
        activeStep.value = 0
        return
    }
    activeStep.value--
}
const nextStep = () => {
    
    if (skipI9W4.value && activeStep.value === 0 ) {
        activeStep.value = 3
        return
    }
    activeStep.value++
}

const i9Documents = computed(() => {
    const docs = []
    const i9Data = onboardingData.value.form_i9 || {}
        console.log(processDocs);
        
    if (i9Data.list_a_file_path) {
        docs.push({ 
            name: `List A Document${ i9Data.list_a_doc_title_1 ? ': ' + processDocs.list_a_doc_title_1[i9Data.list_a_doc_title_1]?.value : ''}`, 
            url: i9Data.list_a_file_path 
        })
    }
    if (i9Data.list_b_file_path) {
        docs.push({ 
            name: `List B Document${i9Data.list_b_doc_title ? ': ' + processDocs.list_b_doc_title[i9Data.list_b_doc_title]?.value : ''}`, 
            url: i9Data.list_b_file_path 
        })
    }
    if (i9Data.list_c_file_path) {
        docs.push({ 
            name: `List C Document${i9Data.list_c_doc_title ? ': ' + processDocs.list_c_doc_title[i9Data.list_c_doc_title]?.value : ''}`, 
            url: i9Data.list_c_file_path 
        })
    }
    if (i9Data.additional_document_path) {
        docs.push({ 
            name: i9Data.additional_document_label || 'Additional Document', 
            url: i9Data.additional_document_path 
        })
    }
    
    return docs
})



const isStepCompleted = (index) => {
    const step = steps.value[index]
    return !!onboardingData.value[step.key]
}

const fetchData = async () => {
    loading.value = true
    error.value = null
    try {
        const response = await useRequest('get', `/onboarding/i9-review/${route.params.id}`)
        onboardingData.value = response.model
        skipI9W4.value = response.model.i9_with_work_bright
        
        if (skipI9W4.value && steps.value.length > 4) {
            steps.value.splice(1, 2)
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load onboarding data'
        message.error(error.value)
    } finally {
        loading.value = false
    }
}

const printI9Pdf = async () => {
    pdfDownloadKey.value = 'i9'
    try {
        const response = await api.get(`/onboarding/i9-review/${route.params.id}/export-pdf`, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to generate PDF')
    } finally {
        pdfDownloadKey.value = null
    }
}

const printW4Pdf = async () => {
    pdfDownloadKey.value = 'w4'
    try {
        const response = await api.get(`/onboarding/i9-review/${route.params.id}/export-w4-pdf`, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to generate PDF')
    } finally {
        pdfDownloadKey.value = null
    }
}

const printBenefitPdf = async () => {
    pdfDownloadKey.value = 'benefit'
    try {
        const response = await api.get(`/onboarding/i9-review/${route.params.id}/export-benefit-pdf`, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to generate PDF')
    } finally {
        pdfDownloadKey.value = null
    }
}

const printHireFormPdf = async () => {
    pdfDownloadKey.value = 'hire'
    try {
        const response = await api.get(`/onboarding/i9-review/${route.params.id}/export-hire-form-pdf`, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to generate PDF')
    } finally {
        pdfDownloadKey.value = null
    }
}

const printDirectDepositPdf = async () => {
    pdfDownloadKey.value = 'direct-deposit'
    try {
        const response = await api.get(`/onboarding/i9-review/${route.params.id}/export-direct-deposit-pdf`, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to generate PDF')
    } finally {
        pdfDownloadKey.value = null
    }
}

const approveForm = async () => {
    approving.value = true
    try {
        await useRequest('post', `/onboarding/i9-review/${route.params.id}/approve`)
        message.success('I-9 form approved successfully')
        await fetchData()
        if(!skipI9W4.value){
            await printW4Pdf()
        }
    } catch (error) {
        message.error(error.response?.data?.message || 'Failed to approve form')
    } finally {
        approving.value = false
    }
}
const rejectForm = async (reason) => {
    try {
        await useRequest('post', `/onboarding/i9-review/${route.params.id}/reject`, { reason })
        message.success('Note sent and form marked for corrections')
        await fetchData()
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to reject form')
    }
}
onMounted(() => {
    fetchData()
})
</script>

<style scoped>
.onboarding-review-container {
    margin: 0 auto;
    max-width: 1200px;
    position: relative;
}

.pdf-download-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(17, 24, 39, 0.45);
}

.pdf-download-panel {
    background: #fff;
    border-radius: 0.5rem;
    padding: 2rem 2.5rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    text-align: center;
    min-width: 240px;
}

.pdf-download-message {
    margin: 1rem 0 0;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #374151;
}

.loading-container, .error-container {
    text-align: center;
    padding: 3rem;
}

.spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 1rem;
    border: 4px solid #f3f4f6;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.error-message {
    color: #dc2626;
    margin-bottom: 1rem;
}


.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-back:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.info-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.info-item label {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.info-item p {
    color: #111827;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-verified {
    background: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-not-submitted {
    background: #f3f4f6;
    color: #374151;
}

.multi-step-form {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.sidebar {
    flex: 0 0 280px;
    border-right: 2px solid #e7e7f1;
    padding-right: 1.5rem;
}

.step-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.step-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.step-list li:hover {
    background: #f9fafb;
}

.step-list li.active {
    background: #eff6ff;
    color: #1e40af;
}

.step-list li.active .step-title {
    font-weight: 700;
}

.step-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.step-icon.completed {
    background: #10b981;
    color: #fff;
}

.step-title {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.document-preview {
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.document-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    color: #2563eb;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.document-link:hover {
    background: #eff6ff;
}

.form-content {
    flex: 1;
    min-width: 0;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}
</style>
