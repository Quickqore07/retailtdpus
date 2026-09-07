export default [
    {
        path: '/data-entry/royalty-fees',
        name: 'data-entry.royalty-fees.index',
        component: () => import('@/views/data-entry/royalty-fees/index.vue'),
        meta: {
            title: 'Royalty Fees',
            resource: 'data-entry/royalty-fees'
        }
    },
    {
        path: '/data-entry/royalty-fees/create',
        name: 'data-entry.royalty-fees.create',
        component: () => import('@/views/data-entry/royalty-fees/form.vue'),
        meta: {
            title: 'Create Royalty Fee',
            resource: 'data-entry/royalty-fees',
            mode: 'create'
        }
    },
    {
        path: '/data-entry/royalty-fees/:id',
        name: 'data-entry.royalty-fees.show',
        component: () => import('@/views/data-entry/royalty-fees/show.vue'),
        meta: {
            title: 'Royalty Fee',
            resource: 'data-entry/royalty-fees'
        }
    },
    {
        path: '/data-entry/royalty-fees/:id/edit',
        name: 'data-entry.royalty-fees.edit',
        component: () => import('@/views/data-entry/royalty-fees/form.vue'),
        meta: {
            title: 'Edit Royalty Fee',
            resource: 'data-entry/royalty-fees',
            mode: 'edit'
        }
    }
]
