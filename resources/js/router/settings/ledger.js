export default [
    {
        path: '/settings/ledgers',
        name: 'settings.ledgers.index',
        component: () => import('@/views/settings/ledgers/index.vue'),
        meta: {
            resource: 'settings/ledgers'
        }
    },
    {
        path: '/settings/ledgers/:id',
        name: 'settings.ledgers.show',
        component: () => import('@/views/settings/ledgers/show.vue'),
        meta: {
            resource: 'settings/ledgers'
        }
    },
    {
        path: '/settings/ledgers/:id/edit',
        name: 'settings.ledgers.edit',
        component: () => import('@/views/settings/ledgers/form.vue'),
        meta: {
            resource: 'settings/ledgers',
            mode: 'edit',
        }
    },
    {
        path: '/settings/ledgers/create',
        name: 'settings.ledgers.create',
        component: () => import('@/views/settings/ledgers/form.vue'),
        meta: {
            resource: 'settings/ledgers',
            mode: 'create',
        }
    },
]
