export default [
    {
        path: '/data-entry/daily-sales',
        name: 'data-entry.daily-sales.index',
        component: () => import('@/views/data-entry/daily-sales/index.vue'),
        meta: {
            title: 'Daily Sales',
            resource: 'data-entry/daily-sales'
        }
    },
    {
        path: '/data-entry/daily-sales/:id',
        name: 'data-entry.daily-sales.show',    
        component: () => import('@/views/data-entry/daily-sales/show.vue'),
        meta: {
            title: 'Daily Sale',
            resource: 'data-entry/daily-sales'
        }
    },
    {
        path: '/data-entry/daily-sales/:id/edit',
        name: 'data-entry.daily-sales.edit',
        component: () => import('@/views/data-entry/daily-sales/form.vue'),
        meta: {
            title: 'Edit Daily Sale',
            resource: 'data-entry/daily-sales',
            mode: 'edit'
        }
    },
    {
        path: '/data-entry/daily-sales/create',
        name: 'data-entry.daily-sales.create',
        component: () => import('@/views/data-entry/daily-sales/form.vue'),
        meta: {
            title: 'Create Daily Sale',
            resource: 'data-entry/daily-sales',
            mode: 'create'
        }
    }
]
