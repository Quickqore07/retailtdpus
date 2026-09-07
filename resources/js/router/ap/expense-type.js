export default [
    {
        path: '/ap/expense-types',
        name: 'ap.expense-types.index',
        component: () => import('@/views/ap/expense-types/index.vue'),
        meta: {
            title: 'Expense Types',
            resource: 'ap/expense-types'
        }
    },
    {
        path: '/ap/expense-types/create',
        name: 'ap.expense-types.create',
        component: () => import('@/views/ap/expense-types/form.vue'),
        meta: {
            title: 'Create Expense Type',
            resource: 'ap/expense-types',
            mode: 'create'
        }
    },
    {
        path: '/ap/expense-types/:id/edit',
        name: 'ap.expense-types.edit',
        component: () => import('@/views/ap/expense-types/form.vue'),
        meta: {
            title: 'Edit Expense Type',
            resource: 'ap/expense-types',
            mode: 'edit'
        }
    },
    {
        path: '/ap/expense-types/:id',
        name: 'ap.expense-types.show',
        component: () => import('@/views/ap/expense-types/show.vue'),
        meta: {
            title: 'Expense Type',
            resource: 'ap/expense-types'
        }
    }
]
