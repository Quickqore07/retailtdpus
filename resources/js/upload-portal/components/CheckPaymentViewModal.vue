<template>
  <Modal
    :model-value="modelValue"
    size="7xl"
    title="View Check Payment"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="space-y-5">
      <div v-if="loading" class="py-12 flex justify-center">
        <Spinner size="lg" text="Loading payment..." />
      </div>

      <div v-else-if="paymentData" class="space-y-5">
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 bg-white dark:bg-gray-900">
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Company</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ companyName }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Vendor</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ vendorDisplay }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Bank</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ bankDisplay }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Payment Type</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0 capitalize">{{ paymentData.payment_type || 'N/A' }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Total Amount</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white !mb-0">{{ formatAmount(paymentData.amount) }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Date</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ formatDate(paymentData.date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Check No</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ paymentData.check_no || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="text-left px-3 py-2">#</th>
                <th class="text-left px-3 py-2">Ref (Bill No)</th>
                <th class="text-left px-3 py-2">Invoice Date</th>
                <th class="text-right px-3 py-2">Invoice Amount</th>
                <th class="text-right px-3 py-2">Paid Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, idx) in paymentItems"
                :key="item.id || idx"
                class="border-b border-gray-100 dark:border-gray-800 last:border-0"
              >
                <td class="px-3 py-2">{{ idx + 1 }}</td>
                <td class="px-3 py-2">{{ item.bill?.bill_number || `Bill ID: ${item.bill_id}` }}</td>
                <td class="px-3 py-2">{{ formatDate(item.bill?.date) }}</td>
                <td class="px-3 py-2 text-right">{{ formatAmount(item.bill?.amount) }}</td>
                <td class="px-3 py-2 text-right">{{ formatAmount(item.amount) }}</td>
              </tr>
              <tr v-if="!paymentItems.length">
                <td colspan="5" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                  No payment references found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white !mb-3">Audit Details</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Created By</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ paymentData.created_by?.name || document?.created_by?.name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Created At</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ formatDateTime(paymentData.created_at || document?.created_at) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Last Updated By</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ paymentData.updated_by?.name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Last Updated At</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ formatDateTime(paymentData.updated_at) }}</p>
            </div>
          </div>
        </div>

        <div v-if="paymentData.memo" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white !mb-2">Memo</h4>
          <p class="text-sm text-gray-700 dark:text-gray-300 !mb-0 whitespace-pre-wrap">{{ paymentData.memo }}</p>
        </div>
      </div>

      <div v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        Unable to load payment details.
      </div>

      <div class="flex justify-end pt-2 border-t border-gray-200 dark:border-gray-700">
        <Button variant="outline-secondary" @click="$emit('update:modelValue', false)">Close</Button>
      </div>
    </div>
  </Modal>
</template>

<script>
import { computed, ref, watch } from 'vue'
import axios from '../plugins/axios'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate, formatDateTime } from '@/utils/date'

export default {
  name: 'CheckPaymentViewModal',
  components: {
    Modal,
    Button,
    Spinner,
    formatDate,
    formatDateTime
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    document: {
      type: Object,
      default: null
    }
  },
  emits: ['update:modelValue'],
  setup (props) {
    const loading = ref(false)
    const paymentData = ref(null)
    const vendorName = ref('')
    const bankName = ref('')

    const paymentItems = computed(() => paymentData.value?.items || [])
    const companyName = computed(() => paymentData.value?.company?.name || props.document?.company?.name || 'N/A')
    const vendorDisplay = computed(() => vendorName.value || paymentData.value?.qq_vendor_name || `Vendor ID: ${paymentData.value?.qq_vendor_id || 'N/A'}`)
    const bankDisplay = computed(() => bankName.value || `Ledger ID: ${paymentData.value?.qq_bank_id || 'N/A'}`)

    const formatAmount = (value) => {
      const parsed = Number(value)
      if (Number.isNaN(parsed)) return '0.00'
      return parsed.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    }

    const loadVendorsAndBanks = async () => {
      if (!paymentData.value) return

      const storeNumber = paymentData.value?.company?.store_number || props.document?.company?.store_number
      const workgroupName = paymentData.value?.company?.workgroup?.name || props.document?.company?.workgroup?.name || ''
      if (!storeNumber) return

      const payload = {
        store_number: storeNumber,
        workgroup: workgroupName
      }

      try {
        const [{ data: vendors }, { data: ledgers }] = await Promise.all([
          axios.post('/upload-portal/api/vendors', payload),
          axios.post('/upload-portal/api/banks', payload)
        ])

        const selectedVendor = Array.isArray(vendors)
          ? vendors.find((vendor) => Number(vendor.id) === Number(paymentData.value.qq_vendor_id))
          : null
        vendorName.value = selectedVendor?.name || ''

        const selectedBank = Array.isArray(ledgers)
          ? ledgers.find((ledger) => Number(ledger.id) === Number(paymentData.value.qq_bank_id))
          : null
          if(selectedBank){
            bankName.value =  `${selectedBank?.code || ''} - ${selectedBank?.name || ''}`.trim()
          }else{
            bankName.value = `Ledger ID: ${paymentData.value?.qq_bank_id || 'N/A'}`
          }
      } catch (error) {
        vendorName.value = ''
        bankName.value = ''
        console.error('Error loading vendor/expense-ledger options:', error)
      }
    }

    const loadPayment = async () => {
      if (!props.document?.id) return
      loading.value = true
      try {
        const { data } = await axios.get(`/upload-portal/api/ap-payments/${props.document.id}`)
        if (data?.success) {
          paymentData.value = data.payment || null
          await loadVendorsAndBanks()
        } else {
          paymentData.value = null
          vendorName.value = ''
          bankName.value = ''
        }
      } catch (error) {
        paymentData.value = null
        vendorName.value = ''
        bankName.value = ''
        console.error('Error loading payment details:', error)
      } finally {
        loading.value = false
      }
    }

    watch(
      () => props.modelValue,
      async (isOpen) => {
        if (!isOpen) return
        await loadPayment()
      }
    )

    return {
      loading,
      paymentData,
      paymentItems,
      companyName,
      vendorDisplay,
      bankDisplay,
      formatAmount,
      formatDate,
      formatDateTime
    }
  }
}
</script>
