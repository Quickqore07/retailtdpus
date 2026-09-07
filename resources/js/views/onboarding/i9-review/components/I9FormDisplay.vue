<template>
    <div class="form-panel">
        <h3 class="form-title">I-9 Form (SS# / DOB / Work Authorization)</h3>
        
        <div v-if="onboardingData?.verified_i9?.remark" class="remark-status">
            <strong>Remark:</strong> {{ onboardingData?.verified_i9?.remark }}
        </div>

        <div class="form-content">
            <!-- Section 1: Employee Information -->
            <div class="section-header">Section 1: Employee Information and Attestation</div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="field-group">
                    <label class="field-label">Last Name (Family Name)</label>
                    <div class="field-value">{{ onboardingData?.applicant_last_name || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">First Name (Given Name)</label>
                    <div class="field-value">{{ onboardingData?.applicant_first_name || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Middle Initial</label>
                    <div class="field-value">{{ onboardingData?.applicant_middle_initial || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Other Last Names Used</label>
                    <div class="field-value">{{ data?.other_last_names || 'N/A' }}</div>
                </div>
                
                <div class="field-group col-span-2">
                    <label class="field-label">Address</label>
                    <div class="field-value">{{ onboardingData?.applicant_address || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Apt. Number</label>
                    <div class="field-value">{{ onboardingData?.apt_number || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Date of Birth</label>
                    <div class="field-value">{{ formatDate(onboardingData?.dob) || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">City or Town</label>
                    <div class="field-value">{{ onboardingData?.city || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">State</label>
                    <div class="field-value">{{ onboardingData?.state || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">ZIP Code</label>
                    <div class="field-value">{{ onboardingData?.zipcode || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">U.S. Social Security Number</label>
                    <div class="field-value" :class="{ 'field-value-invalid': !isSSNValid(onboardingData?.employee?.ssn) }">
                        {{ maskSSN(onboardingData?.employee?.ssn) }}
                    </div>
                    <p v-if="onboardingData?.employee?.ssn && !isSSNValid(onboardingData?.employee?.ssn)" class="field-error">
                        Please ensure a valid 9-digit SSN is on file.
                    </p>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Employee's Email Address</label>
                    <div class="field-value">{{ onboardingData?.applicant_email || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Employee's Telephone Number</label>
                    <div class="field-value">{{ onboardingData?.applicant_contact_number || 'N/A' }}</div>
                </div>
            </div>

            <!-- Citizenship Status -->
            <div class="field-group mb-4">
                <label class="field-label">Citizenship/Immigration Status</label>
                <div class="citizenship-status">
                    <div v-if="data?.citizenship_status == 1" class="status-item">
                        ✓ A citizen of the United States
                    </div>
                    <div v-else-if="data?.citizenship_status == 2" class="status-item">
                        ✓ A noncitizen national of the United States
                    </div>
                    <div v-else-if="data?.citizenship_status == 3" class="status-item">
                        ✓ A lawful permanent resident
                        <span class="ml-2">(USCIS #: {{ data?.uscis_or_a_number || 'N/A' }})</span>
                    </div>
                    <div v-else-if="data?.citizenship_status == 4" class="status-item">
                        ✓ An alien authorized to work until
                        <span class="ml-2">(Exp: {{ formatDate(data?.uscis_a_number) || 'N/A' }})</span>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div v-if="data?.uscis_number || data?.form_i94_admission_number || data?.foreign_passport_number" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div v-if="data?.uscis_number" class="field-group">
                    <label class="field-label">USCIS A-Number</label>
                    <div class="field-value">{{ data.uscis_number }}</div>
                </div>
                
                <div v-if="data?.form_i94_admission_number" class="field-group">
                    <label class="field-label">Form I-94 Admission Number</label>
                    <div class="field-value">{{ data.form_i94_admission_number }}</div>
                </div>
                
                <div v-if="data?.foreign_passport_number" class="field-group">
                    <label class="field-label">Foreign Passport Number</label>
                    <div class="field-value">{{ data.foreign_passport_number }}</div>
                </div>
            </div>

            <div class="field-group mb-4">
                <label class="field-label">Today's Date</label>
                <div class="field-value">{{ formatDate(data?.section1_today_date) || 'N/A' }}</div>
            </div>

            <!-- Section 2: Employer Review and Verification -->
            <div class="section-header mt-6">Section 2: Employer Review and Verification</div>
            
            <!-- Document List A -->
            <DocumentTable 
                title="List A"
                :documents="listADocuments"
            />

            <div class="separator">----------- OR -----------</div>

            <!-- Document List B & C -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <DocumentTable 
                    title="List B"
                    :documents="listBDocuments"
                />
                <DocumentTable 
                    title="List C"
                    :documents="listCDocuments"
                />
            </div>

            <!-- Additional Information -->
            <div v-if="data?.additional_information" class="field-group mt-4">
                <label class="field-label">Additional Information</label>
                <div class="field-value">{{ data.additional_information }}</div>
            </div>

            <!-- Alternative Procedure -->
            <div v-if="data?.alternative_procedure" class="field-group mt-4">
                <div class="checkbox-item">
                    ✓ Used alternative procedure authorized by DHS to examine documents
                </div>
            </div>

            <!-- Employer Certification -->
            <div class="employer-cert mt-6">
                <h6 class="font-semibold mb-3">Employer Certification</h6>
                <div class="certification-attestation mb-4">
                    <strong>Certification:</strong> I attest, under penalty of perjury, that (1) I have examined the documentation presented by the above-named employee, (2) the above-listed documentation appears to be genuine and to relate to the employee named, and (3) to the best of my knowledge, the employee is authorized to work in the United States.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <Input
                            v-if="!readonly"
                            v-model="employerCertForm.first_day_employment"
                            label="First Day of Employment"
                            type="date"
                        />
                        <template v-else>
                            <label class="field-label">First Day of Employment</label>
                            <div class="field-value">{{ formatDate(employerCertForm.first_day_employment) || 'N/A' }}</div>
                        </template>
                    </div>
                    
                    <div class="field-group">
                        <Input
                            v-if="!readonly"
                            v-model="employerCertForm.employer_name"
                            label="Employer Name"
                            type="text"
                            placeholder="Enter employer name"
                        />
                        <template v-else>
                            <label class="field-label">Employer Name</label>
                            <div class="field-value">{{ employerCertForm.employer_name || 'N/A' }}</div>
                        </template>
                    </div>
                    
                    <div class="field-group">
                        <Input
                            v-if="!readonly"
                            v-model="employerCertForm.employer_today_date"
                            label="Verification Date"
                            type="date"
                        />
                        <template v-else>
                            <label class="field-label">Verification Date</label>
                            <div class="field-value">{{ formatDate(employerCertForm.employer_today_date) || 'N/A' }}</div>
                        </template>
                    </div>
                    
                    <div class="field-group">
                        <Input
                            v-if="!readonly"
                            v-model="employerCertForm.employer_business_name"
                            label="Business Name"
                            type="text"
                            placeholder="Enter business name"
                        />
                        <template v-else>
                            <label class="field-label">Business Name</label>
                            <div class="field-value">{{ employerCertForm.employer_business_name || 'N/A' }}</div>
                        </template>
                    </div>
                    
                    <div class="field-group col-span-2">
                        <Input
                            v-if="!readonly"
                            v-model="employerCertForm.employer_business_address"
                            label="Business Address"
                            type="text"
                            placeholder="Enter business address"
                        />
                        <template v-else>
                            <label class="field-label">Business Address</label>
                            <div class="field-value">{{ employerCertForm.employer_business_address || 'N/A' }}</div>
                        </template>
                    </div>
                    <div class="field-group">
                        <Button variant="success" @click="submitEmployerCert">
                            Update Employer Certification
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Verification Status -->
            <div v-if="verifiedData" class="verification-status mt-6">
                <div class="status-badge" :class="getVerificationClass()">
                    Status: {{ getVerificationStatus() }}
                </div>
                <div v-if="verifiedData.remark" class="mt-2">
                    <strong>Remark:</strong> {{ verifiedData.remark }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { formatDate } from '@/utils/date'
import { isSSNValid, maskSSN } from '@/utils/ssn'
import DocumentTable from './DocumentTable.vue'
import Input from '@/components/ui/input.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import Button from '@/components/ui/button.vue'
import processDocs from '@/views/onboarding-process/docs.json'

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    onboardingData: {
        type: Object,
        default: () => ({})
    },
    verifiedData: {
        type: Object,
        default: () => ({})
    },
    readonly: {
        type: Boolean,
        default: true
    }
})

const message = useMessage()
// Employer certification form data
const employerCertForm = ref({
    first_day_employment: '',
    employer_name: '',
    employer_today_date: '',
    employer_business_name: '',
    employer_business_address: ''
})

// Initialize form with existing data
watch(() => [props.onboardingData, props.data], () => {
    employerCertForm.value = {
        first_day_employment: props.data?.first_day_employment || props.onboardingData?.doj || '',
        employer_name: props.data?.employer_name || '',
        employer_today_date: new Date().toISOString().split('T')[0],
        employer_business_name: props.data?.employer_business_name || '',
        employer_business_address: props.data?.employer_business_address || ''
    }
}, { immediate: true })

const submitEmployerCert = async () => {
    try {
        await useRequest('post', `/onboarding/i9-review/${props.onboardingData?.id}/update-employer-cert`, employerCertForm.value)
        message.success('Employer certification updated successfully')
    } catch (err) {
        message.error(err.response?.data?.message || 'Failed to update employer certification')
    }
}

const listADocuments = computed(() => {
    const docs = []
    const docData = props.data
    
    if (docData?.list_a_doc_title_1) {
        docs.push({
            type: processDocs.list_a_doc_title_1[docData.list_a_doc_title_1]?.value || '',
            issuingAuthority: docData.list_a_issuing_authority_1,
            documentNumber: docData.list_a_document_number_1,
            expirationDate: docData.list_a_expiration_date_1,
            filePath: docData.list_a_file_path
        })
    }
    if (docData?.list_a_doc_title_2) {
        docs.push({
            type: processDocs.list_a_doc_title_2[docData.list_a_doc_title_2]?.value || '',
            issuingAuthority: docData.list_a_issuing_authority_2,
            documentNumber: docData.list_a_document_number_2,
            expirationDate: docData.list_a_expiration_date_2,
            filePath: docData.list_a_file_path
        })
    }
    if (docData?.list_a_doc_title_3) {
        docs.push({
            type: processDocs.list_a_doc_title_3[docData.list_a_doc_title_3]?.value || '',
            issuingAuthority: docData.list_a_issuing_authority_3,
            documentNumber: docData.list_a_document_number_3,
            expirationDate: docData.list_a_expiration_date_3,
            filePath: docData.list_a_file_path
        })
    }
    
    return docs
})

const listBDocuments = computed(() => {
    const docData = props.data
    if (!docData?.b_selectDocumentType) return []
    
    return [{
        type: processDocs.list_b_doc_title[docData.b_selectDocumentType]?.value || '',
        issuingAuthority: docData.b_issuingAutority,
        documentNumber: docData.b_documentNumber,
        expirationDate: docData.b_expireDate,
        filePath: docData.list_b_file_path
    }]
})

const listCDocuments = computed(() => {
    const docData = props.data
    if (!docData?.c_selectDocumentType) return []
    
    return [{
        type: processDocs.list_c_doc_title[docData.c_selectDocumentType]?.value || '',
        issuingAuthority: docData.c_issuingAutority,
        documentNumber: docData.c_documentNumber,
        expirationDate: docData.c_expireDate,
        filePath: docData.list_c_file_path
    }]
})



const getVerificationStatus = () => {
    if (props.verifiedData?.onboardStatus === '4' || props.verifiedData?.onboardStatus === 4) {
        return 'Verified'
    }
    if (props.verifiedData?.onboardStatus === '3' || props.verifiedData?.onboardStatus === 3) {
        return 'Incomplete'
    }
    return 'Pending'
}

const getVerificationClass = () => {
    const status = getVerificationStatus()
    if (status === 'Verified') return 'status-verified'
    if (status === 'Incomplete') return 'status-incomplete'
    return 'status-pending'
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

.remark-status {
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    color: #92400e;
}

.section-header {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    padding: 0.75rem 0;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 1.5rem;
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

.citizenship-status {
    padding: 1rem;
    background: #eff6ff;
    border-radius: 0.5rem;
}

.status-item {
    color: #1e40af;
    font-weight: 500;
}

.separator {
    text-align: center;
    font-weight: 600;
    color: #6b7280;
    margin: 1.5rem 0;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #059669;
    font-weight: 500;
}

.employer-cert {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.certification-attestation {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 1rem;
    border-radius: 0.375rem;
    color: #78350f;
    line-height: 1.6;
}

.verification-status {
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 600;
}

.status-verified {
    background: #d1fae5;
    color: #065f46;
}

.status-incomplete {
    background: #fee2e2;
    color: #991b1b;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}
</style>
