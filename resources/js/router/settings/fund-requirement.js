export default [
    {
        path: '/settings/fund-requirements',
        name: 'settings.fund-requirements.index',
        component: () => import('@/views/settings/fund-requirements/index.vue'),
        meta: {
            title: 'Fund Requirements',
            resource: 'settings/fund-requirements'
        }
    },
    {
        path: '/settings/fund-requirements/:id',
        name: 'settings.fund-requirements.show',
        component: () => import('@/views/settings/fund-requirements/show.vue'),
        meta: {
            title: 'Fund Requirement',
            resource: 'settings/fund-requirements'
        }
    },
    {
        path: '/settings/fund-requirements/:id/edit',
        name: 'settings.fund-requirements.edit',
        component: () => import('@/views/settings/fund-requirements/form.vue'),
        meta: {
            title: 'Edit Fund Requirement',
            resource: 'settings/fund-requirements',
            mode: 'edit'
        }
    },
    {
        path: '/settings/fund-requirements/create',
        name: 'settings.fund-requirements.create',
        component: () => import('@/views/settings/fund-requirements/form.vue'),
        meta: {
            title: 'Create Fund Requirement',
            resource: 'settings/fund-requirements',
            mode: 'create'
        }
    }
]
