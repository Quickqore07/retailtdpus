<template>
    <div class="lft_part">
        <div class="sidebar">
            <ul>
                <template v-for="step in steps" :key="step.tabId || step.id">
                    <li
                        v-if="step.tabId !== 'form-MW507-tab' && !isStepHidden(step)"
                        :id="step.tabId"
                        :class="{ active: isStepActive(step), done: isStepDone(step) }"
                        :style="step.hidden ? { display: 'none' } : undefined"
                        role="button"
                        >
                        <!-- @click="goToStep(step)" -->
                        <span class="status-icon" :aria-label="isStepDone(step) ? 'Completed' : ''">
                            <span v-if="isStepDone(step)" class="check">✓</span>
                        </span>
                        <span class="sidebar-menu">{{ step.label }}</span>
                    </li>
                    <li
                        v-else
                        id="form-MW507-tab"
                        :class="{ active: currentProcessId === 'MW507', done: isStepDone({ step: 'MW507', tabId: 'form-MW507-tab' }) }"
                        :style="{ display: showFormMW507 ? undefined : 'none' }"
                        role="button"
                        @click="goToStep({ step: 'MW507', tabId: 'form-MW507-tab', label: 'onboarding-Form-MW507' })"
                    >
                        <span class="status-icon">
                            <span v-if="isStepDone({ step: 'MW507', tabId: 'form-MW507-tab' })" class="check">✓</span>
                        </span>
                        <span class="sidebar-menu">onboarding-Form-MW507</span>
                    </li>
                </template>
            </ul>
        </div>

        <!-- <div v-if="showPreviewImages" class="preview_image">
            <a href="javascript:void(0);" id="previewI9Document" class="i9-preview-img" @click.prevent="previewI9">
                <img :src="i9PreviewSrc" alt="I-9 form preview" />
            </a>
            <a href="javascript:void(0);" id="previewW4Document" class="w4-preview-img" @click.prevent="previewW4">
                <img :src="w4PreviewSrc" alt="W-4 form preview" />
            </a>
            <a
                href="javascript:void(0);"
                class="emergency-preview-img"
                :class="{ hidden: !showEmergencyPreview }"
                @click.prevent="previewEmergency"
            >
                <img :src="emergencyPreviewSrc" alt="Emergency form preview" />
            </a>
        </div> -->
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    /** Current process id (1–9 or 'MW507', 'Benefit', 'Benefit-PDF'). Determines which item is active. */
    currentProcessId: {
        type: [Number, String],
        default: 1,
    },
    /** Whether to show the Form MW507 sidebar item. */
    showFormMW507: {
        type: Boolean,
        default: false,
    },
    /** Whether to show the preview images block (i9, w4, emergency). */
    showPreviewImages: {
        type: Boolean,
        default: true,
    },
    /** Show the emergency contact preview image. */
    showEmergencyPreview: {
        type: Boolean,
        default: false,
    },
    /** Base URL for assets (e.g. from Laravel asset()). */
    assetBase: {
        type: String,
        default: '',
    },
    /** Skip I-9 and W-4 steps when WorkBright flow is enabled. */
    skipI9W4: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['step-click'])

const steps = [
    { step: 1, id: 'step1', tabId: 'step1-tab', label: 'Welcome message', hidden: false },
    { step: 2, id: 'step2', tabId: 'step2-tab', label: 'Employee Handbook', hidden: false },
    { step: 3, id: 'step3', tabId: 'step3-tab', label: 'Create Your Profile', hidden: false },
    { step: 4, id: 'step4', tabId: 'step4-tab', label: 'Complete Your Form I-9 (Employment Eligibility)', hidden: false },
    { step: 5, id: 'step5', tabId: 'step5-tab', label: 'Complete Your IRS Form W-4 (Employee\'s Withholding Certificate)', hidden: false },
    { step: 6, id: 'Benefit', tabId: 'Benefit-tab', label: 'Benefits Enrollment', hidden: false },
    { step: 'MW507', id: 'form-MW507', tabId: 'form-MW507-tab', label: 'onboarding-Form-MW507', hidden: false },
    { step: 7, id: 'step6', tabId: 'step6-tab', label: 'Direct Deposit Authorization Form', hidden: false },
    { step: 8, id: 'step7', tabId: 'step7-tab', label: 'Emergency Contact Information Form', hidden: false },
    { step: 9, id: 'step8', tabId: 'step8-tab', label: 'Final Submission & Employee Declaration', hidden: false },
    { step: 10, id: 'step9', tabId: 'step9-tab', label: 'Digital Signature Fields', hidden: false },
    { step: 11, id: 'step10', tabId: 'step10-tab', label: 'Thank You', hidden: true },
]

function isStepHidden(step) {
    return props.skipI9W4 && (step.step === 4 || step.step === 5)
}

function isStepActive(step) {
    if (isStepHidden(step)) return false
    const pid = props.currentProcessId

    if (step.step === 5) {
        return pid === 5 || pid === 'MW507'
    }
    return pid === step.step
}

/** Index of the current step in the steps array (for "done" comparison). */
function getCurrentStepIndex() {
    const visibleSteps = steps.filter((s) => !isStepHidden(s))
    const idx = visibleSteps.findIndex((s) => {
        if (s.tabId === 'form-MW507-tab') return props.currentProcessId === 'MW507'
        return isStepActive(s)
    })
    return idx >= 0 ? idx : 0
}

/** True if this step is before the current step (user has completed it). */
function isStepDone(step) {
    if (isStepHidden(step)) return false
    const visibleSteps = steps.filter((s) => !isStepHidden(s))
    const currentIdx = getCurrentStepIndex()
    const stepIdx = visibleSteps.findIndex((s) => (s.tabId && step.tabId ? s.tabId === step.tabId : s.step === step.step))
    return stepIdx >= 0 && stepIdx < currentIdx
}

const base = computed(() => (props.assetBase ? props.assetBase.replace(/\/$/, '') : ''))

// const i9PreviewSrc = computed(() => `${base.value}/frontend/assets/images/i9-preview.png`)
// const w4PreviewSrc = computed(() => `${base.value}/frontend/assets/images/w4-preview.png`)
// const emergencyPreviewSrc = computed(() => `${base.value}/frontend/assets/images/emergency-preview.png`)

function goToStep(step) {
    emit('step-click', step.step) 
}

function previewI9() {
    emit('preview-i9')
}

function previewW4() {
    emit('preview-w4')
}

function previewEmergency() {
    emit('preview-emergency')
}
</script>

<style scoped>
.lft_part {
    flex: 0 0 auto;
}

.sidebar {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem 0;
    margin-bottom: 1.5rem;
}

.sidebar ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    cursor: pointer;
    border-left: 3px solid transparent;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.sidebar li:hover {
    background: rgba(0, 0, 0, 0.04);
}

.sidebar li.active {
    background: rgba(13, 110, 253, 0.08);
    border-left-color: #0d6efd;
    font-weight: 500;
}

.sidebar .status-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #dee2e6;
    transition: background 0.15s ease, color 0.15s ease;
}

.sidebar .status-icon .check {
    font-size: 0.7rem;
    font-weight: bold;
    color: #fff;
}

.sidebar li.done .status-icon {
    background: #198754;
}

.sidebar li.done .status-icon .check {
    color: #fff;
}

.sidebar li.active .status-icon {
    background: #0d6efd;
}

.sidebar li.active .status-icon .check {
    color: #fff;
}

.sidebar .sidebar-menu {
    font-size: 0.9rem;
    color: #495057;
}

.sidebar li.active .sidebar-menu {
    color: #0d6efd;
}

.preview_image {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.preview_image a {
    display: block;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.preview_image a:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
}

.preview_image img {
    display: block;
    width: 100%;
    max-width: 120px;
    height: auto;
    object-fit: cover;
}

.preview_image a.hidden {
    display: none;
}
</style>
