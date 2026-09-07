export default [
    {
        path: '/data-entry/bank-deposits',
        name: 'data-entry.bank-deposits.index',
        component: () => import('@/views/data-entry/bank-deposit/index.vue'),
        meta: {
            title: 'Bank Deposits',
            resource: 'data-entry/bank-deposits'
        }
    },
    {
        path: '/data-entry/bank-deposits/:id',
        name: 'data-entry.bank-deposits.show',
        component: () => import('@/views/data-entry/bank-deposit/show.vue'),
        meta: {
            title: 'Bank Deposits',
            resource: 'data-entry/bank-deposits'
        }
    },
    {
        path: '/data-entry/bank-deposits/:id/edit',
        name: 'data-entry.bank-deposits.edit',
        component: () => import('@/views/data-entry/bank-deposit/form.vue'),
        meta: {
            title: 'Manage Bank Deposits',
            resource: 'data-entry/bank-deposits',
            mode: 'edit'
        }
    }
]
