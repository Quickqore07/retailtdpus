export default [
    {
        path: '/settings/bank-category-rules',
        name: 'settings.bank-category-rules.index',
        component: () => import('@/views/settings/bank-category-rules/index.vue'),
        meta: {
            resource: 'settings/bank-category-rules'
        }
    },
    {
        path: '/settings/bank-category-rules/:id',
        name: 'settings.bank-category-rules.show',
        component: () => import('@/views/settings/bank-category-rules/show.vue'),
        meta: {
            resource: 'settings/bank-category-rules'
        }
    },
    {
        path: '/settings/bank-category-rules/:id/edit',
        name: 'settings.bank-category-rules.edit',
        component: () => import('@/views/settings/bank-category-rules/form.vue'),
        meta: {
            resource: 'settings/bank-category-rules',
            mode: 'edit',
        }
    },
    {
        path: '/settings/bank-category-rules/create',
        name: 'settings.bank-category-rules.create',
        component: () => import('@/views/settings/bank-category-rules/form.vue'),
        meta: {
            resource: 'settings/bank-category-rules',
            mode: 'create',
        }
    },
]
