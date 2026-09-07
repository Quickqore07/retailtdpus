<template>
  <Modal :model-value="modelValue" size="7xl" title="Edit Payment" @update:model-value="$emit('update:modelValue', $event)">
    <div class="space-y-5">
      <form class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" @submit.prevent="handleSubmit">
        <Input v-model="form.date" label="Date" type="date" :required="true" />
        <DynamicDropdown v-model="form.bank" label="Bank" :custom-options="banks" display-name="name" placeholder="Select bank" :required="true" :disabled="loadingBanks" />
        <DynamicDropdown v-model="form.payment_type" label="Payment Type" :custom-options="paymentTypes" display-name="label" placeholder="Select payment type" :required="true" />
        <Input v-model="form.check_no" label="Check no" type="number" />
        <Input v-model="form.memo" label="Memo" />
        <div v-if="!isCheckType" class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">Payment File</label>
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="onFileChange"
            class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300"
          />
          <p v-if="form.file" class="text-xs text-gray-600 dark:text-gray-400 !mt-2">Selected: {{ form.file.name }}</p>
        </div>
      </form>

      <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <table class="w-full text-sm min-w-[820px]">
          <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="text-left py-2 px-2 w-10">#</th>
              <th class="text-left py-2 px-2">Date</th>
              <th class="text-left py-2 px-2">Bill no</th>
              <th class="text-right py-2 px-2">Original Amount</th>
              <th class="text-right py-2 px-2">Due Amount</th>
              <th class="text-right py-2 px-2 w-[170px]">Payment</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="bill in bills" :key="bill.id" class="border-b border-gray-100 dark:border-gray-800">
              <td class="py-2 px-2">
                <input type="checkbox" v-model="bill.selected" @change="onBillToggle(bill)" />
              </td>
              <td class="py-2 px-2">{{ bill.date || '-' }}</td>
              <td class="py-2 px-2">{{ bill.bill_number || '-' }}</td>
              <td class="py-2 px-2 text-right">{{ formatAmount(bill.original_amount) }}</td>
              <td class="py-2 px-2 text-right">{{ formatAmount(bill.due_amount) }}</td>
              <td class="py-2 px-2">
                <Input v-model="bill.payment" type="number" step="0.01" min="0" :max="bill.due_amount" :disabled="!bill.selected" @input="onBillPaymentInput(bill)" />
              </td>
            </tr>
            <tr v-if="!bills.length">
              <td colspan="6" class="text-center text-gray-500 py-4">No bills found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-700 dark:text-gray-200 !mb-0">Total Payment: <b>{{ formatAmount(totalAmount) }}</b></p>
        <div class="flex items-center gap-3">
          <Button variant="outline-secondary" @click="closeModal">Cancel</Button>
          <Button variant="primary" :disabled="saving || loading" @click="handleSubmit">{{ saving ? 'Updating...' : 'Update Payment' }}</Button>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script>
import { computed, ref, watch } from 'vue'
import axios from '../plugins/axios'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Button from '@/components/ui/button.vue'
import Modal from '@/components/common/Modal.vue'
import { assignValidatedFile } from '@/utils/documentUpload'

export default {
  name: 'CheckPaymentEditModal',
  components: { Input, DynamicDropdown, Button, Modal },
  props: {
    modelValue: { type: Boolean, required: true },
    document: { type: Object, default: null }
  },
  emits: ['update:modelValue', 'success'],
  setup (props, { emit }) {
    const loading = ref(false)
    const loadingBanks = ref(false)
    const saving = ref(false)
    const banks = ref([])
    const bills = ref([])
    const form = ref({ date: '', bank: null, payment_type: null, check_no: '', memo: '', file: null })
    const payment = ref(null)

    const paymentTypes = [
      { id: 'check', label: 'Check' },
      { id: 'online', label: 'Online' },
      { id: 'cash', label: 'Cash' }
    ]

    const totalAmount = computed(() => bills.value.reduce((sum, b) => sum + (b.selected ? (parseFloat(b.payment) || 0) : 0), 0))
    const isCheckType = computed(() => form.value.payment_type?.id === 'check')
    const formatAmount = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

    const onBillToggle = (bill) => { bill.payment = bill.selected ? String(bill.due_amount) : '' }
    const onBillPaymentInput = (bill) => {
      const paymentVal = parseFloat(bill.payment || 0)
      if (paymentVal > parseFloat(bill.due_amount)) bill.payment = String(bill.due_amount)
    }
    const onFileChange = (event) => {
      const input = event.target
      assignValidatedFile(input?.files?.[0] || null, (file) => {
        form.value.file = file
      }, {
        onError: (error) => alert(error),
        input,
      })
    }

    const loadBanks = async (storeNumber, workgroup) => {
      if (!storeNumber) return
      loadingBanks.value = true
      try {
        const { data } = await axios.post('/upload-portal/api/banks', {
          store_no: storeNumber,
          store_number: storeNumber,
          workgroup
        })
        banks.value = data || []
      } finally {
        loadingBanks.value = false
      }
    }

    const hydrate = async () => {
      if (!props.document?.id) return
      loading.value = true
      try {
        const { data } = await axios.get(`/upload-portal/api/ap-payments/${props.document.id}`)
        payment.value = data.payment
        const workgroup = props.document?.company?.workgroup?.name || ''
        const storeNumber = props.document?.company?.store_number
        await loadBanks(storeNumber, workgroup)

        form.value = {
          date: data.payment?.date ? String(data.payment.date).slice(0, 10) : '',
          bank: banks.value.find((b) => Number(b.id) === Number(data.payment.qq_bank_id)) || null,
          payment_type: paymentTypes.find((pt) => pt.id === String(data.payment.payment_type || '').toLowerCase()) || paymentTypes[0],
          check_no: data.payment?.check_no || '',
          memo: data.payment?.memo || '',
          file: null
        }

        const billIds = (data.payment?.items || []).map((item) => item.bill_id)
        const { data: billsData } = await axios.post('/upload-portal/api/vendor-bills', {
          company_id: data.payment.company_id,
          qq_vendor_id: data.payment.qq_vendor_id
        })
        const amountMap = Object.fromEntries((data.payment?.items || []).map((item) => [item.bill_id, item.amount]))
        bills.value = (billsData || []).map((bill) => {
          const paid = amountMap[bill.id]
          return {
            ...bill,
            due_amount: Number(bill.due_amount) + Number(paid || 0),
            selected: billIds.includes(bill.id),
            payment: paid ? String(paid) : ''
          }
        })
      } finally {
        loading.value = false
      }
    }

    const handleSubmit = async () => {
      const selected = bills.value.filter((bill) => bill.selected && parseFloat(bill.payment || 0) > 0)
      if (!form.value.date || !form.value.bank || !form.value.payment_type || selected.length === 0) {
        alert('Please fill required fields and select at least one bill.')
        return
      }
      saving.value = true
      try {
        const payload = new FormData()
        payload.append('date', form.value.date)
        payload.append('qq_bank_id', form.value.bank.id)
        payload.append('payment_type', form.value.payment_type.id)
        payload.append('check_no', form.value.check_no || '')
        payload.append('memo', form.value.memo || '')
        payload.append('items', JSON.stringify(selected.map((bill) => ({
          bill_id: bill.id,
          due_amount: bill.due_amount,
          qq_purchase_id: bill.qq_purchase_id,
          amount: parseFloat(bill.payment || 0)
        }))))
        if (!isCheckType.value && form.value.file) {
          payload.append('file', form.value.file)
        }
        const { data } = await axios.post(`/upload-portal/api/ap-payments/${props.document.id}?_method=PUT`, payload, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (data?.success) {
          closeModal()
          emit('success')
        }
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to update payment')
      } finally {
        saving.value = false
      }
    }

    const closeModal = () => emit('update:modelValue', false)

    watch(() => props.modelValue, async (open) => {
      if (!open) return
      await hydrate()
    })

    return {
      loading,
      loadingBanks,
      saving,
      banks,
      bills,
      form,
      paymentTypes,
      totalAmount,
      formatAmount,
      onBillToggle,
      onBillPaymentInput,
      onFileChange,
      handleSubmit,
      closeModal,
      isCheckType
    }
  }
}
</script>
