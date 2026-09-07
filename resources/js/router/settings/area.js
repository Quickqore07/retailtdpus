export default [
    {
        path: '/settings/areas',
        name: 'settings.areas.index',
        component: () => import('@/views/settings/areas/index.vue'),
        meta: {
            title: 'Areas',
            resource: 'settings/areas'
        }
    },
    {
        path: '/settings/areas/:id',
        name: 'settings.areas.show',
        component: () => import('@/views/settings/areas/show.vue'),
        meta: {
            title: 'Area',
            resource: 'settings/areas'
        }
    },
    {
        path: '/settings/areas/:id/edit',
        name: 'settings.areas.edit',
        component: () => import('@/views/settings/areas/form.vue'),
        meta: {
            title: 'Edit Area',
            resource: 'settings/areas',
            mode: 'edit'
        }
    },
    {
        path: '/settings/areas/create',
        name: 'settings.areas.create',
        component: () => import('@/views/settings/areas/form.vue'),
        meta: {
            title: 'Create Area',
            resource: 'settings/areas',
            mode: 'create'
        }
    }
]