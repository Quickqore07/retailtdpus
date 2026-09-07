<template>
  <div class="onboarding-page">
    <div v-if="loading && !showCodeModal" class="loading-container">
      <Spinner size="lg" text="Loading..." centered />
    </div>

    <div
      v-else-if="employeeId && !submitted && !showCodeModal"
      class="onboarding-layout"
    >
      <div class="content-col">
        <div class="form-content">
          <StepBenefit
            :onboarding-id="onboardingId"
            :employee-id="employeeId"
            :hide-back-button="true"
            :save-button="true"
            @save="submitBenefit"
          />
        </div>
      </div>
    </div>
    <div v-else-if="submitted && !showCodeModal" class="success-container">
      <div class="success-card">
        <div class="success-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h1 class="success-title">Benefits Enrollment Complete!</h1>
        <p class="success-message">
          Your benefits enrollment has been submitted successfully. Our HR team
          will review your selections and contact you if any additional
          information is needed.
        </p>
        <!-- <div class="success-footer">
                    <p class="success-note">
                        You will receive a confirmation email shortly with the details of your enrollment.
                    </p>
                </div> -->
      </div>
    </div>
    <Modal
      v-model="showCodeModal"
      title="Your Benefit Code"
      :show-close="false"
      :close-on-backdrop="false"
      size="md"
      show-footer
      :show-cancel="false"
      :show-confirm="false"
    >
      <form @submit.prevent="submitCode" class="space-y-4">
        <div
          class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
        >
          <p class="mb-3">
            <strong>Disclaimer &amp; Consent</strong>
          </p>
          <p class="mb-3">
            By proceeding, I confirm that I have read and agree to the
            <a
              href="https://www.quickob.com/privacy-policy"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary-600 hover:underline"
              >Privacy Policy</a
            >
            and
            <a
              href="https://www.quickob.com/terms-condition"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary-600 hover:underline"
              >Terms &amp; Conditions</a
            >. I understand that continuing this process constitutes my consent
            to use this portal for employment-related onboarding.
          </p>
          <label class="flex cursor-pointer items-center gap-2">
            <input
              v-model="agreedToPolicy"
              type="checkbox"
              class="rounded border-gray-300"
            />
            <span
              >I agree to the Privacy Policy and Terms &amp; Conditions</span
            >
          </label>
        </div>
        <div>
          <Input
            v-model="benefitCode"
            label="Benefit Code"
            type="text"
            placeholder="Enter benefit code"
            required
            :error="codeError"
          />
        </div>
      </form>
      <template #footer>
        <Button
          variant="primary"
          :loading="submitting"
          :disabled="!agreedToPolicy"
          @click="submitCode"
        >
          Submit
        </Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Modal from "../../components/common/Modal.vue";
import Button from "../../components/ui/button.vue";
import Input from "@/components/ui/input.vue";
import Spinner from "@/components/ui/spinner.vue";
import StepBenefit from "./steps/StepBenefit.vue";
import axios from "axios";

function getQueryParam(name) {
  const params = new URLSearchParams(window.location.search);
  return params.get(name) || "";
}

const onboardingId = ref("");
const benefitCode = ref("");
const codeError = ref("");
const submitting = ref(false);
const agreedToPolicy = ref(false);
const showCodeModal = ref(false);
const employeeId = ref("");
const submitted = ref(true);
const loading = ref(true);

onMounted(async () => {
  employeeId.value = getQueryParam("employee_id");

  const benefitCodeData = JSON.parse(localStorage.getItem("benefitCodeData"));
  let localEmployeeId = null;
  if (
    benefitCodeData &&
    benefitCodeData.timestamp &&
    Date.now() - benefitCodeData.timestamp < 1000 * 60 * 60 * 24
  ) {
    localEmployeeId = benefitCodeData.employeeId;
  }
  showCodeModal.value = localEmployeeId != employeeId.value;
  if (
    employeeId.value &&
    !showCodeModal.value &&
    localEmployeeId == employeeId.value
  ) {
    await checkBenefitSubmitted();
  }
});

async function checkBenefitSubmitted() {
  try {
    const { data } = await axios.get(
      "/benefit-enrollment/check-benefit-submitted",
      {
        params: { employee_id: employeeId.value },
        headers: {
          "X-CSRF-TOKEN":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") || "",
        },
        withCredentials: true,
      }
    );
    submitted.value = data.submitted;
  } catch (error) {
    console.error("Error checking benefit submission:", error);
  } finally {
    loading.value = false;
  }
}

async function submitCode() {
  codeError.value = "";
  if (!benefitCode.value.trim()) {
    codeError.value = "Benefit code is required";
    return;
  }
  submitting.value = true;
  try {
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content");
    const response = await axios.post(
      "/benefit-enrollment/verify-benefit-code",
      {
        benefit_code: benefitCode.value.trim(),
        employee_id: employeeId.value,
      },
      {
        headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {},
        withCredentials: true,
      }
    );
    if (response.data.valid) {
      showCodeModal.value = false;
      localStorage.setItem(
        "benefitCodeData",
        JSON.stringify({ employeeId: employeeId.value, timestamp: Date.now() })
      );
      await checkBenefitSubmitted();
    } else {
      codeError.value = "Invalid benefit code";
    }
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

function submitBenefit() {
  submitted.value = true;
}
</script>

<style scoped>
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  padding: 2rem;
}

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

.success-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  padding: 2rem;
}

.success-card {
  background: #fff;
  border-radius: 12px;
  padding: 3rem 2.5rem;
  max-width: 600px;
  width: 100%;
  text-align: center;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.success-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: scaleIn 0.5s ease-out;
}

.success-icon svg {
  width: 48px;
  height: 48px;
  color: #fff;
  stroke-width: 3;
}

.success-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 1rem 0;
  animation: fadeInUp 0.6s ease-out 0.2s both;
}

.success-message {
  font-size: 1.125rem;
  color: #6b7280;
  line-height: 1.7;
  margin: 0 0 2rem 0;
  animation: fadeInUp 0.6s ease-out 0.3s both;
}

.success-footer {
  border-top: 1px solid #e5e7eb;
  padding-top: 1.5rem;
  animation: fadeInUp 0.6s ease-out 0.4s both;
}

.success-note {
  font-size: 0.95rem;
  color: #9ca3af;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.success-note::before {
  font-size: 1.1rem;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes fadeInUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@media (max-width: 640px) {
  .success-card {
    padding: 2rem 1.5rem;
  }

  .success-icon {
    width: 64px;
    height: 64px;
  }

  .success-icon svg {
    width: 38px;
    height: 38px;
  }

  .success-title {
    font-size: 1.5rem;
  }

  .success-message {
    font-size: 1rem;
  }
}
</style>