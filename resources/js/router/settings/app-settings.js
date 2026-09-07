export default [
    {
        path: '/app-settings',
        name: 'app.settings',
        component: () => import('@/views/app-settings.vue'),
        meta: {
            title: 'Application Settings',
            resource: 'settings'
        }
    }
]
