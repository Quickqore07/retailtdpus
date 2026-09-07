export default [
    {
        path: '/charge-back',
        name: 'charge-back',
        component: () => import('@/views/charge-back/index.vue'),
        meta: { resource: 'charge-back' },
    },
]
