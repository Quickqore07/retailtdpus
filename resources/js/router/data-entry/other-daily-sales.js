export default [
    {
        path: '/data-entry/other-daily-sales',
        name: 'data-entry.other-daily-sales.index',
        component: () => import('@/views/data-entry/other-daily-sales/index.vue'),
        meta: {
            title: 'Other Daily Sales',
            resource: 'data-entry/other-daily-sales'
        }
    },
    {
        path: '/data-entry/other-daily-sales/:id',
        name: 'data-entry.other-daily-sales.show',
        component: () => import('@/views/data-entry/other-daily-sales/show.vue'),
        meta: {
            title: 'Other Daily Sale',
            resource: 'data-entry/other-daily-sales'
        }
    },
    {
        path: '/data-entry/other-daily-sales/:id/edit',
        name: 'data-entry.other-daily-sales.edit',
        component: () => import('@/views/data-entry/other-daily-sales/form.vue'),
        meta: {
            title: 'Edit Other Daily Sale',
            resource: 'data-entry/other-daily-sales',
            mode: 'edit'
        }
    },
    {
        path: '/data-entry/other-daily-sales/create',
        name: 'data-entry.other-daily-sales.create',
        component: () => import('@/views/data-entry/other-daily-sales/form.vue'),
        meta: {
            title: 'Create Other Daily Sale',
            resource: 'data-entry/other-daily-sales',
            mode: 'create'
        }
    }
]
