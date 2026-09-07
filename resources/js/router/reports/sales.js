export default [
   
    {
        path: '/reports/sales/network-weekly-sales',
        name: 'reports.sales.network-weekly-sales',
        component: () => import('@/views/reports/sales/network-weekly-sales-report.vue'),
        meta: {
            title: 'Network Weekly Sales Report'
        }
    },
    {
        path: '/reports/sales/network-monthly-sales',
        name: 'reports.sales.network-monthly-sales',
        component: () => import('@/views/reports/sales/network-monthly-sales-report.vue'),
        meta: {
            title: 'Network Monthly Sales Report'
        }
    }
]