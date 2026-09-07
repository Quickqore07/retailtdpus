export default [
    {
        path: '/settings/roles',
        name: 'settings.roles.index',
        component: () => import('@/views/settings/roles/index.vue'),
        meta: {
            title: 'Roles',
            resource: 'settings/roles'
        }
    },
    {
        path: '/settings/roles/:id',
        name: 'settings.roles.show',
        component: () => import('@/views/settings/roles/show.vue'),
        meta: {
            title: 'Role',
            resource: 'settings/roles'
        }
    },{
        path: '/settings/roles/:id/edit',
        name: 'settings.roles.edit',
        component: () => import('@/views/settings/roles/form.vue'),
        meta: {
            title: 'Edit Role',
            resource: 'settings/roles',
            mode: 'edit'
        }
    },
    {
        path: '/settings/roles/create',
        name: 'settings.roles.create',
        component: () => import('@/views/settings/roles/form.vue'),
        meta: {
            title: 'Create Role',
            resource: 'settings/roles',
            mode: 'create'
        }
    }
]

