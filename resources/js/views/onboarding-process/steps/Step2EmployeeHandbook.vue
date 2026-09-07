<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h5 class="mb-4 text-lg font-semibold text-[#bf162f]">Employee Handbook</h5>
        <form @submit.prevent="submit" class="mb-3">
            <div class="mb-4">
                <iframe
                    :src="handbookPdfUrl"
                    class="h-[500px] w-full rounded-md border border-gray-200"
                    title="Employee Handbook PDF"
                />
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                    id="employeeSign"
                    v-model="form.employee_sign" 
                    type="text"
                    label="Employee's Signature"
                    placeholder="Enter employee's signature"
                    :required="true"
                />
                <Input
                    id="date"
                    v-model="formattedDate"
                    type="date"
                    label="Date"
                    :readonly="true"
                    :required="true"
                />
                <Input
                    id="printFullName"
                    v-model="form.print_full_name"
                    type="text"
                    label="Print Full Name"
                    placeholder="Enter print full name"
                    :required="true"
                />
                <Input
                    id="company_code"
                    v-model="props.companyCode"
                    type="text"
                    label="Store Code"
                    placeholder="Enter location name"
                    :readonly="true"
                    :required="true"
                />
            </div>

            <p v-if="submitError" class="mb-2 text-sm text-red-600">{{ submitError }}</p>
            <p class="mb-4 flex items-center gap-2">
                <input
                    id="employeeHandbook"
                    v-model="form.is_checked"
                    type="checkbox"
                    value="true"
                    required
                    class="h-4 w-4 rounded border-gray-300 text-[#bf162f] focus:ring-[#bf162f]"
                />
                <label for="employeeHandbook" class="text-sm text-gray-700">
                    I have read the document carefully and agree with the
                    <a
                        href="https://www.quickob.com/terms-condition"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-blue-600 hover:underline"
                    >terms and conditions</a>
                    outlined.
                </label>
            </p>

            <div class="mt-6 flex items-center justify-between">
                <Button
                    variant="secondary"
                    @click="goToBackStep"
                    class="rounded-md border border-gray-300 bg-gray-100 px-4 py-2 font-medium text-gray-700 no-underline hover:bg-gray-200"
                >
                    Back
                </Button>
                <Button
                    type="submit"
                    variant="primary"
                    :loading="submitting"
                    :disabled="!form.is_checked"
                >
                    Save & Next
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import { formatDate } from '@/utils/date'

const props = defineProps({
    onboardingId: { type: String, default: '' },
    goToNextStep: { type: Function, default: () => {} },
    goToBackStep: { type: Function, default: () => {} },
    companyCode: { type: String, default: '' },
    /** Pre-filled handbook data from server */
    handbook: {
        type: Object,
        default: () => ({}),
    },
    /** URL for the handbook PDF (e.g. from Laravel asset()). */
    handbookPdfUrl: {
        type: String,
        default: '/docs/DMV Handbook.pdf',
    },
})

const submitting = ref(false)
const submitError = ref('')

const form = reactive({
    employee_sign: '',
    date: '',
    print_full_name: '',
    is_checked: false,
})

const formattedDate = computed(() => {
    return formatDate(form.date)
})

onMounted(() => {
    form.date = new Date().toISOString().slice(0, 10)
    fetchHandbookData()
})

async function fetchHandbookData() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const { data: res } = await axios.get('/onboarding-process/handbook-data', {
            params: { onboardingId: props.onboardingId },
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })
        if (res.success) {
            form.employee_sign = res.data.employee_sign
            form.date = res.data.date ? res.data.date : new Date().toISOString().slice(0, 10)
            form.print_full_name = res.data.print_full_name
            form.is_checked = res.data.is_checked

            
        }
    } catch (err) {
        console.error('Error fetching handbook data:', err)
    }
}

async function submit() {
    if (!form.is_checked) {
        submitError.value = 'You must agree with the terms and conditions before proceeding.'
        return
    }
    submitError.value = ''
    submitting.value = true
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        await axios.post('/onboarding-process/employee-handbook', {
            onboardingId: props.onboardingId,
            employee_sign: form.employee_sign,
            date: form.date,
            print_full_name: form.print_full_name,
            is_checked: form.is_checked,
        }, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            withCredentials: true,
        })
        props.goToNextStep()
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to save. Please try again.'
        submitError.value = msg
    } finally {
        submitting.value = false
    }
}
</script>

