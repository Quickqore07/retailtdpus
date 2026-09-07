export default [
    {
        path: '/upload/users',
        name: 'upload.users.index',
        component: () => import('@/views/upload/users/index.vue'),
        meta: {
            title: 'Upload Users',
            resource: 'upload/users'
        }
    },
    {
        path: '/upload/users/:id',
        name: 'upload.users.show',
        component: () => import('@/views/upload/users/show.vue'),
        meta: {
            title: 'Upload User',
            resource: 'upload/users'
        }
    },
    {
        path: '/upload/users/:id/edit',
        name: 'upload.users.edit',
        component: () => import('@/views/upload/users/form.vue'),
        meta: {
            title: 'Edit Upload User',
            resource: 'upload/users',
            mode: 'edit'
        }
    },
    {
        path: '/upload/users/create',
        name: 'upload.users.create',
        component: () => import('@/views/upload/users/form.vue'),
        meta: {
            title: 'Create Upload User',
            resource: 'upload/users',
            mode: 'create'
        }
    }
]
