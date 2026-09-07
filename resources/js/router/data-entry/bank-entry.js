export default [
    {
        path: '/data-entry/bank-entries',
        name: 'data-entry.bank-entries.index',
        component: () => import('@/views/data-entry/bank-entries/index.vue'),
        meta: {
            title: 'Bank Entries',
            resource: 'data-entry/bank-entries'
        }
    },
    {
        path: '/data-entry/bank-entries/:id',
        name: 'data-entry.bank-entries.show',
        component: () => import('@/views/data-entry/bank-entries/show.vue'),
        meta: {
            title: 'Bank Entry',
            resource: 'data-entry/bank-entries'
        }
    },
    {
        path: '/data-entry/bank-entries/:id/edit',
        name: 'data-entry.bank-entries.edit',
        component: () => import('@/views/data-entry/bank-entries/form.vue'),
        meta: {
            title: 'Edit Bank Entry',
            resource: 'data-entry/bank-entries',
            mode: 'edit'
        }
    },
    {
        path: '/data-entry/bank-entries/create',
        name: 'data-entry.bank-entries.create',
        component: () => import('@/views/data-entry/bank-entries/form.vue'),
        meta: {
            title: 'Create Bank Entry',
            resource: 'data-entry/bank-entries',
            mode: 'create'
        }
    }
]
