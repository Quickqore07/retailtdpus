<template>
  <Modal
    v-model="visible"
    size="5xl"
    :show-footer="true"
    :show-cancel="true"
    :show-confirm="true"
    cancel-text="Cancel"
    confirm-text="Submit"
    :loading="loading"
    @close="close"
    @confirm="submit"
  >
    <template #header>
      <h5 class="text-xl font-bold !mb-0">Add New Employee</h5>
    </template>

    <div class="space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Input
          v-model="form.last_name"
          label="Last Name (Family Name)"
          placeholder="Enter last name"
          :required="true"
          icon-left="user"
          :error="errors.last_name"
        />
        <Input
          v-model="form.first_name"
          label="First Name (Given Name)"
          placeholder="Enter first name"
          :required="true"
          icon-left="user"
          :error="errors.first_name"
        />
        <Input
          v-model="form.middle_name"
          label="Middle Initial"
          placeholder="Enter middle initial"
          icon-left="user"
          :error="errors.middle_name"
          @input="handleMiddleInitialInput"
        />
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Input
          v-model="form.employee_id"
          :required="true"
          label="Employee ID"
          placeholder="Auto-generated if left blank"
          icon-left="hash"
          :error="errors.employee_id"
        />
        <Input
          v-model="form.pos_name"
          :required="true"
          label="POS Name"
          placeholder="Auto-generated if left blank"
          icon-left="user"
          :error="errors.pos_name"
        />
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Input
          v-model="form.ssn"
          label="U.S. Social Security Number"
          placeholder="XXX-XX-XXXX"
          icon-left="shield"
          :error="errors.ssn"
          @input="handleSsnInput"
        />
        <Input
          v-model="form.street"
          label="Address (Street Number and Name)"
          placeholder="Enter street address"
          icon-left="map-pin"
          :error="errors.street"
        />
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <Input
          v-model="form.apt_number"
          label="Apt. Number"
          placeholder="Apt. number"
          icon-left="map-pin"
          :error="errors.apt_number"
        />
        <Input
          v-model="form.city"
          label="City or Town"
          placeholder="Enter city"
          icon-left="map"
          :error="errors.city"
        />
        <Input
          v-model="form.state"
          label="State"
          placeholder="e.g. CA"
          icon-left="map"
          :error="errors.state"
        />
        <Input
          v-model="form.zip"
          label="ZIP Code"
          placeholder="Enter ZIP"
          icon-left="map-pin"
          :error="errors.zip"
        />
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Input
          v-model="form.dob"
          label="Date of Birth"
          type="date"
          icon-left="calendar"
          :error="errors.dob"
        />
        <Input
          v-model="form.email"
          label="Employee's Email Address"
          type="email"
          placeholder="Enter email"
          icon-left="mail"
          :error="errors.email"
        />
        <Input
          v-model="form.phone"
          label="Employee's Telephone Number"
          type="tel"
          placeholder="Enter phone"
          icon-left="phone"
          :error="errors.phone"
        />
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Input
          v-model="form.hire_date"
          label="Date of Joining / Hire"
          type="date"
          :required="true"
          icon-left="calendar"
          :error="errors.hire_date"
        />
        <DynamicDropdown
          v-model="form.company"
          label="Store (where hired)"
          resource="companies"
          display-name="name"
          placeholder="Select store"
          :required="true"
          :error="errors.company_id"
          :remove-null-option="true"
          icon-left="building"
        />
        <DynamicDropdown
          v-model="form.role"
          label="Job Title"
          resource="employee-roles"
          display-name="name"
          placeholder="Select job title"
          :required="true"
          :error="errors.role_id"
          :remove-null-option="true"
        />
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatSSNAsYouType } from '@/utils/ssn'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  defaultCompany: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'created'])

const message = useMessage()
const loading = ref(false)
const errors = ref({})

const emptyForm = () => ({
  last_name: '',
  first_name: '',
  middle_name: '',
  employee_id: '',
  pos_name: '',
  ssn: '',
  street: '',
  apt_number: '',
  city: '',
  state: '',
  zip: '',
  dob: '',
  email: '',
  phone: '',
  hire_date: '',
  company: null,
  role: null,
})

const form = ref(emptyForm())

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const resetForm = () => {
  form.value = emptyForm()
  if (props.defaultCompany?.id) {
    form.value.company = props.defaultCompany
  }
  errors.value = {}
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      resetForm()
    }
  }
)

const handleSsnInput = (event) => {
  const raw = event?.target?.value ?? form.value.ssn ?? ''
  form.value.ssn = formatSSNAsYouType(raw)
}

const handleMiddleInitialInput = (event) => {
  const raw = String(event?.target?.value ?? form.value.middle_name ?? '')
  form.value.middle_name = raw.replace(/[^a-zA-Z]/g, '').slice(0, 1).toUpperCase()
}

const close = () => {
  visible.value = false
  errors.value = {}
}

const validate = () => {
  const next = {}

  if (!form.value.last_name?.trim()) {
    next.last_name = 'Last name is required'
  }
  if (!form.value.first_name?.trim()) {
    next.first_name = 'First name is required'
  }
  if (!form.value.hire_date) {
    next.hire_date = 'Date of joining / hire is required'
  }
  if (!form.value.company?.id) {
    next.company_id = 'Store is required'
  }
  if (!form.value.role?.id) {
    next.role_id = 'Job title is required'
  }
  if (form.value.email?.trim()) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(form.value.email.trim())) {
      next.email = 'Please enter a valid email address'
    }
  }

  errors.value = next
  return Object.keys(next).length === 0
}

const submit = async () => {
  if (!validate()) {
    return
  }

  loading.value = true
  try {
    const payload = {
      last_name: form.value.last_name.trim(),
      first_name: form.value.first_name.trim(),
      middle_name: form.value.middle_name?.trim() || null,
      employee_id: form.value.employee_id?.trim() || null,
      pos_name: form.value.pos_name?.trim() || null,
      ssn: form.value.ssn?.trim() || null,
      street: form.value.street?.trim() || null,
      apt_number: form.value.apt_number?.trim() || null,
      city: form.value.city?.trim() || null,
      state: form.value.state?.trim() || null,
      zip: form.value.zip?.trim() || null,
      dob: form.value.dob || null,
      email: form.value.email?.trim() || null,
      phone: form.value.phone?.trim() || null,
      hire_date: form.value.hire_date,
      company_id: form.value.company.id,
      role_id: form.value.role.id,
    }

    const response = await useRequest('post', 'onboarding/employee-confirmation/employees', payload)

    if (response?.saved || response?.success) {
      message.success(response.message || 'Employee created successfully')
      emit('created', response.data || response.employee)
      close()
    } else {
      message.error(response?.message || 'Failed to create employee')
    }
  } catch (error) {
    const validationErrors = error?.response?.data?.errors
    if (validationErrors) {
      errors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([key, value]) => [
          key,
          Array.isArray(value) ? value[0] : value,
        ])
      )
    }
    message.error(error?.response?.data?.message || 'Failed to create employee')
  } finally {
    loading.value = false
  }
}
</script>
