export default [
    {
        path: '/settings/minimum-wages',
        name: 'settings.minimum-wages.index',
        component: () => import('@/views/settings/minimum-wages/index.vue'),
        meta: {
            title: 'Minimum Wages',
            resource: 'settings/minimum-wages'
        }
    },
    {
        path: '/settings/minimum-wages/:id',
        name: 'settings.minimum-wages.show',
        component: () => import('@/views/settings/minimum-wages/show.vue'),
        meta: {
            title: 'Minimum Wage',
            resource: 'settings/minimum-wages'
        }
    },
    {
        path: '/settings/minimum-wages/:id/edit',
        name: 'settings.minimum-wages.edit',
        component: () => import('@/views/settings/minimum-wages/form.vue'),
        meta: {
            title: 'Edit Minimum Wage',
            resource: 'settings/minimum-wages',
            mode: 'edit'
        }
    },
    {
        path: '/settings/minimum-wages/create',
        name: 'settings.minimum-wages.create',
        component: () => import('@/views/settings/minimum-wages/form.vue'),
        meta: {
            title: 'Create Minimum Wage',
            resource: 'settings/minimum-wages',
            mode: 'create'
        }
    }
]
