    <template>
    <div v-if="show">
        <!-- Form Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Employee Rate Request' : 'Create Employee Rate Request' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-6">
                <div v-if="form.employee?.id && loadingEmployeeProfile" class="flex items-center gap-2 py-2 text-sm text-gray-600 dark:text-gray-400">
                    <Spinner size="sm" text="" />
                    <span>Loading employee record…</span>
                </div>

                <!-- Basic Information Section -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Rate Request Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Employee -->
                        <DynamicDropdown 
                            v-model="form.employee" 
                            label="Employee" 
                            resource="employees" 
                            display-name="pos_name"
                            placeholder="Select an employee" 
                            :required="true"
                            :error="errors.employee_id ? errors.employee_id[0] : null" 
                            icon-left="user" 
                        />

                        <!-- Company -->
                        <DynamicDropdown 
                            v-model="form.company" 
                            label="Company" 
                            resource="companies" 
                            display-name="name"
                            placeholder="Select a company" 
                            :required="true"
                            :error="errors.company_id ? errors.company_id[0] : null" 
                            icon-left="building" 
                        />

                        <!-- Role -->
                        <DynamicDropdown 
                            v-model="form.role" 
                            label="Role" 
                            resource="employee-roles" 
                            display-name="name"
                            placeholder="Select a role" 
                            :required="true"
                            :error="errors.role_id ? errors.role_id[0] : null" 
                            icon-left="briefcase" 
                        />

                        <!-- Effective Date -->
                        <DynamicDropdown
                            v-model="form.effective_date"
                            label="Effective Date"
                            :custom-options="effectiveDateOptions"
                            placeholder="Select effective date"
                            :required="true"
                            :error="errors.effective_date ? errors.effective_date[0] : null"
                        />

                        <!-- Till Date -->
                        <Input 
                            v-model="form.till_date" 
                            label="Till Date" 
                            type="date" 
                            placeholder="Select till date"
                            :error="errors.till_date ? errors.till_date[0] : null"
                            icon-left="calendar" 
                        />

                        <!-- Pay Type -->
                        <DynamicDropdown 
                            v-model="form.pay_type" 
                            label="Pay Type" 
                            :custom-options="payTypes"
                            placeholder="Select pay type" 
                            :required="true"
                            :error="errors.pay_type ? errors.pay_type[0] : null" 
                            icon-left="credit-card" 
                        />

                        <!-- Rate Type -->
                        <DynamicDropdown 
                            v-model="form.rate_type" 
                            label="Rate Type" 
                            :custom-options="rateTypes"
                            placeholder="Select rate type" 
                            :required="true"
                            :error="errors.rate_type ? errors.rate_type[0] : null" 
                            icon-left="tag" 
                        />
                        <div v-if="form.rate_type?.id === 'Payroll Regular' || form.rate_type?.id === 'Payroll Slab' || form.rate_type?.id === 'Payroll 1099'">
                            <DynamicDropdown v-model="form.payroll_type" label="Payroll Type" :custom-options="payrollTypes"
                                display-name="name" placeholder="Select payroll type" :removable="false"
                                :searchable="false" :required="true" />
                        </div>

                        <!-- Rate -->
                        <Input 
                            v-model="form.rate" 
                            label="Rate" 
                            type="number" 
                            step="0.01"
                            placeholder="Enter rate"
                            :required="true" 
                            :error="errors.rate ? errors.rate[0] : null"
                            icon-left="dollar" 
                        />

                        <!-- Profile Picture (required when rate type is 1099, unless no picture ID) -->
                        <div v-if="is1099RateType && !form.employee.profile_picture_url" class="md:col-span-2 lg:col-span-4">
                            <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-2">
                                Profile Picture
                                <span v-if="!noPictureId" class="text-red-600 dark:text-red-400">*</span>
                            </h6>
                            <p class="text-sm text-amber-600 dark:text-amber-400 mb-2">
                                <template v-if="noPictureId">
                                    Profile picture is optional when you don't have picture ID.
                                </template>
                                <template v-else>
                                    Profile picture is required when rate type is 1099. It will be saved to the employee record.
                                </template>
                            </p>
                            <label class="flex items-center gap-2 mb-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                                <input
                                    v-model="noPictureId"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                                />
                                <span>I don't have picture ID</span>
                            </label>
                            <div v-if="!noPictureId" class="flex flex-wrap items-center gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <input
                                        ref="profilePictureRef"
                                        type="file"
                                        accept="image/jpeg,image/jpg,image/png"
                                        class="block w-full max-w-xs text-sm text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-600 dark:file:text-gray-200"
                                        @change="onProfilePictureChange"
                                    />
                                    <p v-if="errors.profile_picture" class="text-xs text-red-600 dark:text-red-400">
                                        {{ errors.profile_picture[0] }}
                                    </p>
                                </div>
                                <div v-if="profilePicturePreview" class="flex items-center gap-2">
                                    <img
                                        :src="profilePicturePreview"
                                        alt="Profile"
                                        title="Open full size in new tab"
                                        class="h-20 w-20 object-cover border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:opacity-90"
                                        @click="onProfilePictureClick"
                                    />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Uploaded</span>
                                </div>
                            </div>
                        </div>

                        <!-- Slab First Hours (conditional) -->
                        <Input 
                            v-if="form.rate_type?.id === 'Payroll Slab' || form.rate_type?.id === '1099 Slab' || form.rate_type?.id === 'Payroll 1099' || form.rate_type?.id === '1099 1099'"
                            v-model="form.slab_first_hours" 
                            label="Slab First Hours" 
                            type="number" 
                            step="0.01"
                            placeholder="Enter slab first hours"
                            :error="errors.slab_first_hours ? errors.slab_first_hours[0] : null"
                            icon-left="clock" 
                        />

                        <!-- Slab Rest Rate (conditional) -->
                        <Input 
                            v-if="form.rate_type?.id === 'Payroll Slab' || form.rate_type?.id === '1099 Slab' || form.rate_type?.id === 'Payroll 1099' || form.rate_type?.id === '1099 1099'"
                            v-model="form.slab_rest_rate" 
                            label="Slab Rest Rate" 
                            type="number" 
                            step="0.01"
                            placeholder="Enter slab rest rate"
                            :error="errors.slab_rest_rate ? errors.slab_rest_rate[0] : null"
                            icon-left="dollar" 
                        />
                        <Input v-if="form.rate_type?.id === 'Payroll 1099'"
                                v-model="form.payroll_rate" label="Payroll Rate" type="number"
                                placeholder="Enter payroll rate"
                                icon-left="dollar" :required="true" />

                        <!-- 1099 Rate (conditional, for 1099 1099) -->
                        <Input v-if="form.rate_type?.id === '1099 1099'"
                            v-model="form.ten99_rate"
                            label="1099 Rate"
                            type="number"
                            step="0.01"
                            placeholder="Enter 1099 rate"
                            :required="true"
                            :error="errors.ten99_rate ? errors.ten99_rate[0] : null"
                            icon-left="dollar" />

                        <!-- Payroll Hours (conditional) -->
                        <div
                                v-if="form.rate_type?.id === 'Payroll Slab'">
                                <InputLabel :required="true">Payroll Hours Type</InputLabel>
                                <div class="flex items-start gap-2">
                                    <div class="w-[40px] flex-shrink-0">
                                        <DynamicDropdown v-model="form.payroll_hours_type"
                                            :custom-options="payrollHoursTypes" display-name="name"
                                            placeholder="%" :removable="false" :searchable="false"
                                            :required="true" />
                                    </div>
                                    <div class="flex-1">
                                        <Input v-model="form.payroll_hours" type="number"
                                            placeholder="Enter payroll hours"
                                            :max="form.payroll_hours_type?.id === 'percentage' ? 100 : 9999999999"
                                            :error="errors.payroll_hours ? errors.payroll_hours[0] : null"
                                            :icon-left="form.payroll_hours_type?.id === 'percentage' ? 'percent' : 'clock'" :required="true" />
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="form.rate_type?.id === '1099 Regular' || form.rate_type?.id === '1099 Slab' || form.rate_type?.id === 'Payroll Slab' || form.rate_type?.id === 'Payroll 1099'">
                                <InputLabel :required="true">Check Payment Type</InputLabel>
                                <div class="flex items-start gap-2">
                                    <div class="w-[40px] flex-shrink-0">
                                        <DynamicDropdown v-model="form.check_payment_type"
                                            :custom-options="checkPaymentTypes" display-name="name"
                                            placeholder="%" :removable="false" :searchable="false"
                                            :required="true" />
                                    </div>
                                    <div class="flex-1">
                                        <Input v-model="form.check_payment_amount" type="number"
                                            placeholder="Enter amount"
                                            :max="form.check_payment_type?.id === 'percentage' ? 100 : 9999999999"
                                            :error="errors.check_payment_amount ? errors.check_payment_amount[0] : null"
                                            :icon-left="form.check_payment_type?.id === 'percentage' ? 'percent' : 'dollar'" :required="true" />
                                    </div>
                                </div>
                            </div>

                        <!-- Overtime note (Payroll Regular / 1099 Regular) -->
                        <div v-if="(form.rate_type?.id === '1099 Regular' || form.rate_type?.id === 'Payroll Regular') && form.rate" class="md:col-span-2 lg:col-span-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                <span class="font-bold">Note:</span>
                                Overtime will be calculated at 1.5× the regular rate ({{ (Number(form.rate) || 0) * 1.5 }}).
                            </p>
                        </div>
                        <!-- Payroll Slab note -->
                        <div v-if="form.rate_type?.id === 'Payroll Slab' && form.payroll_hours != null" class="md:col-span-2 lg:col-span-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                <span class="font-bold">Note:</span>
                                Any hours worked beyond {{ form.payroll_hours }} will be paid as 1099.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="filteredEmployeeRates.length > 0 || filteredEmployeeRateRequests.length > 0"
                    class="border-t border-gray-200 dark:border-gray-700 pt-4"
                >
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Old rates and rate requests</h6>

                    <div v-if="filteredEmployeeRates.length > 0" class="mb-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rates</p>
                        <div class="space-y-2">
                            <div
                                v-for="(rate, idx) in filteredEmployeeRates"
                                :key="'old-rate-' + idx"
                                class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <Label label="Company" :value="rate.company?.name || '—'" />
                                    <Label label="Role" :value="rate.role?.name ? (rate.role?.code ?? '') + ' - ' + rate.role?.name : '—'" />
                                    <Label label="Rate Type" :value="rate.rate_type || '—'" />
                                    <Label label="Rate" :value="formatCurrency(rate.rate)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredEmployeeRateRequests.length > 0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rate requests</p>
                        <div class="space-y-2">
                            <div
                                v-for="(req, idx) in filteredEmployeeRateRequests"
                                :key="'old-request-' + idx"
                                class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                                    <Label label="Status" :value="req.status || '—'" />
                                    <Label label="Role" :value="req.role?.name ? (req.role?.code ?? '') + ' - ' + req.role?.name : '—'" />
                                    <Label label="Rate Type" :value="req.rate_type || '—'" />
                                    <Label label="Rate" :value="formatCurrency(req.rate)" />
                                    <Label label="Effective Date" :value="formatDate(req.effective_date)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button 
                        type="button" 
                        variant="secondary" 
                        @click="handleCancel"
                    >
                        Cancel
                    </Button>
                    <Button 
                        type="submit" 
                        variant="primary"
                        :loading="isSaving"
                        :disabled="isSaving"
                    >
                        {{ mode === 'edit' ? 'Update Request' : 'Create Request' }}
                    </Button>
                </div>
            </form>
        </Panel>
    </div>

    <!-- Loading State -->
    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading..." centered />
    </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import InputLabel from '@/components/ui/inputLabel.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'
import Label from '@/components/ui/label.vue'
import { watch, ref, computed } from 'vue'
import axios from 'axios'
import { useMessage } from '@/composables/useMessage'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { assignValidatedFile } from '@/utils/documentUpload'

const message = useMessage()

const route = useRoute()
const router = useRouter()
const resource = route.meta?.resource || 'onboarding/employee-rate-request'

// Use the useFormable composable
const { form, show, mode, errors, isSaving, setData: originalSetData, save } = useFormable(resource, 'payroll/employee-new-rate-request','employee-rate-request')

// Custom setData to transform the data for edit mode
const setData = (res) => {
    const data = res.data.form
    
    // Transform dropdown values to match the format expected by the form
    if (data.pay_type) {
        data.pay_type = payTypes.find(pt => pt.id === data.pay_type) || payTypes[0]
    }
    if (data.rate_type) {
        data.rate_type = rateTypes.find(rt => rt.id === data.rate_type) || rateTypes[0]
    }
    if (data.payroll_hours_type) {
        data.payroll_hours_type = payrollHoursTypes.find(pht => pht.id === data.payroll_hours_type) || payrollHoursTypes[0]
    }
    if (data.check_payment_type) {
        data.check_payment_type = checkPaymentTypes.find(cpt => cpt.id === data.check_payment_type) || checkPaymentTypes[0]
    }
    if (data.payroll_type) {
        data.payroll_type = payrollTypes.find(pt => pt.id === data.payroll_type) || null
    }
    if (data.effective_date) {
        const date = normalizeEffectiveDate(data.effective_date)
        data.effective_date = effectiveDateOptions.value.find((option) => option.id === date) || { id: date, name: date }
    }

    originalSetData(res)
}

const payTypes = [
    {
        id: 'HR',
        name: 'HR'
    },
    {
        id: 'WK',
        name: 'WK'
    }
]

const rateTypes = [
    { id: 'Payroll Regular', name: 'Payroll Regular' },
    { id: 'Payroll Slab', name: 'Payroll Slab' },
    { id: 'Payroll 1099', name: 'Payroll 1099' },
    { id: '1099 Regular', name: '1099 Regular' },
    { id: '1099 Slab', name: '1099 Slab' },
    { id: '1099 1099', name: '1099 1099' }
]

const payrollHoursTypes = [
    { id: 'fixed', name: 'HR' },
    { id: 'percentage', name: '%' }
]

const checkPaymentTypes = [
    { id: 'fixed', name: '$' },
    { id: 'percentage', name: '%' }
]

const payrollTypes = [
    { id: 'Direct Deposit', name: 'Direct Deposit' },
    { id: 'Print on site', name: 'Print on site' }
]

const formatDateOption = (date) => {
    const month = String(date.getUTCMonth() + 1).padStart(2, '0')
    const day = String(date.getUTCDate()).padStart(2, '0')
    const year = date.getUTCFullYear()
    return `${year}-${month}-${day}`
}

const generatePeriodStartDatesForYear = (year) => {
    const options = []
    let startDate = new Date(Date.UTC(year, 0, 1))
    const dayOfWeek = startDate.getUTCDay()
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)

    let periodNumber = 1
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13)

        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }

        const value = formatDateOption(startDate)
        options.push({ id: value, name: value })

        startDate.setUTCDate(startDate.getUTCDate() + 14)
        periodNumber++
    }

    return options
}

const effectiveDateOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    const all = [
        ...generatePeriodStartDatesForYear(currentYear - 1),
        ...generatePeriodStartDatesForYear(currentYear),
        ...generatePeriodStartDatesForYear(currentYear + 1),
    ]

    return all.filter((item, index, arr) => index === arr.findIndex((x) => x.id === item.id))
})

const normalizeEffectiveDate = (value) => {
    if (!value) return null
    if (typeof value === 'string') return value.slice(0, 10)
    if (value?.id) return String(value.id).slice(0, 10)
    return null
}

const nextEffectiveDateOption = computed(() => {
    const today = new Date()
    const todayUtc = `${today.getUTCFullYear()}-${String(today.getUTCMonth() + 1).padStart(2, '0')}-${String(today.getUTCDate()).padStart(2, '0')}`
    return effectiveDateOptions.value.find((option) => option.id > todayUtc) || effectiveDateOptions.value[0] || null
})

const RATE_TYPES_1099 = ['Payroll 1099', '1099 Regular', '1099 Slab', '1099 1099']
const is1099RateType = computed(() => {
    const id = form.value?.rate_type?.id
    return id && RATE_TYPES_1099.includes(id)
})

/** When true, 1099 rate requests may be submitted without a profile picture (no government picture ID). */
const noPictureId = ref(false)

const employeeDetails = ref(null)
const loadingEmployeeProfile = ref(false)

const selectedCompanyId = computed(() => form.value?.company?.id ?? null)

const filteredEmployeeRates = computed(() => {
    const rates = employeeDetails.value?.employee_rates
    if (!Array.isArray(rates)) return []
    const companyId = selectedCompanyId.value
    if (!companyId) return rates
    return rates.filter((item) => Number(item.company_id) === Number(companyId))
})

const filteredEmployeeRateRequests = computed(() => {
    const requests = employeeDetails.value?.employee_rates_requests
    if (!Array.isArray(requests)) return []
    const companyId = selectedCompanyId.value
    const byCompany = companyId
        ? requests.filter((item) => Number(item.company_id) === Number(companyId))
        : requests
    return byCompany.sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
})

async function loadEmployeeDetails(employeeId) {
    if (!employeeId) {
        employeeDetails.value = null
        return
    }
    loadingEmployeeProfile.value = true
    try {
        const { data } = await axios.get(`/api/employee/${employeeId}`)
        const model = data?.model ?? null
        employeeDetails.value = model
        if (model && form.value?.employee?.id === employeeId) {
            Object.assign(form.value.employee, {
                profile_picture: model.profile_picture,
                profile_picture_url: model.profile_picture_url,
            })
        }
    } catch {
        employeeDetails.value = null
    } finally {
        loadingEmployeeProfile.value = false
    }
}

const profilePictureRef = ref(null)
const profilePictureUploading = ref(false)
const uploadedPreviewUrl = ref(null)

const profilePicturePreview = computed(() => {
    if (uploadedPreviewUrl.value) return uploadedPreviewUrl.value
    const emp = form.value?.employee
    if (emp?.profile_picture_url) return emp.profile_picture_url
    if (emp?.profile_picture) return `/storage/${emp.profile_picture}`
    return null
})

function onProfilePictureClick() {
    const url = profilePicturePreview.value
    if (!url) return
    window.open(url, '_blank', 'noopener,noreferrer')
}

async function onProfilePictureChange(event) {
    const input = event.target
    const file = input?.files?.[0]
    if (!file) return
    const employeeId = form.value?.employee?.id
    if (!employeeId) {
        message.error('Please select an employee first.')
        if (profilePictureRef.value) profilePictureRef.value.value = ''
        return
    }
    if (!assignValidatedFile(file, () => {}, {
        onError: (error) => message.error(error),
        input,
    })) {
        return
    }
    profilePictureUploading.value = true
    try {
        const formData = new FormData()
        formData.append('file', file)
        formData.append('employee_id', employeeId)
        const response = await axios.post('/api/employee/upload-profile-picture', formData)
        if (response?.data?.path) {
            noPictureId.value = false
            uploadedPreviewUrl.value = response.data.url || `/storage/${response.data.path}`
            if (form.value?.employee) {
                form.value.employee.profile_picture = response.data.path
                if (response.data.url) form.value.employee.profile_picture_url = response.data.url
            }
        }
    } catch (e) {
        message.error(e?.response?.data?.message || 'Failed to upload profile picture')
    } finally {
        profilePictureUploading.value = false
        if (profilePictureRef.value) profilePictureRef.value.value = ''
    }
}

watch(
    () => form.value?.employee?.id,
    (id) => {
        uploadedPreviewUrl.value = null
        noPictureId.value = false
        loadEmployeeDetails(id)
    },
    { immediate: true }
)

watch(is1099RateType, (is1099) => {
    if (!is1099) noPictureId.value = false
})

watch(
    [() => mode.value, () => form.value?.effective_date, nextEffectiveDateOption],
    ([currentMode, currentEffectiveDate, nextOption]) => {
        // Only prefill create mode and do not override an existing selection.
        if (currentMode === 'edit' || currentEffectiveDate || !nextOption) return
        form.value.effective_date = nextOption
    },
    { immediate: true }
)

watch(() => form.value, (newVal) => {
    const payTypeId = newVal.pay_type?.id ?? newVal.pay_type
    const rateTypeId = newVal.rate_type?.id ?? newVal.rate_type
    const payrollHoursTypeId = newVal.payroll_hours_type?.id ?? newVal.payroll_hours_type
    const checkPaymentTypeId = newVal.check_payment_type?.id ?? newVal.check_payment_type
    const payrollTypeId = newVal.payroll_type?.id ?? newVal.payroll_type

    newVal.pay_type = payTypes.find(pt => pt.id === payTypeId) || payTypes[0]
    newVal.rate_type = rateTypes.find(rt => rt.id === rateTypeId) || rateTypes[0]
    newVal.payroll_hours_type = payrollHoursTypes.find(pht => pht.id === payrollHoursTypeId) || payrollHoursTypes[0]
    newVal.check_payment_type = checkPaymentTypes.find(cpt => cpt.id === checkPaymentTypeId) || checkPaymentTypes[0]
    newVal.payroll_type = payrollTypes.find(pt => pt.id === payrollTypeId) || null
    if (typeof newVal.effective_date === 'string' && newVal.effective_date) {
        const date = normalizeEffectiveDate(newVal.effective_date)
        newVal.effective_date = effectiveDateOptions.value.find((option) => option.id === date) || { id: date, name: date }
    }
})
// Handle form submission
const handleSave = async () => {
    if (is1099RateType.value && !profilePicturePreview.value && !noPictureId.value) {
        message.error('Profile picture is required when rate type is 1099 (unless you don\'t have picture ID).')
        return
    }
    try {
        let obj = {
            ...form.value,
            employee_id: form.value.employee?.id,
            company_id: form.value.company?.id,
            role_id: form.value.role?.id,
            effective_date: normalizeEffectiveDate(form.value.effective_date),
            pay_type: form.value.pay_type?.id,
            rate_type: form.value.rate_type?.id,
            check_payment_type: form.value.check_payment_type?.id,
            payroll_hours_type: form.value.payroll_hours_type?.id,
            payroll_type: form.value.payroll_type?.id,
            ten99_rate: form.value.ten99_rate,
            no_picture_id: noPictureId.value,
        }
        delete obj.employee
        delete obj.company
        delete obj.role

        await save(obj)
    } catch (error) {
        console.error(error)
    }
}

// Handle cancel
const handleCancel = () => {
    router.push('/payroll/employee-new-rate-request')
}

// Expose setData for useFormable route guards
defineExpose({
    setData
})
</script>

<style scoped>
textarea:focus,
select:focus,
input:focus {
    outline: none !important;
    outline-offset: 0 !important;
}
</style>
