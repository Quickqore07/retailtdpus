export default [
    {
        path: '/settings/workgroups',
        name: 'settings.workgroups.index',
        component: () => import('@/views/settings/workgroups/index.vue'),
        meta: {
            title: 'Workgroups',
            resource: 'settings/workgroups'
        }
    },
    {
        path: '/settings/workgroups/:id',
        name: 'settings.workgroups.show',
        component: () => import('@/views/settings/workgroups/show.vue'),
        meta: {
            title: 'Workgroup',
            resource: 'settings/workgroups'
        }
    },
    {
        path: '/settings/workgroups/:id/edit',
        name: 'settings.workgroups.edit',
        component: () => import('@/views/settings/workgroups/form.vue'),
        meta: {
            title: 'Edit Workgroup',
            resource: 'settings/workgroups',
            mode: 'edit'
        }
    },
    {
        path: '/settings/workgroups/create',
        name: 'settings.workgroups.create',
        component: () => import('@/views/settings/workgroups/form.vue'),
        meta: {
            title: 'Create Workgroup',
            resource: 'settings/workgroups',
            mode: 'create'
        }
    }
]