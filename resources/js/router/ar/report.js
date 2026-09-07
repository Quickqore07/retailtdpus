export default [
    {
        path: '/ar/weekly-network-ar-report',
        name: 'ar.weekly-network-ar-report',
        component: () => import('@/views/ar/weekly-network-ar-report.vue'),
        meta: {
            title: 'Weekly Network AR Report'
        }
    },
    {
        path: '/ar/balance-due-network-report',
        name: 'ar.balance-due-network-report',
        component: () => import('@/views/ar/balance-due-network-report.vue'),
        meta: {
            title: 'Balance Due Network Report'
        }
    },
    {
        path: '/ar/missing-bank-amount-report',
        name: 'ar.missing-bank-amount-report',
        component: () => import('@/views/ar/missing-bank-amount-report.vue'),
        meta: {
            title: 'Missing Bank Amount Report'
        }
    },
    {
        path: '/ar/deposit-report',
        name: 'ar.deposit-report',
        component: () => import('@/views/ar/deposit-report.vue'),
        meta: {
            title: 'Visa/Mastercard Deposit Report'
        }
    },
]
