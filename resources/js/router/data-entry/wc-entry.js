export default [
    {
        path: '/data-entry/wc-entries',
        name: 'data-entry.wc-entries.index',
        component: () => import('@/views/data-entry/wc-entries/index.vue'),
        meta: {
            title: 'WC Entries',
            resource: 'data-entry/wc-entries'
        }
    },
    {
        path: '/data-entry/wc-entries/:id',
        name: 'data-entry.wc-entries.show',
        component: () => import('@/views/data-entry/wc-entries/show.vue'),
        meta: {
            title: 'WC Entry',
            resource: 'data-entry/wc-entries'
        }
    },
    {
        path: '/data-entry/wc-entries/:id/edit',
        name: 'data-entry.wc-entries.edit',
        component: () => import('@/views/data-entry/wc-entries/form.vue'),
        meta: {
            title: 'Edit WC Entry',
            resource: 'data-entry/wc-entries',
            mode: 'edit'
        }
    },
    {
        path: '/data-entry/wc-entries/create',
        name: 'data-entry.wc-entries.create',
        component: () => import('@/views/data-entry/wc-entries/form.vue'),
        meta: {
            title: 'Create WC Entry',
            resource: 'data-entry/wc-entries',
            mode: 'create'
        }
    }
]
