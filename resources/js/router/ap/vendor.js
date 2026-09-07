export default [
    {
        path: '/ap/vendors',
        name: 'ap.vendors.index',
        component: () => import('@/views/ap/vendors/index.vue'),
        meta: {
            title: 'Vendors',
            resource: 'ap/vendors'
        }
    },
    {
        path: '/ap/vendors/:id',
        name: 'ap.vendors.show',
        component: () => import('@/views/ap/vendors/show.vue'),
        meta: {
            title: 'Vendor',
            resource: 'ap/vendors'
        }
    },
    {
        path: '/ap/vendors/:id/edit',
        name: 'ap.vendors.edit',
        component: () => import('@/views/ap/vendors/form.vue'),
        meta: {
            title: 'Edit Vendor',
            resource: 'ap/vendors',
            mode: 'edit'
        }
    },
    {
        path: '/ap/vendors/create',
        name: 'ap.vendors.create',
        component: () => import('@/views/ap/vendors/form.vue'),
        meta: {
            title: 'Create Vendor',
            resource: 'ap/vendors',
            mode: 'create'
        }
    }
]
