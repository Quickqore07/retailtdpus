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

                <!-- Editable profile for onboarding/new employees -->
                <div
                    v-else-if="showOnboardingEmployeeDetails"
                    class="space-y-6 pb-6 border-b border-gray-200 dark:border-gray-700"
                >
                    <div>
                        <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-1">
                            Employee record
                        </h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Onboarding employee — update the record below; changes are saved with this rate request.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Input
                                v-model="employeeProfileForm.employee_id"
                                label="Employee ID"
                                placeholder="Employee ID"
                                :error="profileFieldError('employee_id')"
                                icon-left="hash"
                            />
                            <Input
                                v-model="employeeProfileForm.pos_name"
                                label="POS Name"
                                placeholder="POS name"
                                :error="profileFieldError('pos_name')"
                                icon-left="user"
                            />
                            <Input
                                v-model="employeeProfileForm.hire_date"
                                label="Hire Date"
                                type="date"
                                :error="profileFieldError('hire_date')"
                                icon-left="calendar"
                            />
                            <Input
                                v-model="employeeProfileForm.ssn"
                                label="SSN"
                                placeholder="SSN"
                                :error="profileFieldError('ssn')"
                                icon-left="lock"
                            />
                            <DynamicDropdown
                                v-model="employeeProfileForm.workgroup"
                                label="Workgroup"
                                resource="workgroups"
                                display-name="name"
                                placeholder="Select workgroup"
                                :error="profileFieldError('workgroup_id')"
                                icon-left="building"
                            />
                            <!-- <Input
                                v-model="employeeProfileForm.onboarding_status"
                                label="Onboarding status"
                                placeholder="Status"
                                :error="profileFieldError('onboarding_status')"
                                icon-left="info"
                            />
                            <Input
                                v-model="employeeProfileForm.employee_type"
                                label="Employee type"
                                placeholder="Type"
                                :error="profileFieldError('employee_type')"
                                icon-left="tag"
                            /> -->
                            <div class="md:col-span-2 lg:col-span-4">
                                <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profile picture</h6>
                                <label
                                    v-if="is1099RateType"
                                    class="flex items-center gap-2 mb-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none"
                                >
                                    <input
                                        v-model="noPictureId"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                                    />
                                    <span>I don't have picture ID</span>
                                </label>
                                <div
                                    v-if="!is1099RateType || !noPictureId"
                                    class="flex flex-wrap items-center gap-4"
                                >
                                    <div v-if="profilePicturePreview" class="flex items-center gap-2">
                                        <img
                                            :src="profilePicturePreview"
                                            alt="Profile"
                                            title="Open full size in new tab"
                                            class="h-20 w-20 object-cover border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:opacity-90"
                                            @click="onProfilePictureClick"
                                        />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <input
                                            ref="onboardingProfilePictureRef"
                                            type="file"
                                            accept="image/jpeg,image/jpg,image/png"
                                            class="block w-full max-w-xs text-sm text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-600 dark:file:text-gray-200"
                                            @change="onProfilePictureChange"
                                        />
                                        <p v-if="errors.profile_picture" class="text-xs text-red-600 dark:text-red-400">
                                            {{ errors.profile_picture[0] }}
                                        </p>
                                    </div>
                                </div>
                                <p
                                    v-else-if="is1099RateType && noPictureId"
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Profile picture is optional because you indicated you don't have picture ID.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Employee names</h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Input
                                v-model="employeeProfileForm.first_name"
                                label="First name"
                                placeholder="First name"
                                :required="isDC ? false : true"
                                :error="profileFieldError('first_name')"
                                icon-left="user"
                            />
                            <Input
                                v-model="employeeProfileForm.middle_name"
                                label="Middle name"
                                placeholder="Middle name"
                                :error="profileFieldError('middle_name')"
                                icon-left="user"
                            />
                            <Input
                                v-model="employeeProfileForm.last_name"
                                label="Last name"
                                placeholder="Last name"
                                :required="isDC ? false : true"
                                :error="profileFieldError('last_name')"
                                icon-left="user"
                            />
                            <Input
                                v-model="employeeProfileForm.check_name"
                                label="Check name"
                                placeholder="Name on check"
                                :required="isDC ? false : true"
                                :error="profileFieldError('check_name')"
                                icon-left="credit-card"
                            />
                        </div>
                        <div class="mt-4">
                            <h6 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Employee Aliases</h6>
                            <div
                                v-for="(alias, index) in employeeProfileForm.aliases"
                                :key="'alias-' + index"
                                class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-3 items-end"
                            >
                                <Input
                                    v-model="alias.alias_employee_id"
                                    label="Alias Employee ID"
                                    placeholder="Alias employee ID"
                                    :error="profileFieldError(`aliases.${index}.alias_employee_id`)"
                                    icon-left="hash"
                                />
                                <Input
                                    v-model="alias.alias_name"
                                    label="Alias Name"
                                    placeholder="Alias name"
                                    :error="profileFieldError(`aliases.${index}.alias_name`)"
                                    icon-left="user"
                                />
                                <div class="pb-1">
                                    <Button variant="danger" size="sm" type="button" @click="removeProfileAlias(index)">
                                        Remove
                                    </Button>
                                </div>
                            </div>
                            <Button variant="outline" size="sm" type="button" @click="addProfileAlias">
                                Add Alias
                            </Button>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Contact information</h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Input
                                v-model="employeeProfileForm.phone"
                                label="Phone"
                                placeholder="Phone"
                                :error="profileFieldError('phone')"
                                icon-left="phone"
                            />
                            <Input
                                v-model="employeeProfileForm.email"
                                label="Email"
                                type="email"
                                placeholder="Email"
                                :error="profileFieldError('email')"
                                icon-left="mail"
                            />
                            <Input
                                v-model="employeeProfileForm.dob"
                                label="Date of birth"
                                type="date"
                                :error="profileFieldError('dob')"
                                icon-left="calendar"
                            />
                            <Input
                                v-model="employeeProfileForm.termination_date"
                                label="Termination date"
                                type="date"
                                :error="profileFieldError('termination_date')"
                                icon-left="calendar"
                            />
                            <div class="md:col-span-2 lg:col-span-2">
                                <Input
                                    v-model="employeeProfileForm.street"
                                    label="Street address"
                                    placeholder="Street"
                                    :error="profileFieldError('street')"
                                    icon-left="map-pin"
                                />
                            </div>
                            <Input
                                v-model="employeeProfileForm.city"
                                label="City"
                                placeholder="City"
                                :error="profileFieldError('city')"
                                icon-left="map"
                            />
                            <Input
                                v-model="employeeProfileForm.state"
                                label="State"
                                placeholder="State"
                                :error="profileFieldError('state')"
                                icon-left="map"
                            />
                            <Input
                                v-model="employeeProfileForm.zip"
                                label="ZIP code"
                                placeholder="ZIP"
                                :error="profileFieldError('zip')"
                                icon-left="map"
                            />
                            <Input
                                v-model="employeeProfileForm.emergency_contact_name"
                                label="Emergency contact name"
                                placeholder="Name"
                                :error="profileFieldError('emergency_contact_name')"
                                icon-left="user"
                            />
                            <Input
                                v-model="employeeProfileForm.emergency_contact_phone"
                                label="Emergency contact phone"
                                placeholder="Phone"
                                :error="profileFieldError('emergency_contact_phone')"
                                icon-left="phone"
                            />
                            <div class="md:col-span-2 lg:col-span-2">
                                <Input
                                    v-model="employeeProfileForm.emergency_contact_relationship"
                                    label="Emergency contact relationship"
                                    placeholder="Relationship"
                                    :error="profileFieldError('emergency_contact_relationship')"
                                    icon-left="user"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="onboardingEmployeeRates.length > 0"
                        class="border-t border-gray-200 dark:border-gray-700 pt-4"
                    >
                        <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Employee rates on file</h6>
                        <div class="space-y-3">
                            <div
                                v-for="(rate, idx) in onboardingEmployeeRates"
                                :key="'ob-rate-' + idx"
                                class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <Label label="Company" :value="rate.company?.name" />
                                    <Label label="Effective date" :value="formatDate(rate.effective_date)" />
                                    <Label
                                        label="Role"
                                        :value="
                                            rate.role?.name
                                                ? (rate.role?.code ?? '') + ' - ' + rate.role.name
                                                : (rate.role_id ?? '—')
                                        "
                                    />
                                    <Label label="Rate" :value="formatCurrency(rate.rate)" />
                                </div>
                            </div>
                        </div>
                    </div>
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
                        <Input 
                            v-model="form.effective_date" 
                            label="Effective Date" 
                            type="date" 
                            placeholder="Select effective date"
                            :required="true" 
                            :error="errors.effective_date ? errors.effective_date[0] : null"
                            icon-left="calendar" 
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
const { form, show, mode, errors, isSaving, setData: originalSetData, save } = useFormable(resource, 'onboarding/employee-rate-request','employee-rate-request')

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

const RATE_TYPES_1099 = ['Payroll 1099', '1099 Regular', '1099 Slab', '1099 1099']
const is1099RateType = computed(() => {
    const id = form.value?.rate_type?.id
    return id && RATE_TYPES_1099.includes(id)
})

/** When true, 1099 rate requests may be submitted without a profile picture (no government picture ID). */
const noPictureId = ref(false)

const employeeDetails = ref(null)
const loadingEmployeeProfile = ref(false)

function emptyEmployeeProfile() {
    return {
        employee_id: '',
        pos_name: '',
        hire_date: '',
        ssn: '',
        workgroup: null,
        onboarding_status: '',
        employee_type: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        aliases: [],
        check_name: '',
        phone: '',
        email: '',
        dob: '',
        termination_date: '',
        street: '',
        city: '',
        state: '',
        zip: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        emergency_contact_relationship: '',
    }
}

const employeeProfileForm = ref(emptyEmployeeProfile())

function applyModelToEmployeeProfileForm(model) {
    if (!model) {
        employeeProfileForm.value = emptyEmployeeProfile()
        return
    }
    const d = (v) => (v == null || v === undefined ? '' : String(v))
    const dateStr = (v) => (v ? String(v).slice(0, 10) : '')
    employeeProfileForm.value = {
        ...emptyEmployeeProfile(),
        employee_id: d(model.employee_id),
        pos_name: d(model.pos_name),
        hire_date: dateStr(model.hire_date),
        ssn: d(model.ssn),
        workgroup: model.workgroup ? { id: model.workgroup.id, name: model.workgroup.name } : null,
        onboarding_status: d(model.onboarding_status),
        employee_type: d(model.employee_type),
        first_name: d(model.first_name),
        middle_name: d(model.middle_name),
        last_name: d(model.last_name),
        aliases: Array.isArray(model.aliases)
            ? model.aliases.map((a) => ({
                alias_employee_id: d(a.alias_employee_id),
                alias_name: d(a.alias_name),
            }))
            : [],
        check_name: d(model.check_name),
        phone: d(model.phone),
        email: d(model.email),
        dob: dateStr(model.dob),
        termination_date: dateStr(model.termination_date),
        street: d(model.street),
        city: d(model.city),
        state: d(model.state),
        zip: d(model.zip),
        emergency_contact_name: d(model.emergency_contact_name),
        emergency_contact_phone: d(model.emergency_contact_phone),
        emergency_contact_relationship: d(model.emergency_contact_relationship),
    }
}

function addProfileAlias() {
    if (!Array.isArray(employeeProfileForm.value.aliases)) {
        employeeProfileForm.value.aliases = []
    }
    employeeProfileForm.value.aliases.push({
        alias_employee_id: '',
        alias_name: '',
    })
}

function removeProfileAlias(index) {
    employeeProfileForm.value.aliases.splice(index, 1)
}

function profileFieldError(key) {
    const e = errors.value?.[`employee_profile.${key}`]
    return Array.isArray(e) ? e[0] : null
}

const showOnboardingEmployeeDetails = computed(() => {
    const e = employeeDetails.value
    if (!e?.id || loadingEmployeeProfile.value) return false
    return (
        e.employee_type === 'New' ||
        e.employee_type === 'Rate Approval' || !e.first_name || !e.last_name || !e.check_name
    )
})

const onboardingEmployeeRates = computed(() => {
    const rates = employeeDetails.value?.employee_rates
    return Array.isArray(rates) ? rates : []
})

async function loadEmployeeDetails(employeeId) {
    if (!employeeId) {
        employeeDetails.value = null
        applyModelToEmployeeProfileForm(null)
        return
    }
    loadingEmployeeProfile.value = true
    try {
        const { data } = await axios.get(`/api/employee/${employeeId}`)
        const model = data?.model ?? null
        employeeDetails.value = model
        applyModelToEmployeeProfileForm(model)
        if (model && form.value?.employee?.id === employeeId) {
            Object.assign(form.value.employee, {
                profile_picture: model.profile_picture,
                profile_picture_url: model.profile_picture_url,
            })
        }
    } catch {
        employeeDetails.value = null
        applyModelToEmployeeProfileForm(null)
    } finally {
        loadingEmployeeProfile.value = false
    }
}

const profilePictureRef = ref(null)
const onboardingProfilePictureRef = ref(null)
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
        if (onboardingProfilePictureRef.value) onboardingProfilePictureRef.value.value = ''
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
})
// Handle form submission
const handleSave = async () => {
    if (showOnboardingEmployeeDetails.value) {
        const p = employeeProfileForm.value
        if (!String(p.first_name ?? '').trim() || !String(p.last_name ?? '').trim() || !String(p.check_name ?? '').trim()) {
            message.error('First name, last name, and check name are required.')
            return
        }
    }
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

        if (showOnboardingEmployeeDetails.value && form.value?.employee?.id) {
            const p = employeeProfileForm.value
            obj.employee_profile = {
                employee_id: p.employee_id || null,
                pos_name: p.pos_name || null,
                hire_date: p.hire_date || null,
                ssn: p.ssn || null,
                workgroup_id: p.workgroup?.id ?? null,
                onboarding_status: p.onboarding_status || null,
                employee_type: p.employee_type || null,
                first_name: String(p.first_name).trim(),
                middle_name: p.middle_name || null,
                last_name: String(p.last_name).trim(),
                aliases: (p.aliases || []).map((a) => ({
                    alias_employee_id: a.alias_employee_id || null,
                    alias_name: a.alias_name || null,
                })),
                check_name: String(p.check_name).trim(),
                phone: p.phone || null,
                email: p.email || null,
                dob: p.dob || null,
                termination_date: p.termination_date || null,
                street: p.street || null,
                city: p.city || null,
                state: p.state || null,
                zip: p.zip || null,
                emergency_contact_name: p.emergency_contact_name || null,
                emergency_contact_phone: p.emergency_contact_phone || null,
                emergency_contact_relationship: p.emergency_contact_relationship || null,
            }
        }

        await save(obj)
    } catch (error) {
        console.error(error)
    }
}

// Handle cancel
const handleCancel = () => {
    router.push('/onboarding/employee-rate-request')
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
