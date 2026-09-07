export default [
    {
        path: '/reports/ap/bill-wise',
        name: 'reports.ap.bill-wise',
        component: () => import('@/views/reports/ap/bill-wise-report.vue'),
        meta: {
            title: 'Bill Wise Report'
        }
    },
    {
        path: '/reports/ap/monthly',
        name: 'reports.ap.monthly',
        component: () => import('@/views/reports/ap/monthly-report.vue'),
        meta: {
            title: 'Monthly Report'
        }
    }
]
