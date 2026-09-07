<template>
    <div v-if="show">
        <!-- Form Panel -->
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Employee' : 'Create New Employee' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Basic Information Section -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Basic Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Employee ID -->
                        <Input v-model="form.employee_id" label="Employee ID" 
                            placeholder="Enter employee ID" :required="true"
                            :error="errors.employee_id ? errors.employee_id[0] : null" icon-left="hash" />

                        <!-- POS Name -->
                        <Input v-model="form.pos_name" label="POS Name" placeholder="Enter POS name" :required="true"
                            :error="errors.pos_name ? errors.pos_name[0] : null" icon-left="user" />

                        <!-- Check Name -->

                        <!-- Store Number -->
                        <!-- <DynamicDropdown v-model="form.company" label="Company" resource="companies" display-name="name"
                            placeholder="Select a company" :required="true"
                            :error="errors.company_id ? errors.company_id[0] : null" icon-left="building" /> -->

                       

                        <!-- Hire Date -->
                        <Input v-model="form.hire_date" label="Hire Date" type="date" placeholder="Select hire date"
                            :required="true" :error="errors.hire_date ? errors.hire_date[0] : null"
                            icon-left="calendar" />



                        <!-- SSN -->
                        <Input v-model="form.ssn" label="SSN" placeholder="Enter 9 digits (dashes added automatically)"
                            :error="errors.ssn ? errors.ssn[0] : null" icon-left="shield"  @input="handleSSNInput($event)"/>

                            <DynamicDropdown v-model="form.workgroup"  :disabled="true" label="Workgroup" resource="workgroups" display-name="name"
                                placeholder="Select workgroup" :required="true"
                                :error="errors.workgroup_id ? errors.workgroup_id[0] : null" icon-left="building" />

                            <div v-if="isNew && !isDC" class="flex flex-col gap-1.5">
                                <InputLabel :required="true">Employee Payment Type</InputLabel>
                                <div class="flex flex-wrap gap-4 pt-0.5">
                                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                        <input type="radio" v-model="employeePaymentType" value="payroll"
                                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" />
                                        Payroll
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                        <input type="radio" v-model="employeePaymentType" value="1099"
                                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" />
                                        1099
                                    </label>
                                </div>
                            </div>

                            <Input  v-model="form.email" label="Email" type="email"
                                placeholder="Enter email address"
                                :required="employeePaymentType === 'payroll'"
                                :error="errors.email ? errors.email[0] : null" icon-left="mail" />

                                <div class="flex flex-col gap-1.5" v-if="route.path.includes('existing')">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Employee Type
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.mark_as_new"
                                            :true-value="true"
                                            :false-value="false"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            Mark as New
                                        </span>
                                    </label>
                                </div>

                        <!-- Active Status -->
                        <!-- <div class="flex flex-col gap-1.5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Status
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" v-model="form.active" :true-value="true" :false-value="false"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    Active
                                </span>
                            </label>
                            <p v-if="errors.active" class="text-xs text-red-600 dark:text-red-400 mt-1">
                                {{ errors.active[0] }}
                            </p>
                        </div> -->

                        <!-- Profile Picture -->
                        <div class="md:col-span-2 lg:col-span-4" v-if="!isNew">
                            <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-2">
                                Profile Picture
                            </h6>
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <input
                                        ref="profilePictureRef"
                                        type="file"
                                        accept="image/jpeg,image/jpg,image/png"
                                        class="block w-full max-w-xs text-sm text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-600 dark:file:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="profilePictureUploading"
                                        @change="onProfilePictureChange"
                                    />
                                    <p v-if="errors.profile_picture" class="text-xs text-red-600 dark:text-red-400">
                                        {{ errors.profile_picture[0] }}
                                    </p>
                                </div>
                                <div v-if="profilePicturePreview || profilePictureUploading" class="flex items-center gap-2">
                                    <div
                                        class="relative h-20 w-20 shrink-0 overflow-hidden rounded border-2 border-gray-200 dark:border-gray-600"
                                        :class="{
                                            'bg-gray-100 dark:bg-gray-700': profilePictureUploading && !profilePicturePreview,
                                        }"
                                    >
                                        <img
                                            v-if="profilePicturePreview"
                                            :src="profilePicturePreview"
                                            alt="Profile"
                                            title="Open full size in new tab"
                                            class="h-full w-full object-cover cursor-pointer hover:opacity-90"
                                            :class="{ 'pointer-events-none': profilePictureUploading }"
                                            @click="onProfilePictureClick"
                                        />
                                    </div>
                                    <span v-if="profilePictureUploading" class="text-sm text-gray-500 dark:text-gray-400">
                                        Uploading…
                                    </span>
                                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">Uploaded</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!isNew">
                    <!-- Employee Names Section -->
                    <div >
                        <h6
                            class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            Employee Names
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Employee Name 1 -->
                            <Input v-model="form.first_name" label="First Name" placeholder="Enter first name"
                                :error="errors.first_name ? errors.first_name[0] : null" icon-left="user" />
                            <Input v-model="form.middle_name" label="Middle Name" placeholder="Enter middle name"
                                :error="errors.middle_name ? errors.middle_name[0] : null" icon-left="user" />
                            <Input v-model="form.last_name" label="Last Name" placeholder="Enter last name"
                                :error="errors.last_name ? errors.last_name[0] : null" icon-left="user" />

                            <Input v-model="form.check_name" label="Check Name" placeholder="Enter check name"
                                :required="isDC ? false : true" :error="errors.check_name ? errors.check_name[0] : null"
                                icon-left="user" />
                        </div>
                    </div>

                    <!-- Employee Aliases Section -->
                    <div class="mt-4">
                        <h6
                            class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            Employee Aliases
                        </h6>
                        <div v-for="(alias, index) in form.aliases" :key="'alias-' + index"
                            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-3 items-end">
                            <Input v-model="alias.alias_employee_id" label="Alias Employee ID"
                                placeholder="Enter alias employee ID"
                                :error="errors[`aliases.${index}.alias_employee_id`] ? errors[`aliases.${index}.alias_employee_id`][0] : null"
                                icon-left="hash" />
                            <Input v-model="alias.alias_name" label="Alias Name"
                                placeholder="Enter alias name"
                                :error="errors[`aliases.${index}.alias_name`] ? errors[`aliases.${index}.alias_name`][0] : null"
                                icon-left="user" />
                            <div class="pb-1">
                                <Button variant="danger" size="sm" type="button" @click="removeAlias(index)">
                                    Remove
                                </Button>
                            </div>
                        </div>
                        <Button variant="outline" size="md" type="button" @click="addAlias">
                            Add Alias
                        </Button>
                    </div>

                    <!-- Contact Information Section -->
                    <div  class="mt-4">
                        <h6
                            class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            Contact Information
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Phone -->
                            <Input v-model="form.phone" label="Phone" type="tel" placeholder="Enter phone number"
                                :error="errors.phone ? errors.phone[0] : null" icon-left="phone" />

                            <!-- Email -->
                            <Input v-model="form.email" label="Email" type="email" placeholder="Enter email address"
                                :error="errors.email ? errors.email[0] : null" icon-left="mail"  />
                            <!-- Date of Birth -->
                            <Input v-model="form.dob" label="Date of Birth" type="date" placeholder="Select date of birth"
                                :error="errors.dob ? errors.dob[0] : null" icon-left="calendar"/>
                            <!-- Termination Date -->
                            <Input v-model="form.termination_date" label="Termination Date" type="date"
                                placeholder="Select termination date"
                                :error="errors.termination_date ? errors.termination_date[0] : null" icon-left="calendar" />
                            <!-- Street -->
                            <Input v-model="form.street" label="Street Address" placeholder="Enter street address"
                                :error="errors.street ? errors.street[0] : null" icon-left="map-pin" />

                            <!-- City -->
                            <Input v-model="form.city" label="City" placeholder="Enter city"
                                :error="errors.city ? errors.city[0] : null" icon-left="map" />

                            <!-- State -->
                            <Input v-model="form.state" label="State" placeholder="Enter state"
                                :error="errors.state ? errors.state[0] : null" icon-left="map" />

                            <!-- ZIP -->
                            <Input v-model="form.zip" label="ZIP Code" placeholder="Enter ZIP code"
                                :error="errors.zip ? errors.zip[0] : null" icon-left="map-pin" />

                            <!-- Emergency Contact Name -->
                            <Input v-model="form.emergency_contact_name" label="Emergency Contact Name"
                                placeholder="Enter emergency contact name"
                                :error="errors.emergency_contact_name ? errors.emergency_contact_name[0] : null"
                                icon-left="user" />

                            <!-- Emergency Contact Phone -->
                            <Input v-model="form.emergency_contact_phone" label="Emergency Contact Phone"
                                placeholder="Enter emergency contact phone"
                                :error="errors.emergency_contact_phone ? errors.emergency_contact_phone[0] : null"
                                icon-left="phone" />

                            <!-- Emergency Contact Relationship -->
                            <Input v-model="form.emergency_contact_relationship" label="Emergency Contact Relationship"
                                placeholder="Enter emergency contact relationship"
                                :error="errors.emergency_contact_relationship ? errors.emergency_contact_relationship[0] : null"
                                icon-left="user" />
                        </div>
                    </div>

                    <!-- Section 1: Employee Rates (approved rates) -->
                    <div class="space-y-4 mt-3" >
                        <h6
                            class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            Employee Rates
                        </h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            Approved rates. Pending update requests can be edited.
                        </p>
                        <div v-for="(rate, index) in form.employee_rates" :key="'rate-' + index"
                            class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 mb-4"
                            :class="checkDisabled(rate) ? 'bg-gray-50 dark:bg-gray-800/30' : 'bg-white dark:bg-gray-800/50'">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <DynamicDropdown v-model="rate.company" label="Company" resource="companies" display-name="name"
                                    placeholder="Select a company" :required="true" :disabled="checkDisabled(rate)"
                                :error="errors.company ? errors.company[0] : null" icon-left="building" />

                                <Input v-model="rate.effective_date" label="Effective date" type="date"
                                    placeholder="Enter effective date" :error="errors.date ? errors.date[0] : null"
                                    :disabled="checkDisabled(rate)"
                                    icon-left="calendar" :required="true" />
                                <Input v-model="rate.till_date" label="Till Date" type="date"
                                    placeholder="Enter till date" :error="errors.till_date ? errors.till_date[0] : null"
                                    :disabled="checkDisabled(rate) && tillDate[index]"
                                    icon-left="calendar" />
                                <div>
                                    <InputLabel :required="true">Role</InputLabel>
                                    <DynamicDropdown v-model="rate.role" resource="employee-roles" display-name="name"
                                        :placeholder="`Select a role for ${index + 1}`" :removable="false"
                                        :required="true" :disabled="checkDisabled(rate)" />
                                </div>
                                <div>
                                    <InputLabel :required="true">Pay Type</InputLabel>
                                    <DynamicDropdown v-model="rate.pay_type" :custom-options="payTypes" display-name="name"
                                        placeholder="Select pay type" :removable="false" :searchable="false"
                                        :required="true" :disabled="checkDisabled(rate)" />
                                </div>
                                <div v-if="can('employee-new-rate-request', 'create')">
                                    <InputLabel :required="true">Rate Type</InputLabel>
                                    <DynamicDropdown v-model="rate.rate_type" :custom-options="rateTypes"
                                        display-name="name" placeholder="Select rate type" :removable="false"
                                        :searchable="false" :required="true" :disabled="checkDisabled(rate) || isDC" />
                                </div>
                                <div v-if="rate.rate_type?.id === 'Payroll Regular' || rate.rate_type?.id === 'Payroll Slab' || rate.rate_type?.id === 'Payroll 1099'">
                                    <InputLabel :required="true">Payroll Type</InputLabel>
                                    <DynamicDropdown v-model="rate.payroll_type" :custom-options="payrollTypes"
                                        display-name="name" placeholder="Select payroll type" :removable="false"
                                        :searchable="false" :required="true" :disabled="checkDisabled(rate)" />
                                </div>
                                <Input v-if="rate.rate_type?.id === 'Payroll Slab' || rate.rate_type?.id === '1099 Slab' || rate.rate_type?.id === 'Payroll 1099' || rate.rate_type?.id === '1099 1099'"
                                    v-model="rate.slab_first_hours" label="Slab First Hours" type="number"
                                    placeholder="Enter slab first hours"
                                    :error="errors.slab_first_hours ? errors.slab_first_hours[0] : null" icon-left="clock"
                                    :required="true" :disabled="checkDisabled(rate)" />
                                <Input v-model="rate.rate" label="Rate" type="number" placeholder="Enter rate" 
                                    :error="errors.rate ? errors.rate[0] : null" icon-left="dollar" :required="true" :disabled="checkDisabled(rate)" />
                                <Input v-if="rate.rate_type?.id === 'Payroll Slab' || rate.rate_type?.id === '1099 Slab' || rate.rate_type?.id === 'Payroll 1099' || rate.rate_type?.id === '1099 1099'"
                                    v-model="rate.slab_rest_rate" label="Slab Rest Rate" type="number"
                                    placeholder="Enter slab rest rate"
                                    :error="errors.slab_rest_rate ? errors.slab_rest_rate[0] : null" icon-left="dollar"
                                    :required="true" :disabled="checkDisabled(rate)" />
                                
                                <div
                                    v-if="rate.rate_type?.id === 'Payroll Slab'">
                                    <InputLabel :required="true">Payroll Hours Type</InputLabel>
                                    <div class="flex items-start gap-2">
                                        <div class="w-[40px] flex-shrink-0">
                                            <DynamicDropdown v-model="rate.payroll_hours_type"
                                                :custom-options="payrollHoursTypes" display-name="name"
                                                placeholder="%" :removable="false" :searchable="false"
                                                :required="true" :disabled="checkDisabled(rate)" />
                                        </div>
                                        <div class="flex-1">
                                            <Input v-model="rate.payroll_hours" type="number"
                                                placeholder="Enter payroll hours"
                                                :max="rate.payroll_hours_type?.id === 'percentage' ? 100 : 9999999999"
                                                :disabled="checkDisabled(rate)"
                                                :error="errors.payroll_hours ? errors.payroll_hours[0] : null"
                                                :icon-left="rate.payroll_hours_type?.id === 'percentage' ? 'percent' : 'clock'" :required="true" />
                                        </div>
                                    </div>
                                </div>

                                <Input v-if="rate.rate_type?.id === 'Payroll 1099'"
                                    v-model="rate.payroll_rate" label="Payroll Rate" type="number"
                                    placeholder="Enter payroll rate"
                                    :error="errors.payroll_rate ? errors.payroll_rate[0] : null" icon-left="dollar"
                                    :required="true" :disabled="checkDisabled(rate)" />

                                <Input v-if="rate.rate_type?.id === '1099 1099'"
                                        v-model="rate.ten99_rate" label="1099 Rate" type="number"
                                        placeholder="Enter payroll rate"
                                        :error="errors.ten99_rate ? errors.ten99_rate[0] : null" icon-left="dollar"
                                        :required="true" :disabled="checkDisabled(rate)" />
                                <div
                                    v-if="rate.rate_type?.id === '1099 Regular' || rate.rate_type?.id === '1099 Slab' || rate.rate_type?.id === 'Payroll Slab' || rate.rate_type?.id === 'Payroll 1099'">
                                    <InputLabel :required="true">Check Payment Type</InputLabel>
                                    <div class="flex items-start gap-2">
                                        <div class="w-[40px] flex-shrink-0">
                                            <DynamicDropdown v-model="rate.check_payment_type"
                                                :custom-options="checkPaymentTypes" display-name="name"
                                                placeholder="%" :removable="false" :searchable="false"
                                                :required="true" :disabled="checkDisabled(rate)" />
                                        </div>
                                    


                                        <div class="flex-1">
                                            <Input v-model="rate.check_payment_amount" type="number"
                                                placeholder="Enter amount"
                                                :max="rate.check_payment_type?.id === 'percentage' ? 100 : 9999999999"
                                                :disabled="checkDisabled(rate)"
                                                :error="errors.check_payment_amount ? errors.check_payment_amount[0] : null"
                                                :icon-left="rate.check_payment_type?.id === 'percentage' ? 'percent' : 'dollar'" :required="true" />
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-end " v-if="!checkDisabled(rate)"> 
                                    <Button variant="outline-danger" size="md" @click="removeRate(index)" type="button">
                                        <SvgIcon name="trash" />
                                    </Button>
                                </div>

                            </div>
                            <div v-if="(rate.rate_type?.id === '1099 Regular' || rate.rate_type?.id === 'Payroll Regular') && rate.rate"
                                class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    <span class="font-bold">Note:</span>
                                    Overtime will be calculated at 1.5× the regular rate ({{ rate.rate * 1.5 }}).
                                </p>
                            </div>
                            <div v-if="rate.rate_type?.id === 'Payroll Slab'" class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    <span class="font-bold">Note:</span>
                                    Any hours worked beyond {{ rate.payroll_hours }} will be paid as 1099.
                                </p>
                            </div>
                        </div>
                        <div v-if="mode === 'create'">
                            <Button variant="outline" size="md" @click="addRate" type="button">
                                Add Rate
                            </Button>
                        </div>
                    </div>

                    <!-- Section 2: New Rate Requests (standalone - edit mode only) -->
                    <div v-if="mode === 'edit'" class="space-y-4">
                        <h6
                            class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            New Rate Requests
                        </h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            Add new rates here. These will be sent for approval before being applied.
                        </p>
                        <div v-for="(req, index) in form.employee_rate_requests" :key="'req-' + index"
                            class="p-4 rounded-lg border border-orange-200 dark:border-orange-800/50 bg-orange-50/50 dark:bg-orange-900/10 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <DynamicDropdown v-model="req.company" label="Company" resource="companies" display-name="name"
                                    placeholder="Select a company" :required="true" :disabled="requestDisabled"
                                    :error="errors[`employee_rate_requests.${index}.company_id`] ? errors[`employee_rate_requests.${index}.company_id`][0] : null" icon-left="building" />
    
                                <Input v-model="req.effective_date" label="Effective date" type="date"
                                    placeholder="Enter effective date"
                                    icon-left="calendar" :required="true" :disabled="requestDisabled" />
                                <Input v-model="req.till_date" label="Till Date" type="date"
                                    placeholder="Enter till date"
                                    icon-left="calendar" :disabled="requestDisabled" />
                                <div>
                                    <InputLabel :required="true">Role</InputLabel>
                                    <DynamicDropdown v-model="req.role" resource="employee-roles" display-name="name"
                                        :placeholder="`Select a role`" :removable="false"
                                        :required="true" :disabled="requestDisabled" />
                                </div>
                                <div>
                                    <InputLabel :required="true">Pay Type</InputLabel>
                                    <DynamicDropdown v-model="req.pay_type" :custom-options="payTypes" display-name="name"
                                        placeholder="Select pay type" :removable="false" :searchable="false"
                                        :required="true" :disabled="requestDisabled" />
                                </div>
                                <div v-if="can('employee-new-rate-request', 'create')">
                                    <InputLabel :required="true">Rate Type</InputLabel>
                                    <DynamicDropdown v-model="req.rate_type" :custom-options="rateTypes"
                                        display-name="name" placeholder="Select rate type" :removable="false"
                                        @change="handleRateTypeChange(req)"
                                        :searchable="false" :required="true" :disabled="requestDisabled || isDC" />
                                </div>
                                <Input v-if="req.rate_type?.id === 'Payroll Slab' || req.rate_type?.id === '1099 Slab' || req.rate_type?.id === 'Payroll 1099'"
                                    v-model="req.slab_first_hours" label="Slab First Hours" type="number"
                                    placeholder="Enter slab first hours"
                                    icon-left="clock" :required="true" :disabled="requestDisabled" />
                                <Input v-model="req.rate" label="Rate" type="number" placeholder="Enter rate" 
                                    icon-left="dollar" :required="true" :disabled="requestDisabled" />
                                <Input v-if="req.rate_type?.id === 'Payroll Slab' || req.rate_type?.id === '1099 Slab' || req.rate_type?.id === 'Payroll 1099' || req.rate_type?.id === '1099 1099'"
                                    v-model="req.slab_rest_rate" label="Slab Rest Rate" type="number"
                                    placeholder="Enter slab rest rate"
                                    icon-left="dollar" :required="true" :disabled="requestDisabled" />
    
                                <div v-if="req.rate_type?.id === 'Payroll Slab'">
                                    <InputLabel :required="true">Payroll Hours Type</InputLabel>
                                    <div class="flex items-start gap-2">
                                        <div class="w-[40px] flex-shrink-0">
                                            <DynamicDropdown v-model="req.payroll_hours_type"
                                                :custom-options="payrollHoursTypes" display-name="name"
                                                placeholder="%" :removable="false" :searchable="false"
                                                :required="true" :disabled="requestDisabled" />
                                        </div>
                                        <div class="flex-1">
                                            <Input v-model="req.payroll_hours" type="number"
                                                placeholder="Enter payroll hours"
                                                :disabled="requestDisabled" 
                                                :max="req.payroll_hours_type?.id === 'percentage' ? 100 : 9999999999"
                                                :icon-left="req.payroll_hours_type?.id === 'percentage' ? 'percent' : 'clock'" :required="true" />
                                        </div>
                                    </div>
                                </div>
                                <Input v-if="req.rate_type?.id === 'Payroll 1099'"
                                    v-model="req.payroll_rate" label="Payroll Rate" type="number"
                                    placeholder="Enter payroll rate"
                                    :disabled="requestDisabled" 
                                    icon-left="dollar" :required="true" />
                                
                                <Input v-if="req.rate_type?.id === '1099 1099'"
                                        v-model="req.ten99_rate" label="1099 Rate" type="number"
                                        placeholder="Enter payroll rate"
                                        :disabled="requestDisabled" 
                                        icon-left="dollar" :required="true" />
    
                                <div
                                    v-if="req.rate_type?.id === '1099 Regular' || req.rate_type?.id === '1099 Slab' || req.rate_type?.id === 'Payroll Slab' || req.rate_type?.id === 'Payroll 1099'">
                                    <InputLabel :required="true">Check Payment Type</InputLabel>
                                    <div class="flex items-start gap-2">
                                        <div class="w-[40px] flex-shrink-0">
                                            <DynamicDropdown v-model="req.check_payment_type"
                                                :custom-options="checkPaymentTypes" display-name="name"
                                                placeholder="%" :removable="false" :searchable="false"
                                                :required="true" :disabled="requestDisabled" />
                                        </div>
                                        <div class="flex-1">
                                            <Input v-model="req.check_payment_amount" type="number"
                                                placeholder="Enter amount"
                                                :disabled="requestDisabled" 
                                                :max="req.check_payment_type?.id === 'percentage' ? 100 : 9999999999"
                                                :icon-left="req.check_payment_type?.id === 'percentage' ? 'percent' : 'dollar'" :required="true" />
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-end">
                                    <Button variant="outline-danger" size="md" @click="removeRateRequest(index)" type="button">
                                        <SvgIcon name="trash" />
                                    </Button>
                                </div>
                            </div>
                            <div v-if="(req.rate_type?.id === '1099 Regular' || req.rate_type?.id === 'Payroll Regular') && req.rate"
                                class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    <span class="font-bold">Note:</span>
                                    Overtime will be calculated at 1.5× the regular rate ({{ req.rate * 1.5 }}).
                                </p>
                            </div>
                            <div v-if="req.rate_type?.id === 'Payroll Slab'" class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    <span class="font-bold">Note:</span>
                                    Any hours worked beyond {{ req.payroll_hours }} will be paid as 1099.
                                </p>
                            </div>
                        </div>
                        <div v-if="can('employee-new-rate-request', 'create')">
                            <Button variant="outline" size="md" @click="addRateRequest" type="button"
                                class="border-orange-300 text-orange-600 hover:bg-orange-50 dark:border-orange-700 dark:text-orange-400 dark:hover:bg-orange-900/20">
                                Add New Rate Request
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="isNew" class="space-y-4">
                    <h6
                        class="text-base font-semibold text-gray-800 dark:text-white mb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                        Employee Rate
                    </h6>
                    <div v-for="(rate, index) in [...form.employee_rates, ...form.employee_rate_requests]" :key="'onboarding-rate-' + index"
                        class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 mb-4 bg-white dark:bg-gray-800/50">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <DynamicDropdown v-model="rate.company" label="Company" resource="companies"
                                display-name="name" placeholder="Select a company" :required="true" :disabled="mode === 'create' ? false : true"
                                :error="errors.company ? errors.company[0] : null" icon-left="building" />
                            <Input v-if="mode === 'create'" v-model="rate.effective_date" label="Effective date" type="date"
                                placeholder="Enter effective date" :error="errors.date ? errors.date[0] : null"
                                :disabled="mode === 'create' ? false : true" icon-left="calendar" :required="true" />
                            <Input v-else :model-value="formatDate(rate.effective_date)" label="Effective date" type="date"
                                placeholder="Enter effective date" :error="errors.date ? errors.date[0] : null"
                                :disabled="mode === 'create' ? false : true" icon-left="calendar" :required="true" />
                            <div>
                                <InputLabel :required="true">Role</InputLabel>
                                <DynamicDropdown v-model="rate.role" resource="employee-roles" display-name="name"
                                    :placeholder="'Select a role'" :removable="false" :required="true" />
                            </div>
                            <Input v-model="rate.rate" label="Rate" type="number" placeholder="Enter rate"
                                :error="errors.rate ? errors.rate[0] : null" icon-left="dollar" :required="true" />
                        </div>
                    </div>
                    <Button variant="outline" size="md" @click="addRateRequest" type="button">
                        <SvgIcon name="plus" />
                        Add Rate
                    </Button>
                </div>


                

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button variant="outline-secondary" size="md" @click="cancel" type="button">
                        Cancel
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving"
                        v-if="mode === 'create' ? access.includes('create') : access.includes('update')">
                        Save
                    </Button>
                </div>
            </form>
        </Panel>
    </div>

    <!-- Loading State -->
    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading form..." centered />
    </div>

    <AlertBox
        v-model="showPayrollEmailConfirm"
        title="Send Onboarding Email"
        message="Do you want to send onboarding email to this payroll employee?"
        confirm-text="Okay"
        cancel-text="Cancel"
        @confirm="handlePayrollEmailConfirm"
        @cancel="handlePayrollEmailCancel"
    />
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useMessage } from '@/composables/useMessage'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import { isSSNValid, formatSSN, formatSSNAsYouType } from '@/utils/ssn'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import InputLabel from '@/components/ui/inputLabel.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { assignValidatedFile } from '@/utils/documentUpload'
import { formatDate } from '@/utils/date'
import AlertBox from '@/components/common/AlertBox.vue'
import { useRequest } from '@/services/api'
import { usePermission } from '@/composables/usePermission'

const route = useRoute()
const resource = route.meta?.resource || 'employee'
const authStore = useAuthStore()
const message = useMessage()
const path = route.path.includes('missing-profile-picture')
  ? 'onboarding/employee/missing-profile-picture'
  : route.path.includes('pending-i9-w4')
  ? 'onboarding/employee/pending-i9-w4'
  : route.path.includes('existing')
  ? 'onboarding/employee/existing'
  : route.path.includes('new')
  ? 'onboarding/employee/new'
  : 'employee'
const isNew = computed(() => route.path.includes('onboarding/employee/new'))
const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, path, 'employee')
const { can } = usePermission()
const isDC = computed(() => authStore.isDC)
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
const checkPaymentTypes = [
    {
        id: 'percentage',
        name: '%'
    },
    {
        id: 'fixed',
        name: '$'
    }
]
const payrollHoursTypes = [
    {
        id: 'fixed',
        name: 'HR'
    },
    {
        id: 'percentage',
        name: '%'
    }
]


const rateTypes = ref([
    {
        id: 'Payroll Regular',
        name: 'Payroll Regular'
    },
    {
        id: 'Payroll Slab',
        name: 'Payroll Slab'
    },
    {
        id: 'Payroll 1099',
        name: 'Payroll 1099'
    },
    {
        id: '1099 Regular',
        name: '1099 Regular'
    },
    {
        id: '1099 Slab',
        name: '1099 Slab'
    },
    {
        id: '1099 1099',
        name: '1099 1099'
    }
])
const payrollTypes = [
    {
        id: 'Direct Deposit',
        name: 'Direct Deposit'
    },
    {
        id: 'Print on site',
        name: 'Print on site'
    }
]

const employeePaymentType = ref('payroll')
const showPayrollEmailConfirm = ref(false)
const confirmPayrollEmailResolver = ref(null)

const isPayrollEmployee = computed(() => {
    if (!isNew.value) return false
    return employeePaymentType.value === 'payroll'
})

function is1099StyleRate(rate) {
    if (!rate?.rate_type?.id) return false
    const id = rate.rate_type.id
    return id === '1099 Regular' || id === '1099 Slab' || id === '1099 1099' || id === 'Payroll 1099'
}

function applyOnboardingPaymentType(type) {
    if (!isNew.value) return
    const rates = form.value?.employee_rates?.concat(form.value?.employee_rate_requests ?? []) || []
    if (!rates?.length) return
    const hr = payTypes.find((t) => t.id === 'HR') || payTypes[0]
    const payrollRegular = rateTypes.value.find((t) => t.id === 'Payroll Regular')
    const ten99Regular = rateTypes.value.find((t) => t.id === '1099 Regular')
    const directDeposit = payrollTypes.find((t) => t.id === 'Direct Deposit')
    const pct = checkPaymentTypes.find((t) => t.id === 'percentage') || checkPaymentTypes[0]
    
    rates.forEach((rate) => {
        rate.pay_type = hr
        if (type === 'payroll') {
            rate.rate_type = payrollRegular
            rate.payroll_type = directDeposit || null
            rate.check_payment_type = null
            rate.check_payment_amount = null
        } else {
            rate.rate_type = ten99Regular
            rate.payroll_type = null
            rate.check_payment_type = pct
            rate.check_payment_amount = 100
        }
    })
}

// const showOnboardingEmail = computed(() => {
//     if (!isNew.value) return false
//     const rate = form.value?.employee_rates?.[0]
//     if (mode.value === 'create') {
//         return employeePaymentType.value === 'payroll'
//     }
//     return !is1099StyleRate(rate)
// })

const requestDisabled = computed(() => {
    return !can('employee-new-rate-request', 'create')
})

const profilePictureRef = ref(null)
const profilePictureUploading = ref(false)
const uploadedPreviewUrl = ref(null)

const profilePicturePreview = computed(() => {
    if (uploadedPreviewUrl.value) return uploadedPreviewUrl.value
    if (form.value?.profile_picture_url) return form.value.profile_picture_url
    if (form.value?.profile_picture) return `/storage/${form.value.profile_picture}`
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
        const response = await axios.post('/api/employee/upload-profile-picture', formData)
        if (response?.data?.path) {
            uploadedPreviewUrl.value = response.data.url || `/storage/${response.data.path}`
            form.value.profile_picture = response.data.path
            if (response.data.url) form.value.profile_picture_url = response.data.url
        }
    } catch (e) {
        message.error(e?.response?.data?.message || 'Failed to upload profile picture')
    } finally {
        profilePictureUploading.value = false
        if (profilePictureRef.value) profilePictureRef.value.value = ''
    }
}

const tillDate = ref([])
onMounted(() => {
    if(isDC.value) {
        rateTypes.value.splice(1, 5)
    }
})

// Shallow watch - only fires when form is replaced (e.g. API load), not on nested edits. Prevents recursive loop.
watch(() => form.value, (newVal) => {
    if (!newVal) return

    if(isNew.value){

        let is1099 = form.value.employee_rates_requests?.some(req => req.rate_type === '1099 Regular' || req.rate_type === '1099 Slab' || req.rate_type === '1099 1099' || req.rate_type === 'Payroll 1099');
        employeePaymentType.value = is1099 ? '1099' : 'payroll';
    }
    if(!newVal.workgroup){
        newVal.workgroup = authStore.company.workgroup;
        newVal.workgroup_id = authStore.company.workgroup.id;
    }
    // Skip if already transformed (pay_type is object from dropdown)
    const firstRate = newVal.employee_rates?.[0]
    if (firstRate && typeof firstRate.pay_type === 'object' && firstRate.pay_type?.id) return

    // Keep employee_rates and employee_rate_requests separate
    if (newVal.employee_rates) {
        // For rates with pending update requests, show request data for editing
        form.value.employee_rates = newVal.employee_rates.map(rate => {
            const hasPending = rate.employee_rates_request?.status === 'pending'
            const base = hasPending
                ? { ...rate.employee_rates_request, id: rate.id, employee_rate_id: rate.id,rate_request_id: rate.employee_rates_request.id }
                : { ...rate }
            return base
        })
    }

    // Standalone new rate requests (employee_rate_id = 0)
    if (newVal.employee_rates_requests?.length) {
        form.value.employee_rate_requests = newVal.employee_rates_requests.map(req => ({
            ...req,
            pay_type: req.pay_type ? payTypes.find(t => t.id === req.pay_type) || payTypes[0] : payTypes[0],
            rate_type: req.rate_type ? rateTypes.value.find(t => t.id === req.rate_type) || rateTypes.value[0] : rateTypes.value[0],
            check_payment_type: req.check_payment_type ? checkPaymentTypes.find(t => t.id === req.check_payment_type) || checkPaymentTypes[0] : checkPaymentTypes[0],
            payroll_hours_type: req.payroll_hours_type ? payrollHoursTypes.find(t => t.id === req.payroll_hours_type) || payrollHoursTypes[0] : payrollHoursTypes[0],
            payroll_type: req.payroll_type ? payrollTypes.find(t => t.id === req.payroll_type) || null : null,
        }))
    } else if (mode.value === 'edit') {
        form.value.employee_rate_requests = []
    }

    // Transform dropdown values for employee_rates
    tillDate.value = form.value.employee_rates?.map(r => r.till_date && (r.status == 'approved')) ?? []
    form.value.employee_rates?.forEach((rate) => {
        rate.pay_type = rate.pay_type ? payTypes.find(t => t.id === rate.pay_type) : null
        rate.rate_type = rate.rate_type ? rateTypes.value.find(t => t.id === rate.rate_type) : null
        rate.check_payment_type = rate.check_payment_type ? checkPaymentTypes.find(t => t.id === rate.check_payment_type) : checkPaymentTypes[0]
        rate.payroll_hours_type = rate.payroll_hours_type ? payrollHoursTypes.find(t => t.id === rate.payroll_hours_type) : payrollHoursTypes[0]
        rate.payroll_type = rate.payroll_type ? payrollTypes.find(t => t.id === rate.payroll_type) : null
    })

    if (!newVal.employee_rates?.length && !newVal.employee_rate_requests?.length && (can('employee-new-rate-request', 'create') || route.path.includes('new'))) {
        form.value.employee_rate_requests = [
            {
                role: null,
                role_id: null,
                pay_type: payTypes[0],
                rate_type: rateTypes.value[0],
                payroll_type: null,
                rate: null,
                payroll_rate: null,
                slab_first_hours: null,
                slab_rest_rate: null,
                payroll_hours: null,
                payroll_hours_type: payrollHoursTypes[0],
                payroll_type: payrollTypes[0],
                effective_date: new Date().toISOString().split('T')[0],
                till_date: null,
                check_payment_type: checkPaymentTypes[0],
                check_payment_amount: 0,
                employee_rate_id: null,
                company: authStore.company,
            }
        ]
        if (isNew.value && mode.value === 'create') {
            applyOnboardingPaymentType(employeePaymentType.value)
        }
    }
    if(!form.value.employee_rate_requests?.length) {
        form.value.employee_rate_requests = []
    }
    if(!form.value.employee_rates?.length) {
        form.value.employee_rates = []
    }
    if (!Array.isArray(form.value.aliases)) {
        form.value.aliases = []
    }
})

watch(employeePaymentType, (type) => {
    if (type === '1099' && form.value) {
        form.value.email = ''
    }
    applyOnboardingPaymentType(type)
})

watch(() => form.value.active, (newVal) => {
    form.value.active = newVal ? true : false
})

const handleRateTypeChange = (req) => {
    if( req.rate_type?.id === 'Payroll Regular' || req.rate_type?.id === '1099 Regular' ) {
        req.slab_first_hours = null
        req.slab_rest_rate = null
        req.payroll_hours = null
        req.payroll_hours_type = null
    }

    if( req.rate_type?.id === 'Payroll Regular' ) {
        req.check_payment_type = null
        req.check_payment_amount = null
        req.payroll_type = payrollTypes[0];
    }else if( req.rate_type?.id === '1099 Slab' ) {
        req.payroll_hours = null
        req.payroll_hours_type = null
        req.payroll_type = null;
    }
}

const handleSave = async () => {
    if (form.value.ssn && !isSSNValid(form.value.ssn)) {
        errors.value.ssn = ['Please enter a valid 9-digit SSN (e.g. XXX-XX-XXXX or 9 digits).']
        return
    }
    if (errors.value.ssn) delete errors.value.ssn
    
    // Validate till_date is not greater than effective_date
    const allRates = [...(form.value.employee_rates || []), ...(form.value.employee_rate_requests || [])]
    for (let i = 0; i < allRates.length; i++) {
        const rate = allRates[i]
        if (rate.till_date && rate.effective_date) {
            const tillDate = new Date(rate.till_date)
            const effectiveDate = new Date(rate.effective_date)
            if (tillDate < effectiveDate) {
                message.error('Till date cannot be earlier than effective date')
                return
            }
        }
    }
    
    const employeeRatesRequests = form.value.employee_rates.filter(rate => rate.employee_rate_id);
    let nonRole =0;

 

    try {
        if (isPayrollEmployee.value && mode.value === 'create') {
            const shouldContinue = await new Promise((resolve) => {
                confirmPayrollEmailResolver.value = resolve
                showPayrollEmailConfirm.value = true
            })
            if (!shouldContinue) {
                return
            }
        }

        let obj = {
            ...form.value,
            company_id: form.value.company?.id,
            employee_rates: form.value.employee_rates.filter(rate => !rate.employee_rate_id).map(rate => {
                let payroll_type = rate.payroll_type?.id;
                if(!payroll_type && (rate.rate_type?.id == 'Payroll Slab' || rate.rate_type?.id == 'Payroll Regular' || rate.rate_type?.id == 'Payroll 1099')){
                    payroll_type = 'Print on site';
                }else if(rate.rate_type?.id == '1099 Regular' || rate.rate_type?.id == '1099 Slab'){
                    payroll_type = null;
                }
                if(!rate.role?.id) {
                    nonRole++;
                }
                return {
                    id: rate.id ?? null,
                    role_id: rate.role?.id,
                    pay_type: rate.pay_type?.id,
                    rate_type: rate.rate_type?.id,
                    ten99_rate: rate.ten99_rate,
                    payroll_type: payroll_type,
                    rate: rate.rate,
                    payroll_rate: rate.payroll_rate,
                    slab_first_hours: rate.slab_first_hours,
                    slab_rest_rate: rate.slab_rest_rate,
                    payroll_hours: rate.payroll_hours,
                    effective_date: rate.effective_date,
                    till_date: rate.till_date,
                    check_payment_type: rate.check_payment_type?.id,
                    check_payment_amount: rate.check_payment_amount,
                    employee_rate_id: rate.employee_rate_id ? rate.employee_rate_id : null,
                    company_id: rate.company?.id,
                    payroll_hours_type: rate.payroll_hours_type?.id,
                };
            }),
            employee_rate_requests: (form.value.employee_rate_requests || [])
                .filter(req => req.role?.id && req.company?.id && req.rate != null)
                .map(req => {
                    let payroll_type = req.payroll_type?.id;
                    if(!payroll_type && (req.rate_type?.id == 'Payroll Slab' || req.rate_type?.id == 'Payroll Regular' || req.rate_type?.id == 'Payroll 1099')){
                        payroll_type = 'Print on site';
                    }else if(req.rate_type?.id == '1099 Regular' || req.rate_type?.id == '1099 Slab'){
                        payroll_type = null;
                    }
                    if(!req.role?.id) {
                    console.log('req', req);

                        nonRole++;
                    }
                    return {
                    id: req.id ?? null,
                    role_id: req.role?.id,
                    pay_type: req.pay_type?.id,
                    rate_type: req.rate_type?.id,
                    ten99_rate: req.ten99_rate,
                    payroll_type: payroll_type,
                    rate: req.rate,
                    payroll_rate: req.payroll_rate,
                    slab_first_hours: req.slab_first_hours,
                    slab_rest_rate: req.slab_rest_rate,
                    payroll_hours: req.payroll_hours,
                    effective_date: req.effective_date,
                    till_date: req.till_date,
                    check_payment_type: req.check_payment_type?.id,
                    check_payment_amount: req.check_payment_amount ?? 0,
                    company_id: req.company?.id,
                    payroll_hours_type: req.payroll_hours_type?.id,
                }
                })
        }
            obj.employee_rate_requests = [...obj.employee_rate_requests, ...employeeRatesRequests.map(req => {
                let payroll_type = req.payroll_type?.id;
                if(!payroll_type && (req.rate_type?.id == 'Payroll Slab' || req.rate_type?.id == 'Payroll Regular' || req.rate_type?.id == 'Payroll 1099')){
                    payroll_type = 'Print on site';
                }else if(req.rate_type?.id == '1099 Regular' || req.rate_type?.id == '1099 Slab'){
                    payroll_type = null;
                }
                if(!req.role?.id) {
                    nonRole++;
                }
                return {
                    id: req.rate_request_id ?? null,
                    role_id: req.role?.id,
                    pay_type: req.pay_type?.id,
                    rate_type: req.rate_type?.id,
                    payroll_type: payroll_type,
                    rate: req.rate,
                    payroll_rate: req.payroll_rate,
                    slab_first_hours: req.slab_first_hours,
                    slab_rest_rate: req.slab_rest_rate,
                    payroll_hours: req.payroll_hours,
                    effective_date: req.effective_date,
                    till_date: req.till_date,
                    check_payment_type: req.check_payment_type?.id,
                    check_payment_amount: req.check_payment_amount ?? 0,
                    company_id: req.company?.id,
                    payroll_hours_type: req.payroll_hours_type?.id,
                }
            })]
        if(nonRole > 0) {
            return message.error('Please select a role for the employee rates and requests')
        }
        delete obj.company
        if (obj.ssn && isSSNValid(obj.ssn)) obj.ssn = formatSSN(obj.ssn)
        const saveResponse = await save(obj)
        if (isPayrollEmployee.value && obj.email && saveResponse?.id &&  !form.value.mail_sent ) {
            try {
                await useRequest('post', 'onboarding/send-mail-to-employee', {
                    employee_id: saveResponse.id,
                    email: obj.email,
                })
            } catch (mailError) {
                message.error(mailError?.response?.data?.message || 'Employee created, but onboarding email failed to send.')
            }
        }
    } catch (error) {
        console.error(error)
    }
}

const handlePayrollEmailConfirm = () => {
    if (confirmPayrollEmailResolver.value) {
        confirmPayrollEmailResolver.value(true)
        confirmPayrollEmailResolver.value = null
    }
}

const handlePayrollEmailCancel = () => {
    if (confirmPayrollEmailResolver.value) {
        confirmPayrollEmailResolver.value(false)
        confirmPayrollEmailResolver.value = null
    }
}
const addRate = () => {
    form.value.employee_rates.push(getDefaultRateRequest())
}
const removeRate = (index) => {
    form.value.employee_rates.splice(index, 1)
}

const getDefaultRateRequest = () => ({
    id: null,
    role: null,
    role_id: null,
    pay_type: payTypes[0],
    rate_type: rateTypes.value[0],
    ten99_rate: null,
    payroll_type: null,
    rate: null,
    payroll_rate: null,
    slab_first_hours: null,
    slab_rest_rate: null,
    payroll_hours: null,
    payroll_hours_type: payrollHoursTypes[0],
    effective_date: new Date().toISOString().split('T')[0],
    till_date: null,
    check_payment_type: checkPaymentTypes[0],
    check_payment_amount: 0,
    company: authStore.company,
})

const addRateRequest = () => {
    if (!form.value.employee_rate_requests) {
        form.value.employee_rate_requests = []
    }
    form.value.employee_rate_requests.push(getDefaultRateRequest())
}

const removeRateRequest = (index) => {
    form.value.employee_rate_requests.splice(index, 1)
}

const addAlias = () => {
    if (!Array.isArray(form.value.aliases)) {
        form.value.aliases = []
    }
    form.value.aliases.push({
        alias_employee_id: '',
        alias_name: '',
    })
}

const removeAlias = (index) => {
    form.value.aliases.splice(index, 1)
}

const handleSSNInput = (event) => {
    const raw = event.target.value
    const formatted = formatSSNAsYouType(raw)
    if (formatted !== raw) {
        form.value.ssn = formatted
    }
   
    if (form.value.ssn && !isSSNValid(form.value.ssn)) {
        errors.value.ssn = ['Please enter a valid 9-digit SSN.']
    } else {
        errors.value.ssn = null
    }
}
const checkDisabled = (item) => {
    if(!can('employee-new-rate-request', 'create')){
        return true
    }
    return false


    // let request = item.employee_rates_request;

    // if(item.id && (request?.status == 'approved' || !request || request?.do_approved == true)){
    //     return true
    // }else{
    //     return false
    // }

    const lastReviewDate = new Date(form.value.lastReviewDate)
    const effectiveDate = new Date(item.effective_date)
    if((form.value.lastReviewDate && effectiveDate < lastReviewDate) || request?.status == 'approved'){
        return true
    }
    
    if(mode.value === 'edit' && item.id){
        const date = new Date(item.effective_date)
        const today = new Date()
        if((today - date) / (1000*60*60*24) > 7 || request?.status == 'approved'){
            return true
        }
    }
    return false
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
