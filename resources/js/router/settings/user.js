export default [
    {
        path: '/settings/users',
        name: 'settings.users.index',
        component: () => import('@/views/settings/users/index.vue'),
        meta: {
            title: 'Users',
            resource: 'settings/users'
        }
    },
    {
        path: '/settings/users/:id',
        name: 'settings.users.show',
        component: () => import('@/views/settings/users/show.vue'),
        meta: {
            title: 'User',
            resource: 'settings/users'   
        }
    },{
        path: '/settings/users/:id/edit',
        name: 'settings.users.edit',
        component: () => import('@/views/settings/users/form.vue'),
        meta: {
            title: 'Edit User',
            resource: 'settings/users',
            mode: 'edit'
        }
    },
    {
        path: '/settings/users/create',
        name: 'settings.users.create',
        component: () => import('@/views/settings/users/form.vue'),
        meta: {
            title: 'Create User',
            resource: 'settings/users',
            mode: 'create'
        }
    }
]