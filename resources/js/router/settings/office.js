export default [
    {
        path: '/settings/offices',
        name: 'settings.offices.index',
        component: () => import('@/views/settings/offices/index.vue'),
        meta: {
            title: 'Offices',
            resource: 'settings/offices'
        }
    },
    {
        path: '/settings/offices/:id',
        name: 'settings.offices.show',
        component: () => import('@/views/settings/offices/show.vue'),
        meta: {
            title: 'Office',
            resource: 'settings/offices'
        }
    },
    {
        path: '/settings/offices/:id/edit',
        name: 'settings.offices.edit',
        component: () => import('@/views/settings/offices/form.vue'),
        meta: {
            title: 'Edit Office',
            resource: 'settings/offices',
            mode: 'edit'
        }
    },
    {
        path: '/settings/offices/create',
        name: 'settings.offices.create',
        component: () => import('@/views/settings/offices/form.vue'),
        meta: {
            title: 'Create Office',
            resource: 'settings/offices',
            mode: 'create'
        }
    }
]
