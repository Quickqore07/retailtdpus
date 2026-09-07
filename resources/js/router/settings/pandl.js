export default [
    {
        path: '/settings/pandl',
        name: 'settings.pandl.index',
        component: () => import('@/views/settings/pandl/index.vue'),
        meta: {
            resource: 'settings/pandl'
        }
    },
    {
        path: '/settings/pandl/:id',
        name: 'settings.pandl.show',
        component: () => import('@/views/settings/pandl/show.vue'),
        meta: {
            resource: 'settings/pandl'
        }
    },
    {
        path: '/settings/pandl/:id/edit',
        name: 'settings.pandl.edit',
        component: () => import('@/views/settings/pandl/form.vue'),
        meta: {
            resource: 'settings/pandl',
            mode: 'edit',
        }
    },
    {
        path: '/settings/pandl/create',
        name: 'settings.pandl.create',
        component: () => import('@/views/settings/pandl/form.vue'),
        meta: {
            resource: 'settings/pandl',
            mode: 'create',
        }
    },
]
