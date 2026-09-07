<template>
    <div class="benefit-form-wrap max-h-screen">
        <!-- Step 1: Benefits Information PDF -->
        <div v-if="currentStep === 'pdf'" class="rounded-lg bg-white p-6 shadow-sm">
            <h5 class="mb-4 text-lg font-semibold text-[#bf162f]">Benefits Information</h5>

            <div class="mb-4">
                <iframe
                    :src="benefitPdfUrl"
                    class="h-[700px] w-full rounded-md border border-gray-200"
                    title="Benefits Information PDF"
                />
            </div>

            <p v-if="ackError" class="mb-2 text-sm text-red-600">{{ ackError }}</p>
            <div class="mb-4 space-y-3">
                <p class="flex items-center gap-2">
                    <input
                        id="benefitQualify"
                        type="checkbox"
                        :checked="benefitAckChoice === 'qualify'"
                        class="h-4 w-4 rounded border-gray-300 text-[#bf162f] focus:ring-[#bf162f]"
                        @change="setAckChoice('qualify')"
                    />
                    <label for="benefitQualify" class="text-sm text-gray-700">
                        I qualify for benefits and wish to proceed with the benefit enrollment form.
                    </label>
                </p>
                <p class="flex items-center gap-2">
                    <input
                        id="benefitDecline"
                        type="checkbox"
                        :checked="benefitAckChoice === 'decline'"
                        class="h-4 w-4 rounded border-gray-300 text-[#bf162f] focus:ring-[#bf162f]"
                        @change="setAckChoice('decline')"
                    />
                    <label for="benefitDecline" class="text-sm text-gray-700">
                        I decline benefits enrollment at this time.
                    </label>
                </p>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <button
                    v-if="!hideBackButton"
                    type="button"
                    class="btn btn-secondary"
                    @click="goToBackStep"
                >
                    Back
                </button>
                <div v-else></div>
                <Button
                    variant="primary"
                    :disabled="!benefitAckChoice || savingDecline"
                    :loading="savingDecline"
                    @click="confirmPdfAcknowledgment"
                >
                    {{ benefitAckChoice === 'decline' ? 'Submit Decline' : 'Continue' }}
                </Button>
            </div>
        </div>

        <!-- Step 2: Benefits Acknowledgement / Waive Form -->
        <div v-else-if="currentStep === 'waive'" class="rounded-lg bg-white p-6 shadow-sm">
            <h5 class="mb-4 text-lg font-semibold text-[#bf162f]">Benefits Acknowledgement</h5>

            <div class="waive-form-content mb-6 space-y-4 text-sm leading-relaxed text-gray-800">
                <p>
                    I acknowledge that I have been offered the option to enroll myself and eligible family members in the Company's
                    Group Health Plan, and that coverage is entirely voluntary. I also understand that my employer has offered me a
                    compliant health plan as defined by the Affordable Care Act (ACA). I am aware that in order to qualify for benefits, I
                    must meet the eligibility requirement of working an average of at least 30 hours per week.
                </p>
                <p>
                    I understand that benefit eligibility begins after 60 days of employment, and that should I meet the qualification
                    criteria, the Human Resources department will make three attempts to contact me via email, providing me with a
                    Benefits Election Form and information regarding the medical, dental, and vision plans that are offered. In this case,
                    I will be given 5 calendar days to select my benefits plan.
                </p>

                <div>
                    <p class="mb-2 font-medium">If I qualify and opt in for benefits, I understand that:</p>
                    <ul class="list-none space-y-1 pl-2">
                        <li>➢ The Company will only contribute to individual plans, and that the same healthcare plans are available for dependents, at a voluntary contribution.</li>
                        <li>➢ I am authorizing the Company to deduct the agreed-upon premium amount from my biweekly paychecks.</li>
                        <li>➢ Coverage will not be effective until I complete all necessary enrollment forms for my selected plans.</li>
                    </ul>
                </div>

                <div>
                    <p class="mb-2 font-medium">If I decline benefits coverage through the Parent Company, I understand that:</p>
                    <ul class="list-none space-y-1 pl-2">
                        <li>➢ I may not be offered another opportunity to participate until during the next open enrollment period.</li>
                        <li>➢ By electing not to enroll in this ACA compliant plan, I will not be eligible for a premium subsidy at either a state-based or federally-operated insurance exchange.</li>
                    </ul>
                </div>

                <div>
                    <p class="mb-2 font-medium">If I qualify for benefits and do not submit my Benefits Enrollment Form by the deadline, I understand that:</p>
                    <ul class="list-none space-y-1 pl-2">
                        <li>➢ The Company will accept my attestation below, as authorization to waive my participation in the Company's group health plan.</li>
                        <li>➢ This does not disqualify me from enrolling in benefits in the next open enrollment period, provided I continue to meet the previously outlined eligibility requirements.</li>
                    </ul>
                </div>

                <p>
                    By signing my name electronically on this form, I am agreeing that my electronic signature is the legal equivalent of my manual signature.
                </p>

                <p class="text-xs text-gray-600">
                    <strong>Please note,</strong> that if you have any questions about your coverage options, need more time to finalize your decision,
                    or have missed the deadline to submit your election form, you can reach out to the Human Resources department.
                    In order to request a deadline extension, please contact HR at least 24 hours before the deadline to allow our HR
                    manager enough time to process your request. Extension requests received after this period are not guaranteed
                    and may not be granted.
                </p>
            </div>

            <div class="mb-4 space-y-3">
                <p class="flex items-center gap-2">
                    <input
                        id="waiveBenefitQualify"
                        type="checkbox"
                        :checked="benefitAckChoice === 'qualify'"
                        class="h-4 w-4 rounded border-gray-300 text-[#bf162f] focus:ring-[#bf162f]"
                        @change="setAckChoice('qualify')"
                    />
                    <label for="waiveBenefitQualify" class="text-sm text-gray-700">
                        I qualify for benefits and wish to proceed with the benefit enrollment form.
                    </label>
                </p>
                <p class="flex items-center gap-2">
                    <input
                        id="waiveBenefitDecline"
                        type="checkbox"
                        :checked="benefitAckChoice === 'decline'"
                        class="h-4 w-4 rounded border-gray-300 text-[#bf162f] focus:ring-[#bf162f]"
                        @change="setAckChoice('decline')"
                    />
                    <label for="waiveBenefitDecline" class="text-sm text-gray-700">
                        I decline benefits enrollment at this time.
                    </label>
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Input
                    v-model="waiveForm.date"
                    label="Date"
                    type="text"
                    readonly
                    disabled
                />
                <Input
                    v-model="waiveForm.name"
                    label="Name"
                    type="text"
                    readonly
                    disabled
                />
            </div>
            <div class="mt-4">
                <Input
                    v-model="waiveForm.signature"
                    label="Signature"
                    type="text"
                    placeholder="Enter your signature"
                    required
                    :error="waiveErrors.signature"
                />
            </div>

            <p v-if="waiveErrors.general" class="mt-2 text-sm text-red-600">{{ waiveErrors.general }}</p>

            <div class="mt-6 flex items-center justify-between">
                <button type="button" class="btn btn-secondary" @click="currentStep = 'pdf'">Back</button>
                <Button
                    variant="primary"
                    :disabled="!benefitAckChoice || savingWaive"
                    :loading="savingWaive"
                    @click="confirmWaiveForm"
                >
                    {{ benefitAckChoice === 'decline' ? 'Submit Decline' : 'Continue to Enrollment Form' }}
                </Button>
            </div>
        </div>

        <!-- Step 3: Benefits Enrollment Form -->
        <form v-else @submit.prevent="submit" class="benefit-form-body">
            <h5 class="mb-4" style="color: #BF162F;">Benefits Enrollment</h5>

            <div class="table-responsive">
                <div class="w4_wrapper outer" style="height: auto; overflow-y: auto;">
                    <div class="inner">
                        <table class="benefit-table">
                            <tbody>
                                <tr>
                                    <td>
                                        <b>Papa John's<br>{{ benefitElection.date }} Benefit Election Form ({{ state }})</b>
                                    </td>
                                    <td class="header-note">Mandatory to complete at time of hire</td>
                                </tr>
                            </tbody>
                        </table>
                        <br />

                        <!-- A. General Employee Information -->
                        <table class="bordered">
                            <tbody>
                                <tr>
                                    <td colspan="8" class="section-title">A. General Employee Information</td>
                                </tr>
                                <tr>
                                    <td><Input v-model="form.last_name" label="Last Name" required/></td>
                                    <td><Input v-model="form.first_name" label="First Name" required/></td>
                                    <td><Input v-model="form.middle_initial" label="M.I." required/></td>
                                    <td><Input v-model="form.dob" label="Date of Birth" type="date" required /></td>
                                    <td>
                                        <Input v-model="form.ssn" label="Social Security Number" placeholder="Enter 9 digits (dashes added automatically)"
                                            :disabled="ssnDisable" @input="handleEmployeeSSNInput($event)" />
                                        <span v-if="errors.ssn" class="text-danger small d-block mt-1">{{ errors.ssn }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3"><Input v-model="form.address" label="Home Address" required/></td>
                                    <td><Input v-model="form.city" label="City" required/></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <Input v-model="form.state" label="State" class="flex-1" required/>
                                            <Input v-model="form.zip" label="Zip" class="flex-1" required/>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2"><Input v-model="form.email" label="Email" type="email" readonly /></td>
                                    <td>
                                        <b>Gender <span class="text-danger">*</span></b><br>
                                        <div class="flex gap-2 mt-3">
                                            <label><input v-model="form.gender" type="radio" value="Male" /> Male</label>
                                            <label><input v-model="form.gender" type="radio" value="Female" /> Female</label>
                                            <label><input v-model="form.gender" type="radio" value="Other" /> Other</label>
                                        </div>
                                        </td>
                                    <td><Input v-model="form.salary" label="Salary" type="number" :required="true" /></td>
                                    <td><Input v-model="form.hire_date" label="Hire Date" type="date" :required="true" /></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- B. Health Care -->
                        <table class="bordered">
                            <tbody>
                                <tr>
                                    <td colspan="5" class="section-title">B. Health Care Benefit Costs Per Bi-Weekly Pay</td>
                                </tr>
                                <tr>
                                    <td colspan="5"><b>1. Independence United healthcare:</b><br><span class="small">Select only one medical option. <span class="text-danger">*</span></span></td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th>Option #1 (PPO $40-$70/$500)</th>
                                    <th>Option #2 (PPO HSA $5000)</th>
                                    <th>Waive Medical</th>
                                    <th>Reason</th>
                                </tr>
                                <tr>
                                    <td><b>Employee Only</b></td>
                                    <td>{{ benefitElection.health_care_plan.op1.employee_only }} <input v-model="form.medical_plan" type="radio" value="option1_employee" /></td>
                                    <td>{{ benefitElection.health_care_plan.op2.employee_only }} <input v-model="form.medical_plan" type="radio" value="option2_employee" /></td>
                                    <td rowspan="4" style="text-align:center"><input v-model="form.medical_plan" type="radio" value="waive" /></td>
                                    <td rowspan="4">
                                        <textarea
                                            v-model="form.medical_waive_reason"
                                            class="form-control"
                                            placeholder="Required if waiving"
                                            style="width:100%"
                                            :disabled="form.medical_plan !== 'waive'"
                                            :class="{ 'field-error': errors.medical_waive_reason }"
                                        ></textarea>
                                        <span v-if="errors.medical_waive_reason" class="text-danger small">{{ errors.medical_waive_reason }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><b>Employee/Spouse</b></td>
                                    <td>{{ benefitElection.health_care_plan.op1.employee_spouse }} <input v-model="form.medical_plan" type="radio" value="option1_spouse" /></td>
                                    <td>{{ benefitElection.health_care_plan.op2.employee_spouse }} <input v-model="form.medical_plan" type="radio" value="option2_spouse" /></td>
                                </tr>
                                <tr>
                                    <td><b>Employee/Child</b></td>
                                    <td>{{ benefitElection.health_care_plan.op1.employee_child }} <input v-model="form.medical_plan" type="radio" value="option1_child" /></td>
                                    <td>{{ benefitElection.health_care_plan.op2.employee_child }} <input v-model="form.medical_plan" type="radio" value="option2_child" /></td>
                                </tr>
                                <tr>
                                    <td><b>Employee/Family</b></td>
                                    <td>{{ benefitElection.health_care_plan.op1.employee_family }} <input v-model="form.medical_plan" type="radio" value="option1_family" /></td>
                                    <td>{{ benefitElection.health_care_plan.op2.employee_family }} <input v-model="form.medical_plan" type="radio" value="option2_family" /></td>
                                </tr>
                                <tr v-if="errors.medical_plan">
                                    <td colspan="5" class="text-danger small py-1">{{ errors.medical_plan }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- 2. Dental -->
                        <table class="bordered">
                            <tbody>
                                <tr><td colspan="6"><b>2. Principal Dental</b> <span class="text-danger">*</span></td></tr>
                                <tr>
                                    <th></th>
                                    <th>Employee {{ benefitElection.dental_plan.employee }}</th>
                                    <th>Child {{ benefitElection.dental_plan.child }}</th>
                                    <th>Spouse {{ benefitElection.dental_plan.spouse }}</th>
                                    <th>Family {{ benefitElection.dental_plan.family }}</th>
                                    <th>Waive</th>
                                </tr>
                                <tr>
                                    <td><b>Dental Coverage</b></td>
                                    <td class="text-center"><input v-model="form.dental" type="radio" value="employee" /></td>
                                    <td class="text-center"><input v-model="form.dental" type="radio" value="child" /></td>
                                    <td class="text-center"><input v-model="form.dental" type="radio" value="spouse" /></td>
                                    <td class="text-center"><input v-model="form.dental" type="radio" value="family" /></td>
                                    <td class="text-center"><input v-model="form.dental" type="radio" value="waive" /></td>
                                </tr>
                                <tr v-if="errors.dental">
                                    <td colspan="6" class="text-danger small py-1">{{ errors.dental }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- 3. Vision -->
                        <table class="bordered">
                            <tbody>
                                <tr><td colspan="6"><b>3. Principal Vision</b> <span class="text-danger">*</span></td></tr>
                                <tr>
                                    <th></th>
                                    <th>Employee {{ benefitElection.vision_plan.employee }}</th>
                                    <th>Child {{ benefitElection.vision_plan.child }}</th>
                                    <th>Spouse {{ benefitElection.vision_plan.spouse }}</th>
                                    <th>Family {{ benefitElection.vision_plan.family }}</th>
                                    <th>Waive</th>
                                </tr>
                                <tr>
                                    <td><b>Vision Coverage</b></td>
                                    <td class="text-center"><input v-model="form.vision" type="radio" value="employee" /></td>
                                    <td class="text-center"><input v-model="form.vision" type="radio" value="child" /></td>
                                    <td class="text-center"><input v-model="form.vision" type="radio" value="spouse" /></td>
                                    <td class="text-center"><input v-model="form.vision" type="radio" value="family" /></td>
                                    <td class="text-center"><input v-model="form.vision" type="radio" value="waive" /></td>
                                </tr>
                                <tr v-if="errors.vision">
                                    <td colspan="6" class="text-danger small py-1">{{ errors.vision }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- C. Life/AD&D -->
                        <table class="bordered">
                            <tbody>
                                <tr><td colspan="6" class="section-title">C. Voluntary Life/AD&D</td></tr>
                                <tr>
                                    <td colspan="6">
                                        <b>Principal Voluntary Life/AD&D</b> (Please write the amount of coverage you are electing as well as the cost per pay).<br>
                                        <span class="small">Please note: Employee must elect coverage for dependents to enroll. Spouse amount cannot exceed employee amount.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <label><input v-model="form.life_option" type="radio" value="elect" /> Elect</label>
                                    </td>
                                    <td colspan="3" class="red">
                                        <label><input v-model="form.life_option" type="radio" value="waive" /> Waive</label>
                                    </td>
                                </tr>
                                <tr v-if="errors.life_option">
                                    <td colspan="6" class="text-danger small py-1">{{ errors.life_option }}</td>
                                </tr>
                                <tr>
                                    <td><Input v-model="form.emp_life_amount" label="Employee Amount $" type="number" readonly /></td>
                                    <td><Input v-model="form.spouse_life_amount" label="Spouse Amount $" type="number" readonly /></td>
                                    <td><Input v-model="form.child_life_amount" label="Child Amount $" type="number" readonly /></td>
                                    <td><Input v-model="form.emp_life_cost" label="Employee Cost $" type="number" step="0.01" readonly /></td>
                                    <td><Input v-model="form.spouse_life_cost" label="Spouse Cost $" type="number" step="0.01" readonly /></td>
                                    <td><Input v-model="form.child_life_cost" label="Child Cost $" type="number" step="0.01" readonly /></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- D. Pre-Tax / After-Tax -->
                        <table class="bordered">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="section-title">
                                        D. I wish to defer the amounts selected above in Section B (Medical/Dental/Vision) from my pay: (Choose One) <span class="text-danger">*</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="35%">
                                        <label><input v-model="form.tax_method" type="radio" value="pre_tax" /> Pre-Tax</label>
                                    </td>
                                    <td width="65%"><span class="small">By choosing pre-tax, taxable income is reduced and premiums are deducted before taxes are calculated.</span></td>
                                </tr>
                                <tr>
                                    <td width="35%">
                                        <label><input v-model="form.tax_method" type="radio" value="after_tax" /> After-Tax</label>
                                    </td>
                                    <td width="65%"><span class="small">By choosing after-tax, premiums are deducted from your pay after taxes are calculated.</span></td>
                                </tr>
                                <tr v-if="errors.tax_method">
                                    <td colspan="2" class="text-danger small py-1">{{ errors.tax_method }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <p class="my-3" style="text-align: justify">
                            <b>YES,</b> I hereby make the above benefit elections for Plan Year {{ benefitElection.plan_year }}. I authorize my employer to deduct from my pay the
                            amounts listed above to pay the premiums for myself and/or my dependents. I understand that coverage will not be effective until
                            I complete all necessary enrollment forms for my above selected plans. I understand I cannot change or revoke any pre-tax
                            election until the next open enrollment for the {{ benefitElection.next_plan_year }} plan year unless I have a qualifying change in family status such as:
                            marriage, divorce, death of spouse or child, birth or adoption of a child, termination or commencement of employment of a spouse,
                            change in my or my spouse's employment status from full-time to part-time or part-time to full-time, my spouse or I taking an
                            unpaid leave of absence, and such other events as a plan administrator determines will permit a change or a revocation of an
                            election. I understand that by participating in the Pre-Tax Plan, my Social Security benefits may be affected because the above
                            elections will be deducted before my salary is taxed. Prior to each plan year, I will be offered the opportunity to change my benefit
                            election for the following plan year.
                        </p>

                        <table class="benefit-table">
                            <tbody>
                                <tr>
                                    <td colspan="7"><Input v-model="form.employee_sign" label="Signature" placeholder="Enter Signature" required :error="errors.employee_sign" /></td>
                                    <td style="text-align: center;"><label>{{ form.employee_date || dateToday }}<br>Date</label></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- E. Dependents -->
                        <table class="bordered">
                            <tbody>
                                <tr><td colspan="7" class="section-title">E. Dependents Information</td></tr>
                                <tr>
                                    <th>Last</th>
                                    <th>First</th>
                                    <th>Gender</th>
                                    <th>DOB</th>
                                    <th>SSN</th>
                                    <th>Relation</th>
                                    <th>Coverage</th>
                                </tr>
                                <tr v-for="(dep, i) in form.dependents" :key="i">
                                    <td><Input v-model="dep.last_name" /></td>
                                    <td><Input v-model="dep.first_name" /></td>
                                    <td>
                                        <label><input v-model="dep.gender" type="radio" value="Male" /> M</label>
                                        <label class="ml-2"><input v-model="dep.gender" type="radio" value="Female" /> F</label>
                                    </td>
                                    <td><Input v-model="dep.dob" type="date" /></td>
                                    <td><Input v-model="dep.ssn" placeholder="XXX-XX-XXXX"  @input="handleSSNInput($event,i)"/></td>
                                    <td><Input v-model="dep.relationship" /></td>
                                    <td>
                                        <label><input v-model="dep.medical" type="checkbox" :true-value="1" :false-value="0" /> Medical</label><br>
                                        <label><input v-model="dep.dental" type="checkbox" :true-value="1" :false-value="0" /> Dental</label><br>
                                        <label><input v-model="dep.vision" type="checkbox" :true-value="1" :false-value="0" /> Vision</label>
                                    </td>
                                </tr>
                                <tr v-if="errors.dependents_required">
                                    <td colspan="7" class="text-danger small py-1">{{ errors.dependents_required }}</td>
                                </tr>
                                <tr v-if="errors.dependents_ssn">
                                    <td colspan="7" class="text-danger small py-1">{{ errors.dependents_ssn }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="step-actions flex_between mt-4">
                <button type="button" class="btn btn-secondary" @click="currentStep = 'waive'" v-if="!hideBackButton">Back</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : (saveButton ? 'Save' : 'Save & Next' )}}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import { isSSNValid, formatSSN, formatSSNAsYouType } from '@/utils/ssn'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import benefitElectionForm from '../docs.json'

const SUPPORTED_BENEFIT_STATES = ['PA', 'DE', 'NJ', 'MD', 'VA']
const STATE_NAME_TO_CODE = {
    PENNSYLVANIA: 'PA',
    DELAWARE: 'DE',
    'NEW JERSEY': 'NJ',
    MARYLAND: 'MD',
    VIRGINIA: 'VA',
}

const ssnDisable = ref(false)
const props = defineProps({
    onboardingId: { type: String, default: '' },
    goToNextStep: { type: Function },
    goToBackStep: { type: Function },
    hideBackButton: { type: Boolean, default: false },
    saveButton: { type: Boolean, default: false },
    employeeId: { type: String, default: '' },
})
const state = ref('')
const currentStep = ref('pdf')
const benefitAckChoice = ref('')
const ackError = ref('')
const savingDecline = ref(false)
const savingWaive = ref(false)
const profile = ref({})

const waiveForm = reactive({
    name: '',
    signature: '',
    date: '',
})
const waiveErrors = reactive({
    signature: '',
    general: '',
})

const emit = defineEmits(['save'])
const saving = ref(false)
const errors = reactive({})

const employeeFullName = computed(() => {
    const parts = [form.first_name, form.middle_initial, form.last_name].filter(Boolean)
    return parts.join(' ').trim()
})

function todayFormatted() {
    const d = new Date()
    return `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}-${d.getFullYear()}`
}

function setAckChoice(choice) {
    benefitAckChoice.value = benefitAckChoice.value === choice ? '' : choice
    ackError.value = ''
    waiveErrors.general = ''
    waiveErrors.signature = ''
}

async function confirmPdfAcknowledgment() {
    if (!benefitAckChoice.value) {
        ackError.value = 'Please select Qualify or Decline before continuing.'
        return
    }

    if (benefitAckChoice.value === 'qualify') {
        ackError.value = ''
        waiveForm.name = profile.value.pos_name
        waiveForm.date = todayFormatted()
        currentStep.value = 'waive'
        return
    }

    savingDecline.value = true
    ackError.value = ''
    try {
        await axios.post('/onboarding-process/save-benefits-decline', {
            ...(props.onboardingId ? { onboardingId: props.onboardingId } : {}),
            ...(props.employeeId ? { employeeId: props.employeeId } : {}),
        }, {
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' },
            withCredentials: true,
        })
        if (props.saveButton) {
            emit('save')
        } else {
            props.goToNextStep()
        }
    } catch (err) {
        ackError.value = err.response?.data?.message || 'Failed to save benefits decline.'
    } finally {
        savingDecline.value = false
    }
}

function validateWaiveForm() {
    waiveErrors.signature = ''
    waiveErrors.general = ''
    if (!benefitAckChoice.value) {
        waiveErrors.general = 'Please select Qualify or Decline before continuing.'
        return false
    }
    if (!(waiveForm.signature || '').trim()) {
        waiveErrors.signature = 'Signature is required.'
        return false
    }
    return true
}

async function confirmWaiveForm() {
    if (!validateWaiveForm()) return

    savingWaive.value = true
    waiveErrors.general = ''
    try {
        if (benefitAckChoice.value === 'decline') {
            await axios.post('/onboarding-process/save-benefits-decline', {
                ...(props.onboardingId ? { onboardingId: props.onboardingId } : {}),
                ...(props.employeeId ? { employeeId: props.employeeId } : {}),
                waive_ack_name: waiveForm.name,
                waive_ack_signature: waiveForm.signature.trim(),
                waive_ack_date: new Date().toISOString().slice(0, 10),
            }, {
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' },
                withCredentials: true,
            })
            if (props.saveButton) {
                emit('save')
            } else {
                props.goToNextStep()
            }
            return
        }

        await axios.post('/onboarding-process/save-benefits-waive-ack', {
            ...(props.onboardingId ? { onboardingId: props.onboardingId } : {}),
            ...(props.employeeId ? { employeeId: props.employeeId } : {}),
            waive_ack_name: waiveForm.name,
            waive_ack_signature: waiveForm.signature.trim(),
            waive_ack_date: new Date().toISOString().slice(0, 10),
        }, {
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' },
            withCredentials: true,
        })
        currentStep.value = 'enrollment'
    } catch (err) {
        waiveErrors.general = err.response?.data?.message || 'Failed to save benefits acknowledgement.'
    } finally {
        savingWaive.value = false
    }
}

function resolveInitialStep(saved) {
    if (saved.benefit_acknowledgment === 'decline') {
        return 'done'
    }
    if (hasSavedEnrollmentData(saved)) {
        return 'enrollment'
    }
    if (saved.waive_ack_signature) {
        return 'enrollment'
    }
    if (saved.benefit_acknowledgment === 'qualify') {
        return 'waive'
    }
    return 'pdf'
}

function resolveStateCode(raw) {
    const normalized = (raw || '').trim().toUpperCase()
    if (SUPPORTED_BENEFIT_STATES.includes(normalized)) return normalized
    if (STATE_NAME_TO_CODE[normalized]) return STATE_NAME_TO_CODE[normalized]
    if (normalized.includes('MD')) return 'MD'
    return 'PA'
}

const benefitPdfUrl = computed(() => `/docs/${resolveStateCode(state.value)}-Benefit.pdf`)

function hasSavedEnrollmentData(saved) {
    if (!saved || typeof saved !== 'object') return false
    return Boolean(
        saved.medical_plan ||
        saved.dental ||
        saved.vision ||
        saved.life_option ||
        saved.employee_sign
    )
}

const dateToday = computed(() => todayFormatted())

function resolveBenefitRate(value) {
    if (!value || typeof value !== 'object') return value
    const normalizedState = (state.value || '').trim().toUpperCase()
    if (normalizedState && value[normalizedState]) return value[normalizedState]
    return value.PA ?? value.VA ?? Object.values(value)[0]
}

function resolveBenefitPlan(plan) {
    if (!plan) return plan
    return Object.fromEntries(
        Object.entries(plan).map(([key, value]) => [key, resolveBenefitRate(value)])
    )
}

const benefitElection = computed(() => {
    const active = benefitElectionForm.benefit_election_form.find((item) => item.active)
    if (!active) return null

    return {
        ...active,
        health_care_plan: {
            op1: resolveBenefitPlan(active.health_care_plan.op1),
            op2: resolveBenefitPlan(active.health_care_plan.op2),
        },
        dental_plan: resolveBenefitPlan(active.dental_plan),
        vision_plan: resolveBenefitPlan(active.vision_plan),
    }
})

const defaultDependent = () => ({
    last_name: '',
    first_name: '',
    gender: '',
    dob: '',
    ssn: '',
    relationship: '',
    medical: 0,
    dental: 0,
    vision: 0,
})

const form = reactive({
    last_name: '',
    first_name: '',
    middle_initial: '',
    dob: '',
    ssn: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    email: '',
    gender: '',
    salary: '',
    hire_date: '',
    medical_plan: '',
    medical_waive_reason: '',
    dental: '',
    vision: '',
    life_option: '',
    emp_life_amount: '',
    spouse_life_amount: '',
    child_life_amount: '',
    emp_life_cost: '',
    spouse_life_cost: '',
    child_life_cost: '',
    tax_method: '',
    employee_sign: '',
    employee_date: '',
    dependents: [defaultDependent(), defaultDependent(), defaultDependent(), defaultDependent()],
})

const handleEmployeeSSNInput = (event) => {
    const raw = event.target.value
    const formatted = formatSSNAsYouType(raw)
    if (formatted !== raw) {
        form.ssn = formatted
    }
    if (form.ssn && !isSSNValid(form.ssn)) {
        errors.ssn = 'Please enter a valid 9-digit SSN.'
    } else {
        errors.ssn = null
    }
}

const handleSSNInput = (event, index) => {
    const raw = event.target.value
    const formatted = formatSSNAsYouType(raw)
    if (formatted !== raw) {
        form.dependents[index].ssn = formatted
    }
}
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

async function loadData() {
    if (!props.onboardingId && !props.employeeId) return
    try {
        const { data } = await axios.get('/onboarding-process/benefit-data', {
            params: {...(props.onboardingId ? { onboardingId: props.onboardingId } : {}), ...(props.employeeId ? { employeeId: props.employeeId } : {}) },
            headers: { 'X-CSRF-TOKEN': getCsrfToken() },
            withCredentials: true,
        })

        const saved = data.form || {}

        if (props.employeeId && saved.benefit_submitted_at) {
            emit('save')
            return
        }

        const p = data.profile || {}
        profile.value = p
        form.last_name = p.last_name ?? form.last_name
        form.first_name = p.first_name ?? form.first_name
        form.middle_initial = p.middle_initial ?? form.middle_initial
        form.dob = p.dob ?? form.dob
        form.ssn = p.social_security_number ? formatSSNAsYouType(p.social_security_number) : form.ssn
        form.address = p.address ?? form.address
        form.city = p.city ?? form.city
        form.state = p.state ?? form.state
        form.zip = p.zip ?? form.zip
        form.email = p.email ?? form.email

        ssnDisable.value = !!form.ssn
        state.value = data.state ?? ''

        waiveForm.name = saved.waive_ack_name || p.pos_name
        waiveForm.date = saved.waive_ack_date
            ? formatDisplayDate(saved.waive_ack_date)
            : todayFormatted()
        waiveForm.signature = saved.waive_ack_signature || ''

        if (saved.benefit_acknowledgment === 'qualify') {
            benefitAckChoice.value = 'qualify'
        } else if (saved.benefit_acknowledgment === 'decline') {
            benefitAckChoice.value = 'decline'
        }

        // const initialStep = resolveInitialStep(saved)
        // if (initialStep === 'done') {
        //     if (props.employeeId) {
        //         emit('save')
        //     } else if (props.goToNextStep) {
        //         props.goToNextStep()
        //     }
        //     return
        // }
        currentStep.value = 'pdf'

        Object.keys(saved).forEach((key) => {
            if (key === 'dependents' && Array.isArray(saved.dependents)) {
                saved.dependents.forEach((dep, i) => {
                    if (form.dependents[i]) Object.assign(form.dependents[i], dep)
                })
            } else if (key !== 'dependents' && form.hasOwnProperty(key)) {
                form[key] = saved[key]
            }
        })
        if (!form.employee_date) form.employee_date = new Date().toISOString().slice(0, 10)
    } catch (_) {
        form.employee_date = new Date().toISOString().slice(0, 10)
        waiveForm.date = todayFormatted()
        waiveForm.name = p.pos_name
    }
}

function formatDisplayDate(value) {
    if (!value) return todayFormatted()
    const d = new Date(value.includes('T') ? value : `${value}T00:00:00`)
    if (Number.isNaN(d.getTime())) return todayFormatted()
    return `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}-${d.getFullYear()}`
}

function validate() {
    const e = {}
    if (!form.medical_plan) e.medical_plan = 'Required'
    if (!form.dental) e.dental = 'Required'
    if (!form.vision) e.vision = 'Required'
    if (!form.life_option) e.life_option = 'Required'
    if (!form.tax_method || !['pre_tax', 'after_tax'].includes(form.tax_method)) {
        e.tax_method = 'Please select Pre-Tax or After-Tax'
    }
    if (!form.employee_sign) e.employee_sign = 'Required'
    if (form.medical_plan === 'waive' && !(form.medical_waive_reason || '').trim()) e.medical_waive_reason = 'Required if waiving'
    if (form.ssn && !isSSNValid(form.ssn)) e.ssn = 'Please enter a valid 9-digit SSN in your profile (e.g. XXX-XX-XXXX).'
    const hasInvalidDependent = form.dependents?.some((dep) => {
        const hasAnyValue = Boolean(
            (dep.last_name || '').trim() ||
            (dep.first_name || '').trim() ||
            dep.gender ||
            dep.dob ||
            (dep.ssn || '').trim() ||
            (dep.relationship || '').trim() ||
            dep.medical ||
            dep.dental ||
            dep.vision
        )
        if (!hasAnyValue) return false
        const hasCoverage = Boolean(dep.medical || dep.dental || dep.vision)
        return !(
            (dep.last_name || '').trim() &&
            (dep.first_name || '').trim() &&
            dep.gender &&
            dep.dob &&
            (dep.ssn || '').trim() &&
            (dep.relationship || '').trim() &&
            hasCoverage
        )
    })
    if (hasInvalidDependent) {
        e.dependents_required = 'If a dependent is selected, Last, First, Gender, DOB, SSN, Relation, and at least one Coverage are required.'
    }
    const invalidDep = form.dependents?.findIndex((dep) => dep.ssn && !isSSNValid(dep.ssn))
    if (invalidDep !== -1) e.dependents_ssn = 'Each dependent SSN must be a valid 9-digit SSN (e.g. XXX-XX-XXXX).'
    return e
}

const validatedKeys = ['medical_plan', 'dental', 'vision', 'life_option', 'tax_method', 'employee_sign', 'medical_waive_reason', 'ssn', 'dependents_ssn', 'dependents_required']
async function submit() {
    validatedKeys.forEach((k) => { errors[k] = null })
    const e = validate()
    Object.assign(errors, e)
    if (Object.keys(e).length > 0) {
        return
    }
    saving.value = true
    try {
        const dependentsToSend = form.dependents.map((dep) => ({
            ...dep,
            ssn: dep.ssn && isSSNValid(dep.ssn) ? formatSSN(dep.ssn) : (dep.ssn || ''),
        }))
        const payload = {
            ...(props.onboardingId ? { onboardingId: props.onboardingId } : {}),
            ...(props.employeeId ? { employeeId: props.employeeId } : {}),
            ...form,
            ssn: form.ssn && isSSNValid(form.ssn) ? formatSSN(form.ssn) : (form.ssn || ''),
            dependents: dependentsToSend,
        }
        await axios.post('/onboarding-process/save-benefits-enroll', payload, {
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' },
            withCredentials: true,
        })
        if(props.saveButton) {
            emit('save')
        } else {
            props.goToNextStep()
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Failed to save benefits enrollment.'
        alert(msg)
    } finally {
        saving.value = false
    }
}

watch(() => form.medical_plan, (plan) => {
    if (plan !== 'waive') {
        form.medical_waive_reason = ''
    }
})

onMounted(() => {
    loadData()
})
</script>

<style scoped>
.benefit-form-wrap {
    padding: 0.5rem 0;
}
.benefit-table,
.bordered {
    width: 100%;
    border-collapse: collapse;
}
.section-title {
    background: #f1f1f1;
    font-weight: bold;
    padding: 6px;
}
.bordered td,
.bordered th {
    border: 1px solid #000;
    padding: 6px;
    vertical-align: top;
}
.header-note {
    color: red;
    font-weight: bold;
    text-align: right;
}
.small {
    font-size: 12px;
}
.red {
    color: red;
    font-weight: bold;
}
.flex_between {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.text-danger {
    color: #dc2626;
}
.field-error {
    border-color: #dc2626 !important;
}
.error-msg {
    display: block;
    margin-top: 4px;
}
.waive-form-content ul li {
    margin-bottom: 0.25rem;
}
</style>
