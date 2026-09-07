export default [
    {
        path: '/upload/companies',
        name: 'upload.companies.index',
        component: () => import('@/views/upload/companies/index.vue'),
        meta: {
            title: 'Upload Companies',
            resource: 'upload/companies'
        }
    },
    {
        path: '/upload/companies/:id',
        name: 'upload.companies.show',
        component: () => import('@/views/upload/companies/show.vue'),
        meta: {
            title: 'Upload Company',
            resource: 'upload/companies'
        }
    },
    {
        path: '/upload/companies/:id/edit',
        name: 'upload.companies.edit',
        component: () => import('@/views/upload/companies/form.vue'),
        meta: {
            title: 'Edit Upload Company',
            resource: 'upload/companies',
            mode: 'edit'
        }
    },
    {
        path: '/upload/companies/create',
        name: 'upload.companies.create',
        component: () => import('@/views/upload/companies/form.vue'),
        meta: {
            title: 'Create Upload Company',
            resource: 'upload/companies',
            mode: 'create'
        }
    }
]
