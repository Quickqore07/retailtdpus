<template>
  <div v-if="show" class="ledger-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Ledger' : 'Create New Ledger' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Account Type -->
         <DynamicDropdown
            label="Account Type"
            v-model="form.account_type"
            :custom-options="accountTypeOptions"
            display-name="name"
            :error="errors.account_type ? errors.account_type[0] : null"
            icon-left="wallet"
            @change="handleAccountTypeChange"
          />

          <Input 
            v-model="form.code" 
            label="Code" 
            placeholder="e.g. LED-001" 
            :required="true"
            :error="errors.code ? errors.code[0] : null" 
          />
          <Input 
            v-model="form.name" 
            label="Name" 
            placeholder="Ledger name" 
            :required="true"
            :error="errors.name ? errors.name[0] : null" 
          />
        </div>

        <!-- Bank fields (only when account type is Bank) -->
        <div v-if="form.account_type?.name == 'Bank' && access.includes('bank_details')" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <Input 
            v-model="form.account_no" 
            label="Account No." 
            placeholder="Bank account number" 
            :error="errors.account_no ? errors.account_no[0] : null" 
          />
          <Input 
            v-model="form.bank_name" 
            label="Bank Name" 
            placeholder="Name of bank" 
            :error="errors.bank_name ? errors.bank_name[0] : null" 
          />
          <Input 
            v-model="form.transition_code" 
            label="Transition Code" 
            placeholder="Transition code" 
            :error="errors.transition_code ? errors.transition_code[0] : null" 
          />
          <Input 
            v-model="form.routing" 
            label="Routing" 
            placeholder="Routing number" 
            :error="errors.routing ? errors.routing[0] : null" 
          />
          <Input 
            v-model="form.starting_check_number" 
            label="Starting Check Number" 
            placeholder="Starting check number" 
            type="number"
            min="0"
            max="100000"
            :error="errors.starting_check_number ? errors.starting_check_number[0] : null" 
          />
          <div class="flex flex-col gap-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2"> 
                Default Bank
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input
                    type="checkbox"
                    v-model="form.default_bank"
                    :true-value="true"
                    :false-value="false"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
            </label>
        </div>
          <div class="md:col-span-2 lg:col-span-3">
            <Textarea
              v-model="form.bank_address"
              label="Bank Address"
              placeholder="Bank address"
              :rows="3"
              :error="errors.bank_address ? errors.bank_address[0] : null"
            />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button variant="primary" size="md" type="submit" :loading="isSaving" 
            v-if="mode === 'create' ? access.includes('create') : access.includes('update')">
            {{ mode === 'edit' ? 'Update Ledger' : 'Create Ledger' }}
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
import { watch, ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Textarea from '@/components/ui/textarea.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const resource = route.meta?.resource || 'settings/ledgers'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/ledgers', 'ledger')

const userStore = useAuthStore()


const userRole = computed(() => userStore.userRole?.name)

const accountTypeOptions = ref([{ id: 'General', name: 'General' }, { id: 'Bank', name: 'Bank' }])

watch(() => form.value, (newType) => {
  form.value.account_type = accountTypeOptions.value.find(option => option.id == newType?.account_type)
  form.value.default_bank = newType?.default_bank ? true : false
}, { immediate: true })

const handleAccountTypeChange = (value) => {
  if (value.account_type?.name !== 'Bank') {
    form.value.account_no = null
    form.value.bank_name = null
    form.value.bank_address = null
    form.value.transition_code = null
    form.value.routing = null
    form.value.starting_check_number = null
    form.value.default_bank = false
  }
}

const handleSave = () => {
  const obj = {
    account_type: form.value.account_type?.name,
    code: form.value.code,
    name: form.value.name,
  }
  if (obj.account_type == 'Bank') {
    obj.account_no = form.value.account_no
    obj.bank_name = form.value.bank_name
    obj.bank_address = form.value.bank_address
    obj.transition_code = form.value.transition_code
    obj.routing = form.value.routing
    obj.starting_check_number = form.value.starting_check_number
    obj.default_bank = form.value.default_bank
  } else {
    obj.account_no = null
    obj.bank_name = null
    obj.bank_address = null
    obj.transition_code = null
    obj.routing = null
    obj.starting_check_number = null
    obj.default_bank = false
  }
  save(obj)
}

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .ledger-form {
    padding: 1rem;
  }
}
</style>
