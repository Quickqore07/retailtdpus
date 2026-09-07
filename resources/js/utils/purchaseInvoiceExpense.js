export const ELECTRIC_GAS_EXPENSE_NAME = 'Electric / Gas'

export function isElectricGasExpense(expense) {
  return expense?.name === ELECTRIC_GAS_EXPENSE_NAME
}

export function getExpenseAmountLabels(expense) {
  if (!expense) {
    return { amount: 'Amount', otherAmount: 'Other Amount', showOtherAmount: true, amountRequired: true }
  }

  return {
    amount: expense.amount_label || 'Amount',
    otherAmount: expense.other_amount_label || 'Other Amount',
    showOtherAmount: !!expense.show_other_amount,
    amountRequired: !isElectricGasExpense(expense),
  }
}

export function formatExpenseType(expense) {
  if (!expense) return '-'
  if (typeof expense === 'string') return expense
  return expense.name || '-'
}

export function calculateTotalAmount(amount, otherAmount) {
  const primary = parseFloat(amount) || 0
  const secondary = parseFloat(otherAmount) || 0
  return primary + secondary
}
