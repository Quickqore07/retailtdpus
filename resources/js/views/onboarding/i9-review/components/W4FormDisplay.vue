<template>
    <div class="form-panel">
        <h3 class="form-title">W-4 Form (Employee's Withholding Certificate)</h3>
        
        <div v-if="onboardingData?.verified_w4?.w4Remark" class="remark-status">
            <strong>Remark:</strong> {{ verifiedData.w4Remark }}
        </div>

        <div class="form-content">
            <div class="form-header text-center mb-6">
                <h5 class="text-lg font-semibold">Employee's Withholding Certificate</h5>
                <p class="text-sm text-gray-600">Complete Form W-4 so that your employer can withhold the correct federal income tax from your pay.</p>
            </div>

            <!-- Step 1: Personal Information -->
            <div class="section-header">Step 1: Enter Personal Information</div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="field-group">
                    <label class="field-label">First Name</label>
                    <div class="field-value">{{ onboardingData?.applicant_first_name || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Middle Initial</label>
                    <div class="field-value">{{ onboardingData?.applicant_middle_initial || 'N/A' }}</div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">Last Name</label>
                    <div class="field-value">{{ onboardingData?.applicant_last_name || 'N/A' }}</div>
                </div>
            </div>

            <div class="field-group mb-4">
                <label class="field-label">Social Security Number</label>
                <div class="field-value" :class="{ 'field-value-invalid': !isSSNValid(onboardingData?.employee?.ssn) }">
                    {{ maskSSN(onboardingData?.employee?.ssn) }}
                </div>
                <p v-if="onboardingData?.employee?.ssn && !isSSNValid(onboardingData?.employee?.ssn)" class="field-error">
                    Please ensure a valid 9-digit SSN is on file.
                </p>
            </div>

            <div class="field-group mb-4">
                <label class="field-label">Address</label>
                <div class="field-value">{{ onboardingData?.applicant_address || 'N/A' }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
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
                    <div class="field-value">{{ onboardingData?.zip_code || 'N/A' }}</div>
                </div>
            </div>

            <!-- Filing Status -->
            <div class="field-group mb-6">
                <label class="field-label">Filing Status</label>
                <div class="filing-status">
                    <div v-if="data?.single_or_married == 1" class="status-item">
                        ✓ Single or Married filing separately
                    </div>
                    <div v-if="data?.married_filing == 2" class="status-item">
                        ✓ Married filing jointly or Qualifying surviving spouse
                    </div>
                    <div v-if="data?.head_of_household == 3" class="status-item">
                        ✓ Head of household
                    </div>
                </div>
            </div>

            <!-- Step 2: Multiple Jobs -->
            <div class="section-header">Step 2: Multiple Jobs or Spouse Works</div>
            
            <div class="field-group mb-6">
                <div class="multi-jobs-option">
                    <div v-if="data?.multiple_jobs_and_spouse_works == 1" class="option-item">
                        ✓ (a) Use the estimator at www.irs.gov/W4App
                    </div>
                    <div v-if="data?.multiple_jobs_and_spouse_works == 2" class="option-item">
                        ✓ (b) Use the Multiple Jobs Worksheet
                    </div>
                    <div v-if="data?.multiple_jobs_and_spouse_works == 3" class="option-item">
                        ✓ (c) Two jobs total - check this box
                    </div>
                </div>
            </div>

            <!-- Step 3: Claim Dependents -->
            <div class="section-header">Step 3: Claim Dependent and Other Credits</div>
            
            <div class="credit-table">
                <table class="w-full">
                    <tbody>
                        <tr>
                            <td class="label-col">Qualifying children under age 17 × $2,000</td>
                            <td class="value-col">${{ data?.qualifying_children || '0' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Other dependents × $500</td>
                            <td class="value-col">${{ data?.dependents || '0' }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label-col"><strong>Total Credits (Line 3)</strong></td>
                            <td class="value-col"><strong>${{ data?.total_amount || '0' }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Step 4: Other Adjustments -->
            <div class="section-header">Step 4 (Optional): Other Adjustments</div>
            
            <div class="adjustment-table">
                <table class="w-full">
                    <tbody>
                        <tr>
                            <td class="label-col">(a) Other income (not from jobs)</td>
                            <td class="value-col">${{ data?.other_income || '0' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">(b) Deductions</td>
                            <td class="value-col">${{ data?.deductions || '0' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">(c) Extra withholding</td>
                            <td class="value-col">${{ data?.extra_withholding || '0' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Step 5: Sign Here -->
            <div class="section-header">Step 5: Sign Here</div>
            
            <div class="signature-section">
                <p class="mb-4 text-sm text-gray-600">
                    Under penalties of perjury, I declare that this certificate, to the best of my knowledge and belief, is true, correct, and complete.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Date</label>
                        <div class="field-value">{{ formatDate(data?.employee_date) || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Employer Section -->
            <div v-if="verifiedData" class="employer-section mt-6">
                <h6 class="font-semibold mb-3">Employer Information</h6>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="field-group">
                        <label class="field-label">Employer's Name and Address</label>
                        <div class="field-value">{{ verifiedData.employer_name || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">First Date of Employment</label>
                        <div class="field-value">{{ formatDate(verifiedData.date_of_employment) || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Employer Identification Number (EIN)</label>
                        <div class="field-value">{{ verifiedData.identification_number || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Verification Status -->
            <div v-if="verifiedData" class="verification-status mt-6">
                <div class="status-badge" :class="getVerificationClass()">
                    Status: {{ getVerificationStatus() }}
                </div>
                <div v-if="verifiedData.w4Remark" class="mt-2">
                    <strong>Remark:</strong> {{ verifiedData.w4Remark }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/date'
import { isSSNValid, maskSSN } from '@/utils/ssn'

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    onboardingData: {
        type: Object,
        default: () => ({})
    },
    readonly: {
        type: Boolean,
        default: true
    }
})

const getVerificationStatus = () => {
    if (props.verifiedData?.w4Status === '4' || props.verifiedData?.w4Status === 4) {
        return 'Verified'
    }
    if (props.verifiedData?.w4Status === '3' || props.verifiedData?.w4Status === 3) {
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

.filing-status,
.multi-jobs-option {
    padding: 1rem;
    background: #eff6ff;
    border-radius: 0.5rem;
}

.status-item,
.option-item {
    color: #1e40af;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.status-item:last-child,
.option-item:last-child {
    margin-bottom: 0;
}

.credit-table,
.adjustment-table {
    margin-bottom: 1.5rem;
}

.credit-table table,
.adjustment-table table {
    border-collapse: collapse;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    overflow: hidden;
}

.credit-table td,
.adjustment-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.credit-table tr:last-child td,
.adjustment-table tr:last-child td {
    border-bottom: none;
}

.label-col {
    background: #f9fafb;
    font-weight: 500;
    color: #374151;
}

.value-col {
    background: #fff;
    text-align: right;
    font-weight: 600;
}

.total-row {
    background: #eff6ff;
}

.signature-section,
.employer-section {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1.5rem;
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
