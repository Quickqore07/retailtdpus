<template>
  <div
    v-if="invoice"
    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4"
  >
    <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-200 !mb-3">
      Invoice Details
    </h6>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3">
      <Label label="Company" :value="invoice.company?.name || '-'" />
      <Label label="Vendor" :value="invoice.vendor?.name || '-'" />
      <Label label="Invoice #" :value="invoice.invoice_no || '-'" />
      <Label label="Expense Type" :value="formatExpenseType(invoice.expense)" />
      <Label label="Invoice Date" :value="formatDate(invoice.invoice_date)" />
      <Label label="Due Date" :value="formatDate(invoice.due_date)" />
      <Label :label="amountLabels.amount" :value="formatCurrency(invoice.amount)" />
      <Label
        v-if="amountLabels.showOtherAmount"
        :label="amountLabels.otherAmount"
        :value="formatCurrency(invoice.other_amount)"
      />
      <Label label="Total Amount" :value="formatCurrency(invoice.total_amount ?? invoice.amount)" />
      <Label label="Remarks" :value="invoice.remarks || '-'" value-custom-class="break-words" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Label from '@/components/ui/label.vue'
import { formatDate } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { formatExpenseType, getExpenseAmountLabels } from '@/utils/purchaseInvoiceExpense'

const props = defineProps({
  invoice: {
    type: Object,
    default: null,
  },
})

const amountLabels = computed(() => getExpenseAmountLabels(props.invoice?.expense))
</script>
