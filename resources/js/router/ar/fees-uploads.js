export default [
    {
        path: '/ar/fees-uploads',
        name: 'ar.fees-uploads.index',
        component: () => import('@/views/ar/fees-uploads/index.vue'),
        meta: {
            title: 'Fees Upload',
            resource: 'ar/fees-uploads'
        }
    },
    {
        path: '/ar/fees-uploads/:id',
        name: 'ar.fees-uploads.show',
        component: () => import('@/views/ar/fees-uploads/show.vue'),
        meta: {
            title: 'Fees Upload Details',
            resource: 'ar/fees-uploads'
        }
    },
    {
        path: '/ar/fees-uploads/:id/edit',
        name: 'ar.fees-uploads.edit',
        component: () => import('@/views/ar/fees-uploads/form.vue'),
        meta: {
            title: 'Edit Fees Upload',
            resource: 'ar/fees-uploads',
            mode: 'edit'
        }
    },
    {
        path: '/ar/fees-uploads/create',
        name: 'ar.fees-uploads.create',
        component: () => import('@/views/ar/fees-uploads/form.vue'),
        meta: {
            title: 'Create Fees Upload',
            resource: 'ar/fees-uploads',
            mode: 'create'
        }
    }
]
