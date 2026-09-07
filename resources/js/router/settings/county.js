export default [
    {
        path: '/settings/counties',
        name: 'settings.counties.index',
        component: () => import('@/views/settings/counties/index.vue'),
        meta: {
            title: 'Counties',
            resource: 'settings/counties'
        }
    },
    {
        path: '/settings/counties/:id',
        name: 'settings.counties.show',
        component: () => import('@/views/settings/counties/show.vue'),
        meta: {
            title: 'County',
            resource: 'settings/counties'
        }
    },
    {
        path: '/settings/counties/:id/edit',
        name: 'settings.counties.edit',
        component: () => import('@/views/settings/counties/form.vue'),
        meta: {
            title: 'Edit County',
            resource: 'settings/counties',
            mode: 'edit'
        }
    },
    {
        path: '/settings/counties/create',
        name: 'settings.counties.create',
        component: () => import('@/views/settings/counties/form.vue'),
        meta: {
            title: 'Create County',
            resource: 'settings/counties',
            mode: 'create'
        }
    }
]
