export default [
    {
        path: '/settings/states',
        name: 'settings.states.index',
        component: () => import('@/views/settings/states/index.vue'),
        meta: {
            title: 'States',
            resource: 'settings/states'
        }
    },
    {
        path: '/settings/states/:id',
        name: 'settings.states.show',
        component: () => import('@/views/settings/states/show.vue'),
        meta: {
            title: 'State',
            resource: 'settings/states'
        }
    },
    {
        path: '/settings/states/:id/edit',
        name: 'settings.states.edit',
        component: () => import('@/views/settings/states/form.vue'),
        meta: {
            title: 'Edit State',
            resource: 'settings/states',
            mode: 'edit'
        }
    },
    {
        path: '/settings/states/create',
        name: 'settings.states.create',
        component: () => import('@/views/settings/states/form.vue'),
        meta: {
            title: 'Create State',
            resource: 'settings/states',
            mode: 'create'
        }
    }
]