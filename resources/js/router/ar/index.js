import pjPayments from './pj-payment';
import pjLedgers from './pj-ledgers';
import feesUploads from './fees-uploads';
import reports from './report';

export default [
    ...pjPayments,
    ...pjLedgers,
    ...feesUploads,
    ...reports,
]
