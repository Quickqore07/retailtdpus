<template>
    <div class="step-direct-deposit max-h-[calc(100vh-100px)] overflow-y-auto">
        <h5 class="mb-4" style="color: #bf162f">Direct Deposit Authorization Form</h5>

        <form @submit.prevent="handleSubmit">
            <div class="form-inner mb-3">
                <div class="form-wrapper">
                    <div class="authorization-text">
                        This authorizes
                        <b class="company-name">{{ companyName }}</b>
                        (the "Company") to send credit entries and, if necessary, debit entries and
                        adjustments for any credit entries made in error to my account indicated below.
                        <p class="note">
                            <b>Note: Enter your company name in the blank space above.</b>
                        </p>
                    </div>

                    <!-- Account #1 -->
                    <div class="account-section">
                        <h6 class="account-title">Account #1</h6>
                        
                        <div class="form-group">
                            <label class="radio-label">
                                Account #1 Type (check one)
                            </label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input
                                        v-model="formData.acct_type_1"
                                        type="radio"
                                        required
                                        name="acct_type_1"
                                        value="checking"
                                    />
                                    <span>Checking</span>
                                </label>
                                <label class="radio-option">
                                    <input
                                        v-model="formData.acct_type_1"
                                        type="radio"
                                        required
                                        name="acct_type_1"
                                        value="savings"
                                    />
                                    <span>Savings</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.bank_name_1"
                                    label="Employee Bank Name"
                                    placeholder="Bank Name"
                                    required
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.bank_routing_1"
                                    label="Bank routing # (ABA#)"
                                    placeholder="Routing Number"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.account_number_1"
                                    label="Account #"
                                    placeholder="Account Number"
                                    required
                                />
                                <Input
                                    v-model="accountNumber1Confirmation"
                                    label="Re-enter Account #"
                                    placeholder="Re-enter Account Number"
                                    required
                                    :error="account1MismatchError"
                                    @input="validateAccountNumber1"
                                />
                            </div>
                            <div class="form-col !gap-0">
                                <label class="deposit-amount-label">Percentage or Dollar Amount</label>
                                <div class="deposit-amount-row">
                                    <select
                                        v-model="formData.deposit_type_1"
                                        class="deposit-type-select"
                                        required
                                    >
                                        <option value="percentage">%</option>
                                        <option value="fixed">$</option>
                                    </select>
                                    <Input
                                        v-model="formData.deposit_amount_1"
                                        type="number"
                                        placeholder="Enter amount"
                                        :max="formData.deposit_type_1 === 'percentage' ? 100 : 9999999999"
                                        :icon-left="formData.deposit_type_1 === 'percentage' ? 'percent' : 'dollar'"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account #2 -->
                    <div class="account-section">
                        <h6 class="account-title">Account #2</h6>
                        
                        <div class="form-group">
                            <label class="radio-label">
                                Account #2 Type (check one)
                            </label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input
                                        v-model="formData.acct_type_2"
                                        type="radio"
                                        name="acct_type_2"
                                        value="checking"
                                    />
                                    <span>Checking</span>
                                </label>
                                <label class="radio-option">
                                    <input
                                        v-model="formData.acct_type_2"
                                        type="radio"
                                        name="acct_type_2"
                                        value="savings"
                                    />
                                    <span>Savings</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.bank_name_2"
                                    label="Employee Bank Name"
                                    placeholder="Bank Name"
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.bank_routing_2"
                                    label="Bank routing # (ABA#)"
                                    placeholder="Routing Number"
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.account_number_2"
                                    label="Account #"
                                    placeholder="Account Number"
                                />
                                <Input
                                    v-model="accountNumber2Confirmation"
                                    label="Re-enter Account #"
                                    placeholder="Re-enter Account Number"
                                    :error="account2MismatchError"
                                    @input="validateAccountNumber2"
                                />
                            </div>
                            <div class="form-col !gap-0">
                                <label class="deposit-amount-label">Percentage or Dollar Amount</label>
                                <div class="deposit-amount-row">
                                    <select
                                        v-model="formData.deposit_type_2"
                                        class="deposit-type-select"
                                    >
                                        <option value="percentage">%</option>
                                        <option value="fixed">$</option>
                                    </select>
                                    <Input
                                        v-model="formData.deposit_amount_2"
                                        type="number"
                                        placeholder="Enter amount"
                                        :max="formData.deposit_type_2 === 'percentage' ? 100 : 9999999999"
                                        :icon-left="formData.deposit_type_2 === 'percentage' ? 'percent' : 'dollar'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.signature"
                                    label="Signature"
                                    placeholder="Signature"
                                    required
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.printed_name"
                                    label="Printed Name"
                                    placeholder="Printed Name"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <Input
                                    v-model="formData.employee_id"
                                    label="Employee ID #"
                                    readonly
                                />
                            </div>
                            <div class="form-col">
                                <Input
                                    v-model="formData.date"
                                    label="Date"
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
                <Button type="submit" :loading="saving" >
                    Save & Next
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
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

const companyName = ref('')
const companyCode = ref('')
const saving = ref(false)
const errorMessage = ref('')

const accountNumber1Confirmation = ref('')
const accountNumber2Confirmation = ref('')
const account1MismatchError = ref('')
const account2MismatchError = ref('')

const formData = reactive({
    acct_type_1: 'checking',
    bank_name_1: '',
    bank_routing_1: '',
    account_number_1: '',
    deposit_amount_1: '',
    deposit_type_1: 'percentage',
    acct_type_2: '',
    bank_name_2: '',
    bank_routing_2: '',
    account_number_2: '',
    deposit_amount_2: '',
    deposit_type_2: 'percentage',
    signature: '',
    printed_name: '',
    employee_id: '',
    date: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD
})

const hasValidationErrors = computed(() => {
    return !!account1MismatchError.value || (formData.account_number_2 && !!account2MismatchError.value)
})

function validateAccountNumber1() {
    if (accountNumber1Confirmation.value && formData.account_number_1 !== accountNumber1Confirmation.value) {
        account1MismatchError.value = 'Account numbers do not match'
    } else {
        account1MismatchError.value = ''
    }
}

function validateAccountNumber2() {
    if (accountNumber2Confirmation.value && formData.account_number_2 !== accountNumber2Confirmation.value) {
        account2MismatchError.value = 'Account numbers do not match'
    } else {
        account2MismatchError.value = ''
    }
}

async function fetchDirectDepositData() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const { data: res } = await axios.get('/onboarding-process/direct-deposit-data', {
            params: { onboardingId: props.onboardingId },
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        companyName.value = res.company_name || ''
        companyCode.value = res.company_code || ''
        formData.employee_id = res.employee_id || ''

        if (res.direct_deposit) {
            const dd = res.direct_deposit
            formData.acct_type_1 = dd.acct_type_1 || 'checking'
            formData.bank_name_1 = dd.bank_name_1 || ''
            formData.bank_routing_1 = dd.bank_routing_1 || ''
            formData.account_number_1 = dd.account_number_1 || ''
            formData.deposit_amount_1 = dd.deposit_amount_1 || ''
            formData.deposit_type_1 = dd.deposit_type_1 === 'fixed' ? 'fixed' : 'percentage'
            formData.acct_type_2 = dd.acct_type_2 || ''
            formData.bank_name_2 = dd.bank_name_2 || ''
            formData.bank_routing_2 = dd.bank_routing_2 || ''
            formData.account_number_2 = dd.account_number_2 || ''
            formData.deposit_amount_2 = dd.deposit_amount_2 || ''
            formData.deposit_type_2 = dd.deposit_type_2 === 'fixed' ? 'fixed' : 'percentage'
            formData.signature = dd.signature || ''
            formData.printed_name = dd.printed_name || ''
            formData.date = dd.date || formData.date

            accountNumber1Confirmation.value = dd.account_number_1 || ''
            accountNumber2Confirmation.value = dd.account_number_2 || ''
        }
    } catch (err) {
        console.error('Failed to fetch direct deposit data:', err)
    }
}

async function handleSubmit() {
    errorMessage.value = ''

    validateAccountNumber1()
    if (formData.account_number_2) {
        validateAccountNumber2()
    }

    if (hasValidationErrors.value) {
        errorMessage.value = 'Please fix validation errors before submitting.'
        return
    }

    saving.value = true

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const payload = {
            onboardingId: props.onboardingId,
            company_code: companyCode.value,
            ...formData,
        }

        await axios.post('/onboarding-process/direct-deposit', payload, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })

        props.goToNextStep()
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            err.response?.data?.error ||
            'Failed to save direct deposit information. Please try again.'
        errorMessage.value = msg
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchDirectDepositData()
})
</script>

<style scoped>
.step-direct-deposit {
    padding: 1rem 0;
}

.form-wrapper {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fff;
}

.authorization-text {
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.company-name {
    border-bottom: 1px solid #333;
    padding-bottom: 2px;
}

.note {
    margin: 0.75rem 0 0 0;
    color: #6c757d;
}

.account-section {
    margin-bottom: 2rem;
    padding: 1.25rem;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #fafbfc;
}

.account-title {
    font-weight: 600;
    font-size: 16px;
    color: #bf162f;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.radio-label {
    display: block;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 0.5rem;
    color: #495057;
}

.radio-group {
    display: flex;
    gap: 1.5rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 14px;
}

.radio-option input[type="radio"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
}

.radio-option span {
    user-select: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-col {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.deposit-amount-label {
    display: block;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 0.3rem;
    color: #495057;
}

.deposit-amount-row {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.deposit-amount-row .deposit-type-select {
    width: 56px;
    flex-shrink: 0;
    padding: 0.4rem 0.35rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    color: #374151;
}

.deposit-amount-row .deposit-type-select:focus {
    outline: none;
    border-color: #bf162f;
    box-shadow: 0 0 0 2px rgba(191, 22, 47, 0.2);
}

.deposit-amount-row > *:last-child {
    flex: 1;
    min-width: 0;
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
    
    .form-wrapper {
        padding: 1rem;
    }
    
    .radio-group {
        flex-direction: column;
        gap: 0.75rem;
    }
}
</style>
