export default [
    {
        path: '/reports/finance',
        name: 'reports.finance',
        component: () => import('@/views/reports/finance/finance-report.vue'),
        meta: {
            title: 'Finance Report'
        }
    },
    {
        path: '/reports/finance/detailed',
        name: 'reports.finance.detailed',
        component: () => import('@/views/reports/finance/detailed-finance-report.vue'),
        meta: {
            title: 'Detailed Finance Report'
        }
    },
    {
        path: '/reports/finance/store-wise',
        name: 'reports.finance.store-wise',
        component: () => import('@/views/reports/finance/store-wise-finance-report.vue'),
        meta: {
            title: 'Store Wise Finance Report'
        }
    },
    {
        path: '/reports/finance/pl',
        name: 'reports.finance.pl',
        component: () => import('@/views/reports/finance/pl-report.vue'),
        meta: {
            title: 'P&L Report'
        }
    }
]
