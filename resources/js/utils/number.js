export const formatNumber = (number) => {
  if (!number) return '0.00'
  return parseFloat(number).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}
export const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return "-";
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};