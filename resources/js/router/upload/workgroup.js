export default [
    {
        path: '/upload/workgroups',
        name: 'upload.workgroups.index',
        component: () => import('@/views/upload/workgroups/index.vue'),
        meta: {
            title: 'Upload Workgroups',
            resource: 'upload/workgroups'
        }
    },
    {
        path: '/upload/workgroups/:id',
        name: 'upload.workgroups.show',
        component: () => import('@/views/upload/workgroups/show.vue'),
        meta: {
            title: 'Upload Workgroup',
            resource: 'upload/workgroups'
        }
    },
    {
        path: '/upload/workgroups/:id/edit',
        name: 'upload.workgroups.edit',
        component: () => import('@/views/upload/workgroups/form.vue'),
        meta: {
            title: 'Edit Upload Workgroup',
            resource: 'upload/workgroups',
            mode: 'edit'
        }
    },
    {
        path: '/upload/workgroups/create',
        name: 'upload.workgroups.create',
        component: () => import('@/views/upload/workgroups/form.vue'),
        meta: {
            title: 'Create Upload Workgroup',
            resource: 'upload/workgroups',
            mode: 'create'
        }
    }
]
