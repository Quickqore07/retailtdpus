import foodPurchase from './food-purchase';
import idealCost from './ideal-cost';
import dailySales from './daily-sales';
import bankDeposit from './bank-deposit';
import shortage from './shortage';
import bankEntry from './bank-entry';
import bankUpload from './bank-upload';
import wcEntry from './wc-entry';
import driver from './driver';
import payrollJournal from './payroll-journal';
import royaltyFees from './royalty-fees';
export default [
    ...foodPurchase,
    ...idealCost,
    ...dailySales,
    ...bankDeposit,
    ...shortage,
    ...bankEntry,
    ...bankUpload,
    ...wcEntry,
    ...driver,
    ...payrollJournal,
    ...royaltyFees,
]
