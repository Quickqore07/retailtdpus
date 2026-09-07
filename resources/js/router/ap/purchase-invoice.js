export default [
    {
        path: '/ap/purchase-invoices',
        name: 'ap.purchase-invoices.index',
        component: () => import('@/views/ap/purchase-invoices/index.vue'),
        meta: {
            title: 'Purchase Invoices',
            resource: 'ap/purchase-invoices'
        }
    },
    {
        path: '/ap/purchase-invoices/:id',
        name: 'ap.purchase-invoices.show',
        component: () => import('@/views/ap/purchase-invoices/show.vue'),
        meta: {
            title: 'Purchase Invoice',
            resource: 'ap/purchase-invoices'
        }
    },
    {
        path: '/ap/purchase-invoices/:id/edit',
        name: 'ap.purchase-invoices.edit',
        component: () => import('@/views/ap/purchase-invoices/form.vue'),
        meta: {
            title: 'Edit Purchase Invoice',
            resource: 'ap/purchase-invoices',
            mode: 'edit'
        }
    },
    {
        path: '/ap/purchase-invoices/create',
        name: 'ap.purchase-invoices.create',
        component: () => import('@/views/ap/purchase-invoices/form.vue'),
        meta: {
            title: 'Create Purchase Invoice',
            resource: 'ap/purchase-invoices',
            mode: 'create'
        }
    }
]
