export default [
    {
        path: '/payroll/mwa',
        name: 'payroll.mwa.index',
        component: () => import('@/views/payroll/mwa/index.vue'),
        meta: {
            title: 'MWA',
            resource: 'payroll/mwa'
        }
    }
]
