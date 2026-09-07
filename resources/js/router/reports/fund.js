export default [
    {
        path: '/reports/fund/company-wise',
        name: 'reports.fund.company-wise',
        component: () => import('@/views/reports/fund/company-wise-fund-report.vue'),
        meta: {
            title: 'Company Wise Fund Report'
        }
    },
    {
        path: '/reports/fund/bank-report',
        name: 'reports.fund.bank-report',
        component: () => import('@/views/reports/fund/bank-report.vue'),
        meta: {
            title: 'Bank Report'
        }
    }
]
