<template>
    <div class="form-panel">
        <h3 class="form-title !mb-1">Direct Deposit Form</h3>

        <div class="form-content">
            <p class="form-description !mb-0">
                Complete this form to request direct deposit of your paycheck.
            </p>

            <div class="section-header">Customer Information</div>
            
            <div class="field-group mb-4">
                <label class="field-label">Customer Name</label>
                <div class="field-value">{{ onboardingData?.applicant_first_name || 'N/A' }} {{ onboardingData?.applicant_middle_initial || 'N/A' }} {{ onboardingData?.applicant_last_name || 'N/A' }}</div>
            </div>

            <div class="field-group mb-4">
                <label class="field-label">Address</label>
                <div class="field-value">{{ onboardingData?.applicant_address || 'N/A' }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="field-group">
                    <label class="field-label">City</label>
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

            <div class="section-header">Account Information</div>
            
            <p class="mb-4 text-sm font-semibold">
                Please have my paycheck automatically deposited into the following account:
            </p>

            <div class="account-info">
                <div class="field-group">
                    <label class="field-label">Account Type</label>
                    <div class="account-type">
                        <div v-if="data?.acct_type_1 === 'savings'" class="type-item">
                            ✓ Checking Account Number
                        </div>
                        <div v-if="data?.acct_type_1 === 'checking'" class="type-item">
                            ✓ Savings/MIA/Money Market Account Number
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Account Number</label>
                    <div class="field-value">{{ data?.account_number_1 }}</div>
                </div>

                <div class="field-group">
                    <label class="field-label">Bank Routing Number</label>
                    <div class="field-value">{{ data?.bank_routing_1 || 'N/A' }}</div>
                </div>
                <div class="field-group">
                    <label class="field-label">Deposit Amount</label>
                    <div class="field-value">{{ data?.deposit_amount_1 || 'N/A' }} {{ data?.deposit_type_1 === 'percentage' ? '%' : '$' }}</div>
                </div>
            </div>

            <div class="account-info" v-if="data?.acct_type_2">
                <div class="field-group">
                    <label class="field-label">Account Type</label>
                    <div class="account-type">
                        <div v-if="data?.acct_type_2 === 'savings'" class="type-item">
                            ✓ Checking Account Number
                        </div>
                        <div v-if="data?.acct_type_2 === 'checking'" class="type-item">
                            ✓ Savings/MIA/Money Market Account Number
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Account Number</label>
                    <div class="field-value">{{ data?.account_number_2 }}</div>
                </div>

                <div class="field-group">
                    <label class="field-label">Bank Routing Number</label>
                    <div class="field-value">{{ data?.bank_routing_2 || 'N/A' }}</div>
                </div>
                <div class="field-group">
                    <label class="field-label">Deposit Amount</label>
                    <div class="field-value">{{ data?.deposit_amount_2 || 'N/A' }} {{ data?.deposit_type_2 === 'percentage' ? '%' : '$' }}</div>
                </div>
            </div>



            <div class="instructions mt-6">
                <h6 class="font-semibold mb-2">Finding Your Account Information:</h6>
                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                    <li>You can find your account and routing numbers when you sign in to your bank's online portal</li>
                    <li>Click on the last four digits of your account number that appear above your account information, or</li>
                    <li>Select the "Account & routing number PDF" from the menu</li>
                </ul>
            </div>

            <div class="authorization mt-6">
                <p class="text-sm">
                    <strong>Authorization:</strong> I authorize 
                    <span class="font-semibold">{{ data?.authorizeCustomerName || 'the Company' }}</span>
                    and my bank to automatically deposit my paycheck into my account listed above (this includes my authorization to correct entries made in error). 
                    This authorization will remain in effect until I give written notice to cancel it.
                </p>
            </div>

            <div class="signature-section mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Customer Signature</label>
                        <div class="field-value">{{ data?.signature || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Date</label>
                        <div class="field-value">{{ formatDate(data?.date) || 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/date'

defineProps({
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

.account-info {
    background: #eff6ff;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.account-type {
    padding: 0.75rem;
    background: #fff;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.type-item {
    color: #1e40af;
    font-weight: 600;
}

.instructions {
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    padding: 1rem;
    border-radius: 0.375rem;
}

.authorization {
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 0.5rem;
    padding: 1rem;
}

.signature-section {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1.5rem;
}
</style>
