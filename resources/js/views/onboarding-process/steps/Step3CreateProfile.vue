<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h5 class="mb-4 text-lg font-semibold text-[#bf162f]">Create Your Profile</h5>
        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    label="First Name"
                    placeholder="First name"
                    :required="true"
                />
                <Input
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    label="Last Name"
                    placeholder="Last name"
                    :required="true"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Input
                    id="middle_initial"
                    v-model="form.middle_initial"
                    :required="true"
                    type="text"
                    label="Middle initial"
                    placeholder="Middle initial"
                    maxlength="1"
                />
                <Input
                    id="pos_name"
                    v-model="form.pos_name"
                    type="text"
                    label="POS Name"
                    placeholder="POS name"
                    :required="true"

                />
                <Input
                    id="pos_id"
                    v-model="form.pos_id"
                    type="text"
                    label="POS ID"
                    placeholder="POS ID"
                    :required="true"

                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Textarea
                    id="address_street"
                    v-model="form.address_street"
                    label="Address (Street Number and Name)"
                    placeholder="Street number and name"
                    :rows="3"
                />
                <Textarea
                    id="apt_number"
                    v-model="form.apt_number"
                    label="Apt. Number"
                    placeholder="Apt. number"
                    :rows="3"
                    :required="true"

                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <Input
                    id="city"
                    v-model="form.city"
                    type="text"
                    label="City or Town"
                    placeholder="City or town"
                    :required="true"

                />
                <Input
                    id="state"
                    v-model="form.state"
                    type="text"
                    label="State"
                    placeholder="State"
                    :required="true"

                />
                <Input
                    id="zip_code"
                    v-model="form.zip_code"
                    type="text"
                    label="ZIP Code"
                    placeholder="ZIP code"
                    :required="true"

                />
                <Input
                    id="dob"
                    v-model="form.dob"
                    type="date"
                    label="Date of Birth"
                    placeholder="Date of birth"
                    :required="true"
                    :error="errors.dob ? errors.dob[0] : null"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                    id="work_permit_issuer"
                    v-model="form.work_permit_issuer"
                    type="text"
                    label="Work Permit Issuer"
                    placeholder="Work permit issuer"
                    :required="isUnder18"
                />
                <div class="flex flex-col gap-1.5">
                    <label class="block text-sm font-medium text-gray-700">
                        Upload Work Permit
                        <span v-if="isUnder18" class="text-red-600">*</span>
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            ref="workPermitFileRef"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full max-w-xs text-sm text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                            @change="onWorkPermitFileChange"
                        />
                        <p class="text-xs text-gray-500 !mb-0">{{ uploadMaxSizeNote }}</p>
                        <a
                            v-if="form.work_permit_document_url"
                            :href="form.work_permit_document_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-blue-600 hover:underline"
                        >
                            Uploaded Doc Link
                        </a>
                        <span v-else-if="workPermitFile" class="text-sm text-gray-500">{{ workPermitFile.name }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="Email"
                    placeholder="Email"
                    :required="true"
                    :disabled="true"
                />
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    label="Phone"
                    :required="true"
                    placeholder="Phone"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Input
                    id="emergency_contact_name"
                    v-model="form.emergency_contact_name"
                    type="text"
                    label="Emergency Contact Name"
                    placeholder="Name"
                    :required="true"
                />
                <Input
                    id="emergency_contact_phone"
                    v-model="form.emergency_contact_phone"
                    type="tel"
                    label="Emergency Contact Phone #"
                    placeholder="Phone number"
                    :required="true"
                />
                <Input
                    id="emergency_contact_relationship"
                    v-model="form.emergency_contact_relationship"
                    type="text"
                    label="Emergency Contact Relationship"
                    placeholder="Relationship"
                    :required="true"
                />
            </div>

            <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>

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
                >
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
import Textarea from '@/components/ui/textarea.vue'
import { assignValidatedFile, UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'

const props = defineProps({
    onboardingId: { type: String, default: '' },
    goToNextStep: { type: Function, default: () => {} },
    goToBackStep: { type: Function, default: () => {} },
})

const submitting = ref(false)
const submitError = ref('')
const workPermitFileRef = ref(null)
const workPermitFile = ref(null)
const uploadMaxSizeNote = UPLOAD_MAX_SIZE_NOTE

function getAgeFromDob(dob) {
    if (!dob) return null
    const birth = new Date(dob)
    const today = new Date()
    let age = today.getFullYear() - birth.getFullYear()
    const monthDiff = today.getMonth() - birth.getMonth()
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) age--
    return age
}

const isUnder18 = computed(() => {
    const age = getAgeFromDob(form.dob)
    return age !== null && age < 18
})

const form = reactive({
    first_name: '',
    last_name: '',
    middle_initial: '',
    pos_name: '',
    pos_id: '',
    address_street: '',
    apt_number: '',
    city: '',
    state: '',
    zip_code: '',
    dob: '',
    work_permit_issuer: '',
    work_permit_document_url: '',
    email: '',
    phone: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relationship: '',
})

onMounted(() => {
    fetchProfileData()
})

const errors = ref([]);
function getCsrfHeaders() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token ? { 'X-CSRF-TOKEN': token } : {}
}

async function fetchProfileData() {
    if (!props.onboardingId) return
    try {
        const { data: res } = await axios.get('/onboarding-process/profile-data', {
            params: { onboardingId: props.onboardingId },
            headers: getCsrfHeaders(),
            withCredentials: true,
        })
        if (res.success && res.data) {
            const d = res.data
            form.first_name = d.first_name ?? ''
            form.last_name = d.last_name ?? ''
            form.middle_initial = d.middle_initial ?? ''
            form.pos_name = d.pos_name ?? ''
            form.pos_id = d.pos_id ?? ''
            form.address_street = d.address_street ?? ''
            form.apt_number = d.apt_number ?? ''
            form.city = d.city ?? ''
            form.state = d.state ?? ''
            form.zip_code = d.zip_code ?? ''
            form.dob = d.dob ?? ''
            form.work_permit_issuer = d.work_permit_issuer ?? ''
            form.work_permit_document_url = d.work_permit_document_url ?? ''
            form.email = d.email ?? ''
            form.phone = d.phone ?? ''
            form.emergency_contact_name = d.emergency_contact_name ?? ''
            form.emergency_contact_phone = d.emergency_contact_phone ?? ''
            form.emergency_contact_relationship = d.emergency_contact_relationship ?? ''
        }
    } catch (err) {
        console.error('Error fetching profile data:', err)
    }
}

function onWorkPermitFileChange(event) {
    const input = event.target
    assignValidatedFile(input?.files?.[0] || null, (file) => {
        workPermitFile.value = file
    }, {
        onError: (error) => {
            submitError.value = error
        },
        input,
    })
}

async function submit() {
    submitError.value = ''
    if (!form.first_name?.trim() || !form.last_name?.trim() || !form.email?.trim()) {
        submitError.value = 'First name, last name, and email are required.'
        return
    }
    if (!form.emergency_contact_name?.trim() || !form.emergency_contact_phone?.trim() || !form.emergency_contact_relationship?.trim()) {
        submitError.value = 'Emergency contact name, phone, and relationship are required.'
        return
    }
    if (isUnder18.value) {
        if (!form.work_permit_issuer?.trim()) {
            submitError.value = 'Work Permit Issuer is required when under 18.'
            return
        }
        if (!workPermitFile.value && !form.work_permit_document_url) {
            submitError.value = 'Upload Work Permit is required when under 18.'
            return
        }
    }
    submitting.value = true
    try {
        const formData = new FormData()
        formData.append('onboardingId', props.onboardingId)
        formData.append('first_name', form.first_name)
        formData.append('last_name', form.last_name)
        formData.append('email', form.email)
        formData.append('middle_initial', form.middle_initial)
        formData.append('pos_name', form.pos_name)
        formData.append('pos_id', form.pos_id)
        formData.append('address_street', form.address_street)
        formData.append('apt_number', form.apt_number)
        formData.append('city', form.city)
        formData.append('state', form.state)
        formData.append('zip_code', form.zip_code)
        formData.append('dob', form.dob)
        formData.append('work_permit_issuer', form.work_permit_issuer)
        formData.append('phone', form.phone)
        formData.append('emergency_contact_name', form.emergency_contact_name)
        formData.append('emergency_contact_phone', form.emergency_contact_phone)
        formData.append('emergency_contact_relationship', form.emergency_contact_relationship)
        if (workPermitFile.value) {
            formData.append('work_permit_file', workPermitFile.value)
        }
        await axios.post('/onboarding-process/save-profile', formData, {
            headers: {
                ...getCsrfHeaders(),
                'Content-Type': 'multipart/form-data',
            },
            withCredentials: true,
        })
        props.goToNextStep()
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to save profile. Please try again.'
        errors.value = err.response?.data?.errors || []
        submitError.value = msg
    } finally {
        submitting.value = false
    }
}
</script>
