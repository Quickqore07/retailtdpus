<template>
  <Modal :model-value="modelValue" size="7xl" @update:model-value="$emit('update:modelValue', $event)" :title="isEditMode ? 'Edit Invoice' : 'Add Invoice'">
    <div>
      <form @submit.prevent="handleSubmit" class="space-y-5 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 ">
          <DynamicDropdown
            v-model="localForm.invoice_type"
            label="Invoice Type"
            :custom-options="invoiceTypes"
            display-name="name"
            placeholder="Select invoice type"
            :required="true"
            @change="onInvoiceTypeChange" 
          />
        <!-- Workgroup -->
        <DynamicDropdown
          v-model="localForm.workgroup"
          label="Workgroup"
          :custom-options="allWorkgroups"
          display-name="name"
          placeholder="Select workgroup"
          :required="true"
          @change="onWorkgroupChange"
        />

        <!-- Company -->
        <DynamicDropdown
          v-model="localForm.company"
          label="Company"
          :custom-options="companies"
          display-name="name"
          placeholder="Select company"
          :required="true"
          :disabled="!localForm.workgroup || loadingCompanies"
          @change="onCompanyChange"
        />

        <!-- Everything below requires a company -->

          <!-- Vendor -->
          <div class="flex gap-2 items-end" v-if="isCheckInvoice">
            <div class="flex-1 min-w-0">
              <DynamicDropdown
                v-model="localForm.vendor"
                label="Vendor"
                :custom-options="vendorsList"
                display-name="name"
                placeholder="Select vendor"
                :required="true"
                :disabled="!companySelected || loadingVendors"
                :searchable="true"
                remove-null-option
                @change="onVendorChange"
              />
            </div>
            <Button
              type="button"
              variant="outline-secondary"
              size="sm"
              class="shrink-0 mb-0.5"
              :disabled="!companySelected || loadingLedgers"
              title="Add vendor"
              icon-only
              icon-left="plus"
              @click="openAddVendorModal"
            />
          </div>
          <Input
            v-if="!isCheckInvoice"
            v-model="localForm.invoice_name"
            label="Invoice Name"
            type="text"
            :required="true"
            placeholder="Enter invoice name"
          />
          <Input
            v-if="isCheckInvoice"
            v-model="localForm.invoice_date"
            label="Invoice Date"
            type="date"
            :required="true"
            :disabled="!companySelected"
          />

          <Input
            v-if="isCheckInvoice"
            v-model="localForm.bill_number"
            label="Invoice #"
            placeholder="Enter Invoice number"
            :required="true"
            :disabled="!companySelected"
          />

          <Input
            v-if="isCheckInvoice"
            v-model="localForm.due_date"
            label="Due date"
            type="date"
            :disabled="!companySelected"
          />

          <Input
            v-if="isCheckInvoice"
            :model-value="formattedTotalAmount"
            label="Amount"
            type="text"
            :disabled="true"
            help-text="Sum of line items"
          />

          <Input
            v-if="isCheckInvoice && localForm.status == 'approved'"
            v-model="localForm.approved_amount"
            label="Approved Amount"
            type="number"
            :disabled="localForm.status !== 'approved'"
            help-text="Approved amount"
          />


          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
              Remarks
            </label>
            <textarea
              v-model="localForm.remarks"
              rows="2"
              :disabled="!companySelected"
              placeholder="Optional remarks"
              class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 !focus:outline-none !focus:ring-0 disabled:opacity-60"
            ></textarea>
          </div>

          <Input
            v-if="isCheckInvoice"
            v-model="localForm.total_stores"
            label="Total Stores"
            type="number"
            :disabled="!companySelected"
            @change="onTotalStoresInput"
          />

          <Input
            v-if="isCheckInvoice"
            v-model="localForm.total_amount"
            label="Total Amount"
            placeholder="Enter total amount"
            type="number"
            :disabled="!companySelected"
            @change="onTotalStoresInput"
          />

          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
              Document file <span v-if="!isEditMode" class="text-red-500">*</span>
            </label>
            <input
              type="file"
              :disabled="!companySelected"
              @change="onFileChange"
              :required="!isEditMode"
              class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300 disabled:opacity-60"
            />
            <p v-if="localForm.file" class="text-xs text-gray-600 dark:text-gray-400 !mt-2">
              Selected: {{ localForm.file.name }}
            </p>
            <p v-else-if="isEditMode" class="text-xs text-gray-600 dark:text-gray-400 !mt-2">
              Keep empty to use existing uploaded bill.
            </p>
          </div>

          <!-- Line items -->
        </form>
        <div class="space-y-3 mt-3" v-if="isCheckInvoice">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Line items</h3>
            <Button
              type="button"
              variant="outline-secondary"
              size="sm"
              :disabled="!companySelected || loadingBusinessUnits"
              @click="addItemRow"
            >
              Add row
            </Button>
          </div>

          <div class=" border border-gray-200 dark:border-gray-700 rounded-lg">
            <table class="w-full text-sm min-w-[640px]">
              <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                  <th class="text-left py-2 px-2 w-10 font-medium text-gray-700 dark:text-gray-300">#</th>
                  <th class="text-left py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[300px]">Store</th>
                  <th class="text-left py-2 px-2 font-medium text-gray-700 dark:text-gray-300 w-[200px]">Expense</th>
                  <th class="text-left py-2 px-2 font-medium text-gray-700 dark:text-gray-300">Description</th>
                  <th class="text-right py-2 px-2 w-28 font-medium text-gray-700 dark:text-gray-300 w-[200px]">Amount</th>
                  <th class="w-12"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in localForm.items"
                  :key="row._key"
                  class="border-b border-gray-100 dark:border-gray-800 last:border-0 align-top"
                >
                  <td class="py-2 px-2 text-gray-600 dark:text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-2 px-2">
                    <DynamicDropdown
                      v-model="row.company"
                      label=""
                      :custom-options="businessUnits"
                      display-name="name"
                      placeholder="Store"
                      :required="true"
                      :disabled="!companySelected || loadingCompanies || loadingBusinessUnits"
                      custom-class="!gap-0"
                    />
                  </td>
                  <td class="py-2 px-2">
                    <DynamicDropdown
                      v-model="row.expense"
                      label=""
                      :custom-options="expenseLedgerOptions"
                      display-name="name"
                      placeholder="Ledger"  
                      :required="true"
                      :disabled="!companySelected || loadingLedgers"
                      :searchable="true"
                      remove-null-option
                      custom-class="!gap-0"
                    />
                  </td>
                  <td class="py-2 px-2 align-middle">

                      <Input
                        v-model="row.description"
                        type="text"
                        placeholder="Description"
                        :disabled="!companySelected"
                      />
                  </td>
                  <td class="py-2 px-2 align-middle">
                    <Input
                      v-model="row.amount"
                      type="number"
                      step="0.01"
                      min="0"
                      :disabled="!companySelected"
                    />
                  </td>
                  <td class="py-2 px-2 text-right align-middle">
                    <Button
                      type="button"
                      variant="outline-danger"
                      size="sm"
                      :disabled="!companySelected || localForm.items.length <= 1"
                      title="Remove row"
                      icon-left="trash"
                      @click="removeItemRow(idx)"
                    >
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="flex items-center justify-end gap-3 py-4 border-t border-gray-200 dark:border-gray-700 mt-3">
        <Button
          type="button"
          @click="closeModal"
          variant="outline-secondary"
          size="md"
        >
          Cancel
        </Button>
        <Button
          type="submit"
          @click="handleSubmit"
          :disabled="uploading || loadingInvoice || !companySelected || loadingBusinessUnits"
          variant="primary"
          size="md"
        >
          {{ uploading ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update Invoice' : 'Save Invoice') }}
        </Button>
      </div>
    </div>
  </Modal>

  <Modal
    :model-value="showAddVendorModal"
    title="Add vendor"
    size="4xl"
    @update:model-value="onAddVendorModalToggle"
  >
    <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" @submit.prevent="submitNewVendor">
      <Input
        v-model="newVendorForm.name"
        label="Name"
        placeholder="Vendor name"
        :required="true"
        :disabled="savingVendor"
      />
      <DynamicDropdown
        v-model="newVendorForm.expense_ledger"
        label="Expense ledger"
        :custom-options="expenseLedgerOptions"
        display-name="name"
        placeholder="Select ledger"
        :required="true"
        :disabled="!companySelected || loadingLedgers || savingVendor"
        :searchable="true"
        remove-null-option
      />
      <Input
        v-model="newVendorForm.email"
        label="Email"
        type="email"
        placeholder="email@example.com"
        :disabled="savingVendor"
      />
      <Input
        v-model="newVendorForm.mobile"
        label="Mobile"
        placeholder="Phone"
        :disabled="savingVendor"
      />
      <div class="sm:col-span-2">
        <Input
          v-model="newVendorForm.billing_address_line_1"
          label="Billing address line 1"
          placeholder="Line 1"
          :disabled="savingVendor"
        />
      </div>
      <div class="sm:col-span-2">
        <Input
          v-model="newVendorForm.billing_address_line_2"
          label="Billing address line 2"
          placeholder="Line 2"
          :disabled="savingVendor"
        />
      </div>
      <Input
        v-model="newVendorForm.city"
        label="City"
        :disabled="savingVendor"
      />
      <Input
        v-model="newVendorForm.state"
        label="State / province"
        :disabled="savingVendor"
      />
      <Input
        v-model="newVendorForm.country"
        label="Country"
        :disabled="savingVendor"
      />
      <Input
        v-model="newVendorForm.zip_code"
        label="ZIP / postal code"
        :disabled="savingVendor"
      />
      <Input
        v-model="newVendorForm.bill_day"
        label="Bill day / net days"
        type="number"
        :min="0"
        :max="366"
        :required="true"
        help-text="Used with bill condition (same rules as invoice due date)"
        :disabled="savingVendor"
      />
      <DynamicDropdown
        v-model="newVendorForm.bill_condition"
        label="Bill condition"
        :custom-options="billConditionOptions"
        display-name="name"
        placeholder="Select condition"
        :required="true"
        :disabled="savingVendor"
      />
      <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
        <Button type="button" variant="outline-secondary" :disabled="savingVendor" @click="onAddVendorModalToggle(false)">
          Cancel
        </Button>
        <Button type="submit" variant="primary" :disabled="savingVendor">
          {{ savingVendor ? 'Saving...' : 'Create vendor' }}
        </Button>
      </div>
    </form>
  </Modal>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import axios from '../plugins/axios'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Button from '@/components/ui/button.vue'
import Modal from '@/components/common/Modal.vue'
import { useMessage } from '@/composables/useMessage'
import { assignValidatedFile } from '@/utils/documentUpload'

let itemKeySeq = 0
function nextItemKey () {
  itemKeySeq += 1
  return `item-${itemKeySeq}`
}

function emptyItemRow () {
  return {
    _key: nextItemKey(),
    company: null,
    expense: null,
    description: '',
    amount: ''
  }
}

/** YYYY-MM-DD or null */
function formatDateInput (input) {
  if (input == null || input === '') return ''
  if (typeof input === 'number' && Number.isFinite(input)) return ''
  const s = String(input).trim()
  if (!s) return ''
  if (/^\d+$/.test(s)) return ''
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return ''
  return d.toISOString().slice(0, 10)
}

/** If vendor provides net days (numeric), combine with invoice date */
function computeDueFromVendorTerms (raw, invoiceDateYmd) {
  if (!raw || !invoiceDateYmd) return ''
  const d = raw.dueDate ?? raw.duedate ?? raw.due_date ?? raw.DueDate
  if (d == null) return ''
  if (typeof d === 'number' && Number.isFinite(d)) {
    const base = new Date(invoiceDateYmd + 'T12:00:00')
    base.setDate(base.getDate() + d)
    return base.toISOString().slice(0, 10)
  }
  const s = String(d).trim()
  if (/^\d+$/.test(s)) {
    const days = parseInt(s, 10)
    const base = new Date(invoiceDateYmd + 'T12:00:00')
    base.setDate(base.getDate() + days)
    return base.toISOString().slice(0, 10)
  }
  return formatDateInput(d)
}

function getVendorPayload (vendor) {
  return vendor?.raw ?? vendor ?? null
}

function getVendorExpenseLedger (vendor, expenseLedgerOptions) {
  const raw = getVendorPayload(vendor)
  const options = expenseLedgerOptions?.value ?? []
  const expenseLedger = options.find((ledger) => Number(ledger.id) === Number(raw?.expense_ledgerid)) ?? null
  return expenseLedger ?? null
}

function computeDueDateFromBillTerms (vendor, invoiceDateYmd) {
  const raw = getVendorPayload(vendor)
  if (!raw || !invoiceDateYmd) return ''

  const billDayRaw = raw?.vendordetails?.billDay
  const billConditionRaw = raw?.vendordetails?.billCondition
  const billDay = Number.parseInt(String(billDayRaw ?? '').trim(), 10)
  const billCondition = String(billConditionRaw ?? '').trim().toLowerCase()
  if (!Number.isFinite(billDay)) return ''

  const base = new Date(`${invoiceDateYmd}T12:00:00`)
  if (Number.isNaN(base.getTime())) return ''

  const sameMonthDay = () => {
    const due = new Date(base)
    due.setDate(1)
    due.setMonth(due.getMonth() + 1)
    due.setDate(0)
    const lastDay = due.getDate()
    due.setDate(Math.max(1, Math.min(billDay, lastDay)))
    return due.toISOString().slice(0, 10)
  }

  if (billCondition === 'of the following month') {
    const due = new Date(base)
    due.setMonth(due.getMonth() + 1, 1)
    due.setMonth(due.getMonth() + 1, 0)
    const lastDay = due.getDate()
    due.setDate(Math.max(1, Math.min(billDay, lastDay)))
    return due.toISOString().slice(0, 10)
  }

  if (billCondition === 'day(s) after the invoice date') {
    const due = new Date(base)
    due.setDate(due.getDate() + billDay)
    return due.toISOString().slice(0, 10)
  }

  if (billCondition === 'day(s) after the end of the invoice month') {
    const monthEnd = new Date(base.getFullYear(), base.getMonth() + 1, 0, 12, 0, 0)
    monthEnd.setDate(monthEnd.getDate() + billDay)
    return monthEnd.toISOString().slice(0, 10)
  }

  if (billCondition === 'of current month' || billCondition === '') {
    return sameMonthDay()
  }

  return ''
}

/** Bill condition strings must match `computeDueDateFromBillTerms` (lowercased in that function). */
const billConditionOptions = [
  { id: 'of current month', name: 'Of current month' },
  { id: 'of the following month', name: 'Of the following month' },
  { id: 'day(s) after the invoice date', name: 'Day(s) after the invoice date' },
  { id: 'day(s) after the end of the invoice month', name: 'Day(s) after the end of the invoice month' }
]

export default {
  name: 'InvoiceModal',
  components: {
    Input,
    DynamicDropdown,
    Button,
    Modal
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    folderId: {
      type: [String, Number],
      default: null
    },
    isAllDocumentsView: {
      type: Boolean,
      default: false
    },
    allFolders: {
      type: Array,
      default: () => []
    },
    document: {
      type: Object,
      default: null
    }
  },
  emits: ['update:modelValue', 'success'],
  setup (props, { emit }) {
    const invoiceTypes = [
      { id: 'check', name: 'Check' },
      { id: 'auto', name: 'Auto' }
    ]
    const uploading = ref(false)
    const loadingInvoice = ref(false)
    const allWorkgroups = ref([])
    const companies = ref([])
    const loadingCompanies = ref(false)
    const vendorsList = ref([])
    const loadingVendors = ref(false)
    const expenseLedgerOptions = ref([])
    const loadingLedgers = ref(false)
    const allowedItemCompanyIds = ref(null)
    const loadingBusinessUnits = ref(false)
    const businessUnits = ref([])

    const localForm = ref({
      id: null,
      invoice_name: '',
      invoice_type: invoiceTypes[0],
      folder: null,
      workgroup: null,
      company: null,
      vendor: null,
      invoice_date: '',
      bill_number: '',
      ledger: null,
      due_date: '',
      remarks: '',
      file: null,
      items: [emptyItemRow()]
    })

    const message = useMessage()
    const isEditMode = computed(() => !!props.document?.id)
    const isCheckInvoice = computed(() => localForm.value.invoice_type?.id === 'check')
    const companySelected = computed(() => !!(localForm.value.company && localForm.value.company.id))

    /** Line-item companies allowed for header company’s business-units */
    const itemCompanies = computed(() => {
      if (!companySelected.value) return []
      const ids = allowedItemCompanyIds.value
      if (!ids || ids.length === 0) return []
      const allowed = new Set(ids)
      return companies.value.filter((c) => allowed.has(c.id))
    })


    watch(() => props.folderId, (newVal) => {
      localForm.value.folder = newVal
    })
    const totalAmount = computed(() =>
      localForm.value.items.reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0)
    )

    const formattedTotalAmount = computed(() => totalAmount.value.toFixed(2))


    const loadWorkgroups = async () => {
      try {
        const endpoint = '/api/search/document-workgroups'
        const params = { query: '', column: 'name', all: true }
        const response = await axios.get(endpoint, { params })
        allWorkgroups.value = response.data?.collection || []
      } catch (error) {
        message.error(error.response?.data?.message || 'Error loading workgroups')
      }
    }

    const loadCompaniesByWorkgroup = async (workgroup) => {
      if (!workgroup || !workgroup.id) {
        companies.value = []
        return
      }
      loadingCompanies.value = true
      try {
        const response = await axios.get('/api/search/upload-companies', {
          params: {query: '',column: 'name',workgroup_id: workgroup.id, ...(isCheckInvoice.value ? {store_numbers: '8000,9000,10000,11000,12000,7000,224455'} : {})}
        })
        if (response.data) {
          companies.value = (response.data.collection || response.data || []).map((company) => ({
            ...company,
            type: workgroup.type
          }))
        }
      } catch (error) {
        message.error(error.response?.data?.message || 'Error loading companies')
        companies.value = []
      } finally {
        loadingCompanies.value = false
      }
    }


    const loadBusinessUnits = async () => {
      loadingBusinessUnits.value = true
      try {
        const payload = {
          store_number: localForm.value.company.store_number,
          workgroup: localForm.value.workgroup?.name
        }
        const { data } = await axios.post('/upload-portal/api/business-units', payload)
        businessUnits.value = data

        if(!isEditMode.value && !props.document?.id){
          localForm.value.items=[];
          let expenseLedger = expenseLedgerOptions.value.find((ledger) => Number(ledger.id) === Number(localForm.value.vendor?.expense_ledgerid))
          localForm.value.total_stores = businessUnits.value.length;
          businessUnits.value.forEach((businessUnit) => {
            localForm.value.items.push({
              company: businessUnit,
              expense: expenseLedger,
              description: '',
              amount: 0
            })
          })
        }
      } catch (e) {
        // message.error(e.response?.data?.message || 'Error loading business units')
        businessUnits.value = []
      } finally {
        loadingBusinessUnits.value = false
      }
    }

    const loadVendors = async () => {
      if (!companySelected.value) return
      loadingVendors.value = true
      try {
        const payload = {
          store_number: localForm.value.company?.store_number,
          workgroup: localForm.value.workgroup?.name
        }
        const { data } = await axios.post('/upload-portal/api/vendors', payload)
        vendorsList.value = data
        if (isEditMode.value) {
          const existingVendor = vendorsList.value.find((vendor) => Number(vendor.id) === Number(localForm.value.qq_vendor_id))
          if (existingVendor) {
            localForm.value.vendor = existingVendor
          }
        }
      } catch (e) {
        // message.error(e.response?.data?.message || 'Error loading vendors')
        vendorsList.value = []
      } finally {
        loadingVendors.value = false
      }
    }

    const loadExpenseLedgers = async () => {
      if (!companySelected.value) return
      loadingLedgers.value = true
      try {
        const payload = {
          store_number: localForm.value.company?.store_number,
          workgroup: localForm.value.workgroup?.name
        }
        const { data } = await axios.post('/upload-portal/api/expense-ledgers', payload)
        expenseLedgerOptions.value = data

        if (isEditMode.value) {
          localForm.value.items.forEach((row) => {
            row.expense = expenseLedgerOptions.value.find((ledger) => Number(ledger.id) === Number(row.qq_ledger_id))
          })
        }
      } catch (e) {
        message.error(e.response?.data?.message || 'Error loading expense ledgers')
        expenseLedgerOptions.value = []
      } finally {
        loadingLedgers.value = false
      }
    }

    const applyVendorDefaults = (vendor) => {
      if (!vendor) return

      const expenseLedger = getVendorExpenseLedger(vendor, expenseLedgerOptions)
      if (expenseLedger) {
        localForm.value.items.forEach((row) => {
          if (!row.expense) row.expense = expenseLedger
        })
      }

      const computedDue = computeDueDateFromBillTerms(vendor, localForm.value.invoice_date)
      if (computedDue) {
        localForm.value.due_date = computedDue
      }
    }

    const onWorkgroupChange = () => {
      localForm.value.company = null
      companies.value = []
      resetVendorAndItemsCompany()
      if (localForm.value.workgroup) {
        loadCompaniesByWorkgroup(localForm.value.workgroup)
      }
    }

    const resetVendorAndItemsCompany = () => {
      localForm.value.vendor = null
      localForm.value.due_date = ''
      vendorsList.value = []
      expenseLedgerOptions.value = []
      allowedItemCompanyIds.value = null
      localForm.value.items = [emptyItemRow()]
    }

    const onCompanyChange = async () => {
      if(!isCheckInvoice.value) return;
      localForm.value.vendor = null
      localForm.value.due_date = ''
      localForm.value.items = [emptyItemRow()]
      if (localForm.value.company) {
        await loadBusinessUnits()
        await Promise.all([loadExpenseLedgers(), loadVendors()])
      } else {
        allowedItemCompanyIds.value = null
        vendorsList.value = []
        expenseLedgerOptions.value = []
      }
    }

    const onVendorChange = () => {
      if (localForm.value.vendor) {
        applyVendorDefaults(localForm.value.vendor)
      }
    }

    const onInvoiceTypeChange = () => {
      let type = localForm.value.invoice_type
      resetForm()
      localForm.value.invoice_type = type
    }

    const showAddVendorModal = ref(false)
    const savingVendor = ref(false)
    const newVendorForm = ref({
      name: '',
      expense_ledger: null,
      email: '',
      mobile: '',
      billing_address_line_1: '',
      billing_address_line_2: '',
      city: '',
      state: '',
      country: '',
      zip_code: '',
      bill_day: '15',
      bill_condition: null
    })

    const resetNewVendorForm = () => {
      newVendorForm.value = {
        name: '',
        expense_ledger: null,
        email: '',
        mobile: '',
        billing_address_line_1: '',
        billing_address_line_2: '',
        city: '',
        state: '',
        country: '',
        zip_code: '',
        bill_day: '15',
        bill_condition: billConditionOptions[0]
      }
    }

    const onAddVendorModalToggle = (open) => {
      showAddVendorModal.value = open
      if (!open) {
        savingVendor.value = false
        resetNewVendorForm()
      }
    }

    const openAddVendorModal = () => {
      if (!companySelected.value || !localForm.value.workgroup?.name) {
        message.error('Select workgroup and company first')
        return
      }
      resetNewVendorForm()
      showAddVendorModal.value = true
    }

    const submitNewVendor = async () => {
      const f = newVendorForm.value
      if (!f.name?.trim()) {
        message.error('Vendor name is required')
        return
      }
      if (!f.expense_ledger?.id) {
        message.error('Expense ledger is required')
        return
      }
      const billDay = parseInt(String(f.bill_day ?? '').trim(), 10)
      if (!Number.isFinite(billDay) || billDay < 0) {
        message.error('Bill day / net days must be a valid number')
        return
      }
      if (!f.bill_condition?.id) {
        message.error('Bill condition is required')
        return
      }

      savingVendor.value = true
      try {
        const { data } = await axios.post('/upload-portal/api/vendors/create', {
          store_number: localForm.value.company.store_number,
          workgroup: String(localForm.value.workgroup.name),
          name: f.name.trim(),
          expense_ledger_id: f.expense_ledger.id,
          email: f.email?.trim() || null,
          mobile: f.mobile?.trim() || null,
          billing_address_line_1: f.billing_address_line_1?.trim() || null,
          billing_address_line_2: f.billing_address_line_2?.trim() || null,
          city: f.city?.trim() || null,
          state: f.state?.trim() || null,
          country: f.country?.trim() || null,
          zip_code: f.zip_code?.trim() || null,
          bill_day: billDay,
          bill_condition: f.bill_condition.id
        })
        if (!data.success) {
          message.error(data.message || 'Could not create vendor')
          return
        }
        message.success('Vendor created')
        onAddVendorModalToggle(false)
        await loadVendors()
        const createdId = Number(data.vendor?.id)
        const created =
          vendorsList.value.find((v) => Number(v.id) === createdId) ||
          data.vendor
        if (created) {
          localForm.value.vendor = created
          onVendorChange()
        }
      } catch (e) {
        message.error(e.response?.data?.message || 'Error creating vendor')
      } finally {
        savingVendor.value = false
      }
    }

    const onTotalStoresInput = () => {
      if(localForm.value.total_stores && localForm.value.total_amount){
        let expenseLedger = expenseLedgerOptions.value.find((ledger) => Number(ledger.id) === Number(localForm.value.vendor?.expense_ledgerid))
        if(localForm.value.items.length === 0){
          localForm.value.items=[];
          for (let i = 0; i < localForm.value.total_stores; i++) {
            const row = emptyItemRow()
            row.company = localForm.value.company
            row.expense = expenseLedger ?? null
            row.amount = (localForm.value.total_amount / ( localForm.value.total_stores > 0 ? localForm.value.total_stores : 1)).toFixed(2)
            localForm.value.items.push(row) 
          }
        }else{
          localForm.value.items.forEach((row) => {
            row.amount = (localForm.value.total_amount / ( localForm.value.total_stores > 0 ? localForm.value.total_stores : 1)).toFixed(2)
          })
        }
      }
    }


    const addItemRow = () => {
      let expenseLedger = expenseLedgerOptions.value.find((ledger) => Number(ledger.id) === Number(localForm.value.vendor?.expense_ledgerid))
      const row = emptyItemRow()
      row.company = localForm.value.company
      row.expense = expenseLedger ?? null
      localForm.value.items.push(row)
    }

    const removeItemRow = (idx) => {
      if (localForm.value.items.length <= 1) return
      localForm.value.items.splice(idx, 1)
    }

    const onFileChange = (event) => {
      const input = event.target
      assignValidatedFile(input?.files?.[0] || null, (file) => {
        localForm.value.file = file
      }, {
        onError: (error) => message.error(error),
        input,
      })
    }

    const normalizeItemRows = (items) => {
      if (!Array.isArray(items) || items.length === 0) return [emptyItemRow()]
      return items.map((row) => ({
        _key: nextItemKey(),
        company: row?.company ? { id: row.company.id, name: row.company.name, store_number: row.company.store_number } : null,
        expense: null,
        qq_ledger_id: row?.qq_ledger_id || null,
        description: row?.description || '',
        amount: row?.amount ?? ''
      }))
    }

    const hydrateEditForm = async () => {
      if (!isEditMode.value || !props.document?.id) return
      loadingInvoice.value = true
      try {
        const { data } = await axios.get(`/upload-portal/api/ap-invoices/${props.document.id}`)
        if (!data?.success) return

        const invoice = data.invoice || {}
        const document = data.document || props.document
        const company = data.company || document.company || props.document.company || null

        localForm.value.id = document.id
        localForm.value.company = company ? { ...company } : null
        localForm.value.bill_number = invoice.bill_number || ''
        localForm.value.invoice_date = formatDateInput(invoice.date)
        localForm.value.due_date = formatDateInput(invoice.due_date)
        localForm.value.remarks = invoice.remarks || ''
        localForm.value.file = null
        localForm.value.vendor =  null
        localForm.value.qq_vendor_id = invoice.qq_vendor_id || null
        localForm.value.items = normalizeItemRows(invoice.items)
        localForm.value.invoice_name = props.document?.name || ''
        localForm.value.status = invoice.status || 'draft';
        if(invoice.status == 'approved'){
          localForm.value.approved_amount = invoice.approved_amount || 0;
        }
        localForm.value.invoice_type = invoice.invoice_type ? invoiceTypes.find((type) => type.id === invoice.invoice_type) : invoiceTypes[0]

        localForm.value.total_stores = invoice.items?.length || 0;
        localForm.value.total_amount = invoice.amount || 0;
        if (localForm.value.company?.workgroup_id) {
          const matchedWorkgroup = allWorkgroups.value.find((wg) => Number(wg.id) === Number(localForm.value.company.workgroup_id))
          localForm.value.workgroup = {
            id: localForm.value.company.workgroup_id,
            name: matchedWorkgroup?.name || localForm.value.company.workgroup?.name || '',
            type: matchedWorkgroup?.type || localForm.value.company.type || 'company'
          }
          if(isCheckInvoice.value){
            await loadCompaniesByWorkgroup(localForm.value.workgroup)
            await loadExpenseLedgers()
            await loadVendors()
            await loadBusinessUnits()
          }
        }

        

      } catch (error) {
        message.error(error.response?.data?.message || 'Error loading invoice details')
      } finally {
        loadingInvoice.value = false
      }
    }

    watch(
      () => [localForm.value.invoice_date, localForm.value.vendor],
      () => {
        const v = localForm.value.vendor
        if (!v || !localForm.value.invoice_date) return
        const raw = getVendorPayload(v)
        const termDueFromBill = computeDueDateFromBillTerms(v, localForm.value.invoice_date)
        if (termDueFromBill) {
          localForm.value.due_date = termDueFromBill
          return
        }
        const absDue = formatDateInput(raw?.dueDate ?? raw?.duedate ?? raw?.due_date ?? raw?.DueDate)
        if (absDue) {
          localForm.value.due_date = absDue
          return
        }
        const termDue = computeDueFromVendorTerms(raw, localForm.value.invoice_date)
        if (termDue) localForm.value.due_date = termDue
      }
    )

    const handleSubmit = async () => {
      if(isCheckInvoice.value){

        if (!isEditMode.value && props.isAllDocumentsView && !localForm.value.folder) {
          message.error('Please select a folder')
          return
        }
        if (!localForm.value.workgroup || !localForm.value.company) {
          message.error('Please select workgroup and company')
          return
        }
        if(localForm.value.status == 'approved' && ((parseFloat(localForm.value.approved_amount) > parseFloat(localForm.value.total_amount)) || parseFloat(localForm.value.approved_amount) < 0)){
          message.error('Approved amount must be greater than zero and less than total amount')
          return
        }
        if (
          !localForm.value.vendor ||
          !localForm.value.invoice_date ||
          !localForm.value.bill_number ||
          (!isEditMode.value && !localForm.value.file)
        ) {
          message.error('Please fill in vendor, date, bill no., ledger, and document file.')
          return
        }
        for (let i = 0; i < localForm.value.items.length; i += 1) {
          const row = localForm.value.items[i]
          if (!row.company?.id || !row.expense?.id) {
            message.error(`Line ${i + 1}: company and expense are required.`)
            return
          }
        }

        if (totalAmount.value <= 0) {
          message.error('Total amount must be greater than zero.')
          return
        }
      }else{
        if (!localForm.value.invoice_name) {
          message.error('Invoice name is required')
          return
        }
      }

      uploading.value = true
      try {
        const folderIdToUse = isEditMode.value
          ? (props.document?.folder_id || props.folderId)
          : (props.isAllDocumentsView ? localForm.value.folder : props.folderId)

        const formData = new FormData()

        formData.append('invoice_type', localForm.value.invoice_type.id)
        if (!isEditMode.value) {
          formData.append('folder_id', folderIdToUse)
        }
        if(isCheckInvoice.value){
          formData.append('name', `Invoice ${localForm.value.bill_number}`)
         
          formData.append('company_store_number', String(localForm.value.company.store_number))
          formData.append('workgroup', String(localForm.value.workgroup.name))
          formData.append('qq_vendor_id', String(localForm.value.vendor.id))
          formData.append('vendor_name', String(localForm.value.vendor.name))
          formData.append('date', localForm.value.invoice_date)
          formData.append('bill_number', localForm.value.bill_number)
          localForm.value.status == 'approved' && formData.append('approved_amount', localForm.value.approved_amount)
          if (localForm.value.due_date) {
            formData.append('due_date', localForm.value.due_date)
          }
          const itemsPayload = localForm.value.items.map((row) => ({
            company_id: row.company.id,
            qq_ledger_id: row.expense.id,
            description: row.description || '',
            amount: parseFloat(row.amount) || 0,
            company_store_number: String(row.company.store_number)
          }))
          formData.append('items', JSON.stringify(itemsPayload))
        }else{
          formData.append('invoice_name', localForm.value.invoice_name)
        }
        localForm.value.file && formData.append('file', localForm.value.file)
        formData.append('remarks', localForm.value.remarks || '')
        formData.append('company_id', localForm.value.company.id);

        const endpoint = isEditMode.value
          ? `/upload-portal/api/ap-invoices/${props.document.id}`
          : '/upload-portal/api/ap-invoices'

        const requestConfig = {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }

        const response = isEditMode.value
          ? await axios.put(endpoint, formData, requestConfig)
          : await axios.post(endpoint, formData, requestConfig)

        if (response.data.success) {
          closeModal()
          emit('success')
        }
      } catch (error) {
        message.error(error.response?.data?.message || 'Error saving invoice')
      } finally {
        uploading.value = false
      }
    }

    const closeModal = () => {
      showAddVendorModal.value = false
      savingVendor.value = false
      resetNewVendorForm()
      emit('update:modelValue', false)
      resetForm()
      companies.value = []
      vendorsList.value = []
      expenseLedgerOptions.value = []
      allowedItemCompanyIds.value = null
    }

    const resetForm = () => {
      localForm.value = {
          id: null,
          folder: null,
          workgroup: null,
          company: null,
          vendor: null,
          invoice_date: '',
          bill_number: '',
          ledger: null,
          due_date: '',
          remarks: '',
          file: null,
          items: [emptyItemRow()]
        }
    }
    watch(
      () => props.modelValue,
      async (newValue) => {
        resetForm()
        companies.value = []
        vendorsList.value = []
        expenseLedgerOptions.value = []
        if (!newValue) return
        if (allWorkgroups.value.length === 0) {
          await loadWorkgroups()
        }
        if (isEditMode.value) {
          await hydrateEditForm()
        }
      }
    )

    return {
      uploading,
      allWorkgroups,
      companies,
      loadingCompanies,
      loadingBusinessUnits,
      vendorsList,
      loadingVendors,
      expenseLedgerOptions,
      loadingLedgers,
      loadingInvoice,
      businessUnits,
      localForm,
      isEditMode,
      companySelected,
      itemCompanies,
      formattedTotalAmount,
      onWorkgroupChange,
      onCompanyChange,
      onVendorChange,
      billConditionOptions,
      showAddVendorModal,
      savingVendor,
      newVendorForm,
      onAddVendorModalToggle,
      openAddVendorModal,
      submitNewVendor,
      onFileChange,
      addItemRow,
      removeItemRow,
      handleSubmit,
      closeModal,
      onTotalStoresInput,
      invoiceTypes,
      isCheckInvoice,
      onInvoiceTypeChange
    }
  }
}
</script>
