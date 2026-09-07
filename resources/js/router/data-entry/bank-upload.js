export default [
    {
        path: '/data-entry/bank-uploads',
        name: 'data-entry.bank-uploads.index',
        component: () => import('@/views/data-entry/bank-uploads/index.vue'),
        meta: {
            title: 'Bank Uploads',
            resource: 'data-entry/bank-uploads'
        }
    }
]
