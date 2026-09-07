<template>
    <div class="onboarding-page">
        <div v-if="codeSuccess && hasOnboardingData" class="onboarding-layout">
            <div class="sidebar-col">
                <Sidebar :current-process-id="processId" :show-form-m-w507="showFormMW507" :skip-i9-w4="skipI9W4"
                    @step-click="goToStep" />
            </div>
            <div class="content-col">
                <div class="form-content">
                    <Step1Welcome v-if="processId === 1" :applicant-first-name="onboardingData.employee?.pos_name"
                        :company-name="onboardingData.company?.name" :go-to-next-step="() => goToStep(2)"
                        :onboarding-id="onboardingId" />
                    <Step2EmployeeHandbook v-else-if="processId === 2" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(3)" :go-to-back-step="() => goToStep(1)"
                        :company-code="onboardingData.company?.store_number" :user-email="onboardingData.user_email"
                        :handbook="onboardingData.handbook || {}" />
                    <Step3CreateProfile v-else-if="processId === 3" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(skipI9W4 ? 6 : 4)" :go-to-back-step="() => goToStep(2)" />
                    <div v-else-if="i9w4Completed && !loading">
                        <div v-if="i9w4Completed && !loading"
                            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-green-800 dark:text-green-300">
                                        I-9 & W-4 Form Submitted Successfully
                                    </h3>
                                    <p class="mt-1 text-sm text-green-700 dark:text-green-400">
                                        Your I-9 and W-4 forms have been submitted{{
                                            i9w4Date ? " on " + formatDate(i9w4Date) : ""
                                        }}. Please check your email and start the onboarding process
                                        with WorkBright.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <Step4I9Form v-else-if="processId === 4" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(5)" :go-to-back-step="() => goToStep(3)" />
                    <Step5W4Form v-else-if="processId === 5" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(6)" :go-to-back-step="() => goToStep(4)" />
                    <StepBenefit v-else-if="processId === 6 || processId === 'Benefit'" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(7)" :go-to-back-step="() => goToStep(skipI9W4 ? 3 : 5)" :employee-details="onboardingData.employee" />
                    <Step7DirectDeposit v-else-if="processId === 7" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(8)" :go-to-back-step="() => goToStep(6)" />
                    <Step8EmergencyContact v-else-if="processId === 8" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(9)" :go-to-back-step="() => goToStep(7)" />
                    <Step9FinalSubmission v-else-if="processId === 9" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(10)" :go-to-back-step="() => goToStep(8)" />
                    <Step10DigitalSignature v-else-if="processId === 10" :onboarding-id="onboardingId"
                        :go-to-next-step="() => goToStep(11)" :go-to-back-step="() => goToStep(9)" />
                    <Step11ThankYou v-else-if="processId === 11" :onboarding-id="onboardingId" />
                    <div v-else class="step-placeholder">
                        <p>Step {{ processId }} — Content coming soon.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="onboardingDataError"
            class="mx-auto max-w-3xl rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ onboardingDataError }}
        </div>

        <Modal v-model="showCodeModal" title="Your Onboarding Code" :show-close="false" :close-on-backdrop="false"
            size="md" show-footer :show-cancel="false" :show-confirm="false">
            <form @submit.prevent="submitCode" class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                    <p class="mb-3">
                        <strong>Disclaimer &amp; Consent</strong>
                    </p>
                    <p class="mb-3">
                        By proceeding, I confirm that I have read and agree to the
                        <a href="https://www.quickob.com/privacy-policy" target="_blank" rel="noopener noreferrer"
                            class="text-primary-600 hover:underline">Privacy Policy</a>
                        and
                        <a href="https://www.quickob.com/terms-condition" target="_blank" rel="noopener noreferrer"
                            class="text-primary-600 hover:underline">Terms &amp; Conditions</a>. I understand that
                        continuing this process constitutes my consent
                        to use this portal for employment-related onboarding.
                    </p>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input v-model="agreedToPolicy" type="checkbox" class="rounded border-gray-300" />
                        <span>I agree to the Privacy Policy and Terms &amp; Conditions</span>
                    </label>
                </div>
                <div>
                    <Input v-model="onboardingCode" label="Onboarding Code" type="text"
                        placeholder="Enter onboarding code" required :error="codeError" />
                </div>
            </form>
            <template #footer>
                <Button variant="primary" :loading="submitting" :disabled="!agreedToPolicy" @click="submitCode">
                    Submit
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import Modal from "../../components/common/Modal.vue";
import Button from "../../components/ui/button.vue";
import axios from "axios";
import Input from "@/components/ui/input.vue";
import Sidebar from "./sidebar.vue";
import Step1Welcome from "./steps/Step1Welcome.vue";
import Step2EmployeeHandbook from "./steps/Step2EmployeeHandbook.vue";
import Step3CreateProfile from "./steps/Step3CreateProfile.vue";
import Step4I9Form from "./steps/Step4I9Form.vue";
import Step5W4Form from "./steps/Step5W4Form.vue";
import StepBenefit from "./steps/StepBenefit.vue";
import Step7DirectDeposit from "./steps/Step7DirectDeposit.vue";
import Step8EmergencyContact from "./steps/Step8EmergencyContact.vue";
import Step9FinalSubmission from "./steps/Step9FinalSubmission.vue";
import Step10DigitalSignature from "./steps/Step10DigitalSignature.vue";
import Step11ThankYou from "./steps/Step11ThankYou.vue";
import { formatDate } from "@/utils/date";

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name) || "";
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
}

const onboardingId = ref("");
const onboardingCode = ref("");
const codeError = ref("");
const submitting = ref(false);
const agreedToPolicy = ref(false);
const codeSuccess = ref(false);
const i9w4Completed = ref(false);
const i9w4Date = ref("");
const showCodeModal = ref(false);
const onboardingDataError = ref("");

// Process id from Blade (decrypted from ?processId=) or URL; used for sidebar active state
const processId = ref(1);
const showFormMW507 = ref(false);
const skipI9W4 = ref(false);

// Onboarding data for welcome step (applicant_first_name, company_name)
const onboardingData = ref({
    applicant_first_name: "",
    company_name: "",
    employee: {},
});
const hasOnboardingData = computed(() => {
    return !!(
        onboardingData.value?.employee?.id ||
        onboardingData.value?.company?.id ||
        onboardingData.value?.applicant_first_name
    );
});

function goToStep(step) {
    if (!hasOnboardingData.value) {
        onboardingDataError.value =
            "Onboarding data is not available. Please try again later or contact support.";
        return;
    }
    if (skipI9W4.value && (step === 4 || step === 5)) {
        processId.value = 6;
        return;
    }
    processId.value = step;
}

function getInitialStepFromProcessId(rawProcessId) {
    const mapped = rawProcessId ? rawProcessId + (rawProcessId == 1 ? 0 : 1) : 1;
    if (skipI9W4.value && (mapped === 4 || mapped === 5)) {
        return 6;
    }
    return mapped;
}

onMounted(async () => {
    // const el = document.getElementById('app')
    // const data = el?.getAttribute('data-process-id')
    // if (data !== null && data !== '') {
    //     const num = Number(data)
    //     processId.value = Number.isNaN(num) ? data  : num
    // } else {
    //     const q = getQueryParam('processId')
    //     processId.value = q ? (Number(q) || q)  : 1
    // }
    onboardingId.value = getQueryParam("onboardingId");
    const onboardingIdData = JSON.parse(localStorage.getItem("onboardingIdData"));
    let localOnboardingId = null;
    if (
        onboardingIdData &&
        onboardingIdData.timestamp &&
        Date.now() - onboardingIdData.timestamp < 1000 * 60 * 60 * 24
    ) {
        localOnboardingId = onboardingIdData.id;
    }
    showCodeModal.value = localOnboardingId != onboardingId.value;
    if (
        onboardingId.value &&
        !showCodeModal.value &&
        localOnboardingId == onboardingId.value
    ) {
        codeSuccess.value = true;
        await fetchOnboardingData();
    }
});

async function fetchOnboardingData() {
    if (!onboardingId.value) return;
    onboardingDataError.value = "";
    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        const { data: res } = await axios.get("/onboarding-process/data", {
            params: { onboardingId: onboardingId.value },
            headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {},
            withCredentials: true,
        });
        skipI9W4.value = !!res.i9_with_work_bright;
        processId.value = getInitialStepFromProcessId(res.process_id);
        i9w4Completed.value = res.i9_w4_completed ?? false;
        i9w4Date.value = res.i9_w4_date ?? "";
        onboardingData.value = {
            applicant_first_name: res.applicant_first_name ?? "",
            company: res.company,
            user_email: res.user_email ?? "",
            employee_handbook_agreed: res.employee_handbook_agreed ?? false,
            handbook: res.handbook ?? null,
            employee: res.employee ?? {},
        };
        if (!hasOnboardingData.value) {
            onboardingDataError.value =
                "Unable to get onboarding data. You cannot continue at this time.";
        }
    } catch (err) {
        console.log(err);
        onboardingDataError.value =
            "Unable to get onboarding data. You cannot continue at this time.";
    }
}

async function submitCode() {
    codeError.value = "";
    if (!onboardingCode.value.trim()) {
        codeError.value = "Onboarding code is required";
        return;
    }
    submitting.value = true;
    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        await axios.post(
            "/onboarding-process/verify-code",
            {
                onboardingId: onboardingId.value,
                onboardingCode: onboardingCode.value.trim(),
            },
            {
                headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {},
                withCredentials: true,
            }
        );
        codeSuccess.value = true;
        showCodeModal.value = false;
        localStorage.setItem(
            "onboardingIdData",
            JSON.stringify({ id: onboardingId.value, timestamp: Date.now() })
        );

        await fetchOnboardingData();
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            err.response?.data?.error ||
            "Verification failed. Please try again.";
        codeError.value = msg;
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.onboarding-layout {
    display: flex;
    gap: 1.5rem;
    max-width: 1500px;
    margin: 0 auto;
    padding: 1rem;
}

.sidebar-col {
    flex: 0 0 320px;
}

.content-col {
    flex: 1;
    min-width: 0;
}

.form-content {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.step-placeholder {
    color: #6c757d;
    padding: 1rem 0;
}
</style>