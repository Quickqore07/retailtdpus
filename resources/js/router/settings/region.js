export default [
    {
        path: '/settings/regions',
        name: 'settings.regions.index',
        component: () => import('@/views/settings/regions/index.vue'),
        meta: {
            title: 'Regions',
            resource: 'settings/regions'
        }
    },
    {
        path: '/settings/regions/:id',
        name: 'settings.regions.show',
        component: () => import('@/views/settings/regions/show.vue'),
        meta: {
            title: 'Region',
            resource: 'settings/regions'
        }
    },
    {
        path: '/settings/regions/:id/edit',
        name: 'settings.regions.edit',
        component: () => import('@/views/settings/regions/form.vue'),
        meta: {
            title: 'Edit Region',
            resource: 'settings/regions',
            mode: 'edit'
        }
    },
    {
        path: '/settings/regions/create',
        name: 'settings.regions.create',
        component: () => import('@/views/settings/regions/form.vue'),
        meta: {
            title: 'Create Region',
            resource: 'settings/regions',
            mode: 'create'
        }
    }
]