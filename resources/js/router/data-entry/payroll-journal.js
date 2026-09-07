export default [
    {
        path: '/data-entry/payroll-journals',
        name: 'data-entry.payroll-journals.index',
        component: () => import('@/views/data-entry/payroll-journals/index.vue'),
        meta: {
            title: 'Payroll Journal',
            resource: 'data-entry/payroll-journals'
        }
    }
]
