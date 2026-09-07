export default [
    {
        path: '/ar/pj-payments',
        name: 'ar.pj-payments.index',
        component: () => import('@/views/ar/pj-payments/index.vue'),
        meta: {
            title: 'PJ Payments',
            resource: 'ar/pj-payments'
        }
    },
    {
        path: '/ar/pj-payments/:id',
        name: 'ar.pj-payments.show',
        component: () => import('@/views/ar/pj-payments/show.vue'),
        meta: {
            title: 'PJ Payment',
            resource: 'ar/pj-payments'
        }
    },
    {
        path: '/ar/pj-payments/:id/edit',
        name: 'ar.pj-payments.edit',
        component: () => import('@/views/ar/pj-payments/form.vue'),
        meta: {
            title: 'Edit PJ Payment',
            resource: 'ar/pj-payments',
            mode: 'edit'
        }
    },
    {
        path: '/ar/pj-payments/create',
        name: 'ar.pj-payments.create',
        component: () => import('@/views/ar/pj-payments/form.vue'),
        meta: {
            title: 'Create PJ Payment',
            resource: 'ar/pj-payments',
            mode: 'create'
        }
    }
]
