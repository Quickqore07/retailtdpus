export default [
    {
        path: '/settings/company-groups',
        name: 'settings.company-groups.index',
        component: () => import('@/views/settings/company-groups/index.vue'),
        meta: {
            title: 'Company Groups',
            resource: 'settings/company-groups'
        }
    },
    {
        path: '/settings/company-groups/:id',
        name: 'settings.company-groups.show',
        component: () => import('@/views/settings/company-groups/show.vue'),
        meta: {
            title: 'Company Group',
            resource: 'settings/company-groups'
        }
    },
    {
        path: '/settings/company-groups/:id/edit',
        name: 'settings.company-groups.edit',
        component: () => import('@/views/settings/company-groups/form.vue'),
        meta: {
            title: 'Edit Company Group',
            resource: 'settings/company-groups',
            mode: 'edit'
        }
    },
    {
        path: '/settings/company-groups/create',
        name: 'settings.company-groups.create',
        component: () => import('@/views/settings/company-groups/form.vue'),
        meta: {
            title: 'Create Company Group',
            resource: 'settings/company-groups',
            mode: 'create'
        }
    }
]