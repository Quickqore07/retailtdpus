<template>
  <Modal
    :model-value="modelValue"
    size="7xl"
    title="View Invoice"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="space-y-5">
      <div v-if="loading" class="py-12 flex justify-center">
        <Spinner size="lg" text="Loading invoice..." />
      </div>

      <div v-else-if="invoiceData" class="space-y-5">
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 bg-white dark:bg-gray-900">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div class="text-right">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Invoice No</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white !mb-0">{{ invoiceData.bill_number || 'N/A' }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Invoice Type</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white !mb-0 capitalize">{{ invoiceData.invoice_type || 'N/A' }}</p>
           </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Company</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ companyName }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Vendor</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ vendorDisplay }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Status</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0 capitalize">{{ invoiceData.status || 'draft' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Invoice Date</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ formatDate(invoiceData.date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Due Date</p>
              <p class="text-sm text-gray-900 dark:text-white !mb-0">{{ formatDate(invoiceData.due_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Amount</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white !mb-0">{{ formatAmount(invoiceData.amount) }}</p>
            </div>
            <div v-if="invoiceData.invoice_type === 'check'">
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Approved Amount</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white !mb-0">{{ formatAmount(invoiceData.approved_amount) }}</p>
            </div>
          </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="text-left px-3 py-2">#</th>
                <th class="text-left px-3 py-2">Store</th>
                <th class="text-left px-3 py-2">Ledger</th>
                <th class="text-left px-3 py-2">Description</th>
                <th class="text-right px-3 py-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, idx) in invoiceItems"
                :key="item.id || idx"
                class="border-b border-gray-100 dark:border-gray-800 last:border-0"
              >
                <td class="px-3 py-2">{{ idx + 1 }}</td>
                <td class="px-3 py-2">{{ item.company?.name || 'N/A' }}</td>
                <td class="px-3 py-2">{{ getLedgerDisplay(item) }}</td>
                <td class="px-3 py-2">{{ item.description || '-' }}</td>
                <td class="px-3 py-2 text-right">{{ formatAmount(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white !mb-3">Audit Details</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Created By</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ invoiceData.created_by?.name || documentData.created_by?.name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Created At</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ formatDateTime(invoiceData.created_at || documentData.created_at) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Last Updated By</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ invoiceData.updated_by?.name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Last Updated At</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ formatDateTime(invoiceData.updated_at) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Approved By</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ invoiceData.approved_by?.name || 'Not approved' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Approved At</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ formatDateTime(invoiceData.approved_at) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 !mb-1">Approval Remarks</p>
              <p class="text-gray-900 dark:text-white !mb-0">{{ invoiceData.approval_remarks || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <div v-if="invoiceData.remarks" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white !mb-2">Remarks</h4>
          <p class="text-sm text-gray-700 dark:text-gray-300 !mb-0 whitespace-pre-wrap">{{ invoiceData.remarks }}</p>
        </div>
      </div>

      <div v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        Unable to load invoice details.
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
  name: 'InvoiceViewModal',
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
    const invoiceData = ref(null)
    const documentData = ref(null)
    const companyData = ref(null)
    const vendorName = ref('')
    const ledgerNameById = ref({})

    const invoiceItems = computed(() => invoiceData.value?.items || [])
    const companyName = computed(() => companyData.value?.name || documentData.value?.company?.name || props.document?.company?.name || 'N/A')
    const vendorDisplay = computed(() => vendorName.value || invoiceData.value?.vendor_name || `Vendor ID: ${invoiceData.value?.qq_vendor_id || 'N/A'}`)
    const getLedgerDisplay = (item) => ledgerNameById.value[item?.qq_ledger_id] || item?.qq_ledger_id || 'N/A'


    const formatAmount = (value) => {
      const parsed = Number(value)
      if (Number.isNaN(parsed)) return '0.00'
      return parsed.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    }

    const loadVendorsAndLedgers = async () => {
      if (!invoiceData.value || !companyData.value?.store_number) return

      const payload = {
        store_no: companyData.value.store_number,
        store_number: companyData.value.store_number,
        workgroup: companyData.value?.workgroup?.name || ''
      }

      try {
        const [{ data: vendors }, { data: ledgers }] = await Promise.all([
          axios.post('/upload-portal/api/vendors', payload),
          axios.post('/upload-portal/api/expense-ledgers', payload)
        ])

        const selectedVendor = Array.isArray(vendors)
          ? vendors.find((vendor) => Number(vendor.id) === Number(invoiceData.value.qq_vendor_id))
          : null
        vendorName.value = selectedVendor?.name || ''

        const ledgerMap = {}
        if (Array.isArray(ledgers)) {
          ledgers.forEach((ledger) => {
            ledgerMap[ledger.id] = ledger.name || `${ledger.code || ''} - ${ledger.label || ''}`.trim()
          })
        }
        ledgerNameById.value = ledgerMap
      } catch (error) {
        vendorName.value = ''
        ledgerNameById.value = {}
        console.error('Error loading vendor/ledger options:', error)
      }
    }

    const loadInvoice = async () => {
      if (!props.document?.id) return
      loading.value = true
      try {
        const { data } = await axios.get(`/upload-portal/api/ap-invoices/${props.document.id}`)
        if (data?.success) {
          invoiceData.value = data.invoice || null
          documentData.value = data.document || null
          companyData.value = data.company || null
          await loadVendorsAndLedgers()
        }
      } catch (error) {
        invoiceData.value = null
        documentData.value = null
        companyData.value = null
        vendorName.value = ''
        ledgerNameById.value = {}
        console.error('Error loading invoice details:', error)
      } finally {
        loading.value = false
      }
    }

    watch(
      () => props.modelValue,
      async (isOpen) => {
        if (!isOpen) return
        await loadInvoice()
      }
    )

    return {
      loading,
      invoiceData,
      documentData,
      invoiceItems,
      companyName,
      vendorDisplay,
      getLedgerDisplay,
      formatDateTime,
      formatAmount,
      formatDate
    }
  }
}
</script>
