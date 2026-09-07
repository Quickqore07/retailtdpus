export default [
    {
        path: '/settings/companies',
        name: 'settings.companies.index',
        component: () => import('@/views/settings/companies/index.vue'),
        meta: {
            title: 'Companies',
            resource: 'settings/companies'
        }
    },
    {
        path: '/settings/companies/:id',
        name: 'settings.companies.show',
        component: () => import('@/views/settings/companies/show.vue'),
        meta: {
            title: 'Company',
            resource: 'settings/companies'
        }
    },
    {
        path: '/settings/companies/:id/edit',
        name: 'settings.companies.edit',
        component: () => import('@/views/settings/companies/form.vue'),
        meta: {
            title: 'Edit Company',
            resource: 'settings/companies',
            mode: 'edit'
        }
    },
    {
        path: '/settings/companies/create',
        name: 'settings.companies.create',
        component: () => import('@/views/settings/companies/form.vue'),
        meta: {
            title: 'Create Company',
            resource: 'settings/companies',
            mode: 'create'
        }
    }
]   