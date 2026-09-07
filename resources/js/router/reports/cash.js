export default [
    {
        path: '/reports/cash/bank-deposit',
        name: 'reports.cash.bank-deposit',
        component: () => import('@/views/reports/cash/bank-deposit-report.vue'),
        meta: {
            title: 'Bank Deposit Report'
        }
    },
    {
        path: '/reports/cash/ledger',
        name: 'reports.cash.ledger',
        component: () => import('@/views/reports/cash/cash-ledger-report.vue'),
        meta: {
            title: 'Cash Ledger Report'
        }
    },
    {
        path: '/reports/cash/daily-cash',
        name: 'reports.cash.daily-cash',
        component: () => import('@/views/reports/cash/daily-cash-report.vue'),
        meta: {
            title: 'Daily Cash Report'
        }
    },
    {
        path: '/reports/cash/cash-short-sales',
        name: 'reports.cash.cash-short-sales',
        component: () => import('@/views/reports/cash/cash-short-sales-report.vue'),
        meta: {
            title: 'Cash Short Sales Report'
        }
    },
    {
        path: '/reports/cash/cash-payout-sales',
        name: 'reports.cash.cash-payout-sales',
        component: () => import('@/views/reports/cash/cash-payout-sales-report.vue'),
        meta: {
            title: 'Cash Payout Sales Report'
        }
    },
    {
        path: '/reports/cash/pending-bank-deposit',
        name: 'reports.cash.pending-bank-deposit',
        component: () => import('@/views/reports/cash/pending-bank-deposit-report.vue'),
        meta: {
            title: 'Pending Bank Deposit Report'
        }
    }
]
