export default [
    {
        path: '/onboarding/pending-applications',
        name: 'onboarding.pending-applications.index',
        component: () => import('@/views/onboarding/pending-applications/index.vue'),
        meta: {
            title: 'Pending Applications',
            resource: 'pending-applications'
        }
    }
]