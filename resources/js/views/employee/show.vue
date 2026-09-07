<template>
  <div v-if="displayShow" class="employee-show space-y-3">
    <!-- Basic Information Panel -->
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Employee Details</h5>
          <div v-if="!embedded" class="flex items-center gap-2">
            <Button
              icon-left="arrow-left"
              icon-size="sm"
              variant="secondary"
              size="xs"
              :to="`/${path}`"
            >
            </Button>
            <Button
              icon-left="files"
              icon-size="sm"
              variant="secondary"
              size="xs"
              v-if="access.includes('show')"
              :to="`/${path}/documents/${displayModel.id}`"
            >
            </Button>
            <Button
            
              icon-left="edit"
              icon-size="sm"
              variant="primary"
              size="xs"
              v-if="access.includes('update')"
              :to="`/${path}/${displayModel.id}/edit`"
            >
            </Button>
            <Button
              icon-left="trash"
              icon-size="sm"
              variant="danger"
              size="xs"
              @click="handleDelete"
              v-if="access.includes('delete')"
            >
            </Button>
          </div>
        </div>
      </template>

      <div class="space-y-6">
        <!-- Basic Information -->
        <div>
          <h6
            class="text-base font-semibold text-gray-800 dark:text-white mb-4"
          >
            Basic Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Employee ID" :value="displayModel.employee_id" />
            <Label label="POS Name" :value="displayModel.pos_name" />
            <!-- <Label label="Company" :value="displayModel.company?.name" /> -->
            <Label label="Hire Date" :value="formatDate(displayModel.hire_date)" />
            <Label label="SSN" :value="displayModel.ssn" />
            <Label label="Workgroup" :value="displayModel.workgroup?.name" />

            <div v-if="isNew" class="md:col-span-2">
              <Label label="Employee Payment Type" :value="displayOnboardingPaymentType" />
            </div>
            <Label
              v-if="isNew && showOnboardingEmail"
              label="Email"
              :value="displayModel.email"
            />

            <!-- Status (matches full employee form: not shown on onboarding new path) -->
            <Label v-if="!isNew" label="Status">
              <span
                :class="[
                  'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
                  displayModel.active
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                ]"
              >
                <SvgIcon
                  :name="displayModel.active ? 'check' : 'x'"
                  size="xs"
                  class="mr-1"
                />
                {{ displayModel.active ? "Active" : "Inactive" }}
              </span>
            </Label>

            <div v-if="!isNew" class="md:col-span-2 lg:col-span-4">
              <h6 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">
                Profile Picture
              </h6>
              <Label label="Profile Picture">
                <img
                  v-if="displayModel.profile_picture_url"
                  :src="displayModel.profile_picture_url"
                  alt="Profile Picture"
                  title="Open full size in new tab"
                  class="h-20 w-20 object-cover border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:opacity-90"
                  @click="openProfilePictureFullSize(displayModel.profile_picture_url)"
                />
                <span v-else class="text-sm text-gray-500 dark:text-gray-400">—</span>
              </Label>
            </div>
          </div>
        </div>

        <!-- Employee Names -->
        <div v-if="!isNew" class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6
            class="text-base font-semibold text-gray-800 dark:text-white mb-4"
          >
            Employee Names
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <Label label="First Name" :value="displayModel.first_name" />
            <Label label="Middle Name" :value="displayModel.middle_name" />
            <Label label="Last Name" :value="displayModel.last_name" />
            <Label label="Check Name" :value="displayModel.check_name" />
          </div>
        </div>

        <!-- Employee Aliases -->
        <div v-if="!isNew" class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6
            class="text-base font-semibold text-gray-800 dark:text-white mb-4"
          >
            Employee Aliases
          </h6>
          <div v-if="displayModel.aliases?.length" class="space-y-3">
            <div
              v-for="(alias, index) in displayModel.aliases"
              :key="'alias-' + index"
              class="grid grid-cols-1 md:grid-cols-4 gap-6"
            >
              <Label label="Alias Employee ID" :value="alias.alias_employee_id" />
              <Label label="Alias Name" :value="alias.alias_name" />
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No aliases</p>
        </div>

        <!-- Contact Information -->
        <div v-if="!isNew" class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <h6
            class="text-base font-semibold text-gray-800 dark:text-white mb-4"
          >
            Contact Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Label label="Phone" :value="displayModel.phone" />
            <Label label="Email" :value="displayModel.email" />
            <Label label="Date of Birth" :value="formatDate(displayModel.dob)" />
            <Label label="Termination Date" :value="displayModel.termination_date ? formatDate(displayModel.termination_date) : '—'" />
            <Label label="Street Address" :value="displayModel.street" />
            <Label label="City" :value="displayModel.city" />
            <Label label="State" :value="displayModel.state" />
            <Label label="ZIP Code" :value="displayModel.zip" />
            <Label
              label="Emergency Contact Name"
              :value="displayModel.emergency_contact_name"
            />
            <Label
              label="Emergency Contact Phone"
              :value="displayModel.emergency_contact_phone"
            />
            <Label
              label="Emergency Contact Relationship"
              :value="displayModel.emergency_contact_relationship"
            />
          </div>
        </div>
      </div>
    </Panel>


    <!-- Onboarding: simplified Employee Rate (matches form.vue) -->
    <Panel
      :divider="true"
      v-if="isNew && onboardingDisplayRates.length > 0"
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0">Employee Rate</h5>
      </template>
      <div class="space-y-3">
        <div
          v-for="(rate, index) in onboardingDisplayRates"
          :key="'ob-rate-' + index"
          class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
          :class="{ 'mb-4': index !== onboardingDisplayRates.length - 1 }"
        >
          <div class="flex items-center justify-between mb-4">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 !mb-0">
              Rate {{ index + 1 }}
            </h6>
            <div
              v-if="!embedded && (rate.employee_rates_request?.status === 'pending' || rate.status === 'pending') && !isNew"
              class="flex items-center gap-2 cursor-pointer"
              @click="handleModal(rate)"
            >
              <div
                class="flex items-center gap-2 px-3 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-lg text-sm font-medium"
              >
                <SvgIcon name="eye" size="md" class="text-orange-500 dark:text-orange-400" />
                <span>Pending Approval</span>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Label label="Company" :value="rate.company?.name" />
            <Label label="Effective date" :value="formatDate(rate.effective_date)" />
            <Label
              label="Role"
              :value="
                rate.role?.name
                  ? rate.role?.code + ' - ' + rate.role?.name
                  : rate.role_id || '—'
              "
            />
            <Label label="Rate" :value="formatCurrency(rate.rate)" />
          </div>
        </div>
      </div>
    </Panel>

    <!-- Employee Roles & Rates Panel (full detail, non-onboarding) -->
    <Panel
      :divider="true"
      v-if="!isNew && displayModel.employee_rates && displayModel.employee_rates.length > 0"
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0">Employee Roles & Rates</h5>
      </template>
      <div class="space-y-3 overflow-y-auto max-h-[700px]">
        <div
          v-for="(rate, index) in displayModel.employee_rates"
          :key="index"
          class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
          :class="{ 'mb-4': index !== displayModel.employee_rates.length - 1 }"
        >
          <div class="flex items-center justify-between mb-4">
            <h6
              class="text-sm font-semibold text-gray-700 dark:text-gray-300 !mb-0"
            >
              Role {{ index + 1 }}
            </h6>
            <div class="flex items-center gap-4">
              <div
                v-if="!embedded && rate.employee_rates_request?.status === 'pending'"
                class="flex items-center gap-2 cursor-pointer"
                @click="handleModal(rate)"
              >
                <div
                  class="flex items-center gap-2 px-3 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-lg text-sm font-medium"
                >
                  <div>
                    <SvgIcon
                      name="eye"
                      size="md"
                      class="text-orange-500 dark:text-orange-400"
                    />
                  </div>
                  <span>Pending Approval</span>
                </div>
              </div>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                Effective: {{ formatDate(rate.effective_date) }}
                <span v-if="rate.till_date">
                  - {{ formatDate(rate.till_date) }}
                </span>
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <Label label="Company" :value="rate.company?.name" />
            <Label
              label="Role"
              :value="
                rate.role?.name
                  ? rate.role?.code + ' - ' + rate.role?.name
                  : rate.role_id || '-'
              "
            />
            <Label label="Pay Type" :value="rate.pay_type" />
            <Label label="Rate Type" :value="rate.rate_type" v-if="can('employee-rate-request', 'approve')" />
            <Label label="Payroll Type" v-if="rate.rate_type === 'Payroll Regular' || rate.rate_type === 'Payroll Slab' || rate.rate_type === 'Payroll 1099'" :value="rate.payroll_type" />
            <Label
              v-if="
                rate.rate_type === 'Payroll Slab' || rate.rate_type === 'Payroll 1099' || rate.rate_type === '1099 1099' ||
                rate.rate_type === '1099 Slab'
              "
              label="Slab First Hours"
              :value="rate.slab_first_hours"
            />
            <Label label="Rate" :value="formatCurrency(rate.rate)" />
            <Label
              v-if="
                rate.rate_type === 'Payroll Slab' || rate.rate_type === 'Payroll 1099' || rate.rate_type === '1099 1099' ||
                rate.rate_type === '1099 Slab'
              "
              label="Slab Rest Rate"
              :value="formatCurrency(rate.slab_rest_rate)"
            />
            <Label v-if="rate.rate_type === 'Payroll 1099'"
              label="Payroll Rate"
              :value="formatCurrency(rate.payroll_rate)"
            />
            <Label v-if="rate.rate_type === '1099 1099'"
              label="1099 Rate"
              :value="formatCurrency(rate.ten99_rate)"
            />
            <Label
              v-if="rate.rate_type === 'Payroll Slab'"
              label="Payroll Hours"
              :value="rate.payroll_hours ? ( rate.payroll_hours + ' ' + (rate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-'"
            />
            <Label
              label="Check Payment"
              v-if="showsCheckPaymentForRateType(rate.rate_type)"
              :value="
                rate.check_payment_type === 'percentage'
                  ? `${rate.check_payment_amount}%`
                  : formatCurrency(rate.check_payment_amount)
              "
            />
            <template v-if="rate.employee_rates_request?.do_approved">
              <Label label="DO Approved By" :value="rate.employee_rates_request.do_approved_by?.name" />
              <Label label="DO Approved At" :value="formatDateTime(rate.employee_rates_request.do_approved_at)" />
            </template>
            <template v-if="rate.employee_rates_request?.hr_approved">
              <Label label="HR Approved By" :value="rate.employee_rates_request.hr_approved_by?.name" />
              <Label label="HR Approved At" :value="formatDateTime(rate.employee_rates_request.hr_approved_at)" />
            </template>
            <template v-if="rate.employee_rates_request?.admin_approved">
              <Label label="Admin Approved By" :value="rate.employee_rates_request.admin_approved_by?.name" />
              <Label label="Admin Approved At" :value="formatDateTime(rate.employee_rates_request.admin_approved_at)" />
            </template>
          </div>

          <!-- Rate Notes -->
         

          <div
              v-if="rate.rate_type === 'Payroll Slab' && rate.payroll_hours"
              class="mt-3"
            >
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-semibold">Note:</span>
                Any hours worked beyond {{ rate.payroll_hours ? ( rate.payroll_hours + ' ' + (rate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }} will be paid as
                1099.
              </p>
            </div>
            <div
              v-if="
                (rate.rate_type === '1099 Regular' ||
                  rate.rate_type === 'Payroll Regular') &&
                rate.rate
              "
              class="mt-3"
            >
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-semibold">Note:</span>
                Overtime will be calculated at 1.5× the regular rate (${{
                  (rate.rate * 1.5).toFixed(2)
                }}).
              </p>
            </div>
        </div>
      </div>
    </Panel>

    <Panel
      :divider="true"
      v-if="
        !isNew &&
        displayModel.employee_rates_requests &&
        displayModel.employee_rates_requests.length > 0
      "
    >
      <template #header>
        <h5 class="text-xl font-bold !mb-0 ">Employee Roles & Rates Requests</h5>
      </template>

      <div class="space-y-3">
        <div
          v-for="(rate, index) in displayModel.employee_rates_requests"
          :key="index"
          class="p-4 rounded-lg bg-orange-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
          :class="{
            'mb-4': index !== displayModel.employee_rates_requests.length - 1,
          }"
        >
          <div class="flex items-center justify-between mb-4">
            <h6
              class="text-sm font-semibold text-gray-700 dark:text-gray-300 !mb-0"
            >
              Role {{ index + 1 }}
            </h6>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <Label label="Effective Date" :value="formatDate(rate.effective_date)" />
            <Label label="Till Date" :value="rate.till_date ? formatDate(rate.till_date) : '-' " />
            <Label label="Company" :value="rate.company?.name" />
            <Label
              label="Role"
              :value="
                rate.role?.name
                  ? rate.role?.code + ' - ' + rate.role?.name
                  : rate.role_id || '-'
              "
            />
            <Label label="Pay Type" :value="rate.pay_type" />
            <Label label="Rate Type" :value="rate.rate_type" v-if="can('employee-rate-request', 'approve')" />
            <Label label="Payroll Type" v-if="rate.rate_type === 'Payroll Regular' || rate.rate_type === 'Payroll Slab' || rate.rate_type === 'Payroll 1099'" :value="rate.payroll_type" />
            <Label
              v-if="
                rate.rate_type === 'Payroll Slab' ||
                rate.rate_type === '1099 Slab' || rate.rate_type === 'Payroll 1099' || rate.rate_type === '1099 1099'
              "
              label="Slab First Hours"
              :value="rate.slab_first_hours"
            />
            <Label label="Rate" :value="formatCurrency(rate.rate)" />
            <Label
              v-if="
                rate.rate_type === 'Payroll Slab' ||
                rate.rate_type === '1099 Slab' || rate.rate_type === 'Payroll 1099' || rate.rate_type === '1099 1099'
              "
              label="Slab Rest Rate"
              :value="formatCurrency(rate.slab_rest_rate)"
            />
            <Label v-if="rate.rate_type === 'Payroll 1099'"
              label="Payroll Rate"
              :value="formatCurrency(rate.payroll_rate)"
            />
            <Label v-if="rate.rate_type === '1099 1099'"
              label="1099 Rate"
              :value="formatCurrency(rate.ten99_rate)"
            />
            <Label
              v-if="rate.rate_type === 'Payroll Slab'"
              label="Payroll Hours"
              :value="rate.payroll_hours ? ( rate.payroll_hours + ' ' + (rate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-'"
            />
            <Label
              label="Check Payment"
              v-if="showsCheckPaymentForRateType(rate.rate_type)"
              :value="
                rate.check_payment_type === 'percentage'
                  ? `${rate.check_payment_amount}%`
                  : formatCurrency(rate.check_payment_amount)
              "
            />
          </div>

          <!-- Rate Notes -->
          <div
            v-if="
              (rate.rate_type === '1099 Regular' ||
                rate.rate_type === 'Payroll Regular') &&
              rate.rate
            "
            class="mt-3"
          >
            <p class="text-sm text-gray-600 dark:text-gray-400">
              <span class="font-semibold">Note:</span>
              Overtime will be calculated at 1.5× the regular rate (${{
                (rate.rate * 1.5).toFixed(2)
              }}).
            </p>
          </div>
          <div
            v-if="rate.rate_type === 'Payroll Slab' && rate.payroll_hours"
            class="mt-3"
          >
            <p class="text-sm text-gray-600 dark:text-gray-400">
              <span class="font-semibold">Note:</span>
              Any hours worked beyond {{ rate.payroll_hours ? ( rate.payroll_hours + ' ' + (rate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }} will be paid as
              1099.
            </p>
          </div>
        </div>
      </div>
    </Panel>

    <!-- Empty State for Roles -->
    <Panel
      :divider="true"
      v-if="
        !onboardingDisplayRates.length &&
        !displayModel.employee_rates_requests?.length &&
        !displayModel.employee_rates?.length
      "
    >
      <div class="text-center py-8">
        <p class="text-gray-500 dark:text-gray-400">
          No employee roles defined
        </p>
      </div>
    </Panel>
    <PayrollInfo :employee-id="displayModel.id" v-if="displayModel.id  && can('payroll-report', 'index')" />
    <EmployeeHours v-if="!embedded && can('employee-hour', 'index')" />

        
  </div>

  <!-- Loading State (only when standalone, not when embedded with employee prop) -->
  <div v-else-if="!employeeProp" class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading employee details..." centered />
  </div>


  <Modal :model-value="showModal" @close="showModal = false" size="2xl">
    <template #header>
      <h5 class="text-xl font-bold !mb-0">Employee Rate Request</h5>
    </template>
    <div class="space-y-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <Label label="Role">
          <div v-if="hasValueChanged(modalRate.role_id, modalRate.old_rate?.role_id)">

            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.role?.name ? modalRate.old_rate?.role?.code + ' - ' + modalRate.old_rate?.role?.name : modalRate.old_rate?.role_id || '-' }}
            </div>
            <div class="text-orange-600 dark:text-orange-400 font-semibold">
              {{ modalRate.role?.name ? modalRate.role?.code + ' - ' + modalRate.role?.name : modalRate.role_id || '-' }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.role?.name ? modalRate.role?.code + ' - ' + modalRate.role?.name : modalRate.role_id || '-' }}
          </span>
      </Label>
      <Label label="Effective Date">
          <div v-if="hasValueChanged(modalRate.effective_date, modalRate.old_rate?.effective_date)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ formatDate(modalRate.old_rate?.effective_date) }}
            </div>
            <div class="text-orange-600 dark:text-orange-400 font-semibold">
              {{ formatDate(modalRate.effective_date) }}
            </div>
          </div>
          <span v-else>
            {{ formatDate(modalRate.effective_date) }}
          </span>
      </Label>
      <Label label="Till Date">
          <div v-if="hasValueChanged(modalRate.till_date, modalRate.old_rate?.till_date)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.till_date ? formatDate(modalRate.old_rate?.till_date) : '-' }}
            </div>
            <div class="text-orange-600 dark:text-orange-400 font-semibold">
              {{ modalRate.till_date ? formatDate(modalRate.till_date) : '-' }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.till_date ? formatDate(modalRate.till_date) : '-' }}
          </span>
      </Label>
      <Label label="Pay Type">
          <div v-if="hasValueChanged(modalRate.pay_type, modalRate.old_rate?.pay_type)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.pay_type }}
            </div>
            <div class="text-orange-600 dark:text-orange-400 font-semibold">
              {{ modalRate.pay_type }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.pay_type }}
          </span>
      </Label>
      <Label label="Rate Type" v-if="can('employee-rate-request', 'approve')">
          <div v-if="hasValueChanged(modalRate.rate_type, modalRate.old_rate?.rate_type)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.rate_type }}
            </div>
            <div class="text-orange-600 dark:text-orange-400 font-semibold">
              {{ modalRate.rate_type }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.rate_type }}
          </span>
      </Label>
      <Label label="Payroll Type" v-if="modalRate.rate_type === 'Payroll Regular' || modalRate.rate_type === 'Payroll Slab' || modalRate.rate_type === 'Payroll 1099'">
        <div v-if="hasValueChanged(modalRate.payroll_type, modalRate.old_rate?.payroll_type)">
          <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
            {{ modalRate.old_rate?.payroll_type }}
          </div>
          <div class="text-orange-600 dark:text-orange-400 font-semibold">
            {{ modalRate.payroll_type }}
          </div>
        </div>
        <span v-else>
          {{ modalRate.payroll_type }}
        </span>
      </Label>
      <Label
        label="Slab First Hours"
        v-if="
          modalRate.rate_type === 'Payroll Slab' ||
          modalRate.rate_type === '1099 Slab' ||
          modalRate.rate_type === 'Payroll 1099'
        "
      >
          <div v-if="hasValueChanged(modalRate.slab_first_hours, modalRate.old_rate?.slab_first_hours)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.slab_first_hours }}
            </div>
            <div :class="getNumericChangeClass(modalRate.slab_first_hours, modalRate.old_rate?.slab_first_hours)">
              {{ modalRate.slab_first_hours }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.slab_first_hours }}
          </span>
      </Label>
      <Label label="Rate">
          <div v-if="hasValueChanged(modalRate.rate, modalRate.old_rate?.rate)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ formatCurrency(modalRate.old_rate?.rate) }}
            </div>
            <div :class="getNumericChangeClass(modalRate.rate, modalRate.old_rate?.rate)">
              {{ formatCurrency(modalRate.rate) }}
            </div>
          </div>
          <span v-else>
            {{ formatCurrency(modalRate.rate) }}
          </span>
      </Label>
      <Label
        label="Slab Rest Rate"
        v-if="
          modalRate.rate_type === 'Payroll Slab' ||
          modalRate.rate_type === '1099 Slab' ||
          modalRate.rate_type === 'Payroll 1099'
        "
      >   
          <div v-if="hasValueChanged(modalRate.slab_rest_rate, modalRate.old_rate?.slab_rest_rate)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ formatCurrency(modalRate.old_rate?.slab_rest_rate) }}
            </div>
            <div :class="getNumericChangeClass(modalRate.slab_rest_rate, modalRate.old_rate?.slab_rest_rate)">
              {{ formatCurrency(modalRate.slab_rest_rate) }}
            </div>
          </div>
          <span v-else>
            {{ formatCurrency(modalRate.slab_rest_rate) }}
          </span>
      </Label>
      <Label v-if="modalRate.rate_type === 'Payroll 1099'"
        label="Payroll Rate"
      >
        <div v-if="hasValueChanged(modalRate.payroll_rate, modalRate.old_rate?.payroll_rate)">
          <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
            {{ formatCurrency(modalRate.old_rate?.payroll_rate) }}
          </div>
          <div :class="getNumericChangeClass(modalRate.payroll_rate, modalRate.old_rate?.payroll_rate)">
            {{ formatCurrency(modalRate.payroll_rate) }}
          </div>
        </div>
        <span v-else>
          {{ formatCurrency(modalRate.payroll_rate) }}
        </span>
      </Label>

      <Label v-if="modalRate.rate_type === '1099 1099'"
        label="1099 Rate"
      >
        <div v-if="hasValueChanged(modalRate.ten99_rate, modalRate.old_rate?.ten99_rate)">
          <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
            {{ formatCurrency(modalRate.old_rate?.ten99_rate) }}
          </div>
          <div :class="getNumericChangeClass(modalRate.ten99_rate, modalRate.old_rate?.ten99_rate)">
            {{ formatCurrency(modalRate.ten99_rate) }}
          </div>
        </div>
        <span v-else>
          {{ formatCurrency(modalRate.ten99_rate) }}
        </span>
      </Label>
      <Label
        label="Payroll Hours"
        v-if="modalRate.rate_type === 'Payroll Slab'"
      >
          <div v-if="hasValueChanged(modalRate.payroll_hours, modalRate.old_rate?.payroll_hours)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.payroll_hours ? ( modalRate.old_rate?.payroll_hours + ' ' + (modalRate.old_rate?.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }}
            </div>
            <div :class="getNumericChangeClass(modalRate.payroll_hours, modalRate.old_rate?.payroll_hours)">
              {{ modalRate.payroll_hours ? ( modalRate.payroll_hours + ' ' + (modalRate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.payroll_hours ? ( modalRate.payroll_hours + ' ' + (modalRate.payroll_hours_type =='percentage' ? '%' : 'Hours')) : '-' }}
          </span>
      </Label>
      <Label
        label="Check Payment"
        v-if="modalRate && showsCheckPaymentForRateType(modalRate.rate_type)"
      >
          <div v-if="hasValueChanged(modalRate.check_payment_amount, modalRate.old_rate?.check_payment_amount)">
            <div class="text-gray-400 dark:text-gray-500 text-xs line-through mb-1">
              {{ modalRate.old_rate?.check_payment_type === 'percentage' ? `${modalRate.old_rate?.check_payment_amount}%` : formatCurrency(modalRate.old_rate?.check_payment_amount) }}
            </div>
            <div :class="getNumericChangeClass(modalRate.check_payment_amount, modalRate.old_rate?.check_payment_amount)">
              {{ modalRate.check_payment_type === 'percentage' ? `${modalRate.check_payment_amount}%` : formatCurrency(modalRate.check_payment_amount) }}
            </div>
          </div>
          <span v-else>
            {{ modalRate.check_payment_type === 'percentage' ? `${modalRate.check_payment_amount}%` : formatCurrency(modalRate.check_payment_amount) }}
          </span>
      </Label>
    </div>
  </Modal>
</template>

<script setup>
import { useRoute } from "vue-router";
import { useShowable } from "@/composables/useShowable";
import Panel from "@/components/ui/panel.vue";
import Button from "@/components/ui/button.vue";
import Label from "@/components/ui/label.vue";
import Spinner from "@/components/ui/spinner.vue";
import SvgIcon from "@/components/SvgIcon.vue";
import { formatDate,formatDateTime } from "@/utils/date";
import { ref, computed } from "vue";
import Modal from "@/components/common/Modal.vue";
import EmployeeHours from "@/views/payroll/employee-hours/index.vue";
import { formatCurrency } from "@/utils/number"
import { usePermission } from '@/composables/usePermission'
import PayrollInfo from "./components/PayrollInfo.vue";

const props = defineProps({
  /** When true, hides header buttons (back, edit, delete), EmployeeHours, and Pending Approval */
  embedded: {
    type: Boolean,
    default: false
  },
  /** Pre-fetched employee data - when provided, used instead of useShowable fetch */
  employee: {
    type: Object,
    default: null
  }
})

const { can } = usePermission()

const route = useRoute();
const resource = route.meta?.resource || "employee";

// Use the useShowable composable (only used when not using employee prop)
const { model, show, setData, removeDB, access } = useShowable(
  resource,
  "employee"
);

const employeeProp = computed(() => props.employee)
const displayModel = computed(() => employeeProp.value || model.value || {})
const displayShow = computed(() => employeeProp.value ? !!employeeProp.value : show.value)
const modalRate = ref(null);
const showModal = ref(false);
// Handle delete action


function openProfilePictureFullSize(url) {
  if (!url) return
  window.open(url, '_blank', 'noopener,noreferrer')
}

const path = route.path.includes("missing-profile-picture")
  ? "onboarding/employee/missing-profile-picture"
  : route.path.includes("pending-i9-w4")
  ? "onboarding/employee/pending-i9-w4"
  : route.path.includes("new")
  ? "onboarding/employee/new"
  : route.path.includes("existing")
  ? "onboarding/employee/existing"
  : "employee";


  const handleDelete = async () => {
  const id = displayModel.value?.id;
  if (id) {
    await removeDB(resource, id,path);
  }
};
const isNew = computed(() => route.path.includes("onboarding/employee/new"))

function is1099StyleRateType(rateType) {
  if (!rateType) return false
  return (
    rateType === "1099 Regular" ||
    rateType === "1099 Slab" ||
    rateType === "1099 1099" ||
    rateType === "Payroll 1099"
  )
}

/** Matches employee form.vue check-payment visibility */
function showsCheckPaymentForRateType(rateType) {
  return (
    rateType === "1099 Regular" ||
    rateType === "1099 Slab" ||
    rateType === "Payroll Slab" ||
    rateType === "Payroll 1099"
  )
}

const firstOnboardingRate = computed(() => {
  const m = displayModel.value
  if (!m) return null
  if (m.employee_rates?.length) return m.employee_rates[0]
  if (m.employee_rates_requests?.length) return m.employee_rates_requests[0]
  return null
})

const onboardingDisplayRates = computed(() => {
  const m = displayModel.value
  if (!isNew.value || !m) return []
  if (m.employee_rates?.length) return m.employee_rates
  return m.employee_rates_requests ?? []
})

const displayOnboardingPaymentType = computed(() => {
  const r = firstOnboardingRate.value
  if (!r?.rate_type) return "—"
  return is1099StyleRateType(r.rate_type) ? "1099" : "Payroll"
})

const showOnboardingEmail = computed(() => {
  if (!isNew.value) return false
  const r = firstOnboardingRate.value
  return !is1099StyleRateType(r?.rate_type)
})

const handleModal = (rate) => {
  if (rate.employee_rates_request) {
    modalRate.value = rate.employee_rates_request
    modalRate.value.old_rate = {
      role_id: rate.role_id,
      effective_date: rate.effective_date,
      till_date: rate.till_date,
      pay_type: rate.pay_type,
      rate_type: rate.rate_type,
      payroll_type: rate.payroll_type,
      slab_first_hours: rate.slab_first_hours,
      slab_rest_rate: rate.slab_rest_rate,
      payroll_hours: rate.payroll_hours,
      payroll_rate: rate.payroll_rate,
      ten99_rate: rate.ten99_rate,
      check_payment_type: rate.check_payment_type,
      check_payment_amount: rate.check_payment_amount,
      rate: rate.rate,
    }
  } else if (rate.status != null) {
    modalRate.value = rate
    modalRate.value.old_rate = {}
  } else {
    return
  }

  showModal.value = true
};

// Helper function to check if value has changed
const hasValueChanged = (newValue, oldValue) => {
  return newValue !== oldValue;
};

// Helper function to get color class for numeric value changes
const getNumericChangeClass = (newValue, oldValue) => {
  if (!oldValue && oldValue !== 0) {
    return '';
  }
  
  const newNum = parseFloat(newValue);
  const oldNum = parseFloat(oldValue);
  
  if (isNaN(newNum) || isNaN(oldNum)) {
    return 'text-orange-600 dark:text-orange-400 font-semibold';
  }
  
  if (newNum > oldNum) {
    return 'text-green-600 dark:text-green-400 font-semibold';
  } else if (newNum < oldNum) {
    return 'text-red-600 dark:text-red-400 font-semibold';
  }
  
  return '';
};

// const maskSSN = (ssn) => {
//     if (!ssn) return '-'
//     const ssnString = ssn.toString()
//     if (ssnString.length >= 4) {
//         return '***-**-' + ssnString.slice(-4)
//     }
//     return '***-**-****'
// }

// Expose setData for useShowable route guards
defineExpose({
  setData,
});
</script>

<style scoped>
@media (max-width: 640px) {
  .employee-show {
    padding: 1rem;
  }

  .mb-6 {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 1rem;
  }

  .mb-6 > div:last-child {
    width: 100%;
  }

  .mb-6 .flex.items-center.gap-2 {
    width: 100%;
    flex-wrap: wrap;
  }

  .mb-6 .flex.items-center.gap-2 > * {
    flex: 1;
    min-width: fit-content;
  }
}
</style>



