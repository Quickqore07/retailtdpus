export default [
    {
        path: '/data-entry/shortages',
        name: 'data-entry.shortages.index',
        component: () => import('@/views/data-entry/shortage/index.vue'),
        meta: {
            title: 'Shortages',
            resource: 'data-entry/shortages'
        }
    },
    {
        path: '/data-entry/shortages/:id',
        name: 'data-entry.shortages.show',
        component: () => import('@/views/data-entry/shortage/show.vue'),
        meta: {
            title: 'Shortages',
            resource: 'data-entry/shortages'
        }
    },
    {
        path: '/data-entry/shortages/:id/edit',
        name: 'data-entry.shortages.edit',
        component: () => import('@/views/data-entry/shortage/form.vue'),
        meta: {
            title: 'Manage Shortages',
            resource: 'data-entry/shortages',
            mode: 'edit'
        }
    }
]
