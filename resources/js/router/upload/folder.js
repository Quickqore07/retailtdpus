export default [
    {
        path: '/upload/folders',
        name: 'upload.folders.index',
        component: () => import('@/views/upload/folders/index.vue'),
        meta: {
            title: 'Upload Folders',
            resource: 'upload/folders'
        }
    },
    {
        path: '/upload/folders/:id',
        name: 'upload.folders.show',
        component: () => import('@/views/upload/folders/show.vue'),
        meta: {
            title: 'Upload Folder',
            resource: 'upload/folders'
        }
    },
    {
        path: '/upload/folders/:id/edit',
        name: 'upload.folders.edit',
        component: () => import('@/views/upload/folders/form.vue'),
        meta: {
            title: 'Edit Upload Folder',
            resource: 'upload/folders',
            mode: 'edit'
        }
    },
    {
        path: '/upload/folders/create',
        name: 'upload.folders.create',
        component: () => import('@/views/upload/folders/form.vue'),
        meta: {
            title: 'Create Upload Folder',
            resource: 'upload/folders',
            mode: 'create'
        }
    }
]
