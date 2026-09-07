export default [
    {
        path: '/settings/bank-rules',
        name: 'settings.bank-rules.index',
        component: () => import('@/views/settings/bank-rules/index.vue'),
        meta: {
            resource: 'settings/bank-rules'
        }
    },
    {
        path: '/settings/bank-rules/:id',
        name: 'settings.bank-rules.show',
        component: () => import('@/views/settings/bank-rules/show.vue'),
        meta: {
            resource: 'settings/bank-rules'
        }
    },
    {
        path: '/settings/bank-rules/:id/edit',
        name: 'settings.bank-rules.edit',
        component: () => import('@/views/settings/bank-rules/form.vue'),
        meta: {
            resource: 'settings/bank-rules',
            mode: 'edit',
        }
    },
    {
        path: '/settings/bank-rules/create',
        name: 'settings.bank-rules.create',
        component: () => import('@/views/settings/bank-rules/form.vue'),
        meta: {
            resource: 'settings/bank-rules',
            mode: 'create',
        }
    },
]
