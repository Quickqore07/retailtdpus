export default [
    {
        path: '/settings/activity-logs',
        name: 'settings.activity-logs.index',
        component: () => import('@/views/settings/activity-logs/index.vue'),
        meta: {
            title: 'Activity Logs',
            resource: 'settings/activity-logs'
        }
    },
    {
        path: '/settings/activity-logs/:id',
        name: 'settings.activity-logs.show',
        component: () => import('@/views/settings/activity-logs/show.vue'),
        meta: {
            title: 'Activity Log',
            resource: 'settings/activity-logs'
        }
    }
]
