<template>
  <Modal :model-value="modelValue" size="7xl" title="Add Payment" @update:model-value="$emit('update:modelValue', $event)">
    <div class="space-y-5">
      <form class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" @submit.prevent="handleSubmit">
        <DynamicDropdown v-model="form.workgroup" label="Workgroup" :custom-options="allWorkgroups" display-name="name" placeholder="Select workgroup" :required="true" @change="onWorkgroupChange" />
        <DynamicDropdown v-model="form.company" label="Company" :custom-options="companies" display-name="name" placeholder="Select company" :required="true" :disabled="!form.workgroup" @change="onCompanyChange" />
        <Input v-model="form.date" label="Date" type="date" :required="true" :disabled="!form.company" />
        <DynamicDropdown v-model="form.vendor" label="Vendor" :custom-options="vendors" display-name="name" placeholder="Select vendor" :required="true" :disabled="!form.company || loadingVendors" :searchable="true" remove-null-option @change="onVendorChange" />
        <DynamicDropdown v-model="form.bank" label="Bank" :custom-options="banks" display-name="name" placeholder="Select bank" :required="true" :disabled="!form.company || loadingBanks" @change="onBankChange" />
        <DynamicDropdown v-model="form.payment_type" label="Payment Type" :custom-options="paymentTypes" display-name="label" placeholder="Select payment type" :required="true" :disabled="!form.company"  @change="onPaymentTypeChange" />
        <Input v-model="form.check_no" label="Check no" type="number" :disabled="!form.bank || loadingCheckNo || form.payment_type?.id != 'check'" />
        <Input v-model="form.memo" label="Memo" :disabled="!form.company" />
        <div v-if="!isCheckType" class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
            Payment File <span class="text-red-500">*</span>
          </label>
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="onFileChange"
            class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300"
          />
          <p v-if="form.file" class="text-xs text-gray-600 dark:text-gray-400 !mt-2">Selected: {{ form.file.name }}</p>
        </div>
        <DynamicDropdown v-model="form.sign" label="Sign" :custom-options="signs"   placeholder="Select sign" :required="form.payment_type.id === 'check' ? true : false" :disabled="!form.company" />

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
                <Input v-model="bill.payment" type="number" step="0.01" min="0" :max="bill.due_amount"  @input="onBillPaymentInput(bill)" />
              </td>
            </tr>
            <tr v-if="!bills.length">
              <td colspan="6" class="text-center text-gray-500 py-4">Select vendor to load bills</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-700 dark:text-gray-200 !mb-0">Total Payment: <b>{{ formatAmount(totalAmount) }}</b></p>
        <div class="flex items-center gap-3">
          <Button variant="outline-secondary" @click="closeModal">Cancel</Button>
          <Button variant="primary" :disabled="saving" @click="handleSubmit">{{ saving ? 'Saving...' : 'Save Payment' }}</Button>
          <Button v-if="isCheckType" variant="primary" :disabled="saving" @click="()=>handleSubmit('print-check')">{{ saving ? 'Saving...' : 'Save & print' }}</Button>
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
  name: 'CheckPaymentCreateModal',
  components: { Input, DynamicDropdown, Button, Modal },
  props: {
    modelValue: { type: Boolean, required: true },
    folderId: { type: [String, Number], required: true },
    prefillData: { type: Object, default: null }
  },
  emits: ['update:modelValue', 'success'],
  setup (props, { emit }) {
    const allWorkgroups = ref([])
    const companies = ref([])
    const vendors = ref([])
    const banks = ref([])
    const bills = ref([])
    const saving = ref(false)
    const loadingVendors = ref(false)
    const loadingBanks = ref(false)
    const loadingCheckNo = ref(false)
    const signs = ref([])
    const paymentTypes = [
      { id: 'check', label: 'Check' },
      { id: 'online', label: 'Online' },
      { id: 'cash', label: 'Cash' }
    ]

    const form = ref({
      workgroup: null,
      company: null,
      date: new Date().toISOString().slice(0, 10),
      vendor: null,
      bank: null,
      payment_type: paymentTypes[0],
      check_no: '',
      memo: '',
      file: null
    })
    const isCheckType = computed(() => form.value.payment_type?.id === 'check')

    const totalAmount = computed(() => bills.value.reduce((sum, b) => sum + (b.selected ? (parseFloat(b.payment) || 0) : 0), 0))

    const formatAmount = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

    const loadWorkgroups = async () => {
      const { data } = await axios.get('/api/search/document-workgroups', { params: { query: '', column: 'name', all: true } })
      allWorkgroups.value = data?.collection || []
    }

    const onWorkgroupChange = async () => {
      form.value.company = null
      companies.value = []
      if (!form.value.workgroup?.id) return
      const { data } = await axios.get('/api/search/upload-companies', { params: { query: '', column: 'name', workgroup_id: form.value.workgroup.id,store_numbers: '8000,9000,10000,11000,12000,7000,224455' } })
      companies.value = (data.collection || data || []).map((company) => ({ ...company, type: form.value.workgroup.type }))
    }

    const onCompanyChange = async () => {
      form.value.vendor = null
      form.value.bank = null
      bills.value = []
      if (!form.value.company?.id) return

      loadingVendors.value = true
      loadingBanks.value = true
      try {
        const payload = { store_number: form.value.company.store_number, workgroup: form.value.workgroup?.name }
        const [{ data: vendorsData }, { data: banksData }, { data: signsData }] = await Promise.all([
          axios.post('/upload-portal/api/vendors', payload),
          axios.post('/upload-portal/api/banks', payload),
          axios.post('/upload-portal/api/signs', payload),
        ])
        vendors.value = vendorsData || []
        banks.value = banksData?.map((bank) => ({ ...bank, name: `${bank.code || ''} - ${bank.name || ''}`.trim() })) || []
        signs.value = signsData?.map((sign) => ({ ...sign, name:sign.sign_name })) || []
      } finally {
        loadingVendors.value = false
        loadingBanks.value = false
      }
    }

    const onVendorChange = async () => {
      bills.value = []
      if (!form.value.vendor?.id || !form.value.company?.id) return
      const { data } = await axios.post('/upload-portal/api/vendor-bills', {
        company_id: form.value.company.id,
        qq_vendor_id: form.value.vendor.id
      })
      bills.value = (data || []).map((bill) => ({ ...bill, selected: false, payment: '' }))
    }

    const onBankChange = async () => {
      if (!form.value.bank?.id || !form.value.company?.store_number) return
      loadingCheckNo.value = true
      try {
        const { data } = await axios.post('/upload-portal/api/latest-check-number', {
          bank_id: form.value.bank.id,
          store_number: form.value.company.store_number,
          workgroup: form.value.workgroup?.name
        })
        if(form.value.payment_type?.id == 'check'){
          form.value.check_no = parseInt(data.data)
        }
        
      } finally {
        loadingCheckNo.value = false
      }
    }

    const onBillToggle = (bill) => {
      bill.payment = bill.selected ? String(bill.due_amount) : ''
    }

    const onBillPaymentInput = (bill) => {
      const payment = parseFloat(bill.payment || 0)
      if (payment > parseFloat(bill.due_amount)) {
        bill.payment = String(bill.due_amount)
      }
    }

    const onPaymentTypeChange = () => {
      form.value.sign = null
      if(form.value.payment_type.id != 'check') {
        form.value.sign = null
        form.value.check_no = ''
      }else{
        form.value.sign = signs.value.length > 0 ? signs.value[0] : null
      }
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

    const resetForm = () => {
      form.value = {
        workgroup: null,
        company: null,
        date: new Date().toISOString().slice(0, 10),
        vendor: null,
        bank: null,
        payment_type: paymentTypes[0],
        check_no: '',
        memo: '',
        file: null
      }
      companies.value = []
      vendors.value = []
      banks.value = []
      bills.value = []
    }

    const applyPrefillData = async () => {
      if (!props.prefillData) return
      const prefillCompany = props.prefillData.company || null
      if (!prefillCompany?.id) return

      const workgroupId = Number(
        props.prefillData.workgroup_id ??
        prefillCompany.workgroup_id ??
        prefillCompany.workgroup?.id
      )
      const workgroupName = String(
        props.prefillData.workgroup_name ??
        prefillCompany.workgroup?.name ??
        ''
      ).trim().toLowerCase()
      const vendorId = Number(props.prefillData.vendor_id)
      const billIds = Array.isArray(props.prefillData.bill_ids)
        ? props.prefillData.bill_ids.map((id) => Number(id))
        : []
      const billAmountMap = props.prefillData.bill_amounts || {}

      const workgroup = allWorkgroups.value.find((wg) => Number(wg.id) === workgroupId)
        || allWorkgroups.value.find((wg) => String(wg.name || '').trim().toLowerCase() === workgroupName)
        || null
      if (!workgroup) return

      form.value.workgroup = workgroup
      await onWorkgroupChange()

      const company = companies.value.find((item) => Number(item.id) === Number(prefillCompany.id))
        || companies.value.find((item) => Number(item.id) === Number(props.prefillData.company_id))
        || null
      if (!company) return

      form.value.company = company
      await onCompanyChange()

      if (vendorId) {
        form.value.vendor = vendors.value.find((item) => Number(item.id) === vendorId) || null
        if (form.value.vendor) {
          await onVendorChange()
        }
      }

      if (billIds.length && bills.value.length) {
        bills.value = bills.value.map((bill) => {
          const selected = billIds.includes(Number(bill.id))
          const amount =  bill.due_amount ?? billAmountMap[bill.id] ?? billAmountMap[String(bill.id)]
          return {
            ...bill,
            selected,
            payment: selected ? String(amount) : ''
          }
        })
      }
    }

    const handleSubmit = async (type = 'save') => {
      const selected = bills.value.filter((bill) => bill.selected && parseFloat(bill.payment || 0) > 0)
      if (!form.value.workgroup || !form.value.company || !form.value.vendor || !form.value.bank || !form.value.payment_type || !form.value.date || selected.length === 0) {
        alert('Please fill all required fields and select at least one bill.')
        return
      }
      if (!isCheckType.value && !form.value.file) {
        alert('Please upload payment file for non-check payment.')
        return
      }

      const invalidateAmount = bills.value.filter((bill) => bill.selected && parseFloat(bill.payment || 0) > parseFloat(bill.due_amount))
      if (invalidateAmount.length > 0) {
        alert('Payment amount cannot exceed due amount.')
        return
      }
      saving.value = true

      try {
        const payload = new FormData()
        payload.append('folder_id', props.folderId)
        payload.append('company_id', form.value.company.id)
        payload.append('company_store_number', form.value.company.store_number)
        payload.append('workgroup', form.value.workgroup.name)
        payload.append('qq_vendor_id', form.value.vendor.id)
        payload.append('qq_vendor_name', form.value.vendor.name)
        payload.append('date', form.value.date)
        payload.append('qq_bank_id', form.value.bank.id)
        payload.append('payment_type', form.value.payment_type.id)
        payload.append('check_no', form.value.check_no || '')
        payload.append('memo', form.value.memo || '')
        payload.append('print_check', type === 'print-check' ? '1' : '0')

        payload.append('qq_sign_id', form.value.sign?.id)
        payload.append('items', JSON.stringify(selected.map((bill) => ({
          bill_id: bill.id,
          qq_purchase_id: bill.qq_purchase_id,
          due_amount: bill.due_amount,
          bill_number: bill.bill_number,
          amount: parseFloat(bill.payment || 0)
        }))))
        if (!isCheckType.value && form.value.file) {
          payload.append('file', form.value.file)
        }
        const { data } = await axios.post('/upload-portal/api/ap-payments', payload, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (data?.success) {
          if (type === 'print-check') {
            window.open(data.document, '_blank')
          }
          closeModal()
          emit('success')
        }
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to save payment')
      } finally {
        saving.value = false
      }
    }

    const closeModal = () => {
      resetForm()
      emit('update:modelValue', false)
    }

    watch(() => props.modelValue, async (open) => {
      if (!open) return
      if (!allWorkgroups.value.length) await loadWorkgroups()
      resetForm()
      await applyPrefillData()
    })

    return {
      allWorkgroups,
      companies,
      vendors,
      banks,
      bills,
      form,
      paymentTypes,
      loadingVendors,
      loadingBanks,
      loadingCheckNo,
      saving,
      totalAmount,
      formatAmount,
      onWorkgroupChange,
      onCompanyChange,
      onVendorChange,
      onBankChange,
      onBillToggle,
      onBillPaymentInput,
      onFileChange,
      handleSubmit,
      closeModal,
      isCheckType,
      signs,
      onPaymentTypeChange
    }
  }
}
</script>
