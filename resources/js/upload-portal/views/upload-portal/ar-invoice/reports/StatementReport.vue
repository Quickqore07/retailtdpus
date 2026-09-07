<template>
  <div>
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Account Statement</h4>
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        View customer account statements for a selected date range. 
      </p>
    </div>

    <div
      v-if="!canViewReport"
      class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        You do not have permission to view account statements.
      </p>
    </div>

    <template v-else>
      <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="max-w-md">
          <DynamicDropdown
            v-model="selectedCustomer"
            label="Customer"
            :custom-options="customers"
            display-name="name"
            placeholder="Select customer"
            :searchable="true"
            :disabled="loadingCustomers"
            remove-null-option
            @change="onCustomerChange"
          />
        </div>
      </div>

      <CustomerAccountStatementTab
        v-if="selectedCustomer?.id && !isAllCustomers"
        :key="selectedCustomer.id"
        ref="statementTabRef"
        :customer-id="selectedCustomer.id"
        :customer-name="selectedCustomer.name"
      />

      <AllCustomersAccountStatementTab
        v-else-if="isAllCustomers"
        ref="allStatementTabRef"
      />

      <div
        v-else
        class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
      >
        <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
          Select a customer to run an account statement.
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '../../../../plugins/axios'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import CustomerAccountStatementTab from '../../../../components/ar-invoice/CustomerAccountStatementTab.vue'
import AllCustomersAccountStatementTab from '../../../../components/ar-invoice/AllCustomersAccountStatementTab.vue'
import { useUser } from '../../../../composables/useUser'
import { useMessage } from '@/composables/useMessage'

const ALL_CUSTOMERS_ID = '__all__'
const allCustomersOption = { id: ALL_CUSTOMERS_ID, name: 'All customers' }

const { can } = useUser()
const message = useMessage()

const canViewReport = computed(() => can('upload-portal-customer', 'view-statement'))

const customers = ref([])
const loadingCustomers = ref(false)
const selectedCustomer = ref(null)
const statementTabRef = ref(null)
const allStatementTabRef = ref(null)

const isAllCustomers = computed(() => selectedCustomer.value?.id === ALL_CUSTOMERS_ID)

const loadCustomers = async () => {
  if (!canViewReport.value) return
  loadingCustomers.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-customers', {
      params: { per_page: 1000 },
    })
    if (response.data.success) {
      const loaded = (response.data.customers?.data || []).map((c) => ({
        id: c.id,
        name: c.name,
      }))
      customers.value = [allCustomersOption, ...loaded]
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to load customers')
  } finally {
    loadingCustomers.value = false
  }
}

const onCustomerChange = () => {
  statementTabRef.value?.reset()
  allStatementTabRef.value?.reset()
}

onMounted(() => {
  loadCustomers()
})
</script>
