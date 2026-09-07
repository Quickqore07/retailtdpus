export default [
    {
        path: '/onboarding/benefits',
        name: 'onboarding.benefits.index',
        component: () => import('@/views/onboarding/benefits/index.vue'),
        meta: {
            title: 'Benefits',
            resource: 'benefits'
        }
    }
]
