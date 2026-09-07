export default [
    {
        path: '/data-entry/driver',
        name: 'data-entry.driver.index',
        component: () => import('@/views/data-entry/driver/index.vue'),
        meta: {
            title: 'Driver',
            resource: 'data-entry/driver'
        }
    }
]