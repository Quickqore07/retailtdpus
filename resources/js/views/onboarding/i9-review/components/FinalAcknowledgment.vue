<template>
    <div class="form-panel">
        <h3 class="form-title">Final Acknowledgment</h3>

        <div class="form-content">
            <p class="form-description">
                By signing below, you acknowledge that you have read and understood all the forms and information provided during the onboarding process.
            </p>

            <div class="acknowledgment-section">
                <div class="section-header">Acknowledgment Statement</div>
                
                <div class="statement-box">
                    <p class="mb-4">
                        I hereby acknowledge that I have:
                    </p>
                    <ul class="acknowledgment-list">
                        <li>✓ Received and reviewed the Employee Handbook</li>
                        <li>✓ Completed and signed the I-9 Form (Employment Eligibility Verification)</li>
                        <li>✓ Completed and signed the W-4 Form (Employee's Withholding Certificate)</li>
                        <li>✓ Provided my Direct Deposit information</li>
                        <li>✓ Provided Emergency Contact information</li>
                        <li>✓ Read and agreed to all company policies and procedures</li>
                    </ul>
                    <p class="mt-4">
                        I understand that all the information I have provided is true and accurate to the best of my knowledge. 
                        I understand that any false or misleading information may result in disciplinary action, up to and including termination of employment.
                    </p>
                </div>
            </div>

            <div class="employee-verification mt-6">
                <div class="section-header">Employee Verification</div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">First Name</label>
                        <div class="field-value">{{ onboardingData?.applicant_first_name || 'N/A' }}</div>  
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Last Name</label>
                        <div class="field-value">{{ data?.last_name || onboardingData?.applicant_last_name || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Last 4 Digits of SSN</label>
                        <div class="field-value" :class="{ 'field-value-invalid': !isSSNValid(onboardingData?.employee?.ssn) }">
                            {{ maskSSN(onboardingData?.employee?.ssn) }}
                        </div>
                        <p v-if="onboardingData?.employee?.ssn && !isSSNValid(onboardingData?.employee?.ssn)" class="field-error">
                            Please enter a valid 9-digit SSN.
                        </p>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Date</label>
                        <div class="field-value">{{ formatDate(onboardingData?.created_at) || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <div v-if="data?.digital_signature" class="signature-section mt-6">
                <div class="section-header">Digital Signature</div>
                
                <div class="signature-display">
                    <img 
                        v-if="isImageSignature" 
                        :src="data.digital_signature" 
                        alt="Digital Signature"
                        class="signature-image"
                    />
                    <div v-else class="signature-text">
                        {{ data.digital_signature }}
                    </div>
                </div>
            </div>

            <div v-if="hrStatus" class="hr-status mt-6">
                <div class="section-header">HR Review Status</div>
                
                <div class="status-box">
                    <div class="status-badge" :class="getStatusClass()">
                        {{ getStatusLabel() }}
                    </div>
                    
                    <div v-if="hrStatus.remark" class="mt-3">
                        <strong>HR Remark:</strong>
                        <p class="remark-text">{{ hrStatus.remark }}</p>
                    </div>
                    
                    <div v-if="hrStatus.reviewed_by" class="mt-3 text-sm text-gray-600">
                        <strong>Reviewed by:</strong> {{ hrStatus.reviewed_by }}
                        <span v-if="hrStatus.reviewed_at"> on {{ formatDate(hrStatus.reviewed_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- v-if="onboardingData?.status === 'form_submitted'" -->
            <div v-if="can('onboarding', 'review') && (onboardingData?.status === 'form_submitted' || onboardingData?.status === 'verified' || onboardingData?.document_approved || onboardingData?.status === 'i9_submitted')" class="flex justify-between items-center flex-wrap gap-2">
                <div class="flex gap-1 items-center flex-wrap">
                    <Button
                        variant="secondary"
                        @click="emit('printPdf')"
                        v-if="(onboardingData?.status === 'verified' || onboardingData?.document_approved) && !skipI9W4"
                        :loading="pdfDownloadKey === 'i9'"
                        :disabled="!isSSNValid(onboardingData?.employee?.ssn) || isPdfDownloading"
                        :title="!isSSNValid(onboardingData?.employee?.ssn) ? 'Valid SSN required to print PDF' : ''"
                    >
                        <SvgIcon name="download" size="sm" />
                        Print I-9 PDF
                    </Button>
                    <Button
                        variant="secondary"
                        @click="emit('printW4Pdf')"
                        v-if="(onboardingData?.status === 'verified' || onboardingData?.document_approved) && !skipI9W4"
                        :loading="pdfDownloadKey === 'w4'"
                        :disabled="isPdfDownloading"
                    >
                        <SvgIcon name="download" size="sm" />
                        Print W-4 PDF
                    </Button>
                    <Button
                        variant="secondary"
                        @click="emit('printBenefitPdf')"
                        v-if="(onboardingData?.status === 'verified' || onboardingData?.document_approved)"
                        :loading="pdfDownloadKey === 'benefit'"
                        :disabled="isPdfDownloading"
                    >
                        <SvgIcon name="download" size="sm" />
                        Print Benefit PDF
                    </Button>
                    <Button
                        variant="secondary"
                        @click="emit('printHireFormPdf')"
                        v-if="onboardingData?.status === 'verified' || onboardingData?.document_approved"
                        :loading="pdfDownloadKey === 'hire'"
                        :disabled="isPdfDownloading"
                    >
                        <SvgIcon name="download" size="sm" />
                        Print Hire Form PDF
                    </Button>
                    <Button
                        variant="secondary"
                        @click="emit('printDirectDepositPdf')"
                        v-if="onboardingData?.status === 'verified' || onboardingData?.document_approved"
                        :loading="pdfDownloadKey === 'direct-deposit'"
                        :disabled="isPdfDownloading"
                    >
                        <SvgIcon name="download" size="sm" />
                        Print Direct Deposit PDF
                    </Button>
                    <Button
                        variant="success"
                        v-if="onboardingData?.status === 'form_submitted' || onboardingData?.status === 'i9_submitted'"
                        :loading="approving"
                        @click="emit('approveForm')"
                        :title="!isSSNValid(onboardingData?.employee?.ssn) ? 'Valid SSN required to approve' : ''"
                        >
                        <!-- :disabled="approving || !isSSNValid(onboardingData?.employee?.ssn)" -->
                        <SvgIcon name="check-circle" size="sm" />
                        Approve
                    </Button>
                    <span v-if="!isSSNValid(onboardingData?.employee?.ssn)" class="text-sm text-amber-600 ml-2">
                        Valid 9-digit SSN required for this action.
                    </span>
                    <div v-if="onboardingData?.status === 'form_submitted'" class="w-full mt-3">
                        <label class="field-label">Notes for employee</label>
                        <textarea
                            v-model="reviewNote"
                            class="note-textarea"
                            rows="3"
                            placeholder="Write note for employee and submit to send back for correction"
                        ></textarea>
                        <Button
                            variant="danger"
                            class="mt-2"
                            :disabled="approving || !reviewNote.trim()"
                            @click="submitNote"
                        >
                            <SvgIcon name="exclamation-circle" size="sm" />
                            Submit Note
                        </Button>
                    </div>
                </div>
                <div :class="getOnboardingStatusClass(onboardingData?.status)" class="status-badge " v-if="onboardingData.employee?.employee_rates_requests.length > 0">
                    <Strong>Employee Rates Requests are not approved yet</Strong>
                </div>
                <div v-else-if="onboardingData?.document_approved" class="status-badge document-approved-badge " :class="getOnboardingStatusClass(onboardingData?.status)">
                    <strong>Document Approved and waiting for I9 and W4 form:</strong>
                </div>
                <div v-else class="status-badge" :class="getOnboardingStatusClass(onboardingData?.status)">
                    <strong>{{ getOnboardingStatusLabel(onboardingData?.status) }}:</strong>
                    {{ getOnboardingStatusMessage(onboardingData?.status) }}
                </div>
            </div>
            <div v-else>
                <div class="status-badge" :class="getOnboardingStatusClass(onboardingData?.status)">
                    <strong>{{ getOnboardingStatusLabel(onboardingData?.status) }}:</strong>
                    {{ getOnboardingStatusMessage(onboardingData?.status) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { formatDate } from '@/utils/date'
import { isSSNValid, maskSSN } from '@/utils/ssn'
import { useSPPermission } from '@/composables/useSPPermission'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'

const { can } = useSPPermission()

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    onboardingData: {
        type: Object,
        default: () => ({})
    },
    hrStatus: {
        type: Object,
        default: null
    },
    approving: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: true
    },
    skipI9W4: {
        type: Boolean,
        default: false
    },
    pdfDownloadKey: {
        type: String,
        default: null,
    },
})
const emit = defineEmits(['approveForm', 'rejectForm', 'printPdf', 'printW4Pdf', 'printBenefitPdf', 'printHireFormPdf', 'printDirectDepositPdf'])
const reviewNote = ref('')

const isPdfDownloading = computed(() => !!props.pdfDownloadKey)

const submitNote = () => {
    const note = reviewNote.value.trim()
    if (!note) {
        return
    }

    emit('rejectForm', note)
    reviewNote.value = ''
}

const isImageSignature = computed(() => {
    return props.data?.digital_signature?.startsWith('data:image') || 
           props.data?.digital_signature?.startsWith('http')
})

const getStatusLabel = () => {
    if (!props.hrStatus) return 'Pending Review'
    
    const status = props.hrStatus.status
    if (status === 'approved' || status === '4' || status === 4) return 'Approved'
    if (status === 'rejected' || status === '3' || status === 3) return 'Needs Revision'
    return 'Pending Review'
}

const getStatusClass = () => {
    const label = getStatusLabel()
    if (label === 'Approved') return 'status-approved'
    if (label === 'Needs Revision') return 'status-rejected'
    return 'status-pending'
}

const renderOnboardingStatus = (status) => {
    if (status === 'form_submitted') return 'Onboarding Form Submitted HR Review Pending'
    if (status === 'pending') return 'Onboarding Pending'
    if (status === 'in_complete_form') return 'Onboarding Incomplete Form'
    if (status === 'verified') return 'Onboarding Verified'
    if (status === 'document_approved') return 'Onboarding Document Approved and waiting for I9 and W4 form'
    if (status === 'i9_submitted') return 'Onboarding I-9 Submitted'
    return 'Onboarding Not Submitted'
}

const getOnboardingStatusClass = (status) => {
    switch(status) {
        case 'verified':
            return 'status-success'
        case 'form_submitted':
            return 'status-warning'
        case 'in_complete_form':
            return 'status-info'
        case 'pending':
            return 'status-pending'
        default:
            return 'status-default'
    }
}

const getOnboardingStatusIcon = (status) => {
    switch(status) {
        case 'verified':
            return '✓'
        case 'form_submitted':
            return '⏳'
        case 'in_complete_form':
            return '⚠'
        case 'pending':
            return '○'
        default:
            return '◌'
    }
}

const getOnboardingStatusLabel = (status) => {
    switch(status) {
        case 'verified':
            return 'Verified'
        case 'form_submitted':
            return 'Submitted'
        case 'in_complete_form':
            return 'Incomplete'
        case 'pending':
            return 'Pending'
        case 'document_approved':
            return 'Document Approved and waiting for I9 and W4 form'
        case 'i9_submitted':
            return 'I9 and W4 form submitted'
        default:
            return 'Not Submitted'
    }
}

const getOnboardingStatusMessage = (status) => {
    switch(status) {
        case 'verified':
            return 'Onboarding approved by HR'
        case 'form_submitted':
            return 'Awaiting HR review'
        case 'in_complete_form':
            return 'Please complete all required fields'
        case 'pending':
            return 'Onboarding not started'
        case 'document_approved':
            return 'Document Approved and waiting for I9 and W4 form'
        case 'i9_submitted':
            return 'I9 and W4 form submitted'
        case 'section_2_verification_done':
            return 'Section 2 verification done'
        case 'waiting_for_section_2_verification':
            return 'Waiting for section 2 verification'
        case 'employee_authorized':
            return 'Employee authorized'
        default:
            return 'Please submit your onboarding form'
    }
}
</script>

<style scoped>
.form-panel {
    padding: 1.5rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #111827;
}

.form-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.acknowledgment-section,
.employee-verification,
.signature-section,
.hr-status {
    margin-bottom: 2rem;
}

.section-header {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    padding: 0.75rem 0;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 1rem;
}

.statement-box {
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    padding: 1.5rem;
    border-radius: 0.375rem;
    line-height: 1.6;
}

.acknowledgment-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0;
}

.acknowledgment-list li {
    padding: 0.5rem 0;
    color: #059669;
    font-weight: 500;
}

.field-group {
    margin-bottom: 1rem;
}

.field-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.field-value {
    padding: 0.5rem 0.75rem;
    background: #f9fafb;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    color: #111827;
}

.field-value-invalid {
    border-color: #f59e0b;
    background: #fffbeb;
}

.field-error {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #b45309;
    font-weight: 500;
}

.note-textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.75rem;
    color: #111827;
    background: #fff;
}

.signature-display {
    padding: 1.5rem;
    background: #fff;
    border: 2px dashed #d1d5db;
    border-radius: 0.5rem;
    text-align: center;
}

.signature-image {
    max-width: 300px;
    max-height: 150px;
    margin: 0 auto;
}

.signature-text {
    font-family: 'Brush Script MT', cursive;
    font-size: 2rem;
    color: #111827;
}

.status-box {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
}

.status-approved {
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

.remark-text {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #fff;
    border-radius: 0.375rem;
    color: #374151;
}

.completion-notice {
    margin-top: 2rem;
}

.notice-box {
    background: #d1fae5;
    border: 1px solid #a7f3d0;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.status-badge {
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    text-align: center;
    font-size: 0.875rem;
}

.status-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.status-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}

.status-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.status-pending {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #9ca3af;
}

.status-default {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.document-approved-badge {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}
</style>
