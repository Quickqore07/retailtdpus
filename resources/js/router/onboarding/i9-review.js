export default [
    {
        path: '/onboarding/i9-review',
        name: 'onboarding.i9-review.index',
        component: () => import('@/views/onboarding/i9-review/index.vue'),
        meta: {
            title: 'I-9 Review',
            resource: 'i9-review'
        }
    },
    {
        path: '/onboarding/i9-review/:id',
        name: 'onboarding.i9-review.show',
        component: () => import('@/views/onboarding/i9-review/show.vue'),
        meta: {
            title: 'I-9 Review',
            resource: 'i9-review'
        }
    }
]