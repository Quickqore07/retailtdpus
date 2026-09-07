<template>
  <div v-if="show" class="vendor-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Vendor' : 'Create New Vendor' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="save" class="space-y-6">
        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">
            Contact Information
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Input
              v-model="form.code"
              label="Code"
              placeholder="Enter vendor code"
              :error="errors.code ? errors.code[0] : null"
              icon-left="hash"
            />
            <Input
              v-model="form.name"
              label="Name"
              placeholder="Enter vendor name"
              :required="true"
              :error="errors.name ? errors.name[0] : null"
              icon-left="user"
            />
            <Input
              v-model="form.email"
              label="Email"
              placeholder="Enter email address"
              :error="errors.email ? errors.email[0] : null"
              icon-left="mail"
            />
            <Input
              v-model="form.mobile"
              label="Mobile Number"
              placeholder="Enter mobile number"
              :error="errors.mobile ? errors.mobile[0] : null"
              icon-left="phone"
            />
            <Input
              v-model="form.fax"
              label="Fax"
              placeholder="Enter fax number"
              :error="errors.fax ? errors.fax[0] : null"
            />
            <Input
              v-model="form.credit_days"
              type="number"
              min="0"
              step="1"
              label="Credit Days"
              placeholder="e.g. 20"
              :error="errors.credit_days ? errors.credit_days[0] : null"
            />
          </div>
        </div>

        <div>
          <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-2 border-b border-gray-200 dark:border-gray-700 pb-2">
            Address
          </h6>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Input
              v-model="form.address_line_1"
              label="Address Line 1"
              placeholder="Street address"
              :error="errors.address_line_1 ? errors.address_line_1[0] : null"
            />
            <Input
              v-model="form.address_line_2"
              label="Address Line 2"
              placeholder="Apartment, suite, etc."
              :error="errors.address_line_2 ? errors.address_line_2[0] : null"
            />
            <Input
              v-model="form.address_line_3"
              label="Address Line 3"
              placeholder="Address line 3"
              :error="errors.address_line_3 ? errors.address_line_3[0] : null"
            />
            <Input
              v-model="form.city"
              label="City"
              placeholder="City"
              :error="errors.city ? errors.city[0] : null"
            />
            <Input
              v-model="form.state"
              label="State"
              placeholder="State"
              :error="errors.state ? errors.state[0] : null"
            />
            <Input
              v-model="form.country"
              label="Country"
              placeholder="Country"
              :error="errors.country ? errors.country[0] : null"
            />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button
            variant="primary"
            size="md"
            type="submit"
            :loading="isSaving"
            v-if="mode === 'create' ? access.includes('create') : access.includes('update')"
          >
            {{ mode === 'edit' ? 'Update Vendor' : 'Create Vendor' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Spinner from '@/components/ui/spinner.vue'

const route = useRoute()
const resource = route.meta?.resource || 'ap/vendors'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'ap/vendors', 'vendor')

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .vendor-form {
    padding: 1rem;
  }
}
</style>
