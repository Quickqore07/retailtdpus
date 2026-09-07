export default [
    {
        path: '/settings/pj-calendars',
        name: 'settings.pj-calendars.index',
        component: () => import('@/views/settings/pj-calendars/index.vue'),
        meta: {
            title: 'PJ Calendars',
            resource: 'settings/pj-calendars'
        }
    },
    {
        path: '/settings/pj-calendars/:id',
        name: 'settings.pj-calendars.show',
        component: () => import('@/views/settings/pj-calendars/show.vue'),
        meta: {
            title: 'PJ Calendar',
            resource: 'settings/pj-calendars'
        }
    },
    {
        path: '/settings/pj-calendars/:id/edit',
        name: 'settings.pj-calendars.edit',
        component: () => import('@/views/settings/pj-calendars/form.vue'),
        meta: {
            title: 'Edit PJ Calendar',
            resource: 'settings/pj-calendars',
            mode: 'edit'
        }
    },
    {
        path: '/settings/pj-calendars/create',
        name: 'settings.pj-calendars.create',
        component: () => import('@/views/settings/pj-calendars/form.vue'),
        meta: {
            title: 'Create PJ Calendar',
            resource: 'settings/pj-calendars',
            mode: 'create'
        }
    }
]
